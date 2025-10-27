<?php
// require_once('../../app/models/Modulos.php');


class ModulosController
{
  public function get_modulos()
  {
    $campos = [
      "id",
      "ifnull(nombre,'') as nombre",
      "descripcion",
      "padre_id",
      "icon_menu",
      "orden"
    ];
    $orders = [
      ["field_name" => "orden", "order_dir" => "asc"],
    ];
    $registros = Modulos::getModulos($campos, null, $orders);
    return $registros;
  }

  public function create_modulo()
  {
    if($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 200);

    $p = json_decode(file_get_contents('php://input'), true);
    if(!$p) throwMiExcepcion("No se enviaron parámetros", "error", 200);

    // Validacion de modulo
    if(trim($p['descripcion']) == "") throwMiExcepcion("Descripción requerida", "warning", 200);

    if($p['padre_id'] && !$p['nombre']){
      throwMiExcepcion("Ingrese el nombre del módulo", "warning", 200);
    }

    // Comprobacion de duplicados
    $count = Modulos::countRecordsBy(["descripcion" => $p['descripcion']]);
    if($count) throwMiExcepcion("La descripción: " . $p['descripcion'] . ", ya existe!", "warning");

    if($p['nombre']){
      $count = Modulos::countRecordsBy(["nombre" => $p['nombre']]);
      if($count) throwMiExcepcion("El módulo de nombre: " . $p['nombre'] . ", ya existe!", "warning");
    }

    $params = [
      "nombre" => $p['nombre'] ? trimSpaces($p['nombre']) : null,
      "descripcion" => trimSpaces($p['descripcion']),
      "padre_id" => $p['padre_id'] ? $p['padre_id'] : 0,
      "icon_menu" => $p['icon_menu'] ? $p['icon_menu'] : "FaRegCircle",
      "orden" => $p['orden'] ? $p['orden'] : 0,
    ];

    $lastId = Modulos::createModulo( $params );
    if(!$lastId) throwMiExcepcion("Ningún registro guardado", "warning");

    // $registro = Modulos::getModulo($lastId);
    $response['msgType'] = "success";
    $response['msg'] = "Módulo registrado";
    // $response['registro'] = $registro;
    return $response;
  }

  public function update_modulo()
  {
    if($_SERVER['REQUEST_METHOD'] != 'PUT') throwMiExcepcion("Método no permitido", "error", 200);

    $p = json_decode(file_get_contents('php://input'), true);
    if(!$p) throwMiExcepcion("No se enviaron parámetros", "error", 200);

    if(trim($p['descripcion']) == "") throwMiExcepcion("Descripción requerida", "warning", 200);
    
    // Comprobacion de duplicados
    $exclude = ["id" => $p['id']];
    $count = Modulos::countRecordsBy(["descripcion" => $p['descripcion']], $exclude);
    if($count) throwMiExcepcion("La descripción: " . $p['descripcion'] . ", ya existe!", "warning");

    $params = [
      "nombre" => $p['nombre'] ? trimSpaces($p['nombre']) : null,
      "descripcion" => trimSpaces($p['descripcion']),
      "padre_id" => $p['padre_id'] ? $p['padre_id'] : 0,
      "icon_menu" => $p['icon_menu'] ? $p['icon_menu'] : "FaRegCircle",
      "orden" => $p['orden'] ? $p['orden'] : 0,
      "id" => intval($p['id']),
    ];


    $resp = Modulos::updateModulo( $params );
    if(!$resp) throwMiExcepcion("Ningún registro modificado", "warning", 200);

    // $registro = Modulos::getModulo($params['id']);

    $response['msgType'] = "success";
    $response['msg'] = "Registro actualizado";
    // $response['registro'] = $registro;
    return $response;
  }

  public function delete_modulo()
  {
    if($_SERVER['REQUEST_METHOD'] != 'DELETE') throwMiExcepcion("Método no permitido", "error", 200);

    $p = json_decode(file_get_contents('php://input'), true);
    if(!$p) throwMiExcepcion("No se enviaron parámetros", "error", 200);
    $omitted_modules = [1,2,4,5,6,7];
    if(in_array($p['id'], $omitted_modules)){ // home, profile, users, modulos, roles, config
      throwMiExcepcion("No es posible eliminar este módulo", "warning", 200);
    } 
    // Contar hijos
    $count = Modulos::countRecordsBy(["padre_id" => $p['id']]);
    if($count) throwMiExcepcion("El módulo a eliminar no debe tener hijos", "warning", 200);

    $params = [
      "id" => $p['id'],
    ];
    $deleteModulo = Modulos::deleteModulo( $params );
    if(!$deleteModulo) throwMiExcepcion("Ningún registro eliminado", "warning");

    $res['msgType'] = "success";
    $res['msg'] = "Registro eliminado";
    return $res;
  }

