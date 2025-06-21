<?php
require_once("Conexion.php");

class Series
{
  static function getSeriesEstablecimiento($establecimiento_id){
    $sql = "SELECT 
        id,
        establecimiento_id,
        tipo_comprobante_cod,
        descripcion,
        serie,
        correlativo,
        modifica_a,
        estado
      FROM series
      WHERE establecimiento_id = :establecimiento_id
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute(["establecimiento_id" => $establecimiento_id]);
    $seriesEstablecimiento = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $seriesEstablecimiento;
  }

  static function getSerieEstablecimiento($id){
    $sql = "SELECT 
        id,
        establecimiento_id,
        tipo_comprobante_cod,
        descripcion,
        serie,
        correlativo,
        modifica_a,
        estado
      FROM series
      WHERE id = :id
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute(["id" => $id]);
    $serieEstablecimiento = $stmt->fetch(PDO::FETCH_ASSOC);
    return $serieEstablecimiento;
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
      FROM series
      WHERE establecimiento_id = :establecimiento_id
        AND serie = :serie
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($paramWhere);
    $correlativo = $stmt->fetch(PDO::FETCH_ASSOC);
    return $correlativo['correlativo'];
  }

  static function createSerieEstablecimiento($params)
  {
    $sql = sqlInsert("series", $params);
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $lastId = $dbh->lastInsertId();
    return $lastId;
  }

  static function updateSerieEstablecimiento($paramCampos, $paramWhere)
  {
    $sql = sqlUpdate("series", $paramCampos, $paramWhere);
    $params = array_merge($paramCampos, $paramWhere);
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $resp = $stmt->rowCount();
    return $resp;
  }

  static function deleteSerieEstablecimiento($params){
    $sql = "DELETE FROM series WHERE id = :id";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $resp = $stmt->rowCount();
    return $resp;
  }

  static function countSeries($equal, $exclude = []){
    $sqlWhere = SqlWhere::and([SqlWhere::equalAnd($equal)]);
    $bindWhere = SqlWhere::arrMerge(["equal" => $equal]);
    if($exclude){
      $sqlWhere .= " AND " . array_keys($exclude)[0] . " != :". array_keys($exclude)[0];
    }
    $sql = "SELECT COUNT(*) AS count FROM series" . $sqlWhere;
    $param = array_merge($bindWhere, $exclude);

    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($param);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);
    $response = $registro['count'];

    return $response;
  }
}
