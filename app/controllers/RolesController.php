<?php
require_once('../../app/models/ModulosRoles.php');
require_once('../../app/models/Roles.php');

class RolesController
{
  public function get_roles(){
    $campos = [ "id", "rol", "estado", ];
    $roles = Roles::getRoles($campos, null);
    return $roles;
  }

  public function create_rol()
  {
    if($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 200);

    $p = json_decode(file_get_contents('php://input'), true);
    if(!$p) throwMiExcepcion("No se enviaron parámetros", "error", 200);

    // Validacion
    if(trim($p['rol']) == "") throwMiExcepcion("Descripción del rol requerida", "warning", 200);
   
    // Buscando duplicados
    $count = Roles::countRecordsBy(["rol" => $p['rol']]);
    if($count) throwMiExcepcion("El rol: " . $p['rol'] . ", ya existe!", "warning");

    $params = [
      "rol" => trimSpaces($p['rol']),
    ];

    $lastId = Roles::createRol( $params );
    if(!$lastId) throwMiExcepcion("Ningún registro ingresado", "warning");

    $rol = Roles::getRol($lastId);

    $res['error'] = false;
    $res['msgType'] = "success";
    $res['msg'] = "Rol registrado";
    $res['rol'] = $rol;
    return $res;
  }
  
  public function update_rol()
  {
    if($_SERVER['REQUEST_METHOD'] != 'PUT') throwMiExcepcion("Método no permitido", "error", 200);

    $p = json_decode(file_get_contents('php://input'), true);
    if(!$p) throwMiExcepcion("No se enviaron parámetros", "error", 200);

    if(trim($p['rol']) == "") throwMiExcepcion("Descripcion de rol requerida", "warning", 200);

    // Buscando duplicados
    $exclude = ["id" => $p['id']];
    $count = Roles::countRecordsBy(["rol" => $p['rol']], $exclude);
    if($count) throwMiExcepcion("El rol: " . $p['rol'] . ", ya existe!", "warning");

    $params = [
      "rol" => trimSpaces($p['rol']),
      "id" => intval($p['id']),
    ];

    $resp = Roles::updateRol( $params );
    if(!$resp) throwMiExcepcion("Ningún registro modificado", "warning", 200);

    $rol = Roles::getRol($p['id']);

    $res['error'] = false;
    $res['msgType'] = "success";
    $res['msg'] = "Registro actualizado";
    $res['rol'] = $rol;
    return $res;
  }

  public function delete_rol()
  {
    if($_SERVER['REQUEST_METHOD'] != 'DELETE') throwMiExcepcion("Método no permitido", "error", 200);

    $p = json_decode(file_get_contents('php://input'), true);
    if(!$p) throwMiExcepcion("No se enviaron parámetros", "error", 200);
    // Verificar si el rol es dev
    if($p['id'] === 1) throwMiExcepcion("No se puede eliminar este rol", "error", 200);
    // Comprobando si este rol se este usando
    $count = Users::countRecordsBy(["rol_id" => $p['id']]);
    if($count) throwMiExcepcion("No se puede eliminar, Este rol está siendo utilizado!", "warning", 200, "isDependent");
    
    $params = [ "id" => $p['id'],];
    $resp = Roles::deleteRol( $params );
    if(!$resp) throwMiExcepcion("Ningún registro eliminado", "warning");

    $res['error'] = false;
    $res['msgType'] = "success";
    $res['msg'] = "Registro eliminado";
    return $res;
  }
}

