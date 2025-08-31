<?php
require_once('../../app/models/Proveedores.php');
require_once('../../app/services/Services.php');
require_once('../../app/models/Ubigeos.php');

use Valitron\Validator;

class ProveedoresController
{
  public function filter_proveedores()
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
      "offset" => $p['offset']
    ];

    $where = MyORM::getWhere($p);
    $orderBy = MyORM::getOrder($p["order"]);

    $res = Proveedores::filterProveedores($campos, $where, $orderBy, $pagination);
    return $res;
  }

  public function get_proveedor()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    $proveedor = Proveedores::getProveedor($p['id']);
    return $proveedor;
  }

  public function create_proveedor()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 200);

    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);
    $nro_documento = trim($p['nro_documento']) ? trim($p['nro_documento']) : null;
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
    $this->validateProveedor($params);

    // Buscando duplicados
    if ($p['email']) {
      $count = Proveedores::countRecordsBy(["email" => $p['email']]);
      if ($count) throwMiExcepcion("El email: " . $p['email'] . ", ya existe!", "warning",200);
    }
    $lastId = Proveedores::createProveedor($params);
    if (!$lastId) throwMiExcepcion("Ningún registro guardado", "warning", 200);
    $proveedor = Proveedores::getProveedor($lastId);
    $response['msgType'] = "success";
    $response['msg'] = "Proveedor registrado";
    $response['proveedor'] = $proveedor;

    return $response;
  }

  public function update_proveedor()
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
    $this->validateProveedor($paramCampos);

    // Buscando duplicados
    $exclude = ["id" => $p['id']];
    $count = Proveedores::countRecordsBy(["nro_documento" => $p['nro_documento']], $exclude);
    if ($count) throwMiExcepcion("El nro de documento: " . $p['nro_documento'] . ", ya existe!", "warning", 200);
    if($p['email']){
      $count = Proveedores::countRecordsBy(["email" => $p['email']], $exclude);
      if ($count) throwMiExcepcion("El email: " . $p['email'] . ", ya existe!", "warning", 200);
    }

    $paramWhere = ["id" => $p['id']];

    $resp = Proveedores::updateProveedor("proveedores", $paramCampos, $paramWhere);
    if (!$resp) throwMiExcepcion("No hay cambios para guardar", "warning", 200);
    
    $proveedor = Proveedores::getProveedor($p['id']);

    $response['msgType'] = "success";
    $response['msg'] = "Registro actualizado";
    $response['proveedor'] = $proveedor;
    return $response;
  }

  public function delete_proveedor()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'DELETE') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    // OJO, antes de eliminar verificar si el proveedor tiene una venta asociada
    $params = [ "id" => $p['id'] ];
    $proveedor = Proveedores::getProveedor($p['id']);
    $resp = Proveedores::deleteProveedor($params);
    if (!$resp) throwMiExcepcion("Ningún registro eliminado", "warning");

    $response['msgType'] = "success";
    $response['msg'] = "Registro eliminado";
    $response['id'] = $p['id'];
    $response['proveedor'] = $proveedor;
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
      // Verificar si el proveedor ya esta registrado
      $paramsEqual = ["tipo_documento_cod" => $p['tipo_documento_cod'], "nro_documento" => $nro_documento];
      $prov = Proveedores::getProveedoresBy($paramsEqual);
      if($prov){
        $nroDoc["direccion"] = $nroDoc["direccion"] ? $nroDoc["direccion"] : $prov[0]["direccion"];
        $nroDoc["ubigeo"] = $nroDoc["ubigeo"] ? $nroDoc["ubigeo"] : $prov[0]["ubigeo_inei"];
        $nroDoc["id"] = $prov[0]["id"];
        $nroDoc["email"] = $prov[0]["email"];
        $nroDoc["telefono"] = $prov[0]["telefono"];
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

  private function validateProveedor($params){
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
