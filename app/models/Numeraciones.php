<?php
require_once("Conexion.php");

class Numeraciones
{
  static function getNumeraciones(){
    $sql = "SELECT 
        id,
        establecimiento_id,
        descripcion_doc,
        serie_pre,
        serie,
        correlativo,
        estado
      FROM numeraciones
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $numeracionesEstablecimiento = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $numeracionesEstablecimiento;
  }

  static function getNumeracion($id){
    $sql = "SELECT 
        id,
        establecimiento_id,
        descripcion_doc,
        serie_pre,
        serie,
        correlativo,
        estado
      FROM numeraciones
      WHERE id = :id
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute(["id" => $id]);
    $numeracion = $stmt->fetch(PDO::FETCH_ASSOC);
    return $numeracion;
  }

  static function getCorrelativo($paramWhere){
    $sql = "SELECT 
        id,
        establecimiento_id,
        serie_pre,
        serie,
        correlativo
      FROM numeraciones
      WHERE establecimiento_id = :establecimiento_id
        AND serie = :serie
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($paramWhere);
    $correlativo = $stmt->fetch(PDO::FETCH_ASSOC);
    return $correlativo['correlativo'];
  }

  static function createNumeracion($params)
  {
    $sql = sqlInsert("numeraciones", $params);
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $lastId = $dbh->lastInsertId();
    return $lastId;
  }

  static function updateNumeracion($paramCampos, $paramWhere)
  {
    $sql = sqlUpdate("numeraciones", $paramCampos, $paramWhere);
    $params = array_merge($paramCampos, $paramWhere);
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $resp = $stmt->rowCount();
    return $resp;
  }

  static function deleteNumeracion($params){
    $sql = "DELETE FROM numeraciones WHERE id = :id";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $resp = $stmt->rowCount();
    return $resp;
  }

  static function getSerie($serie_pre, $establecimiento_id){
    $sql = "SELECT 
        serie
      FROM numeraciones
      WHERE establecimiento_id = :establecimiento_id AND serie_pre = :serie_pre
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute(["serie_pre" => $serie_pre, "establecimiento_id" => $establecimiento_id]);
    $serie = $stmt->fetch(PDO::FETCH_ASSOC);
    return $serie;
  }

  static function countNumeraciones($equal, $exclude = []){
    $sqlWhere = SqlWhere::and([SqlWhere::equalAnd($equal)]);
    $bindWhere = SqlWhere::arrMerge(["equal" => $equal]);
    if($exclude){
      $sqlWhere .= " AND " . array_keys($exclude)[0] . " != :". array_keys($exclude)[0];
    }
    $sql = "SELECT COUNT(*) AS count FROM numeraciones" . $sqlWhere;
    $param = array_merge($bindWhere, $exclude);

    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($param);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);
    $response = $registro['count'];

    return $response;
  }
}
