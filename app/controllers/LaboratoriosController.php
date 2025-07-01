<?php
require_once('../../app/models/Laboratorios.php');

class LaboratoriosController
{
  public function filter_laboratorios($isPaginated = true)
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

    $paramOrders = $p['orders'];

    $pagination = [
      "page" => $_GET["page"] ?? "1",
      "offset" => $p['offset']
    ];

    $res = Laboratorios::filterLaboratorios($campos, $paramWhere, $paramOrders, $pagination, $isPaginated);
    return $res;
  }

  public function filter_laboratorios_full() // sin paginacion
  {
    $res =  self::filter_laboratorios(false);
    unset($res["next"]);
    unset($res["offset"]);
    unset($res["page"]);
    unset($res["pages"]);
    unset($res["previous"]);
    return $res;
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
    $registro = Laboratorios::getLaboratorio($lastId);
    $response['error'] = false;
    $response['msgType'] = "success";
    $response['msg'] = "Laboratorio registrado";
    $response['content'] = $registro;
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
    
    $registro = Laboratorios::getLaboratorio($p['id']);
    Users::setActivityLog("Modificación de registro en la tabla laboratorios: " . $registro["nombre"]);

    $response['msgType'] = "success";
    $response['msg'] = "Registro actualizado";
    $response['registro'] = $registro;
    return $response;
  }

  public function delete_laboratorio()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'DELETE') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    // OJO, antes de eliminar verificar si el laboratorio tiene una venta asociada
    $params = [ "id" => $p['id'] ];
    $resp = Laboratorios::deleteLaboratorio($params);
    if (!$resp) throwMiExcepcion("Ningún registro eliminado", "warning");

    $response['msgType'] = "success";
    $response['error'] = false;
    $response['msg'] = "Registro eliminado";
    return $response;
  }

  public function get_laboratorio()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    $registro = Laboratorios::getLaboratorio($p['id']);
    return $registro;
  }

}
