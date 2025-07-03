<?php
require_once("Conexion.php");
class Config
{

  static function getEmpresaBy($campos, $whereEquals)
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

  static function getEmpresa(){
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

  static function getEmpresaSession(){
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

  static function updateEmpresa($table, $paramCampos, $paramWhere){
    $sql = sqlUpdate($table, $paramCampos, $paramWhere);
    $params = array_merge($paramCampos, $paramWhere);
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $resp = $stmt->rowCount();
    return $resp;
  }

  static function getConfigDb($doc_name){
    $sql = "SELECT
      doc_value
      FROM config
      WHERE doc_name = :doc_name
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute(["doc_name"=>$doc_name]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    return $data;
  }

  static function setConfigDb($doc_value, $doc_name){
    $sql = "UPDATE config
      SET doc_value = :doc_value
      WHERE doc_name = :doc_name
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute(["doc_value"=>$doc_value, "doc_name"=>$doc_name]);
    $count = $stmt->rowCount();
    return $count;
  }

  static function createTerminal($params)
  {
    $sql = sqlInsert("terminales", $params);
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $lastId = $dbh->lastInsertId();
    return $lastId;
  }

  static function updateTerminal($paramCampos, $paramWhere)
  {
    $sql = sqlUpdate("terminales", $paramCampos, $paramWhere);
    $params = array_merge($paramCampos, $paramWhere);
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $count = $stmt->rowCount();
    return $count;
  }

  static function getTerminal($nombre){
    $sql = "SELECT
      nombre,
      descripcion,
      establecimiento_id
      FROM terminales
      WHERE nombre = :nombre
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute(["nombre"=>$nombre]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    return $data;
  }

  static function countRecords($table, $equal, $exclude = []){
    $where = " WHERE " . array_keys($equal)[0] . " = :". array_keys($equal)[0];
    if($exclude){
      $where .= " AND " . array_keys($exclude)[0] . " != :". array_keys($exclude)[0];
    }
    $sql = "SELECT COUNT(*) AS count FROM $table" . $where;
    $param = array_merge($equal, $exclude);

    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($param);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);
    $response = $registro['count'];
    return $response;
  }

  static function countTerminales($equal, $exclude = []){
    $sqlWhere = SqlWhere::and([SqlWhere::equalAnd($equal)]);
    $bindWhere = SqlWhere::arrMerge(["equal" => $equal]);
    if($exclude){
      $sqlWhere .= " AND " . array_keys($exclude)[0] . " != :". array_keys($exclude)[0];
    }
    $sql = "SELECT COUNT(*) AS count FROM terminales" . $sqlWhere;
    $param = array_merge($bindWhere, $exclude);

    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($param);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);
    $response = $registro['count'];

    return $response;
  }
}
