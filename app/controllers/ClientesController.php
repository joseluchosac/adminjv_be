<?php
require_once('../../app/models/Clientes.php');
require_once('../../app/services/Services.php');
require_once('../../app/models/Ubigeos.php');

use Valitron\Validator;

class ClientesController
{
  public function filter_clientes()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);
    $campos = [
      'id',
      'tipo_documento',
      'nro_documento',
      'nombre_razon_social',
      'direccion',
      'dis_prov_dep',
      'email',
      'telefono',
      'estado',
    ];

    $p["search"] = [
      "fieldsName" => ["nro_documento", "nombre_razon_social", "email", "telefono"],
      "like" => trim($p["search"])
    ];

    $pagination = [
      "page" => $_GET["page"] ?? "1",
      "per_page" => $p['per_page']
    ];

    $where = MyORM::getWhere($p);
    $orderBy = MyORM::getOrder($p["order"]);

    $res = Clientes::filterClientes($campos, $where, $orderBy, $pagination);
    return $res;
  }

  public function get_cliente()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    $cliente = Clientes::getCliente($p['id']);
    return $cliente;
  }

  public function create_cliente()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 200);

    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);
    $nro_documento = trim($p['nro_documento']) ? trim($p['nro_documento']) : null;
    echo "<pre>";
    print_r($p);
    echo "</pre>";
    exit();
    $params = [
      "tipo_documento_cod" => $p['tipo_documento_cod'],
      "nro_documento" => $nro_documento,
      "nombre_razon_social" => trimSpaces($p['nombre_razon_social']),
      "direccion" => trimSpaces($p['direccion']),
      "ubigeo_inei" => $p['ubigeo_inei'],
      "dis_prov_dep" => $p['dis_prov_dep'],
      "email" => $p['email'],
      "telefono" => $p['telefono'],
      "api" => $p['api'],
    ];

    // Validacion
    $this->validateCliente($params);

    // Buscando duplicados
    if ($p['email']) {
      $count = Clientes::countRecordsBy(["email" => $p['email']]);
      if ($count) throwMiExcepcion("El email: " . $p['email'] . ", ya existe!", "warning", 200);
    }
    $lastId = Clientes::createCliente($params);
    if (!$lastId) throwMiExcepcion("Ningún registro guardado", "warning");
    Users::setActivityLog("Creación de registro en la tabla clientes con nro doc: " . $params["nro_documento"]);
    $cliente = Clientes::getCliente($lastId);
    $response['msgType'] = "success";
    $response['msg'] = "Cliente registrado";
    $response['cliente'] = $cliente;

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
      "dis_prov_dep" => $p['dis_prov_dep'],
      "ubigeo_inei" => $p['ubigeo_inei'],
      "email" => $p['email'],
      "telefono" => $p['telefono'],
    ];

    // Validacion
    $this->validateCliente($paramCampos);

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
    if (!$resp) throwMiExcepcion("No hay cambios para guardar", "warning", 200);
    
    $cliente = Clientes::getCliente($p['id']);

    $response['msgType'] = "success";
    $response['msg'] = "Registro actualizado";
    $response['cliente'] = $cliente;
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
    $cliente = Clientes::getCliente($p['id']);

    $response['msgType'] = "success";
    $response['error'] = false;
    $response['msg'] = "Registro eliminado";
    $response['cliente'] = $cliente;
    return $response;
  }



  public function query_document()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);
    $tipo_documento_cod = $p["tipo_documento_cod"];
    $nro_documento = trim($p["nro_documento"]);
    $nroDoc = Services::consultarNroDoc($nro_documento, $tipo_documento_cod);

    if(isset($nroDoc["nro_documento"])){
      // Verificar si el cliente ya esta registrado
      $paramsEqual = ["tipo_documento_cod" => $p['tipo_documento_cod'], "nro_documento" => $nro_documento];
      $cli = Clientes::getClientesBy($paramsEqual);
      if($cli){
        $nroDoc["direccion"] = $nroDoc["direccion"] ? $nroDoc["direccion"] : $cli[0]["direccion"];
        $nroDoc["ubigeo"] = $nroDoc["ubigeo"] ? $nroDoc["ubigeo"] : $cli[0]["ubigeo_inei"];
        $nroDoc["id"] = $cli[0]["id"];
        $nroDoc["email"] = $cli[0]["email"];
        $nroDoc["telefono"] = $cli[0]["telefono"];
      }else{
        $nroDoc["id"] = 0;
        $nroDoc["email"] = "";
        $nroDoc["telefono"] = "";
      }
      // Buscar en ubigeo el campo dis_prov_dep
      if($nroDoc["ubigeo"]){
        $ubig = Ubigeos::getUbigeoByUbigeoInei($nroDoc["ubigeo"]);
        $nroDoc["dis_prov_dep"] = $ubig['dis_prov_dep'];
      }
    }
    return $nroDoc;
  }

  private function validateCliente($params){
    $v = new Validator($params);
    $v->addRule('length_dni', function ($field, $value, array $params, array $fields) {
      return ($fields['tipo_documento_cod'] === "1" &&  mb_strlen($value) != 8) ? false : true;
    });
    $v->addRule('length_ruc', function ($field, $value, array $params, array $fields) {
      return ($fields['tipo_documento_cod'] === "6" &&  mb_strlen($value) != 11) ? false : true;
    });
    $v->rule('required', 'tipo_documento_cod')->message('Ingrese un tipo de documento');
    $v->rule('notIn', 'tipo_documento_cod', ["0"])->message('Ingrese un tipo de documento válido'); // si el destino trae el valor de 0
    $v->rule('required', 'nro_documento')->message('Ingrese el Nro de documento');
    $v->rule('length_dni', 'nro_documento')->message('El DNI debe tener 8 dígitos');
    $v->rule('length_ruc', 'nro_documento')->message('El RUC debe tener 11 dígitos');
    $v->rule('required', 'nombre_razon_social')->message('Ingrese Nombre o Razón Social');
    $v->rule('lengthMin', 'nombre_razon_social', 3)->message('El Nombre o Razón Social debe tener mínimo 3 caracteres');
    $v->rule('lengthMax', 'nombre_razon_social', 150)->message('El Nombre o Razón Social debe tener máximo 150 caracteres');
    $v->rule(function($field, $value, $params, $fields) {
      return ($value === "6" &&  $fields['direccion'] == "") ? false : true;
    }, "tipo_documento_cod")->message("Ingrese la dirección");
    $v->rule(function($field, $value, $params, $fields) {
      return ($value === "6" &&  $fields['ubigeo_inei'] == "") ? false : true;
    }, "tipo_documento_cod")->message("Ingrese el ubigeo");
    $v->rule('email', 'email')->message('Ingrese un formato de email válido');
    if (!$v->validate()) {
      foreach ($v->errors() as $campo => $errores) {
        foreach ($errores as $error) {
          throwMiExcepcion($error, "warning", 200);
        }
      }
    }
  }
}
