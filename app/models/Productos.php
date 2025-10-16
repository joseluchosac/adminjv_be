<?php
require_once("Conexion.php");

class Productos
{

  static public function filterProductos($campos, $where, $orderBy, $pagination, $isPaginated = true){
    $table = "productos p";
    $dbh = Conexion::conectar();

    $sqlSelect = !empty($campos) ? "SELECT " . implode(", ", $campos)  : "";
    $join = " LEFT JOIN (SELECT id as _laboratorio_id, nombre as laboratorio FROM laboratorios) l ON p.laboratorio_id = l._laboratorio_id";
    $join .= " LEFT JOIN (SELECT id as _marca_id, nombre as marca FROM marcas) m ON p.marca_id = m._marca_id";

    $page = intval($pagination["page"]);
    $per_page = intval($pagination["per_page"]);

    $num_regs = self::num_regs($table, $join, $where["sql"], $where["params"], $dbh);
    $pages = ceil($num_regs / $per_page);
    if ($page > $pages && $pages != 0)  throwMiExcepcion("Página fuera de rango", "error", 200);
    $page = ($page <= $pages) ? $page : 1;
    $start_reg = $per_page * ($page - 1);

    $sqlLimit = $isPaginated ? " LIMIT $start_reg, $per_page" : "";
    $sql = $sqlSelect . " FROM $table" . $join . $where["sql"] . $orderBy . $sqlLimit;

    $stmt = $dbh->prepare($sql);
    $stmt->execute($where["params"]);
    $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['filas'] = $filas;
    $response['num_regs'] = $num_regs;
    $response['pages'] = $pages;
    $response['page'] = ($pages != 0) ? $page : 0;
    $response['next'] = ($pages > $page) ? $page + 1 : 0;
    $response['previous'] = ($pages > 1) ? $page - 1 : 0;
    $response['per_page'] = $per_page;
    return $response;
  }
  static function getProductos($tabla, $campos, $whereEquals = null)
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

  static function getProducto($id){
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
      CAST(s.stock as DOUBLE) as stock,
      CAST(p.stock_min as DOUBLE) as stock_min,
      p.thumb,
      p.estado,
      p.created_at,
      ifnull(p.updated_at, '') as updated_at
      FROM productos p
      LEFT JOIN marcas m ON p.marca_id = m.id 
      LEFT JOIN laboratorios l ON p.laboratorio_id = l.id
      LEFT JOIN stocks s ON p.id = s.producto_id AND s.establecimiento_id = :establecimiento_id
      WHERE p.id = :id;
    ";
    $curEstab = Users::getCurEstab();
    $establecimiento_id = $curEstab;
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute(['id' => $id, 'establecimiento_id' => $establecimiento_id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    $record['stock'] = $record['stock'] ? $record['stock'] : 0;
    return $record;
  }
  
  static function getProductoBy($paramWhere){
    $sqlWhere = implode(" AND ", array_map(function($el){
      return "p.$el = :$el";
    },array_keys($paramWhere)));
    $sqlWhere = $sqlWhere ? " WHERE " . $sqlWhere : "";
    $sql = "SELECT
      p.id,
      ifnull(p.codigo, '') as codigo,
      ifnull(p.barcode,'') as barcode,
      p.descripcion,
      ifnull(m.nombre, '') as marca,
      ifnull(l.nombre, '') as laboratorio,
      p.unidad_medida_cod,
      p.precio_costo,
      p.inventariable,
      p.thumb,
      p.created_at,
      ifnull(p.updated_at, '') as updated_at
      FROM productos p
      LEFT JOIN marcas m ON p.marca_id = m.id 
      LEFT JOIN laboratorios l ON p.laboratorio_id = l.id 
      $sqlWhere;
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($paramWhere);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    return $record;
  }
  static function getStocks($producto_id){
    $sql = "SELECT stocks
      FROM productos
      WHERE id = :producto_id
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute(["producto_id" => $producto_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC); 
    return $row["stocks"]; 
  }

  static function createProducto($params)
  {
    $sql = sqlInsert("productos", $params);
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $lastId = $dbh->lastInsertId();
    return $lastId;
  }
 
  static function updateProducto($table, $paramCampos, $paramWhere)
  {
    $sql = sqlUpdate($table, $paramCampos, $paramWhere);
    $params = array_merge($paramCampos, $paramWhere);
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $resp = $stmt->rowCount();
    return $resp;
  }

  static function deleteProducto($params){
    $sql = "DELETE FROM productos WHERE id = :id";
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
    $sql = "SELECT COUNT(*) AS count FROM productos" . $where;
    $param = array_merge($equal, $exclude);

    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($param);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);
    $response = $registro['count'];
    return $response;
  }


  // Metodos privados
  static private function num_regs($table, $join, $sqlWhere, $bindWhere, $dbh)
  {
    // Extraemos la cantidad de registros en total
    $sql = "SELECT COUNT(*) AS num_regs FROM $table" . $join . $sqlWhere;

    // $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($bindWhere);
    $rows = $stmt->fetch(PDO::FETCH_ASSOC); 
    return $rows['num_regs']; 
  }

}
