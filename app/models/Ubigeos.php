<?php
require_once("Conexion.php");

class Ubigeos
{

  static public function filterUbigeos($campos, $where, $orderBy, $pagination, $isPaginated = true){
    $table = "ubigeos";
    $dbh = Conexion::conectar();

    $sqlSelect = !empty($campos) ? "SELECT " . implode(", ", $campos)  : "";
    $join = " INNER JOIN (
      SELECT ubigeo_inei as ubigeo_inei2, CONCAT_WS(' - ', distrito, provincia, departamento) AS dis_prov_dep FROM ubigeos) u2 
	    ON ubigeo_inei = u2.ubigeo_inei2
    ";
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


  static function getUbigeo($id){
    $sql = "SELECT ubigeo_inei, ubigeo_reniec, departamento, provincia, distrito FROM ubigeos WHERE ubigeo_inei = :ubigeo_inei;";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute(['id' => $id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    return $record;
  }
  
  static function getUbigeoByUbigeoInei($ubigeo_inei){
    $sql = "SELECT
        ubigeo_inei,
        ubigeo_reniec,
        CONCAT_WS(' - ', distrito, provincia, departamento) AS dis_prov_dep,
	      CONCAT_WS(' - ', departamento, provincia, distrito) AS dep_prov_dis
      FROM ubigeos
      WHERE ubigeo_inei = :ubigeo_inei
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute(["ubigeo_inei" => $ubigeo_inei]);
    $ubigeo = $stmt->fetch(PDO::FETCH_ASSOC);
    return $ubigeo;
  }
  

  static function countRecordsBy($equal, $exclude = []){
    $where = " WHERE " . array_keys($equal)[0] . " = :". array_keys($equal)[0];
    if($exclude){
      $where .= " AND " . array_keys($exclude)[0] . " != :". array_keys($exclude)[0];
    }
    $sql = "SELECT COUNT(*) AS count FROM ubigeos" . $where;
    $param = array_merge($equal, $exclude);

    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($param);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);
    $response = $registro['count'];
    return $response;
  }

  static function createUbigeo($params)
  {
    $sql = sqlInsert("ubigeos", $params);
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $lastId = $dbh->lastInsertId();
    $resp = $stmt->rowCount();
    return $lastId;
  }

  static function updateUbigeo($table, $paramCampos, $paramWhere)
  {
    $sql = sqlUpdate($table, $paramCampos, $paramWhere);
    $params = array_merge($paramCampos, $paramWhere);
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $resp = $stmt->rowCount();
    return $resp;
  }

  static function deleteUbigeo($params){
    $sql = "DELETE FROM ubigeos WHERE id = :id";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $resp = $stmt->rowCount();
    return $resp;
  }



  static private function num_regs($table, $join, $sqlWhere, $bindWhere)
  {
    // Extraemos la cantidad de registros en total
    $sql = "SELECT COUNT(*) AS num_regs FROM $table" . $join . $sqlWhere;

    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($bindWhere);
    $rows = $stmt->fetch(PDO::FETCH_ASSOC); 
    return $rows['num_regs']; 
  }

}
