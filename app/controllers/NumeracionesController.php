<?php
require_once('../../app/models/Numeraciones.php');
use Valitron\Validator;

class NumeracionesController
{

  public function get_numeraciones_establecimiento(){
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    $numeracionesEstablecimiento = Numeraciones::getNumeracionesEstablecimiento($pJson['establecimiento_id']);
    $res['content'] = $numeracionesEstablecimiento;
    
    return $res;
  }

  public function get_numeracion(){
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    $numeracionEstablecimiento = Numeraciones::getNumeracion($pJson['id']);
    $res['content'] = $numeracionEstablecimiento;

    return $res;
  }

  public function create_numeracion()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 200);

    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);
    $descripcion = trimSpaces($pJson['descripcion']);
    $paramCampos = [
      "tipo_comprobante_cod" => $pJson['tipo_comprobante_cod'],
      "descripcion" => $descripcion,
      "establecimiento_id" => $pJson['establecimiento_id'],
      "serie" => trimSpaces($pJson['serie']),
      "correlativo" => $pJson['correlativo'],
      "modifica_a" => $pJson['modifica_a'],
    ];
    // Validacion
    // $this->validateSucursal($paramCampos);

    // Buscando duplicados de numeracion
    $countNumeraciones = Numeraciones::countNumeraciones([
      ["field_name" => "serie", "field_value"=>$paramCampos['serie']],
    ]);
    if($countNumeraciones){
      throwMiExcepcion("La numeracion " . $paramCampos['serie'] . " ya fue registrada, ingrese otra", "warning");
    }
    // Buscando duplicados de establecimiento y descripcion
    $countDescripcion = Numeraciones::countNumeraciones([
      ["field_name" => "establecimiento_id", "field_value"=>$paramCampos['establecimiento_id']],
      ["field_name" => "descripcion", "field_value"=>$descripcion],
    ]);
    if($countDescripcion){
      throwMiExcepcion("Ingrese otra descripcion", "warning");
    }

    $lastId = Numeraciones::createNumeracion($paramCampos);
    if (!$lastId) throwMiExcepcion("Ningún registro guardado", "warning");
    // Users::setActivityLog("Creación de nuevo laboratorio: " . $params["nombre"]);
    $registro = Numeraciones::getNumeracion($lastId);
    $response['error'] = false;
    $response['msgType'] = "success";
    $response['msg'] = "Registro creado";
    $response['content'] = $registro;
    return $response;
  }

  public function update_numeracion()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'PUT') throwMiExcepcion("Método no permitido", "error", 405);

    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 200);
    $descripcion = trimSpaces($pJson['descripcion']);
    $paramCampos = [
      "tipo_comprobante_cod" => $pJson['tipo_comprobante_cod'],
      "descripcion" => $descripcion,
      "establecimiento_id" => $pJson['establecimiento_id'],
      "serie" => trimSpaces($pJson['serie']),
      "correlativo" => $pJson['correlativo'],
      "modifica_a" => $pJson['modifica_a'],
    ];

    // $this->validateSucursal($paramCampos);
    // Buscando duplicados de numeracion
    $exclude = ["id" => $pJson['id']];
    $countNumeraciones = Numeraciones::countNumeraciones([
      ["field_name" => "serie", "field_value"=>$paramCampos['serie']],
    ],$exclude);
    if($countNumeraciones){
      throwMiExcepcion("La numeracion " . $paramCampos['serie'] . " ya fue registrada, ingrese otra", "warning");
    }
    // Buscando duplicados de establecimiento y descripcion
    $countDescripcion = Numeraciones::countNumeraciones([
      ["field_name" => "establecimiento_id", "field_value"=>$paramCampos['establecimiento_id']],
      ["field_name" => "descripcion", "field_value"=>$descripcion],
    ], $exclude);
    if($countDescripcion){
      throwMiExcepcion("Ingrese otra descripcion", "warning");
    }
    // $count = Establecimientos::countRecordsBy(["descripcion" => $descripcion], $exclude);
    // if ($count) throwMiExcepcion("El Establecimiento: " . $descripcion . ", ya existe!", "warning");

    // if($codigo){
    //   $count = Establecimientos::countRecordsBy(["codigo" => $codigo], $exclude);
    //   if ($count) throwMiExcepcion("El código: " . $codigo . ", ya existe!", "warning");
    // }
    

    $paramWhere = ["id" => $pJson['id']];

    $update = Numeraciones::updateNumeracion($paramCampos, $paramWhere);
    if (!$update) throwMiExcepcion("Ningún registro modificado", "warning", 200);

    $registro = Numeraciones::getNumeracion($pJson['id']);

    $res['msgType'] = "success";
    $res['msg'] = "Registro actualizado";
    $res['content'] = $registro;
    return $res;
  }

  public function delete_numeracion()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'DELETE') throwMiExcepcion("Método no permitido", "error", 405);
    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    $params = [
      "id" => $pJson['id'],
    ];

    // $countPrincipal = Establecimientos::countEstablecimientos([
    //   ["field_name" => "codigo", "field_value"=>'0000'],
    //   ["field_name" => "id", "field_value"=>$pJson['id']]
    // ] );
    // if($countPrincipal)throwMiExcepcion("No se puede eliminar al establecimiento principal", "warning");

    $resp = Numeraciones::deleteNumeracion($params);
    if (!$resp) throwMiExcepcion("Ningún registro eliminado", "warning");

    $response['content'] = $params['id'];
    $response['error'] = "false";
    $response['msgType'] = "success";
    $response['msg'] = "Registro eliminado";
    return $response;
  }
  // EVALUAR
  // public function get_establecimiento()
  // {
  //   if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
  //   $pJson = json_decode(file_get_contents('php://input'), true);
  //   if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);

  //   $establecimiento = Establecimientos::getEstablecimiento($pJson['id']);
  //   $res['content'] = $establecimiento;
  //   unset($establecimiento);
  //   return $res;
  // }









  // private function validateSucursal($params){
  //   $v = new Validator($params);
  //   $v->addRule('sinEspacios', function ($field, $value, array $params, array $fields) {
  //     return strpos($value, ' ') === false; // Verificar que no haya espacios en el valor
  //   });
  //   $v->rule('required', 'codigo')->message('Ingrese el código');
  //   $v->rule('sinEspacios', 'codigo')->message('El código no debe tener espacios');
  //   $v->rule('required', 'descripcion')->message('Ingrese la descripción');
  //   $v->rule('required', 'direccion')->message('Ingrese la dirección');
  //   $v->rule('required', 'ubigeo_inei')->message('Ingrese el ubigeo');
  //   if($params['email']){
  //     $v->rule('email', 'email')->message('Ingrese un formato de email válido');
  //   }
  //   if (!$v->validate()) {
  //     foreach ($v->errors() as $campo => $errores) {
  //       foreach ($errores as $error) {
  //         throwMiExcepcion($error, "warning", 200);
  //       }
  //     }
  //   }
  // }
}