  public function sort_modulos()
  {
    if($_SERVER['REQUEST_METHOD'] != 'PUT') throwMiExcepcion("Método no permitido", "error", 200);

    $params = json_decode(file_get_contents('php://input'), true);
    Modulos::sortModulos($params);
    $res['error'] = false;
    $res['msgType'] = "success";
    $res['msg'] = "Módulos reordenados";
    return $res;
  }

  public function get_modulos_rol()
  {

    // Obtiene todos los módulos indicando cuales estan asignados al rol indicado   
    $p = json_decode(file_get_contents('php://input'), true);
    if(!$p["rol_id"]) throwMiExcepcion("Falta asignar el rol", "warning", 200);
    $modulosRol = Modulos::getModulosRol($p["rol_id"]);
    if(!$modulosRol) throwMiExcepcion("No se obtuvieron satos", "warning", 200);
    $modulosRol;





    // EXPERIMENTAL: Sin utilizar la tabla modulos_roles
    // $modulos = $this->get_modulos();
    // $rolId = $p["rol_id"];

    // $modulos2 = array_map(function($modulo) use($rolId){
    //   $allowedRoles = json_decode($modulo['allowed_roles']) ?? [];
    //   $modulo['assign'] = in_array($rolId, $allowedRoles) ? true : false;
    //   unset($modulo['allowed_roles']);
    //   return $modulo;
    // }, $modulos);
    
    // $modulosSesionHijos = array_filter($modulos, function($modulo) use($rolId){
    //   $allowedRoles = json_decode($modulo['allowed_roles']) ?? [];
    //   return in_array($rolId, $allowedRoles);
    // });
    // $padresId = array_filter($modulosSesionHijos, function($el){
    //   return $el['padre_id'] !== 0;
    // });
    // $padresId = array_values(array_unique(array_map(function($el){return $el['padre_id'];},$padresId)));

    // $modulosSesionPadres = array_filter($modulos, function($el) use($padresId){
    //   return in_array($el['id'], $padresId);
    // });
    // $modulosSesion = array_merge($modulosSesionPadres, $modulosSesionHijos);
    // $res['content2'] = $modulos2;
    // $res['modulosSesion'] = array_values($modulosSesion);


    return $modulosRol;
  }

  public function get_modulos_sesion()
  {
    // Obtiene solo los módulos asignados al rol de la sesión activa
    $modulosSesion = Modulos::getModulosSesion();
    // $res['content'] = $modulosSesion;
    return $modulosSesion;
  }

  public function update_modulos_roles()
  {
    $p = json_decode(file_get_contents('php://input'), true);
    $rol_id = $p["rol_id"];
    $modulo_id = $p["modulo_id"];
    if(!$rol_id) throwMiExcepcion("No se guardaron los cambios", "warning", 200);

    $cantidad = Modulos::countModulosRoles($modulo_id, $rol_id);

    if($cantidad){
      if($modulo_id == 1 || $modulo_id == 2) throwMiExcepcion("No es posible desactivar este módulo", "warning", 200);
      if($rol_id === 1) throwMiExcepcion("No es posible desactivar este módulo", "warning", 200);
      Modulos::deleteModuloRol($modulo_id, $rol_id);
    }else{
      Modulos::createModuloRol($modulo_id, $rol_id);
    }




    // EXPERIMENTAL
    // Toggle allow role en la tabla modulos
    // $modulo = Modulos::getModulo($modulo_id);
    // $allowedRoles = json_decode($modulo['allowed_roles']) ?? [];
    // $idx = array_search($rol_id, $allowedRoles);
    // if($idx === false){
    //   array_push($allowedRoles, $rol_id);
    // }else{
    //   unset($allowedRoles[$idx]);
    // }
    // $params = [
    //   'allowed_roles' => json_encode(array_values($allowedRoles)),
    //   'id' => $modulo_id,
    // ];
    // Modulos::updateAllowedRoles($params);







    $res['error'] = false;
    $res['msgType'] = "success";
    $res['msg'] = "Módulos del rol actualizados";
    return $res;
  }

}

