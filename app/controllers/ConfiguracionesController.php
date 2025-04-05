<?php
require_once('../../app/models/Configuraciones.php');

class ConfiguracionesController
{
  public function obtener_empresa(){
    $empresa = Configuraciones::obtenerEmpresa();
    return $empresa;
  }

  public function actualizar_empresa(){
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
    // obtener logo y certificado anterior
    $prev = Configuraciones::getEmpresa(["logo","certificado_digital"], [["campo_name" => "id", "value"=>1]]);
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
    $resp = Configuraciones::actualizarEmpresa("empresa", $campos, ["id" => 1]);
    if (!$resp) throwMiExcepcion("Ningún registro modificado", "warning", 200);
    $registro = Configuraciones::obtenerEmpresa();

    $response['msgType'] = "success";
    $response['msg'] = "Registro actualizado";
    $response['registro'] = $registro;
    return $response;
  }

  public function obtener_configuraciones(){
    $response['modoFacturacion'] = transponerArreglo(Configuraciones::obtenerModoFacturacion());
    $response['modoGuiaDeRemision'] = transponerArreglo(Configuraciones::obtenerModoGuiaDeRemision());
    $response['usuarioSolSecundario'] = transponerArreglo(Configuraciones::obtenerUsuarioSolSecundario());
    $response['servidorCorreo'] = transponerArreglo(Configuraciones::obtenerServidorCorreo());
    return $response;
  }

  public function actualizar_configuraciones(){
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $parJson = json_decode(file_get_contents('php://input'), true) ?? [];
    $entidadId = explode(";", $parJson["entidad"])[0]; // 200
    unset($parJson["entidad"]);

    $resp = Configuraciones::actualizarConfiguraciones($entidadId, $parJson);
    if(!$resp) throwMiExcepcion("Error al actualizar", "error", 405);

    $response['msgType'] = "success";
    $response['msg'] = "Registro actualizado";
    return $response;
  }
}

