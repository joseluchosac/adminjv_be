<?php
// require_once("Conexion.php");
// apis_nro_doc, apis_tipo_cambio
class Services {

  static public function consultarNroDoc($nro_documento, $tipo_documento_cod){
    // Obtener la api por defecto para hacer la consulta
    $sql = "SELECT doc_value FROM config WHERE doc_name = 'apis_nro_doc'";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    $apis = json_decode($data['doc_value'], true);
    $defaultApi = $apis['default'];

    switch ($defaultApi) {
      case 'apisnetpe':{
        $response = self::consultaNroDoc_apis_net_pe($nro_documento, $tipo_documento_cod, $apis[$defaultApi]);
        break;
      }
      default:{
        $response['error'] = true;
        $response['msg'] = "Error de servidor";
        $response['msgType'] = "error";
        break;
      }
    }
    return $response;
  }

  static private function consultaNroDoc_apis_net_pe($nro_documento, $tipo_documento_cod, $api){

    $url = "";
    if($tipo_documento_cod == "1"){ // dni
      $url = $api['dni'];
    }else if($tipo_documento_cod == "6"){ // ruc
      $url = $api['ruc'];
    }
    $url = $url . $nro_documento; // "https://api.apis.net.pe/v2/reniec/dni?numero="
    // Inicializa cURL
    $curl = curl_init();
    // Configura cURL para realizar la solicitud GET
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        "Authorization: Bearer " . $api['token'],
    ));

    // Ejecuta la solicitud
    $response = curl_exec($curl);
    // Obtén el código de estado HTTP
    // $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    $resp = json_decode($response, true);
    if(!$response) throwMiExcepcion("Error en la petición");
    if(curl_errno($curl)) throwMiExcepcion("Error en cURL: " . curl_error($curl));
    curl_close($curl);
    $nroDoc = [];
    $content = null;
    if(isset($resp["message"])){
      $nroDoc['error'] = true;
      $nroDoc['msg'] = $resp['message'];
      $nroDoc['msgType'] = "warning";
    }else{
      if($tipo_documento_cod == "1"){ // DNI
        $content["nombre_razon_social"] = $resp["nombreCompleto"];
        $content["condicion_sunat"] = "";
        $content["estado_sunat"] = "";
        $content["direccion"] = "";
        $content["ubigeo"] = "";
      }else if($tipo_documento_cod == "6"){ // RUC
        $content["nombre_razon_social"] = $resp["razonSocial"];
        $content["condicion_sunat"] = $resp["condicion"];
        $content["estado_sunat"] = $resp["estado"];
        $content["direccion"] = $resp["direccion"];
        $content["ubigeo"] = $resp["ubigeo"];
      }
      $content['tipo_documento_cod'] = $tipo_documento_cod;
      $content['nro_documento'] = $nro_documento;
      $content["dis_prov_dep"] = "";
      $nroDoc['error'] = false;
      $nroDoc['msg'] = "datos obtenidos";
      $nroDoc['msgType'] = "success";
    }
    $nroDoc["content"] = $content;
    return $nroDoc ;
  }

}