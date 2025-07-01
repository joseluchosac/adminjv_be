<?php
require_once('../../app/models/Marcas.php');

class MarcasController
{
  public function filter_marcas($isPaginated = true)
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);

    $campos = [
      'id',
      'nombre',
      'estado',
    ];

    $search = $p['search'] ? "%" . $p['search'] . "%" : "";

    $paramWhere = [
      "paramLike" => ['nombre' => $search],
      "paramEquals" => $p['equals'], // [["field_name" => "id", "field_value"=>1]] 
      "paramBetween" => [
        "campo" => $p['between']['field_name'],
        "rango" => $p['between']['range'] // "2024-12-18 00:00:00, 2024-12-19 23:59:59"
      ]
    ];

    $paramOrders = count($p['orders']) 
      ? $p['orders'] 
      : [["field_name"=>"id","order_dir"=>"DESC", "text" => "Id"]];
    // $paramOrders = $p['orders'];

    // var_dump($paramOrders);
    // exit();
    $pagination = [
      "page" => $_GET["page"] ?? "1",
      "offset" => $p['offset']
    ];

    $res = Marcas::filterMarcas($campos, $paramWhere, $paramOrders, $pagination, $isPaginated);
    return $res;
  }

  public function filter_marcas_full() // sin paginacion
  {
    $res =  self::filter_marcas(false);
    unset($res["next"]);
    unset($res["offset"]);
    unset($res["page"]);
    unset($res["pages"]);
    unset($res["previous"]);
    return $res;
  }
  
  public function get_marca()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);
    
    $registro = Marcas::getMarca($p['id']);
    if (!$registro) throwMiExcepcion("No se encontró el registro", "error", 404);
    $response["content"] = $registro;
    return $response;
  }
  public function create_marca()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 200);

    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);
    $params = ["nombre" => $p['nombre'],];
    // Validacion
    //$this->validateCreateUser($params);

    // Buscando duplicados
    $count = Marcas::countRecordsBy(["nombre" => $p['nombre']]);
    if ($count) throwMiExcepcion("El marca: " . $p['nombre'] . ", ya existe!", "warning");

    $lastId = Marcas::createMarca($params);
    if (!$lastId) throwMiExcepcion("Ningún registro guardado", "warning");
    Users::setActivityLog("Creación de nuevo marca: " . $params["nombre"]);
    $registro = Marcas::getMarca($lastId);
    $response['error'] = false;
    $response['msgType'] = "success";
    $response['msg'] = "Marca registrado";
    $response['content'] = $registro;
    return $response;
  }

  public function update_marca()
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
    $count = Marcas::countRecordsBy(["nombre" => $p['nombre']], $exclude);
    if ($count) throwMiExcepcion("El marca: " . $p['nombre'] . ", ya existe!", "warning");

    $paramWhere = ["id" => $p['id']];

    $resp = Marcas::updateMarca("marcas", $paramCampos, $paramWhere);
    if (!$resp) throwMiExcepcion("Ningún registro modificado", "warning", 200);
    
    $registro = Marcas::getMarca($p['id']);
    Users::setActivityLog("Modificación de registro en la tabla marcas: " . $registro["nombre"]);

    $response['content'] = $registro;
    $response['msgType'] = "success";
    $response['msg'] = "Registro actualizado";
    return $response;
  }

  public function delete_marca()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'DELETE') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    // OJO, antes de eliminar verificar si el marca tiene una venta asociada
    $params = [ "id" => $p['id'] ];
    $resp = Marcas::deleteMarca($params);
    if (!$resp) throwMiExcepcion("Ningún registro eliminado", "warning");

    $response['msgType'] = "success";
    $response['error'] = false;
    $response['msg'] = "Registro eliminado";
    return $response;
  }



}
