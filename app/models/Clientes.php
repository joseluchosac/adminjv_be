<?php
require_once("Conexion.php");

class Clientes
{
  static private $curUser = null;
  static private $activity = [];

  static public function filtrarClientes($campos, $paramWhere, $paramOrders, $pagination, $isPaginated = true)
  {
    $table = "clientes_v";

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

    $response['filas'] = $filas;
    $response['num_regs'] = $num_regs;
    $response['pages'] = $pages;
    $response['page'] = ($pages != 0) ? $page : 0;
    $response['next'] = ($pages > $page) ? $page + 1 : 0;
    $response['previous'] = ($pages > 1) ? $page - 1 : 0;
    $response['offset'] = $offset;
    return $response;
  }

  static function countRecordsBy($equal, $exclude = []){
    $where = " WHERE " . array_keys($equal)[0] . " = :". array_keys($equal)[0];
    if($exclude){
      $where .= " AND " . array_keys($exclude)[0] . " != :". array_keys($exclude)[0];
    }
    $sql = "SELECT COUNT(*) AS count FROM clientes" . $where;
    $param = array_merge($equal, $exclude);

    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($param);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);
    $response = $registro['count'];
    return $response;
  }

  static function createCliente($params)
  {
    $sql = sqlInsert("clientes", $params);
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $lastId = $dbh->lastInsertId();
    $resp = $stmt->rowCount();
    return $lastId;
  }

  static function updateCliente($table, $paramCampos, $paramWhere)
  {
    $sql = sqlUpdate($table, $paramCampos, $paramWhere);
    $params = array_merge($paramCampos, $paramWhere);
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $resp = $stmt->rowCount();
    return $resp;
  }

  static function getCliente($id){
    $sql = "SELECT 
        c.id,
        c.tipo_documento_cod,
        td.descripcion AS tipo_documento,
        ifnull(c.nro_documento,'') AS nro_documento,
        c.nombre_razon_social,
        c.direccion,
        c.ubigeo_inei,
        ifnull(u.departamento,'') AS departamento,
        ifnull(u.provincia, '') AS provincia,
        ifnull(u.distrito, '') AS distrito,
        c.email,
        c.telefono,
        c.api,
        c.estado
      FROM clientes c
      LEFT JOIN ubigeos u ON c.ubigeo_inei = u.ubigeo_inei
      LEFT JOIN tipos_documento td ON c.tipo_documento_cod = td.codigo
      WHERE c.id = :id;
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute(['id' => $id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    return $record;
  }











  static function obtenerClientes($tabla, $campos, $whereEquals = null)
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

  static function getUserBy($param){
    $sqlWhere = implode(" AND ", array_map(function($el){
      return "u.$el = :$el";
    },array_keys($param)));
    $sqlWhere = $sqlWhere ? " WHERE " . $sqlWhere : "";
    $sql = "SELECT
        u.id,
        u.nombres,
        u.apellidos,
        u.username,
        ifnull(u.email,'') AS email,
        u.rol_id,
        r.rol,
        u.caja_id,
        c.descripcion AS caja,
        u.estado,
        u.created_at,
        ifnull(u.updated_at, '') AS updated_at
      FROM users u
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
   
  static function eliminarUser($params){
    $sql = "DELETE FROM users WHERE id = :id";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $resp = $stmt->rowCount();
    return $resp;
  }
  
  static function setCurUser($curUser){
    self::$curUser = $curUser;
  }
  static function getCurUser(){
    return self::$curUser;
  }
  static function setActivity($activity){
    self::$activity = $activity;
  }

  static function getActivity(){
    return self::$activity;
  }

  static function setActivityLog($detalle = ""){
    $sql = "INSERT INTO activity_log 
      (user_id, controller, accion, detalle)
      VALUES
      (:user_id, :controller, :accion, :detalle)
    ";
    $params = [
      "user_id" => self::$curUser["id"],
      "controller" => self::$activity["prefixController"],
      "accion" => self::$activity["accion"],
      "detalle" => $detalle,
    ];
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
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
