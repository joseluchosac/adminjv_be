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

    $registro = Modulos::getModulo($lastId);
    $response['msgType'] = "success";
    $response['msg'] = "Módulo registrado";
    $response['registro'] = $registro;
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

    $registro = Modulos::getModulo($params['id']);

    $response['msgType'] = "success";
    $response['msg'] = "Registro actualizado";
    $response['registro'] = $registro;
    return $response;
  }

  public function delete_modulo()
  {
    if($_SERVER['REQUEST_METHOD'] != 'DELETE') throwMiExcepcion("Método no permitido", "error", 200);

    $p = json_decode(file_get_contents('php://input'), true);
    if(!$p) throwMiExcepcion("No se enviaron parámetros", "error", 200);

    // Contar hijos
    $count = Modulos::countRecordsBy(["padre_id" => $p['id']]);
    if($count) throwMiExcepcion("El módulo a eliminar no debe tener hijos", "warning");

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

  public function get_modulo_rol()
  {
    setcookie("nombre", "Panchito",[
      'expires' => time() + 60*20,
      'path' => '/',
      // 'domain' => 'http://localhost:5173', // Dominio del backend (se puede usar un subdominio comodín)
      'secure' => true, // Solo enviar la cookie sobre HTTPS
      // 'httponly' => true, // No accesible desde JavaScript
      'samesite' => 'None', // Permitir cookies en solicitudes entre dominios
    ]);
    // setcookie("nombre", "steve", time() + 60*2, "/");
    $p = json_decode(file_get_contents('php://input'), true);
    $rol_id = $p["rol_id"];
    if($rol_id){
      $res['content'] = Modulos::getModuloRol($rol_id);
    }else{
      $res['msg'] = "No se obtuvieron datos";
      $res['msgType'] = "info";
      $res['content'] = null;
    }
    return $res;
  }

  public function get_modulos_sesion()
  {
    $modulosSesion = Modulos::getModulosSesion();
    // $res['content'] = $modulosSesion;
    return $modulosSesion;
  }

  public function update_modulos_roles()
  {
    $p = json_decode(file_get_contents('php://input'), true);
    $rol_id = $p["rol_id"];
    if(!$rol_id) throwMiExcepcion("No se guardaron los cambios", "warning", 200);
    $modulos = $p["modulos"];
    Modulos::updateModulosRoles($rol_id, $modulos);
    $res['error'] = false;
    $res['msgType'] = "success";
    $res['msg'] = "Modulos del rol actualizados";
    return $res;
  }

}

