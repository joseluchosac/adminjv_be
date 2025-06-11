<?php
require_once('../../app/models/Config.php');
require_once('../../app/models/Establecimientos.php');
class ConfigController
{
  public function get_empresa(){
    $empresa = Config::getEmpresa();
    return $empresa;
  }

  public function update_empresa(){
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);

    $campos = [
      "razon_social" => $_POST["razon_social"],
      "nombre_comercial" => $_POST["nombre_comercial"],
      "ruc" => $_POST["ruc"],
      "direccion" => $_POST["direccion"],
      "ubigeo_inei" => $_POST["ubigeo_inei"],
      "telefono" => $_POST["telefono"],
      "email" => $_POST["email"],
      "clave_certificado" => $_POST["clave_certificado"],
      "usuario_sol" => $_POST["usuario_sol"],
      "clave_sol" => $_POST["clave_sol"],
    ];
    // obtiene logo y certificado anterior
    $prev = Config::getEmpresaBy(["logo","certificado_digital"], [["field_name" => "id", "field_value"=>1]]);
    $folderLogo = "../store/img/empresa/";
    $folderCertificado = "../../app/files/certificado/";
    
    // Eliminar logo
    if($prev["logo"] && !$_POST["logo"]){
      $campos["logo"] = "";
      if(file_exists($folderLogo . $prev["logo"])){
        unlink($folderLogo . $prev["logo"]);
      }
    }
    // Reemplazando el logo
    if(isset($_FILES["fileLogo"])){
      $name = $_FILES["fileLogo"]["name"];
      $tmpName = $_FILES["fileLogo"]["tmp_name"];
      $to = $folderLogo . $name;
      $uploaded = move_uploaded_file($tmpName, $to);
      if($uploaded){
        $campos["logo"] = $name;
        if($prev["logo"] && file_exists($folderLogo . $prev["logo"])){
          unlink($folderLogo . $prev["logo"]);
        }
      }
    }

    // Eliminar certificado
    if($prev["certificado_digital"] && !$_POST["certificado_digital"]){
      $campos["certificado_digital"] = "";
      if(file_exists($folderCertificado . $prev["certificado_digital"])){
        unlink($folderCertificado . $prev["certificado_digital"]);
      }
    }
    // Reemplazar certificado
    if(isset($_FILES["fileCertificado"])){
      $name = $_FILES["fileCertificado"]["name"];
      $tmpName = $_FILES["fileCertificado"]["tmp_name"];
      $to = $folderCertificado . $name;
      $uploaded = move_uploaded_file($tmpName, $to);
      if($uploaded){
        $campos["certificado_digital"] = $name;
        if($prev["certificado_digital"] && file_exists($folderCertificado . $prev["certificado_digital"])){
          unlink($folderCertificado . $prev["certificado_digital"]);
        }
      }
    }
    $resp = Config::updateEmpresa("empresa", $campos, ["id" => 1]);
    if (!$resp) throwMiExcepcion("Ningún registro modificado", "warning", 200);
    $registro = Config::getEmpresa();

