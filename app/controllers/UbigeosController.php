<?php
require_once('../../app/models/Ubigeos.php');

class UbigeosController
{
  public function filter_ubigeos($isPaginated = true)
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $pJson = json_decode(file_get_contents('php://input'), true);

    $campos = [
      'ubigeo_inei',
      'ubigeo_reniec',
      'departamento',
      'provincia',
      'distrito',
    ];

    $search = $pJson['search'] ? "%" . $pJson['search'] . "%" : "";

    $paramWhere = [
      "paramLike" => [
        'ubigeo_inei' => $search,
        // 'ubigeo_reniec' => $search,
        'departamento' => $search,
        'provincia' => $search,
        'distrito' => $search,
      ],
      "paramEquals" => $pJson['equals'], // [["field_name" => "id", "field_value"=>1]] 
      "paramBetween" => [
        "campo" => $pJson['between']['field_name'],
        "rango" => $pJson['between']['range'] // "2024-12-18 00:00:00, 2024-12-19 23:59:59"
      ]
    ];

    $paramOrders = count($pJson['orders']) 
      ? $pJson['orders'] 
      : [["field_name"=>"orden","order_dir"=>"ASC", "text" => "Orden"]];
    // $paramOrders = $pJson['orders'];

    $pagination = [
      "page" => $_GET["page"] ?? "1",
      "offset" => $pJson['offset']
    ];

    $res = Ubigeos::filterUbigeos($campos, $paramWhere, $paramOrders, $pagination, $isPaginated);
    return $res;
  }

  public function filter_ubigeos_full() // sin paginacion
  {
    $res =  self::filter_ubigeos(false);
    unset($res["next"]);
    unset($res["offset"]);
    unset($res["page"]);
    unset($res["pages"]);
    unset($res["previous"]);
    return $res;
  }

  public function get_ubigeo()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);
    
    $registro = Ubigeos::getUbigeo($pJson['id']);
    if (!$registro) throwMiExcepcion("No se encontró el registro", "error", 404);
    $response["content"] = $registro;
    return $response;
  }

  public function create_ubigeo()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 200);

    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);
    $params = ["nombre" => $pJson['nombre'],];
    // Validacion
    //$this->validateCreateUser($params);

    // Buscando duplicados
    $count = Ubigeos::countRecordsBy(["nombre" => $pJson['nombre']]);
    if ($count) throwMiExcepcion("El ubigeo: " . $pJson['nombre'] . ", ya existe!", "warning");

    $lastId = Ubigeos::createUbigeo($params);
    if (!$lastId) throwMiExcepcion("Ningún registro guardado", "warning");
    Users::setActivityLog("Creación de nuevo ubigeo: " . $params["nombre"]);
    $registro = Ubigeos::getUbigeo($lastId);
    $response['error'] = false;
    $response['msgType'] = "success";
    $response['msg'] = "Ubigeo registrado";
    $response['registro'] = $registro;
    return $response;
  }

  public function update_ubigeo()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'PUT') throwMiExcepcion("Método no permitido", "error", 405);

    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 200);

    $paramCampos = [
      "nombre" => trim($pJson['nombre']) ? trim($pJson['nombre']) : null,
      "estado" => $pJson['estado'],
    ];

    // Validacion
    // $this->validateUpdateUser($paramCampos);

    // Buscando duplicados
    $exclude = ["id" => $pJson['id']];
    $count = Ubigeos::countRecordsBy(["nombre" => $pJson['nombre']], $exclude);
    if ($count) throwMiExcepcion("El ubigeo: " . $pJson['nombre'] . ", ya existe!", "warning");

    $paramWhere = ["id" => $pJson['id']];

    $resp = Ubigeos::updateUbigeo("ubigeos", $paramCampos, $paramWhere);
    if (!$resp) throwMiExcepcion("Ningún registro modificado", "warning", 200);
    
    $registro = Ubigeos::getUbigeo($pJson['id']);
    Users::setActivityLog("Modificación de registro en la tabla ubigeos: " . $registro["nombre"]);

    $response['content'] = $registro;
    $response['msgType'] = "success";
    $response['msg'] = "Registro actualizado";
    return $response;
  }

  public function delete_ubigeo()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'DELETE') throwMiExcepcion("Método no permitido", "error", 405);
    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    // OJO, antes de eliminar verificar si el ubigeo tiene una venta asociada
    $params = [ "id" => $pJson['id'] ];
    $resp = Ubigeos::deleteUbigeo($params);
    if (!$resp) throwMiExcepcion("Ningún registro eliminado", "warning");

    $response['msgType'] = "success";
    $response['error'] = false;
    $response['msg'] = "Registro eliminado";
    return $response;
  }



}
