<?php
require_once("Conexion.php");

class Movimientos
{

  static public function filterMovimientos($campos, $paramWhere, $paramOrders, $pagination, $isPaginated = true)
  {
    $table = "movimientos";

    $sqlWhere = SqlWhere::and([
      SqlWhere::likeOr($paramWhere['paramLike']),
      SqlWhere::equalAnd($paramWhere['paramEquals']),
      SqlWhere::between($paramWhere['paramBetween']),
    ]);

    $bindWhere = SqlWhere::arrMerge([
      "like" => $paramWhere['paramLike'], 
      "equal" => $paramWhere['paramEquals'], 
      "between" => $paramWhere['paramBetween']
    ]);

    $sqlSelect = !empty($campos) ? "SELECT " . implode(", ", $campos)  : "";
    $sqlOrderBy = getSqlOrderBy($paramOrders);
    $page = intval($pagination["page"]);
    $offset = intval($pagination["offset"]);

    $num_regs = self::num_regs($table, $sqlWhere, $bindWhere);
    $pages = ceil($num_regs / $offset);
    if($page > $pages && $pages != 0)  throwMiExcepcion("Págian fuera de rango", "error", 200);
    $page = ($page <= $pages) ? $page : 1;
    $start_reg = $offset * ($page - 1);

    $sqlLimit = $isPaginated ? " LIMIT $start_reg, $offset" : "";
    $sql = $sqlSelect . " FROM $table" . $sqlWhere . $sqlOrderBy . $sqlLimit;

    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($bindWhere);
    $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['content'] = $filas;
    $response['num_regs'] = $num_regs;
    $response['pages'] = $pages;
    $response['page'] = ($pages != 0) ? $page : 0;
    $response['next'] = ($pages > $page) ? $page + 1 : 0;
    $response['previous'] = ($pages > 1) ? $page - 1 : 0;
    $response['offset'] = $offset;
    return $response;
  }

  static function getMovimientos($tabla, $campos, $whereEquals = null)
  {
    $sql = "SELECT " . implode(", ", $campos) . " FROM $tabla";

    $sqlWhere = $whereEquals ? SqlWhere::and([SqlWhere::equalAnd($whereEquals)]) : "";
    $bindWhere = $whereEquals ? SqlWhere::arrMerge(["equal" => $whereEquals]) : null;

    $sql .= $sqlWhere;

    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($bindWhere);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $records;
  }

  static function getMovimiento($id){
    $sql = "SELECT
      p.id,
      ifnull(p.codigo, '') as codigo,
      ifnull(p.barcode,'') as barcode,
      p.categoria_ids,
      p.descripcion,
      ifnull(p.marca_id, 0) as marca_id,
      ifnull(m.nombre, '') as marca,
      ifnull(p.laboratorio_id, 0) as laboratorio_id,
      ifnull(l.nombre, '') as laboratorio,
      p.unidad_medida_cod,
      p.tipo_moneda_cod,
      p.precio_venta,
      p.precio_costo,
      p.impuesto_id_igv,
      p.impuesto_id_icbper,
      p.inventariable,
      p.lotizable,
      p.stock,
      p.stock_min,
      p.imagen,
      p.estado,
      p.created_at,
      ifnull(p.updated_at, '') as updated_at
      FROM movimientos p
      LEFT JOIN marcas m ON p.marca_id = m.id 
      LEFT JOIN laboratorios l ON p.laboratorio_id = l.id 
      WHERE p.id = :id;
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute(['id' => $id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    return $record;
  }
  
  static function getMovimientoBy($param){
    $sqlWhere = implode(" AND ", array_map(function($el){
      return "u.$el = :$el";
    },array_keys($param)));
    $sqlWhere = $sqlWhere ? " WHERE " . $sqlWhere : "";
    $sql = "SELECT
        u.id,
        u.nombres,
        u.apellidos,
        u.movimientoname,
        ifnull(u.email,'') AS email,
        u.rol_id,
        r.rol,
        u.caja_id,
        c.descripcion AS caja,
        u.estado,
        u.created_at,
        ifnull(u.updated_at, '') AS updated_at
      FROM movimientos u
      LEFT JOIN roles r ON u.rol_id = r.id
      LEFT JOIN cajas c ON u.caja_id = c.id
      $sqlWhere;
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($param);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    return $record;
  }

  static function createMovimiento($paramCampos, $paramCamposDetalle, $paramInventarios)
  {
    try {
      $sql = sqlInsert("movimientos", $paramCampos);
      $sqlDetalle = sqlInsert("movimientos_detalle", $paramCamposDetalle[0]);
      $sqlUpdateCorrelativo = "UPDATE series 
        SET correlativo = :correlativo
        WHERE establecimiento_id = :establecimiento_id
          AND serie = :serie
      ";
      $sqlInventarios = sqlInsert("inventarios", $paramInventarios[0]);

      $dbh = Conexion::conectar();
      $dbh->beginTransaction();
      // Insertando a movimientos
      $stmt = $dbh->prepare($sql);
      $stmt->execute($paramCampos);
      $lastId = $dbh->lastInsertId();
      // Insertando a movimientos_detalle
      $stmt = $dbh->prepare($sqlDetalle);
      foreach ($paramCamposDetalle as $fila) {
        $fila['movimiento_id'] = $lastId;
        $stmt->execute($fila);
      }
      // Actualizando el correlativo
      $stmt = $dbh->prepare($sqlUpdateCorrelativo);
      $stmt->execute([
        'correlativo' => intval($paramCampos['correlativo']) + 1,
        'establecimiento_id' => $paramCampos['establecimiento_id'],
        'serie' => $paramCampos['serie']
      ]);
      // Insertando a Inventarios
      $stmt = $dbh->prepare($sqlInventarios);
      foreach ($paramInventarios as $fila) {
        $stmt->execute($fila);
      }
      $dbh->commit();
      return $lastId;
    } catch (PDOException $e) {
      $dbh->rollBack();
      throwMiExcepcion($e->getMessage(), "error", 400);
    }
  }
 
  static function updateMovimiento($table, $paramCampos, $paramWhere)
  {
    $sql = sqlUpdate($table, $paramCampos, $paramWhere);
    $params = array_merge($paramCampos, $paramWhere);
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $resp = $stmt->rowCount();
    return $resp;
  }

  static function deleteMovimiento($params){
    $sql = "DELETE FROM movimientos WHERE id = :id";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $resp = $stmt->rowCount();
    return $resp;
  }

  static function countRecordsBy($equal, $exclude = []){
    $where = " WHERE " . array_keys($equal)[0] . " = :". array_keys($equal)[0];
    if($exclude){
      $where .= " AND " . array_keys($exclude)[0] . " != :". array_keys($exclude)[0];
    }
    $sql = "SELECT COUNT(*) AS count FROM movimientos" . $where;
    $param = array_merge($equal, $exclude);

    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($param);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);
    $response = $registro['count'];
    return $response;
  }


  // Metodos privados
  static private function num_regs($table, $sqlWhere, $bindWhere)
  {
    // Extraemos la cantidad de registros en total
    $sql = "SELECT COUNT(*) AS num_regs FROM $table" . $sqlWhere;

    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($bindWhere);
    $rows = $stmt->fetch(PDO::FETCH_ASSOC); 
    return $rows['num_regs']; 
  }

}
