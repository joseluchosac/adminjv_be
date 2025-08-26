<?php
require_once("Conexion.php");

class Proveedores
{
  static private $curUser = null;
  static private $activity = [];

  static public function filterProveedores($campos, $where, $orderBy, $pagination, $isPaginated = true){
    $table = "proveedores_v";
    $dbh = Conexion::conectar();

    $sqlSelect = !empty($campos) ? "SELECT " . implode(", ", $campos)  : "";

    $page = intval($pagination["page"]);
    $offset = intval($pagination["offset"]);

    $num_regs = self::num_regs($table, $where["sql"], $where["params"], $dbh);
    $pages = ceil($num_regs / $offset);
    if ($page > $pages && $pages != 0)  throwMiExcepcion("Página fuera de rango", "error", 200);
    $page = ($page <= $pages) ? $page : 1;
    $start_reg = $offset * ($page - 1);

    $sqlLimit = $isPaginated ? " LIMIT $start_reg, $offset" : "";
    $sql = $sqlSelect . " FROM $table" . $where["sql"] . $orderBy . $sqlLimit;

    $stmt = $dbh->prepare($sql);
    $stmt->execute($where["params"]);
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
    $sql = "SELECT COUNT(*) AS count FROM proveedores" . $where;
    $param = array_merge($equal, $exclude);

    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($param);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);
    $response = $registro['count'];
    return $response;
  }

  static function createProveedor($params)
  {
    $sql = sqlInsert("proveedores", $params);
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $lastId = $dbh->lastInsertId();
    $stmt->rowCount();
    return $lastId;
  }

  static function updateProveedor($table, $paramCampos, $paramWhere)
  {
    $sql = sqlUpdate($table, $paramCampos, $paramWhere);
    $params = array_merge($paramCampos, $paramWhere);
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $resp = $stmt->rowCount();
    return $resp;
  }

  static function deleteProveedor($params){
    $sql = "DELETE FROM proveedores WHERE id = :id";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $resp = $stmt->rowCount();
    return $resp;
  }

  static function getProveedor($id){
    $sql = "SELECT 
        p.id,
        p.tipo_documento_cod,
        td.descripcion AS tipo_documento,
        ifnull(p.nro_documento,'') AS nro_documento,
        p.nombre_razon_social,
        p.direccion,
        p.ubigeo_inei,
        u.dis_prov_dep,
        p.email,
        p.telefono,
        p.api,
        p.estado
      FROM proveedores p
      LEFT JOIN ubigeos_v u ON p.ubigeo_inei = u.ubigeo_inei
      LEFT JOIN tipos_documento td ON p.tipo_documento_cod = td.codigo
      WHERE p.id = :id;
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute(['id' => $id]);
    $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);
    return $proveedor;
  }

  // $paramsEqual de la forma ["campo1"=>"valor1", "campo2"=>"valor2"]
  static function getProveedoresBy($paramsEqual){
    $sqlWhere = implode(" AND ", array_map(function($el){
      return "p.$el = :$el";
    },array_keys($paramsEqual)));
    $sqlWhere = $sqlWhere ? " WHERE " . $sqlWhere : "";
    $sql = "SELECT 
        p.id,
        p.tipo_documento_cod,
        td.descripcion AS tipo_documento,
        ifnull(p.nro_documento,'') AS nro_documento,
        p.nombre_razon_social,
        p.direccion,
        p.ubigeo_inei,
        p.dis_prov_dep,
        p.email,
        p.telefono,
        p.api,
        p.estado
      FROM proveedores p
      LEFT JOIN tipos_documento td ON p.tipo_documento_cod = td.codigo
      $sqlWhere;
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($paramsEqual);
    $record = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $record;
  }

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
