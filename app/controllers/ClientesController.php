<?php
require_once('../../app/models/Clientes.php');
require_once('../../app/services/Services.php');

class ClientesController
{
  public function filter_clientes($isPaginated = true)
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);

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

    $search = $p['search'] ? "%" . $p['search'] . "%" : "";

    $paramWhere = [
      "paramLike" => [
        'nro_documento' => $search, 
        'nombre_razon_social' => $search, 
      ],
      "paramEquals" => $p['equals'], // [["field_name" => "id", "field_value"=>1]] 
      "paramBetween" => [
        "campo" => $p['between']['field_name'],
        "rango" => $p['between']['range'] // "2024-12-18 00:00:00, 2024-12-19 23:59:59"
      ]
    ];

    $paramOrders = $p['orders'];

    $pagination = [
      "page" => $_GET["page"] ?? "1",
      "offset" => $p['offset']
    ];

    $res = Clientes::filterClientes($campos, $paramWhere, $paramOrders, $pagination, $isPaginated);
    return $res;
  }

  public function filter_users_full() // sin paginacion
  {
    $res =  self::filter_clientes(false);
    unset($res["next"]);
    unset($res["offset"]);
    unset($res["page"]);
    unset($res["pages"]);
    unset($res["previous"]);
    return $res;
  }

  public function create_cliente()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 200);

    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);
    $params = [
      "tipo_documento_cod" => $p['tipo_documento_cod'],
      "nro_documento" => trim($p['nro_documento']) ? trim($p['nro_documento']) : null,
      "nombre_razon_social" => trimSpaces($p['nombre_razon_social']),
      "direccion" => trimSpaces($p['direccion']),
      "ubigeo_inei" => $p['ubigeo_inei'],
      "email" => $p['email'],
      "telefono" => $p['telefono'],
      "api" => $p['api'],
    ];
    // Validacion
    //$this->validateCreateUser($params);

    // Buscando duplicados
    $count = Clientes::countRecordsBy(["nro_documento" => $p['nro_documento']]);
    if ($count) throwMiExcepcion("El nro de documento: " . $p['nro_documento'] . ", ya existe!", "warning");
    if ($p['email']) {
      $count = Clientes::countRecordsBy(["email" => $p['email']]);
      if ($count) throwMiExcepcion("El email: " . $p['email'] . ", ya existe!", "warning");
    }

    $lastId = Clientes::createCliente($params);
    if (!$lastId) throwMiExcepcion("Ningún registro guardado", "warning");
    Users::setActivityLog("Creación de registro en la tabla clientes con nro doc: " . $params["nro_documento"]);
    $registro = Clientes::getCliente($lastId);
    $response['error'] = false;
    $response['msgType'] = "success";
    $response['msg'] = "Cliente registrado";
    $response['content'] = $registro;
    return $response;
  }

  public function update_cliente()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'PUT') throwMiExcepcion("Método no permitido", "error", 405);

    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 200);

    $paramCampos = [
      "tipo_documento_cod" => $p['tipo_documento_cod'],
      "nro_documento" => trim($p['nro_documento']) ? trim($p['nro_documento']) : null,
      "nombre_razon_social" => trimSpaces($p['nombre_razon_social']),
      "direccion" => trimSpaces($p['direccion']),
      "ubigeo_inei" => $p['ubigeo_inei'],
      "email" => $p['email'],
      "telefono" => $p['telefono'],
    ];

    // Validacion
    // $this->validateUpdateUser($paramCampos);

    // Buscando duplicados
    $exclude = ["id" => $p['id']];
    $count = Clientes::countRecordsBy(["nro_documento" => $p['nro_documento']], $exclude);
    if ($count) throwMiExcepcion("El nro de documento: " . $p['nro_documento'] . ", ya existe!", "warning");
    if($p['email']){
      $count = Clientes::countRecordsBy(["email" => $p['email']], $exclude);
      if ($count) throwMiExcepcion("El email: " . $p['email'] . ", ya existe!", "warning");
    }

    $paramWhere = ["id" => $p['id']];

    $resp = Clientes::updateCliente("clientes", $paramCampos, $paramWhere);
    if (!$resp) throwMiExcepcion("Ningún registro modificado", "warning", 200);
    
    $registro = Clientes::getCliente($p['id']);
    Users::setActivityLog("Modificación de registro en la tabla clientes con nro doc: " . $registro["nro_documento"]);

    $response['msgType'] = "success";
    $response['msg'] = "Registro actualizado";
    $response['content'] = $registro;
    return $response;
  }

  public function delete_cliente()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'DELETE') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    // OJO, antes de eliminar verificar si el cliente tiene una venta asociada
    $params = [ "id" => $p['id'] ];
    $resp = Clientes::deleteCliente($params);
    if (!$resp) throwMiExcepcion("Ningún registro eliminado", "warning");

    $response['msgType'] = "success";
    $response['error'] = false;
    $response['msg'] = "Registro eliminado";
    return $response;
  }

  public function get_cliente()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    $registro = Clientes::getCliente($p['id']);
    $res['content'] = $registro;
    return $res;
  }

  public function consultar_nro_documento()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);
    $api = $p["api"];
    // Verificar si el nro de documento ya esta registrado
    $tipo_documento_cod = $p["tipo_documento_cod"];
    $nro_documento = trim($p["nro_documento"]);
    $data = Services::consultarNroDoc($nro_documento, $tipo_documento_cod);
    $res['content'] = $data;
    return $res;
  }
}
