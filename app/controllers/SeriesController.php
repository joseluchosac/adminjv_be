<?php
require_once('../../app/models/Series.php');
use Valitron\Validator;

class SeriesController
{

  public function get_series_establecimiento(){
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    $seriesEstablecimiento = Series::getSeriesEstablecimiento($pJson['establecimiento_id']);
    $res['content'] = $seriesEstablecimiento;
    
    return $res;
  }

  public function get_serie_establecimiento(){
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    $serieEstablecimiento = Series::getSerieEstablecimiento($pJson['id']);
    $res['content'] = $serieEstablecimiento;

    return $res;
  }

  public function create_serie_establecimiento()
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

    // Buscando duplicados de serie
    $countSeries = Series::countSeries([
      ["field_name" => "serie", "field_value"=>$paramCampos['serie']],
    ]);
    if($countSeries){
      throwMiExcepcion("La serie " . $paramCampos['serie'] . " ya fue registrada, ingrese otra", "warning");
    }
    // Buscando duplicados de establecimiento y descripcion
    $countDescripcion = Series::countSeries([
      ["field_name" => "establecimiento_id", "field_value"=>$paramCampos['establecimiento_id']],
      ["field_name" => "descripcion", "field_value"=>$descripcion],
    ]);
    if($countDescripcion){
      throwMiExcepcion("Ingrese otra descripcion", "warning");
    }

    $lastId = Series::createSerieEstablecimiento($paramCampos);
    if (!$lastId) throwMiExcepcion("Ningún registro guardado", "warning");
    // Users::setActivityLog("Creación de nuevo laboratorio: " . $params["nombre"]);
    $registro = series::getSerieEstablecimiento($lastId);
    $response['error'] = false;
    $response['msgType'] = "success";
    $response['msg'] = "Registro creado";
    $response['content'] = $registro;
    return $response;
  }

  public function update_serie_establecimiento()
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
    // Buscando duplicados de serie
    $exclude = ["id" => $pJson['id']];
    $countSeries = Series::countSeries([
      ["field_name" => "serie", "field_value"=>$paramCampos['serie']],
    ],$exclude);
    if($countSeries){
      throwMiExcepcion("La serie " . $paramCampos['serie'] . " ya fue registrada, ingrese otra", "warning");
    }
    // Buscando duplicados de establecimiento y descripcion
    $countDescripcion = Series::countSeries([
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

    $update = Series::updateSerieEstablecimiento($paramCampos, $paramWhere);
    if (!$update) throwMiExcepcion("Ningún registro modificado", "warning", 200);

    $registro = Series::getSerieEstablecimiento($pJson['id']);

    $res['msgType'] = "success";
    $res['msg'] = "Registro actualizado";
    $res['content'] = $registro;
    return $res;
  }

  public function delete_serie_establecimiento()
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
    // if($countPrincipal)throwMiExcepcion("No se puede eliminar a la sucursal principal", "warning");

    $resp = Series::deleteSerieEstablecimiento($params);
    if (!$resp) throwMiExcepcion("Ningún registro eliminado", "warning");

    $response['content'] = $params;
    $response['error'] = "false";
    $response['msgType'] = "success";
    $response['msg'] = "Registro eliminado";
    return $response;
  }
  // EVALUAR
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
