<?php
require_once("Conexion.php");

class Establecimientos
{
  static function getEstablecimientos(){
    $sql = "SELECT
        e.id,
        e.codigo_establecimiento,
        e.nombre,
        e.direccion,
        e.ubigeo_inei,
        CONCAT(u.distrito, ', ', u.provincia, ', ', u.departamento) as distrito,
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
