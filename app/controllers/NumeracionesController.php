<?php
require_once('../../app/models/Numeraciones.php');
use Valitron\Validator;

class NumeracionesController
{

  public function get_numeraciones(){
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $numeraciones = Numeraciones::getNumeraciones();
    $res['content'] = $numeraciones;
    
    return $res;
  }

  public function get_numeracion(){
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    $numeracion = Numeraciones::getNumeracion($p['id']);
    $numeracion['serie_suf'] = substr($numeracion['serie'], strlen($numeracion['serie_pre']));
    $res['content'] = $numeracion;

    return $res;
  }

  public function create_numeracion()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 200);

    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);
    $paramCampos = [
      "establecimiento_id" => $p['establecimiento_id'],
      "descripcion_doc" => $p['descripcion_doc'],
      "serie_pre" => trimSpaces($p['serie_pre']),
      "serie" => trimSpaces($p['serie']),
      "correlativo" => $p['correlativo'],
    ];
    // Validacion
    // $this->validateSucursal($paramCampos);

    // Buscando duplicados de numeracion
    $countNumeraciones = Numeraciones::countNumeraciones([
      ["field_name" => "serie", "field_value"=>$paramCampos['serie']],
    ]);
    if($countNumeraciones){
      throwMiExcepcion("La serie " . $paramCampos['serie'] . " ya fue registrada, ingrese otra", "warning");
    }
    // Buscando duplicados de establecimiento y descripcion
    $countDescripcion = Numeraciones::countNumeraciones([
      ["field_name" => "establecimiento_id", "field_value"=>$paramCampos['establecimiento_id']],
      ["field_name" => "serie_pre", "field_value"=>$paramCampos['serie_pre']],
    ]);
    if($countDescripcion){
      throwMiExcepcion("Ingrese otro tipo de comprobante", "warning");
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

    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 200);
    $paramCampos = [
      "establecimiento_id" => $p['establecimiento_id'],
      "descripcion_doc" => $p['descripcion_doc'],
      "serie_pre" => trimSpaces($p['serie_pre']),
      "serie" => trimSpaces($p['serie']),
      "correlativo" => $p['correlativo'],
    ];

    // $this->validateSucursal($paramCampos);
    // Buscando duplicados de numeracion
    $exclude = ["id" => $p['id']];
    $countNumeraciones = Numeraciones::countNumeraciones([
      ["field_name" => "serie", "field_value"=>$paramCampos['serie']],
    ],$exclude);
    if($countNumeraciones){
      throwMiExcepcion("La numeracion " . $paramCampos['serie'] . " ya fue registrada, ingrese otra", "warning");
    }
    // Buscando duplicados de establecimiento y descripcion
    $countDescripcion = Numeraciones::countNumeraciones([
      ["field_name" => "establecimiento_id", "field_value"=>$paramCampos['establecimiento_id']],
      ["field_name" => "serie_pre", "field_value"=>$paramCampos['serie_pre']],
    ], $exclude);
    if($countDescripcion){
      throwMiExcepcion("Ingrese otro tipo de comprobante", "warning");
    }

    $paramWhere = ["id" => $p['id']];

    $update = Numeraciones::updateNumeracion($paramCampos, $paramWhere);
    if (!$update) throwMiExcepcion("Ningún registro modificado", "warning", 200);

    $registro = Numeraciones::getNumeracion($p['id']);

    $res['msgType'] = "success";
    $res['msg'] = "Registro actualizado";
    $res['content'] = $registro;
    return $res;
  }

  public function delete_numeracion()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'DELETE') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    $params = [
      "id" => $p['id'],
    ];

    // $countPrincipal = Establecimientos::countEstablecimientos([
    //   ["field_name" => "codigo", "field_value"=>'0000'],
    //   ["field_name" => "id", "field_value"=>$p['id']]
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
  //   $p = json_decode(file_get_contents('php://input'), true);
  //   if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);

  //   $establecimiento = Establecimientos::getEstablecimiento($p['id']);
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
