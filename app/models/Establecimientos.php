<?php
require_once("Conexion.php");

class Establecimientos
{

  static function getEstablecimientos($campos, $orders = null)
  {
    $sql = "SELECT " . implode(", ", $campos) . " FROM establecimientos";
    $sqlOrder = $orders ? getSqlOrderBy($orders) : "";
    $sql .= $sqlOrder;

    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $records;
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
      campo_stock,
      estado
      FROM establecimientos
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
