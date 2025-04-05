<?php
require_once('../../app/models/ModulosRoles.php');
require_once('../../app/models/Roles.php');

class RolesController
{
  public function registrar_rol()
  {
    if($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 200);

    $pJson = json_decode(file_get_contents('php://input'), true);
    if(!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 200);

    // Validacion
    if(trim($pJson['rol']) == "") throwMiExcepcion("Descripción del rol requerida", "warning", 200);
   
    // Buscando duplicados
    $count = Roles::countRecordsBy(["rol" => $pJson['rol']]);
    if($count) throwMiExcepcion("El rol: " . $pJson['rol'] . ", ya existe!", "warning");

    $params = [
      "rol" => trimSpaces($pJson['rol']),
    ];

    $lastId = Roles::registrarRol( $params );
    if(!$lastId) throwMiExcepcion("Ningún registro ingresado", "warning");

    $registro = Roles::getRol($lastId);
    $response['error'] = false;
    $response['msgType'] = "success";
    $response['msg'] = "Rol registrado";
    $response['accion'] = "registrar";
    $response['rol'] = $registro;
    return $response;
  }
  
  public function actualizar_rol()
  {
    if($_SERVER['REQUEST_METHOD'] != 'PUT') throwMiExcepcion("Método no permitido", "error", 200);

    $pJson = json_decode(file_get_contents('php://input'), true);
    if(!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 200);

    if(trim($pJson['rol']) == "") throwMiExcepcion("Rol requerido", "warning", 200);

    // Buscando duplicados
    $exclude = ["id" => $pJson['id']];
    $count = Roles::countRecordsBy(["rol" => $pJson['rol']], $exclude);
    if($count) throwMiExcepcion("El rol: " . $pJson['rol'] . ", ya existe!", "warning");

    $params = [
      "rol" => trimSpaces($pJson['rol']),
      "id" => intval($pJson['id']),
    ];

    $resp = Roles::actualizarRol( $params );
    if(!$resp) throwMiExcepcion("Ningún registro modificado", "warning", 200);

    $registro = Roles::getRol($params['id']);

    $response['error'] = false;
    $response['msgType'] = "success";
    $response['msg'] = "Registro actualizado";
    $response['accion'] = "actualizar";
    $response['rol'] = $registro;
    return $response;
  }

  public function eliminar_rol()
  {
    if($_SERVER['REQUEST_METHOD'] != 'DELETE') throwMiExcepcion("Método no permitido", "error", 200);

    $pJson = json_decode(file_get_contents('php://input'), true);
    if(!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 200);

    // Comprobando dependencias
    $count = Users::countRecordsBy(["rol_id" => $pJson['id']]);
    if($count) throwMiExcepcion("Este rol depende de algunos usuarios !", "warning");
    $count = ModulosRoles::countRecordsBy(["rol_id" => $pJson['id']]);
    if($count) throwMiExcepcion("Este rol depende de algunos usuarios !", "warning");
    
    $params = [
      "id" => $pJson['id'],
    ];
    $resp = Roles::eliminarRol( $params );
    if(!$resp) throwMiExcepcion("Ningún registro eliminado", "warning");

    $response['error'] = false;
    $response['msgType'] = "success";
    $response['msg'] = "Registro eliminado";
    $response['accion'] = "eliminar";
    $response['rol_id'] = intval($params["id"]);
    return $response;
  }
}

