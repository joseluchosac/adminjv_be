<?php
require_once("Conexion.php");

class Establecimientos
{
  static public function filterEstablecimientos($campos, $paramWhere, $paramOrders, $pagination, $isPaginated = true)
  {
    $table = "establecimientos_v";

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
  
  static function getEstablecimientos(){
    $sql = "SELECT
      id,
      tipo,
      codigo,
      descripcion,
      direccion,
      ubigeo_inei,
      dis_prov_dep,
      telefono,
      email,
      estado
      FROM establecimientos_v
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $establecimiento = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $establecimiento;
  }

  static function getEstablecimiento($id){
    $sql = "SELECT
      id,
      tipo,
      codigo,
      descripcion,
      direccion,
      ubigeo_inei,
      dis_prov_dep,
      telefono,
      email,
      estado
      FROM establecimientos_v
      WHERE id = :id
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute(["id" => $id]);
    $establecimiento = $stmt->fetch(PDO::FETCH_ASSOC);
    return $establecimiento;
  }

  static function createEstablecimiento($params)
  {
    $sql = sqlInsert("establecimientos", $params);
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $lastId = $dbh->lastInsertId();
    return $lastId;
  }

  static function updateEstablecimiento($paramCampos, $paramWhere)
  {
    $sql = sqlUpdate("establecimientos", $paramCampos, $paramWhere);
    $params = array_merge($paramCampos, $paramWhere);
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $count = $stmt->rowCount();
    return $count;
  }

  static function deleteEstablecimiento($params){
    $sql = "DELETE FROM establecimientos WHERE id = :id";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $count = $stmt->rowCount();
    return $count;
  }

  static function countEstablecimientos($equal, $exclude = []){
    $sqlWhere = SqlWhere::and([SqlWhere::equalAnd($equal)]);
    $bindWhere = SqlWhere::arrMerge(["equal" => $equal]);

    if($exclude){
      $sqlWhere .= " AND " . array_keys($exclude)[0] . " != :". array_keys($exclude)[0];
    }

    $sql = "SELECT COUNT(*) AS count FROM establecimientos" . $sqlWhere;
    $param = array_merge($bindWhere, $exclude);

    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($param);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);
    $response = $registro['count'];

    return $response;
  }

  static function countRecordsBy($equal, $exclude = []){
    $where = " WHERE " . array_keys($equal)[0] . " = :". array_keys($equal)[0];
    if($exclude){
      $where .= " AND " . array_keys($exclude)[0] . " != :". array_keys($exclude)[0];
    }
    $sql = "SELECT COUNT(*) AS count FROM establecimientos" . $where;
    $param = array_merge($equal, $exclude);

    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($param);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);
    $response = $registro['count'];
    return $response;
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
