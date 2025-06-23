<?php
require_once("Conexion.php");

class Numeraciones
{
  static function getNumeracionesEstablecimiento($establecimiento_id){
    $sql = "SELECT 
        id,
        establecimiento_id,
        tipo_comprobante_cod,
        descripcion,
        serie,
        correlativo,
        modifica_a,
        estado
      FROM numeraciones
      WHERE establecimiento_id = :establecimiento_id
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute(["establecimiento_id" => $establecimiento_id]);
    $numeracionesEstablecimiento = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $numeracionesEstablecimiento;
  }

  static function getNumeracion($id){
    $sql = "SELECT 
        id,
        establecimiento_id,
        tipo_comprobante_cod,
        descripcion,
        serie,
        correlativo,
        modifica_a,
        estado
      FROM numeraciones
      WHERE id = :id
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute(["id" => $id]);
    $numeracionEstablecimiento = $stmt->fetch(PDO::FETCH_ASSOC);
    return $numeracionEstablecimiento;
  }

  static function getCorrelativo($paramWhere){
    $sql = "SELECT 
        id,
        establecimiento_id,
        tipo_comprobante_cod,
        descripcion,
        serie,
        correlativo,
        modifica_a,
        estado
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
