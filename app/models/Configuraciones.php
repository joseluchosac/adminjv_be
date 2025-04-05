<?php
require_once("Conexion.php");
class Configuraciones
{

  static function getEmpresa($campos, $whereEquals)
  {
    $sqlSelect = !empty($campos) ? "SELECT " . implode(", ", $campos) : "";

    $sqlWhere = SqlWhere::and([SqlWhere::equalAnd($whereEquals)]);
    $bindWhere = SqlWhere::arrMerge(["equal" => $whereEquals]);

    $sql = "$sqlSelect FROM empresa " . $sqlWhere;

    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($bindWhere);
    $records = $stmt->fetch(PDO::FETCH_ASSOC);
    return $records;
  }

  static function obtenerEmpresa(){
    $sql = "SELECT
        e.razon_social,
        e.nombre_comercial,
        e.ruc,
        e.direccion,
        e.ubigeo_inei,
        IFNULL(u.departamento, '') as departamento,
        IFNULL(u.provincia, '') as provincia,
        IFNULL(u.distrito, '') as distrito,
        e.telefono,
        e.email,
        e.simbolo_moneda,
        e.logo,
        e.certificado_digital,
        e.clave_certificado,
        e.usuario_sol,
        e.clave_sol
      FROM empresa e
      LEFT JOIN ubigeos u ON e.ubigeo_inei = u.ubigeo_inei
      WHERE id = 1
    ";
      $dbh = Conexion::conectar();
      $stmt = $dbh->prepare($sql);
      $stmt->execute();
      $empresa = $stmt->fetch(PDO::FETCH_ASSOC);
      if($empresa){
        $empresa["logo"] = $empresa["logo"] ? $empresa["logo"] : "no_image.png";
        $empresa["urlLogo"] = getBaseUrl() . normalize_url_path(dirname($_SERVER["PHP_SELF"]) . "/../store/img/empresa/".$empresa["logo"]);
        $empresa["urlNoImage"] = getBaseUrl() . normalize_url_path(dirname($_SERVER["PHP_SELF"]) . "/../store/img/empresa/no_image.png");
      }
      return $empresa;
  }

  static function obtenerEmpresaSession(){
    $sql = "SELECT
        e.razon_social,
        e.nombre_comercial,
        e.ruc,
        e.direccion,
        IFNULL(u.departamento, '') as departamento,
        IFNULL(u.provincia, '') as provincia,
        IFNULL(u.distrito, '') as distrito,
        e.telefono,
        e.email,
        e.logo
      FROM empresa e
      LEFT JOIN ubigeos u ON e.ubigeo_inei = u.ubigeo_inei
      WHERE id = 1
    ";
      $dbh = Conexion::conectar();
      $stmt = $dbh->prepare($sql);
      $stmt->execute();
      $empresa = $stmt->fetch(PDO::FETCH_ASSOC);
      if($empresa){
        $logo = $empresa["logo"] ? $empresa["logo"] : "no_image.png";
        $empresa["urlLogo"] = getBaseUrl() . normalize_url_path(dirname($_SERVER["PHP_SELF"]) . "/../store/img/empresa/".$logo);
        unset($empresa["logo"]);
      }
      return $empresa;
  }

  static function actualizarEmpresa($table, $paramCampos, $paramWhere){
    $sql = sqlUpdate($table, $paramCampos, $paramWhere);
    $params = array_merge($paramCampos, $paramWhere);
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $resp = $stmt->rowCount();
    return $resp;
  }

  static function obtenerModoFacturacion()
  {
    $sql = "SELECT
        id,
        clave,
        valor
      FROM configuraciones
      WHERE entidad_id = '200'
    ";
      $dbh = Conexion::conectar();
      $stmt = $dbh->prepare($sql);
      $stmt->execute();
      $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
      return $records;
  }

  static function obtenerModoGuiaDeRemision()
  {
    $sql = "SELECT
        id,
        clave,
        valor
      FROM configuraciones
      WHERE entidad_id = '300'
    ";
      $dbh = Conexion::conectar();
      $stmt = $dbh->prepare($sql);
      $stmt->execute();
      $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
      return $records;
  }
  
  static function obtenerUsuarioSolSecundario()
  {
    $sql = "SELECT
        id,
        clave,
        valor
      FROM configuraciones
      WHERE entidad_id = '400'
    ";
      $dbh = Conexion::conectar();
      $stmt = $dbh->prepare($sql);
      $stmt->execute();
      $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
      return $records;
  }

  static function obtenerServidorCorreo()
  {
    $sql = "SELECT
        id,
        clave,
        valor
      FROM configuraciones
      WHERE entidad_id = '100'
    ";
      $dbh = Conexion::conectar();
      $stmt = $dbh->prepare($sql);
      $stmt->execute();
      $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
      return $records;
  }

  static function actualizarConfiguraciones($entidadId, $datos){
    try {
      $sql = "UPDATE configuraciones 
        SET valor = :valor 
        WHERE clave = :clave AND entidad_id = :entidadId
      ";
      $dbh = Conexion::conectar();
      $dbh->beginTransaction();
      $stmt = $dbh->prepare($sql);
      foreach ($datos as $key => $value) {
        $arreglo = [
          "clave" => $key,
          "valor" => $value,
          "entidadId" => $entidadId
        ];
        $stmt->execute($arreglo);
      }
      $dbh->commit();
      $resp = true;
      return $resp;
    } catch (PDOException $e) {
      $dbh->rollBack();
      throwMiExcepcion($e->getMessage(), "error", 200);
    }
  }
}
