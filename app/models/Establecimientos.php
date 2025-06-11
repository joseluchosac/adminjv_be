<?php
require_once("Conexion.php");

class Establecimientos
{
  static public function filterSucursales($campos, $paramWhere, $paramOrders, $pagination, $isPaginated = true)
  {
    $table = "sucursales_v";

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


  
  // Evaluar
  static function getEstablecimientos(){
    $sql = "SELECT
        e.id,
        e.codigo_establecimiento,
        e.nombre,
        e.direccion,
        e.ubigeo_inei,
        CONCAT(u.distrito, ', ', u.provincia, ', ', u.departamento) as dis_prov_dep,
        e.telefono,
        e.email,
        e.sucursal,
        e.almacen,
        e.estado
      FROM establecimientos e
      LEFT JOIN ubigeos u ON e.ubigeo_inei = u.ubigeo_inei
    ";
      $dbh = Conexion::conectar();
      $stmt = $dbh->prepare($sql);
      $stmt->execute();
      $establecimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
      foreach ($establecimientos as $key => $value) {
        $establecimientos[$key]["sucursal"] = boolval($value['sucursal']);
        $establecimientos[$key]["almacen"] = boolval($value['almacen']);
      }
      return $establecimientos;
  }

  static function getEstablecimiento($id){
    $sql = "SELECT
        e.id,
        e.codigo_establecimiento,
        e.nombre,
        e.direccion,
        e.ubigeo_inei,
        u.departamento,
		    u.provincia,
		    u.distrito,
        e.telefono,
        e.email,
        e.sucursal,
        e.almacen,
        e.estado
      FROM establecimientos e
      LEFT JOIN ubigeos u ON e.ubigeo_inei = u.ubigeo_inei
      WHERE e.id = :id
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
    $resp = $stmt->rowCount();
    return $resp;
  }

  static function deleteEstablecimiento($params){
    $sql = "DELETE FROM establecimientos WHERE id = :id";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $resp = $stmt->rowCount();
    return $resp;
  }
}
