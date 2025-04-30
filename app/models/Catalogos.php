<?php
require_once("Conexion.php");
class Catalogos
{

  static function getCatalogos($tablas)
  {

    $dbh = Conexion::conectar();
    foreach ($tablas as $value) {
      $stmt = $dbh->prepare($value["sql"]);
      $stmt->execute();
      $res[$value['table']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    return $res;
  }

  static function createTipoComprobante($paramCampos){
    $sql = sqlInsert("tipos_comprobante", $paramCampos);
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($paramCampos);
    $lastId = $dbh->lastInsertId();
    return $lastId;
  }

  static function updateTipoComprobante($paramCampos, $paramWhere){
    $sql = sqlUpdate("tipos_comprobante", $paramCampos, $paramWhere);
    $params = array_merge($paramCampos, $paramWhere);
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $resp = $stmt->rowCount();
    return $resp;
  }

  static function deleteTipoComprobante($params){
    $sql = "DELETE FROM tipos_comprobante WHERE id = :id";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $count = $stmt->rowCount();
    return $count;
  }

  static function getProvincias($departamento){
    $sql = "SELECT DISTINCT 
        provincia 
      FROM ubigeos WHERE departamento = :departamento;
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array("departamento" => $departamento));
    $provincias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $provincias;
  }
  
  static function getDistritos($departamento, $provincia){
    $sql = "SELECT
        ubigeo_inei,
        distrito 
      FROM ubigeos WHERE departamento = :departamento AND provincia = :provincia;
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array("departamento" => $departamento, "provincia" => $provincia));
    $distritos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $distritos;

  }
}
