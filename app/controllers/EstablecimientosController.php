<?php
require_once('../../app/models/Establecimientos.php');
require_once('../../app/models/Series.php');
use Valitron\Validator;

class EstablecimientosController
{
  public function filter_sucursales($isPaginated = true)
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $pJson = json_decode(file_get_contents('php://input'), true);

    $campos = [
      'id',
      'codigo',
      'descripcion',
      'direccion',
      'ubigeo_inei',
      'dis_prov_dep',
      'telefono',
      'email',
      'estado',
    ];

    $search = $pJson['search'] ? "%" . $pJson['search'] . "%" : "";

    $paramWhere = [
      "paramLike" => ['descripcion' => $search],
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

    $res = Establecimientos::filterSucursales($campos, $paramWhere, $paramOrders, $pagination, $isPaginated);
    return $res;
  }

  public function filter_sucursales_full() // sin paginacion
  {
    $res =  self::filter_sucursales(false);
    unset($res["next"]);
    unset($res["offset"]);
    unset($res["page"]);
    unset($res["pages"]);
    unset($res["previous"]);
    return $res;
  }
  
  public function get_sucursal()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    $sucursal = Establecimientos::getSucursal($pJson['id']);
    $res['content'] = $sucursal;
    unset($sucursal);
    return $res;
  }

  public function create_sucursal()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 200);

    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);
    $codigo = trimSpaces($pJson['codigo']);
    $descripcion = trimSpaces($pJson['descripcion']);
    $paramCampos = [
      "codigo" => $codigo ? $codigo : null,
      "tipo" => 'sucursal',
      "descripcion" => $descripcion,
      "direccion" => trimSpaces($pJson['direccion']),
      "ubigeo_inei" => $pJson['ubigeo_inei'],
      "telefono" => trimSpaces($pJson['telefono']),
      "email" => trimSpaces($pJson['email']),
    ];
    // Validacion
    $this->validateSucursal($paramCampos);

    // Buscando duplicados
    $count = Establecimientos::countRecordsBy(["descripcion" => $descripcion]);
    if ($count) throwMiExcepcion("El establecimiento: " . $descripcion . ", ya existe!", "warning");
    
    if($codigo){
      $count = Establecimientos::countRecordsBy(["codigo" => $codigo]);
      if ($count) throwMiExcepcion("El código: " . $codigo . ", ya existe!", "warning");
    }

    $lastId = Establecimientos::createSucursal($paramCampos);
    if (!$lastId) throwMiExcepcion("Ningún registro guardado", "warning");
    // Users::setActivityLog("Creación de nuevo laboratorio: " . $params["nombre"]);
    $registro = Establecimientos::getSucursal($lastId);
    $response['error'] = false;
    $response['msgType'] = "success";
    $response['msg'] = "Sucursal registrada";
    $response['content'] = $registro;
    return $response;
  }

  public function update_sucursal()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'PUT') throwMiExcepcion("Método no permitido", "error", 405);

    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 200);
    $codigo = trimSpaces($pJson['codigo']);
    $descripcion = trimSpaces($pJson['descripcion']);
    $paramCampos = [
      "codigo" => $codigo ? $codigo : null,
      "descripcion" => $descripcion,
      "direccion" => trimSpaces($pJson['direccion']),
      "ubigeo_inei" => $pJson['ubigeo_inei'],
      "telefono" => trimSpaces($pJson['telefono']),
      "email" => trimSpaces($pJson['email']),
      "estado" => trimSpaces($pJson['estado']),
    ];

    $this->validateSucursal($paramCampos);
    // Buscando duplicados
    $exclude = ["id" => $pJson['id']];
    // Buscando si es sucursal principal
    $countPrincipal = Establecimientos::countEstablecimientos([
      ["field_name" => "codigo", "field_value"=>'0000'],
      ["field_name" => "id", "field_value"=>$pJson['id']]
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
    

    $paramWhere = ["id" => $pJson['id']];

    $update = Establecimientos::updateSucursal($paramCampos, $paramWhere);
    if (!$update) throwMiExcepcion("Ningún registro modificado", "warning", 200);

    $sucursal = Establecimientos::getSucursal($pJson['id']);

    $res['msgType'] = "success";
    $res['msg'] = "Registro actualizado";
    $res['content'] = $sucursal;
    return $res;
  }

  public function delete_sucursal()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'DELETE') throwMiExcepcion("Método no permitido", "error", 405);
    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    $params = [
      "id" => $pJson['id'],
    ];
    // Buscando duplicados si es sucursal principal
    $countPrincipal = Establecimientos::countEstablecimientos([
      ["field_name" => "codigo", "field_value"=>'0000'],
      ["field_name" => "id", "field_value"=>$pJson['id']]
    ] );
    if($countPrincipal)throwMiExcepcion("No se puede eliminar a la sucursal principal", "warning");
    
    // Buscando si tiene series asociadas
    $countSeries = Series::countSeries([
      ["field_name" => "establecimiento_id", "field_value"=>$params['id']],
    ]);
    if($countSeries)throwMiExcepcion("Debe eliminar las series asociadas a la sucursal", "warning");

    $resp = Establecimientos::deleteSucursal($params);
    if (!$resp) throwMiExcepcion("Ningún registro eliminado", "warning");

    $response['content'] = null;
    $response['error'] = "false";
    $response['msgType'] = "success";
    $response['msg'] = "Registro eliminado";
    return $response;
  }



  private function validateSucursal($params){
    $v = new Validator($params);
    $v->addRule('sinEspacios', function ($field, $value, array $params, array $fields) {
      return strpos($value, ' ') === false; // Verificar que no haya espacios en el valor
    });
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
