<?php
require_once('../../app/models/Clientes.php');
require_once('../../app/services/Services.php');

use Firebase\JWT\JWT;
use Valitron\Validator;

class ClientesController
{
  public function filter_clientes($isPaginated = true)
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
      "paramEquals" => $pJson['equals'], // [["campo_name" => "id", "value"=>1]] 
      "paramBetween" => [
        "campo" => $pJson['between']['campo_name'],
        "rango" => $pJson['between']['range'] // "2024-12-18 00:00:00, 2024-12-19 23:59:59"
      ]
    ];

    $paramOrders = $pJson['orders'];

    $pagination = [
      "page" => $_GET["page"] ?? "1",
      "offset" => $pJson['offset']
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
    $count = Clientes::countRecordsBy(["nro_documento" => $pJson['nro_documento']]);
    if ($count) throwMiExcepcion("El nro de documento: " . $pJson['nro_documento'] . ", ya existe!", "warning");
    if ($pJson['email']) {
      $count = Clientes::countRecordsBy(["email" => $pJson['email']]);
      if ($count) throwMiExcepcion("El email: " . $pJson['email'] . ", ya existe!", "warning");
    }

    $lastId = Clientes::createCliente($params);
    if (!$lastId) throwMiExcepcion("Ningún registro guardado", "warning");
    Users::setActivityLog("Creación de registro en la tabla clientes con nro doc: " . $params["nro_documento"]);
    $registro = Clientes::getCliente($lastId);
    $response['error'] = false;
    $response['msgType'] = "success";
    $response['msg'] = "Cliente registrado";
    $response['registro'] = $registro;
    return $response;
  }

  public function update_cliente()
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
    $count = Clientes::countRecordsBy(["nro_documento" => $pJson['nro_documento']], $exclude);
    if ($count) throwMiExcepcion("El nro de documento: " . $pJson['nro_documento'] . ", ya existe!", "warning");
    if($pJson['email']){
      $count = Clientes::countRecordsBy(["email" => $pJson['email']], $exclude);
      if ($count) throwMiExcepcion("El email: " . $pJson['email'] . ", ya existe!", "warning");
    }

    $paramWhere = ["id" => $pJson['id']];

    $resp = Clientes::updateCliente("clientes", $paramCampos, $paramWhere);
    if (!$resp) throwMiExcepcion("Ningún registro modificado", "warning", 200);
    
    $registro = Clientes::getCliente($pJson['id']);
    Users::setActivityLog("Modificación de registro en la tabla clientes con nro doc: " . $registro["nro_documento"]);

    $response['msgType'] = "success";
    $response['msg'] = "Registro actualizado";
    $response['registro'] = $registro;
    return $response;
  }

  public function delete_cliente()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'DELETE') throwMiExcepcion("Método no permitido", "error", 405);
    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    // OJO, antes de eliminar verificar si el cliente tiene una venta asociada
    $params = [ "id" => $pJson['id'] ];
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
    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    $registro = Clientes::getCliente($pJson['id']);
    return $registro;
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
    return $data;
  }

  // private function validateCreateUser($params){
  //   $v = new Validator($params);
  //   $v->addRule('iguales', function ($field, $value, array $params, array $fields) {
  //     return $fields['password'] === $fields["password_repeat"];
  //   });
  //   $v->addRule('sinEspacios', function ($field, $value, array $params, array $fields) {
  //     return strpos($value, ' ') === false; // Verificar que no haya espacios en el valor
  //   });
  //   $v->rule('required', 'nombres')->message('El nombre es requerido');
  //   $v->rule('lengthMin', 'nombres', 3)->message('El nombre debe tener al menos 3 caracteres.');
  //   $v->rule('lengthMax', 'nombres', 50)->message('El nombre no puede exceder los 50 caracteres.');
  //   $v->rule('required', 'apellidos')->message('Los apellidos son requeridos');
  //   $v->rule('lengthMin', 'apellidos', 3)->message('Los apellidos deben tener al menos 3 caracteres.');
  //   $v->rule('lengthMax', 'apellidos', 50)->message('Los apellidos no puede exceder los 50 caracteres.');
  //   $v->rule('required', 'username')->message('El usuario es requerido');
  //   $v->rule('lengthMin', 'username', 3)->message('El usuario debe tener al menos 3 caracteres.');
  //   $v->rule('lengthMax', 'username', 50)->message('El usuario no puede exceder los 50 caracteres.');
  //   $v->rule('sinEspacios', 'username')->message('El usuario no puede tener espacios');
  //   $v->rule('email', 'email')->message('Ingrese un formato de email válido');
  //   $v->rule('required', 'password')->message('La contraseña es obligatoria');
  //   $v->rule('regex', 'password', '/^[A-Za-z\d@$!%*?&]{6,}$/')->message('La contraseña debe tener al menos 6 caracteres, sin espacios');
  //   $v->rule('iguales', 'password')->message('Los passwords no son iguales');;
  //   if (!$v->validate()) {
  //     foreach ($v->errors() as $campo => $errores) {
  //       foreach ($errores as $error) {
  //         throwMiExcepcion($error, "warning", 200);
  //       }
  //     }
  //   }
  // }

  // private function validateUpdateUser($params){
  //   $v = new Validator($params);
  //   $v->rule('required', 'nombres')->message('El nombre es requerido');
  //   $v->rule('lengthMin', 'nombres', 3)->message('El nombre debe tener al menos 3 caracteres.');
  //   $v->rule('lengthMax', 'nombres', 50)->message('El nombre no puede exceder los 50 caracteres.');
  //   $v->rule('required', 'apellidos')->message('Los apellidos son requeridos');
  //   $v->rule('lengthMin', 'apellidos', 3)->message('Los apellidos deben tener al menos 3 caracteres.');
  //   $v->rule('lengthMax', 'apellidos', 50)->message('Los apellidos no puede exceder los 50 caracteres.');
  //   if (!$v->validate()) {
  //     foreach ($v->errors() as $campo => $errores) {
  //       foreach ($errores as $error) {
  //         throwMiExcepcion($error, "warning", 200);
  //       }
  //     }
  //   }
  // }
}
