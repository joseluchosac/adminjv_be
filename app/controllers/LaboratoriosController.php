<?php
require_once('../../app/models/Laboratorios.php');

class LaboratoriosController
{
  public function filter_laboratorios()
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
      "per_page" => $p['per_page']
    ];

    $where = MyORM::getWhere($p);
    $orderBy = MyORM::getOrder($p["order"]);

    $res = Laboratorios::filterLaboratorios($campos, $where, $orderBy, $pagination);
    return $res;
  }

  public function get_laboratorio()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    $laboratorio = Laboratorios::getLaboratorio($p['id']);
    return $laboratorio;
  }

  public function create_laboratorio()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 200);

    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);
    $params = ["nombre" => $p['nombre'],];
    // Validacion
    //$this->validateCreateUser($params);

    // Buscando duplicados
    $count = Laboratorios::countRecordsBy(["nombre" => $p['nombre']]);
    if ($count) throwMiExcepcion("El laboratorio: " . $p['nombre'] . ", ya existe!", "warning");

    $lastId = Laboratorios::createLaboratorio($params);
    if (!$lastId) throwMiExcepcion("Ningún registro guardado", "warning");
    Users::setActivityLog("Creación de nuevo laboratorio: " . $params["nombre"]);
    $laboratorio = Laboratorios::getLaboratorio($lastId);
    $response['error'] = false;
    $response['msgType'] = "success";
    $response['msg'] = "Laboratorio registrado";
    $response['laboratorio'] = $laboratorio;
    return $response;
  }

  public function update_laboratorio()
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
    $count = Laboratorios::countRecordsBy(["nombre" => $p['nombre']], $exclude);
    if ($count) throwMiExcepcion("El laboratorio: " . $p['nombre'] . ", ya existe!", "warning");

    $paramWhere = ["id" => $p['id']];

    $resp = Laboratorios::updateLaboratorio("laboratorios", $paramCampos, $paramWhere);
    if (!$resp) throwMiExcepcion("Ningún registro modificado", "warning", 200);
    
    $laboratorio = Laboratorios::getLaboratorio($p['id']);

    $response['msgType'] = "success";
    $response['msg'] = "Registro actualizado";
    $response['laboratorio'] = $laboratorio;
    return $response;
  }

  public function set_state_laboratorio()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'PUT') throwMiExcepcion("Método no permitido", "error", 405);

    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 200);

    $paramCampos = [
      "estado" => $p['estado'],
    ];

    $paramWhere = ["id" => $p['id']];

    $resp = Laboratorios::updateLaboratorio("laboratorios", $paramCampos, $paramWhere);
    if (!$resp) throwMiExcepcion("Ningún registro modificado", "warning", 200);

    $laboratorio = Laboratorios::getLaboratorio($p['id']);
    $response['msgType'] = "success";
    $response['msg'] = "Registro actualizado";
    $response['laboratorio'] = $laboratorio;
    return $response;
  }

  public function delete_laboratorio()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'DELETE') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    // OJO, antes de eliminar verificar si el laboratorio tiene una venta asociada
    $params = [ "id" => $p['id'] ];
    $laboratorio = Laboratorios::getLaboratorio($p['id']);
    $resp = Laboratorios::deleteLaboratorio($params);
    if (!$resp) throwMiExcepcion("Ningún registro eliminado", "warning");

    $response['msgType'] = "success";
    $response['error'] = false;
    $response['msg'] = "Registro eliminado";
    $response['laboratorio'] = $laboratorio;
    return $response;
  }



}
