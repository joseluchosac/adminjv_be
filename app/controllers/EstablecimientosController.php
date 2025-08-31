<?php
require_once('../../app/models/Establecimientos.php');
require_once('../../app/models/Numeraciones.php');
use Valitron\Validator;

class EstablecimientosController
{

  public function get_establecimientos()
  {
    $campos = [
      "id",
      "tipo",
      "codigo",
      "descripcion",
      "direccion",
      "ubigeo_inei",
      "dis_prov_dep",
      "telefono",
      "email",
      "campo_stock",
      "estado"
    ];
    $establecimientos = Establecimientos::getEstablecimientos($campos);
    $res['content'] = $establecimientos;
    unset($establecimientos);
    return $res;
  }

  public function get_establecimiento()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    $establecimiento = Establecimientos::getEstablecimiento($p['id']);
    $res['content'] = $establecimiento;
    unset($establecimiento);
    return $res;
  }

  public function create_establecimiento()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 200);

    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);
    $codigo = trimSpaces($p['codigo']);
    $descripcion = trimSpaces($p['descripcion']);
    $paramCampos = [
      "codigo" => $codigo ? $codigo : null,
      "tipo" => $p['tipo'],
      "descripcion" => $descripcion,
      "direccion" => trimSpaces($p['direccion']),
      "ubigeo_inei" => $p['ubigeo_inei'],
      "dis_prov_dep" => $p['dis_prov_dep'],
      "telefono" => trimSpaces($p['telefono']),
      "email" => trimSpaces($p['email']),
      "campo_stock" => $p['campo_stock'],
    ];
    // Validacion
    $this->validateEstablecimiento($paramCampos);

    // Buscando duplicados
    $count = Establecimientos::countRecordsBy(["descripcion" => $descripcion]);
    if ($count) throwMiExcepcion("El establecimiento: " . $descripcion . ", ya existe!", "warning");
    
    if($codigo){
      $count = Establecimientos::countRecordsBy(["codigo" => $codigo]);
      if ($count) throwMiExcepcion("El código: " . $codigo . ", ya existe!", "warning");
    }

    $lastId = Establecimientos::createEstablecimiento($paramCampos);
    if (!$lastId) throwMiExcepcion("Ningún registro guardado", "warning");
    // Users::setActivityLog("Creación de nuevo laboratorio: " . $params["nombre"]);
    $registro = Establecimientos::getEstablecimiento($lastId);
    $response['error'] = false;
    $response['msgType'] = "success";
    $response['msg'] = "Sucursal registrada";
    $response['content'] = $registro;
    return $response;
  }

  public function update_establecimiento()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'PUT') throwMiExcepcion("Método no permitido", "error", 405);

    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 200);
    $codigo = trimSpaces($p['codigo']);
    $descripcion = trimSpaces($p['descripcion']);
    $paramCampos = [
      "codigo" => $codigo ? $codigo : null,
      "tipo" => $p['tipo'],
      "descripcion" => $descripcion,
      "direccion" => trimSpaces($p['direccion']),
      "ubigeo_inei" => $p['ubigeo_inei'],
      "dis_prov_dep" => $p['dis_prov_dep'],
      "telefono" => trimSpaces($p['telefono']),
      "email" => trimSpaces($p['email']),
      "estado" => trimSpaces($p['estado']),
      "campo_stock" => $p['campo_stock'],
    ];

    $this->validateEstablecimiento($paramCampos);
    // Buscando duplicados
    $exclude = ["id" => $p['id']];
    // Buscando si es establecimiento principal
    $countPrincipal = Establecimientos::countEstablecimientos([
      ["field_name" => "codigo", "field_value"=>'0000'],
      ["field_name" => "id", "field_value"=>$p['id']]
    ]);

    if($countPrincipal && $codigo != "0000"){
      throwMiExcepcion("No se puede cambiar el código a la sucursl principal", "warning");
    }

    $count = Establecimientos::countRecordsBy(["descripcion" => $descripcion], $exclude);
    if ($count) throwMiExcepcion("El Establecimiento: " . $descripcion . ", ya existe!", "warning");

    if($codigo){
      $count = Establecimientos::countRecordsBy(["codigo" => $codigo], $exclude);
      if ($count) throwMiExcepcion("El código: " . $codigo . ", ya existe!", "warning");
    }
    

    $paramWhere = ["id" => $p['id']];

    $update = Establecimientos::updateEstablecimiento($paramCampos, $paramWhere);
    if (!$update) throwMiExcepcion("Ningún registro modificado", "warning", 200);

    $establecimiento = Establecimientos::getEstablecimiento($p['id']);

    $res['msgType'] = "success";
    $res['msg'] = "Registro actualizado";
    $res['content'] = $establecimiento;
    return $res;
  }

  public function delete_establecimiento()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'DELETE') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    $params = [
      "id" => $p['id'],
    ];
    // Buscando duplicados si es establecimiento principal
    $countPrincipal = Establecimientos::countEstablecimientos([
      ["field_name" => "codigo", "field_value"=>'0000'],
      ["field_name" => "id", "field_value"=>$p['id']]
    ] );
    if($countPrincipal)throwMiExcepcion("No se puede eliminar al establecimiento principal", "warning");
    
    // Buscando si tiene numeraciones asociadas
    $countNumeraciones = Numeraciones::countNumeraciones([
      ["field_name" => "establecimiento_id", "field_value"=>$params['id']],
    ]);
    if($countNumeraciones)throwMiExcepcion("Debe eliminar las numeraciones asociadas al establecimiento", "warning");

    $resp = Establecimientos::deleteEstablecimiento($params);
    if (!$resp) throwMiExcepcion("Ningún registro eliminado", "warning");
    $response['content'] = intval($params['id']);
    $response['error'] = "false";
    $response['msgType'] = "success";
    $response['msg'] = "Registro eliminado";
    return $response;
  }

  private function validateEstablecimiento($params){
    $v = new Validator($params);
    $v->addRule('sinEspacios', function ($field, $value, array $params, array $fields) {
      return strpos($value, ' ') === false; // Verificar que no haya espacios en el valor
    });
    $v->rule('required', 'tipo')->message('Ingrese el tipo');
    $v->rule('required', 'codigo')->message('Ingrese el código');
    $v->rule('sinEspacios', 'codigo')->message('El código no debe tener espacios');
    $v->rule('required', 'descripcion')->message('Ingrese la descripción');
    $v->rule('required', 'direccion')->message('Ingrese la dirección');
    $v->rule('required', 'ubigeo_inei')->message('Ingrese el ubigeo');
    if($params['email']){
      $v->rule('email', 'email')->message('Ingrese un formato de email válido');
    }
    if (!$v->validate()) {
      foreach ($v->errors() as $campo => $errores) {
        foreach ($errores as $error) {
          throwMiExcepcion($error, "warning", 200);
        }
      }
    }
  }

}
