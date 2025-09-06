<?php
require_once("Conexion.php");

class Clientes
{
  static public function filterClientes($campos, $where, $orderBy, $pagination, $isPaginated = true)
  {
    $table = "clientes_v";
    $dbh = Conexion::conectar();

    $sqlSelect = !empty($campos) ? "SELECT " . implode(", ", $campos)  : "";

    $page = intval($pagination["page"]);
    $per_page = intval($pagination["per_page"]);

    $num_regs = self::num_regs($table, $where["sql"], $where["params"], $dbh);
    $pages = ceil($num_regs / $per_page);
    if ($page > $pages && $pages != 0)  throwMiExcepcion("Página fuera de rango", "error", 200);
    $page = ($page <= $pages) ? $page : 1;
    $start_reg = $per_page * ($page - 1);

    $sqlLimit = $isPaginated ? " LIMIT $start_reg, $per_page" : "";
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
    $response['per_page'] = $per_page;
    return $response;
  }
  
 /*  static public function filterClientes($campos, $paramWhere, $paramOrders, $pagination, $isPaginated = true)
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
    $per_page = intval($pagination["per_page"]);

    $num_regs = self::num_regs($table, $sqlWhere, $bindWhere);
    $pages = ceil($num_regs / $per_page);
    if($page > $pages && $pages != 0)  throwMiExcepcion("Págian fuera de rango", "error", 200);
    $page = ($page <= $pages) ? $page : 1;
    $start_reg = $per_page * ($page - 1);

    $sqlLimit = $isPaginated ? " LIMIT $start_reg, $per_page" : "";
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
    $response['per_page'] = $per_page;
    return $response;
  } */

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
    $stmt->rowCount();
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

  static function deleteCliente($params){
    $sql = "DELETE FROM clientes WHERE id = :id";
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
        u.dis_prov_dep,
        c.email,
        c.telefono,
        c.api,
        c.estado
      FROM clientes c
      LEFT JOIN ubigeos_v u ON c.ubigeo_inei = u.ubigeo_inei
      LEFT JOIN tipos_documento td ON c.tipo_documento_cod = td.codigo
      WHERE c.id = :id;
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute(['id' => $id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    return $record;
  }

  // $paramsEqual de la forma ["campo1"=>"valor1", "campo2"=>"valor2"]
  static function getClientesBy($paramsEqual){
    $sqlWhere = implode(" AND ", array_map(function($el){
      return "c.$el = :$el";
    },array_keys($paramsEqual)));
    $sqlWhere = $sqlWhere ? " WHERE " . $sqlWhere : "";
    $sql = "SELECT 
        c.id,
        c.tipo_documento_cod,
        td.descripcion AS tipo_documento,
        ifnull(c.nro_documento,'') AS nro_documento,
        c.nombre_razon_social,
        c.direccion,
        c.ubigeo_inei,
        c.dis_prov_dep,
        c.email,
        c.telefono,
        c.api,
        c.estado
      FROM clientes c
      LEFT JOIN tipos_documento td ON c.tipo_documento_cod = td.codigo
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