    $response['msgType'] = "success";
    $response['msg'] = "Registro actualizado";
    $response['registro'] = $registro;
    return $response;
  }
  
  public function get_cpe_fact(){
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $resp = Config::getConfigDb("cpe_fact");
    return $resp;
  }

  public function update_cpe_fact(){
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $parJson = file_get_contents('php://input');
    $count = Config::setConfigDb($parJson, "cpe_fact");
    if (!$count) throwMiExcepcion("No hubo actualizaciones", "warning", 200);
    $response['msgType'] = "success";
    $response['msg'] = "Registro actualizado";
    $response['registro'] = $parJson;
    return $response;
  }

  public function get_cpe_guia(){
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $resp = Config::getConfigDb("cpe_guia");
    return $resp;
  }

  public function update_cpe_guia(){
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $parJson = file_get_contents('php://input');
    $count = Config::setConfigDb($parJson, "cpe_guia");
    if (!$count) throwMiExcepcion("No hubo actualizaciones", "warning", 200);
    $response['msgType'] = "success";
    $response['msg'] = "Registro actualizado";
    $response['registro'] = $parJson;
    return $response;
  }

  public function get_apis_nro_doc(){
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $resp = Config::getConfigDb("apis_nro_doc");
    return $resp;
  }

  public function update_apis_nro_doc(){
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $parJson = file_get_contents('php://input');
    $count = Config::setConfigDb($parJson, "apis_nro_doc");
    if (!$count) throwMiExcepcion("No hubo actualizaciones", "warning", 200);
    $response['msgType'] = "success";
    $response['msg'] = "Registro actualizado";
    $response['registro'] = $parJson;
    return $response;
  }

  public function get_usuario_sol_sec(){
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $resp = Config::getConfigDb("usuario_sol_sec");
    return $resp;
  }

  public function update_usuario_sol_sec(){
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $parJson = file_get_contents('php://input');
    $count = Config::setConfigDb($parJson, "usuario_sol_sec");
    if (!$count) throwMiExcepcion("No hubo actualizaciones", "warning", 200);
    $response['msgType'] = "success";
    $response['msg'] = "Registro actualizado";
    $response['registro'] = $parJson;
    return $response;
  }

  public function get_email_config(){
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $resp = Config::getConfigDb("email_config");
    return $resp;
  }

  public function update_email_config(){
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $parJson = file_get_contents('php://input');
    $count = Config::setConfigDb($parJson, "email_config");
    if (!$count) throwMiExcepcion("No hubo actualizaciones", "warning", 200);
    $response['msgType'] = "success";
    $response['msg'] = "Registro actualizado";
    $response['registro'] = $parJson;
    return $response;
  }

  // public function get_establecimientos(){
  //   if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
  //   $establecimientos = Establecimientos::getEstablecimientos();
  //   $resp['content'] = $establecimientos;
  //   return $resp;
  // }

  // public function get_establecimiento(){
  //   if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
  //   $pJson = json_decode(file_get_contents('php://input'), true);
  //   if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 200);
  //   $establecimiento = Establecimientos::getEstablecimiento($pJson['id']);
  //   $resp["content"] = $establecimiento;
  //   return $resp;
  // }

  // public function create_establecimiento(){
  //   if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 200);

  //   $pJson = json_decode(file_get_contents('php://input'), true);
  //   if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);
  //   $codigo_establecimiento = trimSpaces($pJson['codigo_establecimiento']);
  //   $nombre = trimSpaces($pJson['nombre']);
  //   $params = [
  //     "codigo_establecimiento" => $codigo_establecimiento ? $codigo_establecimiento : null,
  //     "nombre" => $nombre,
  //     "direccion" => trimSpaces($pJson['direccion']),
  //     "telefono" => trimSpaces($pJson['telefono']),
  //     "email" => trimSpaces($pJson['email']),
  //     "ubigeo_inei" => $pJson['ubigeo_inei'],
  //     "almacen" => $pJson['almacen'],
  //     "sucursal" => $pJson['sucursal'],
  //   ];
  //   // Validacion
  //   //$this->validateCreateUser($params);

  //   // Buscando duplicados
  //   $count = Config::countRecords("establecimientos", ["nombre" => $nombre]);
  //   if ($count) throwMiExcepcion("El nombre del establecimiento: " . $nombre . ", ya existe!", "warning");

  //   $lastId = Establecimientos::createEstablecimiento($params);
  //   if (!$lastId) throwMiExcepcion("Ningún registro guardado", "warning");
  //   $registro = Establecimientos::getestablecimiento($lastId);
  //   $response['error'] = false;
  //   $response['msgType'] = "success";
  //   $response['msg'] = "Marca registrado";
  //   $response['content'] = $registro;
  //   return $response;
  // }

  // public function update_establecimiento()
  // {
  //   if ($_SERVER['REQUEST_METHOD'] != 'PUT') throwMiExcepcion("Método no permitido", "error", 405);

  //   $pJson = json_decode(file_get_contents('php://input'), true);
  //   if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 200);
  //   $codigo_establecimiento = trimSpaces($pJson['codigo_establecimiento']);
  //   $nombre = trimSpaces($pJson['nombre']);
  //   $paramCampos = [
  //     "codigo_establecimiento" => $codigo_establecimiento ? $codigo_establecimiento : null,
  //     "nombre" => $nombre,
  //     "direccion" => trimSpaces($pJson['direccion']),
  //     "telefono" => trimSpaces($pJson['telefono']),
  //     "email" => trimSpaces($pJson['email']),
  //     "ubigeo_inei" => $pJson['ubigeo_inei'],
  //     "almacen" => $pJson['almacen'],
  //     "sucursal" => $pJson['sucursal'],
  //   ];

  //   // Validacion
  //   // $this->validateUpdateProducto($paramCampos);

  //   // Buscando duplicados
  //   $exclude = ["id" => $pJson['id']];
  //   $count = Config::countRecords("establecimientos", ["nombre" => $nombre], $exclude);
  //   if ($count) throwMiExcepcion("El nombre del establecimiento: " . $nombre . ", ya existe!", "warning");

  //   $paramWhere = ["id" => $pJson['id']];

  //   $resp = Establecimientos::updateEstablecimiento($paramCampos, $paramWhere);
  //   if (!$resp) throwMiExcepcion("Ningún registro modificado", "warning", 200);
    
  //   $registro = Establecimientos::getEstablecimiento($pJson['id']);

  //   $response['error'] = false;
  //   $response['msg'] = "Registro actualizado";
  //   $response['msgType'] = "success";
  //   $response['content'] = $registro;
  //   return $response;
  // }

  // public function update_estado_establecimiento(){
  //   if ($_SERVER['REQUEST_METHOD'] != 'PUT') throwMiExcepcion("Método no permitido", "error", 405);

  //   $pJson = json_decode(file_get_contents('php://input'), true);
  //   if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 200);

  //   $paramCampos = ["estado" => $pJson['estado']];
  //   $paramWhere = ["id" => $pJson['id']];

  //   $resp = Establecimientos::updateEstablecimiento($paramCampos, $paramWhere);
  //   if (!$resp) throwMiExcepcion("Ningún registro modificado", "warning", 200);
    
  //   $registro = Establecimientos::getEstablecimiento($pJson['id']);

  //   $response['msgType'] = "success";
  //   $response['msg'] = "Registro actualizado";
  //   $response['content'] = $registro;
  //   return $response; 
  // }

  // public function delete_Establecimiento()
  // {
  //   if ($_SERVER['REQUEST_METHOD'] != 'DELETE') throwMiExcepcion("Método no permitido", "error", 405);
  //   $pJson = json_decode(file_get_contents('php://input'), true);
  //   if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);

  //   $params = ["id" => $pJson['id']];
  //   $resp = Establecimientos::deleteEstablecimiento($params);
  //   if (!$resp) throwMiExcepcion("Ningún registro eliminado", "warning");

  //   $response['content'] = null;
  //   $response['error'] = "false";
  //   $response['msgType'] = "success";
  //   $response['msg'] = "Registro eliminado";
  //   return $response;
  // }

  // public function get_series_establecimiento(){
  //   if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
  //   $pJson = json_decode(file_get_contents('php://input'), true);
  //   if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 200);

  //   $resp = Config::getSeriesEstablecimiento($pJson['establecimiento_id']);
  //   return $resp;
  // }
}

