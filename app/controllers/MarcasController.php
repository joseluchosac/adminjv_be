<?php
require_once('../../app/models/Marcas.php');

class MarcasController
{
  public function filter_marcas()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);
    $campos = [
      'id',
      'nombre',
      'estado',
    ];

    $p["search"] = [
      "fieldsName" => ["nombre"],
      "like" => trim($p["search"])
    ];

    $pagination = [
      "page" => $_GET["page"] ?? "1",
      "offset" => $p['offset']
    ];

    $where = MyORM::getWhere($p);
    $orderBy = MyORM::getOrder($p["order"]);

    $res = Marcas::filterMarcas($campos, $where, $orderBy, $pagination);
    return $res;
  }

  public function get_marca()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);
    
    $marca = Marcas::getMarca($p['id']);
    if (!$marca) throwMiExcepcion("No se encontró el registro", "error", 404);
    return $marca;
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
    $response['marca'] = $registro;
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

    $response['msgType'] = "success";
    $response['msg'] = "Registro actualizado";
    $response['marca'] = $registro;
    return $response;
  }

  public function set_state_marca()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'PUT') throwMiExcepcion("Método no permitido", "error", 405);

    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 200);

    $paramCampos = [
      "estado" => $p['estado'],
    ];

    $paramWhere = ["id" => $p['id']];

    $resp = Marcas::updateMarca("marcas", $paramCampos, $paramWhere);
    if (!$resp) throwMiExcepcion("Ningún registro modificado", "warning", 200);
    
    $marca = Marcas::getMarca($p['id']);
    $response['msgType'] = "success";
    $response['msg'] = "Registro actualizado";
    $response['marca'] = $marca;
    return $response;
  }

  public function delete_marca()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'DELETE') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    // OJO, antes de eliminar verificar si el marca tiene una venta asociada
    $params = [ "id" => $p['id'] ];
    $marca = Marcas::getMarca($p['id']);
    $resp = Marcas::deleteMarca($params);
    if (!$resp) throwMiExcepcion("Ningún registro eliminado", "warning");

    $response['msgType'] = "success";
    $response['error'] = false;
    $response['msg'] = "Registro eliminado";
    $response['marca'] = $marca;
    return $response;
  }
}
