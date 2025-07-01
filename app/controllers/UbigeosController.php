<?php
require_once('../../app/models/Ubigeos.php');

class UbigeosController
{
  public function filter_ubigeos($isPaginated = true)
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);

    $campos = [
      'ubigeo_inei',
      'ubigeo_reniec',
      'dis_prov_dep',
    ];

    $search = $p['search'] ? "%" . $p['search'] . "%" : "";

    $paramWhere = [
      "paramLike" => [
        'ubigeo_inei' => $search,
        // 'ubigeo_reniec' => $search,
        'dis_prov_dep' => $search,
      ],
      "paramEquals" => $p['equals'], // [["field_name" => "id", "field_value"=>1]] 
      "paramBetween" => [
        "campo" => $p['between']['field_name'],
        "rango" => $p['between']['range'] // "2024-12-18 00:00:00, 2024-12-19 23:59:59"
      ]
    ];

    $paramOrders = count($p['orders']) 
      ? $p['orders'] 
      : [
          ["field_name"=>"orden","order_dir"=>"ASC", "text" => "orden"],
          ["field_name"=>"dep_prov_dis","order_dir"=>"ASC", "text" => "dep_prov_dis"],
        ];
    // $paramOrders = $p['orders'];

    $pagination = [
      "page" => $_GET["page"] ?? "1",
      "offset" => $p['offset']
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
    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);
    
    $registro = Ubigeos::getUbigeo($p['id']);
    if (!$registro) throwMiExcepcion("No se encontró el registro", "error", 404);
    $response["content"] = $registro;
    return $response;
  }

  public function create_ubigeo()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 200);

    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);
    $params = ["nombre" => $p['nombre'],];
    // Validacion
    //$this->validateCreateUser($params);

    // Buscando duplicados
    $count = Ubigeos::countRecordsBy(["nombre" => $p['nombre']]);
    if ($count) throwMiExcepcion("El ubigeo: " . $p['nombre'] . ", ya existe!", "warning");

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

    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 200);

    $paramCampos = [
      "nombre" => trim($p['nombre']) ? trim($p['nombre']) : null,
      "estado" => $p['estado'],
    ];

    // Validacion
    // $this->validateUpdateUser($paramCampos);

    // Buscando duplicados
    $exclude = ["id" => $p['id']];
    $count = Ubigeos::countRecordsBy(["nombre" => $p['nombre']], $exclude);
    if ($count) throwMiExcepcion("El ubigeo: " . $p['nombre'] . ", ya existe!", "warning");

    $paramWhere = ["id" => $p['id']];

    $resp = Ubigeos::updateUbigeo("ubigeos", $paramCampos, $paramWhere);
    if (!$resp) throwMiExcepcion("Ningún registro modificado", "warning", 200);
    
    $registro = Ubigeos::getUbigeo($p['id']);
    Users::setActivityLog("Modificación de registro en la tabla ubigeos: " . $registro["nombre"]);

    $response['content'] = $registro;
    $response['msgType'] = "success";
    $response['msg'] = "Registro actualizado";
    return $response;
  }

  public function delete_ubigeo()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'DELETE') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    // OJO, antes de eliminar verificar si el ubigeo tiene una venta asociada
    $params = [ "id" => $p['id'] ];
    $resp = Ubigeos::deleteUbigeo($params);
    if (!$resp) throwMiExcepcion("Ningún registro eliminado", "warning");

    $response['msgType'] = "success";
    $response['error'] = false;
    $response['msg'] = "Registro eliminado";
    return $response;
  }



}
