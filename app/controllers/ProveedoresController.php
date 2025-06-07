<?php
require_once('../../app/models/Proveedores.php');
require_once('../../app/services/Services.php');

use Firebase\JWT\JWT;
use Valitron\Validator;

class ProveedoresController
{
  public function filter_proveedores($isPaginated = true)
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $pJson = json_decode(file_get_contents('php://input'), true);

    $campos = [
      'id',
      'tipo_documento',
      'nro_documento',
      'nombre_razon_social',
      'direccion',
      'departamento',
      'provincia',
      'distrito',
      'email',
      'telefono',
      'estado',
    ];

    $search = $pJson['search'] ? "%" . $pJson['search'] . "%" : "";

    $paramWhere = [
      "paramLike" => [
        'nro_documento' => $search, 
        'nombre_razon_social' => $search, 
      ],
      "paramEquals" => $pJson['equals'], // [["field_name" => "id", "field_value"=>1]] 
      "paramBetween" => [
        "campo" => $pJson['between']['field_name'],
        "rango" => $pJson['between']['range'] // "2024-12-18 00:00:00, 2024-12-19 23:59:59"
      ]
    ];

    $paramOrders = $pJson['orders'];

    $pagination = [
      "page" => $_GET["page"] ?? "1",
      "offset" => $pJson['offset']
    ];

    $res = Proveedores::filterProveedores($campos, $paramWhere, $paramOrders, $pagination, $isPaginated);
    return $res;
  }

  public function filter_users_full() // sin paginacion
  {
    $res =  self::filter_proveedores(false);
    unset($res["next"]);
    unset($res["offset"]);
    unset($res["page"]);
    unset($res["pages"]);
    unset($res["previous"]);
    return $res;
  }

  public function create_proveedor()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 200);

    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);
    $params = [
      "tipo_documento_cod" => $pJson['tipo_documento_cod'],
      "nro_documento" => trim($pJson['nro_documento']) ? trim($pJson['nro_documento']) : null,
      "nombre_razon_social" => trimSpaces($pJson['nombre_razon_social']),
      "direccion" => trimSpaces($pJson['direccion']),
      "ubigeo_inei" => $pJson['ubigeo_inei'],
      "email" => $pJson['email'],
      "telefono" => $pJson['telefono'],
      "api" => $pJson['api'],
    ];
    // Validacion
    //$this->validateCreateUser($params);

    // Buscando duplicados
    $count = Proveedores::countRecordsBy(["nro_documento" => $pJson['nro_documento']]);
    if ($count) throwMiExcepcion("El nro de documento: " . $pJson['nro_documento'] . ", ya existe!", "warning");
    if ($pJson['email']) {
      $count = Proveedores::countRecordsBy(["email" => $pJson['email']]);
      if ($count) throwMiExcepcion("El email: " . $pJson['email'] . ", ya existe!", "warning");
    }

    $lastId = Proveedores::createProveedor($params);
    if (!$lastId) throwMiExcepcion("Ningún registro guardado", "warning");
    Users::setActivityLog("Creación de registro en la tabla proveedores con nro doc: " . $params["nro_documento"]);
    $registro = Proveedores::getProveedor($lastId);
    $response['error'] = false;
    $response['msgType'] = "success";
    $response['msg'] = "Proveedor registrado";
    $response['content'] = $registro;
    return $response;
  }

  public function update_proveedor()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'PUT') throwMiExcepcion("Método no permitido", "error", 405);

    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 200);

    $paramCampos = [
      "tipo_documento_cod" => $pJson['tipo_documento_cod'],
      "nro_documento" => trim($pJson['nro_documento']) ? trim($pJson['nro_documento']) : null,
      "nombre_razon_social" => trimSpaces($pJson['nombre_razon_social']),
      "direccion" => trimSpaces($pJson['direccion']),
      "ubigeo_inei" => $pJson['ubigeo_inei'],
      "email" => $pJson['email'],
      "telefono" => $pJson['telefono'],
    ];

    // Validacion
    // $this->validateUpdateUser($paramCampos);

    // Buscando duplicados
    $exclude = ["id" => $pJson['id']];
    $count = Proveedores::countRecordsBy(["nro_documento" => $pJson['nro_documento']], $exclude);
    if ($count) throwMiExcepcion("El nro de documento: " . $pJson['nro_documento'] . ", ya existe!", "warning");
    if($pJson['email']){
      $count = Proveedores::countRecordsBy(["email" => $pJson['email']], $exclude);
      if ($count) throwMiExcepcion("El email: " . $pJson['email'] . ", ya existe!", "warning");
    }

    $paramWhere = ["id" => $pJson['id']];

    $resp = Proveedores::updateProveedor("proveedores", $paramCampos, $paramWhere);
    if (!$resp) throwMiExcepcion("Ningún registro modificado", "warning", 200);
    
    $registro = Proveedores::getProveedor($pJson['id']);
    Users::setActivityLog("Modificación de registro en la tabla proveedores con nro doc: " . $registro["nro_documento"]);

    $response['msgType'] = "success";
    $response['msg'] = "Registro actualizado";
    $response['content'] = $registro;
    return $response;
  }

  public function delete_proveedor()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'DELETE') throwMiExcepcion("Método no permitido", "error", 405);
    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    // OJO, antes de eliminar verificar si el proveedor tiene una venta asociada
    $params = [ "id" => $pJson['id'] ];
    $resp = Proveedores::deleteProveedor($params);
    if (!$resp) throwMiExcepcion("Ningún registro eliminado", "warning");

    $response['msgType'] = "success";
    $response['error'] = false;
    $response['msg'] = "Registro eliminado";
    return $response;
  }

  public function get_proveedor()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    $registro = Proveedores::getProveedor($pJson['id']);
    $res['content'] = $registro;
    return $res;
  }

  public function consultar_nro_documento()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);
    $api = $pJson["api"];
    // Verificar si el nro de documento ya esta registrado
    $tipo_documento_cod = $pJson["tipo_documento_cod"];
    $nro_documento = trim($pJson["nro_documento"]);
    $data = Services::consultarNroDoc($nro_documento, $tipo_documento_cod);
    $res['content'] = $data;
    return $res;
  }

}
