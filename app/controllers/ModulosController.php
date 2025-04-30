<?php
// require_once('../../app/models/Modulos.php');


class ModulosController
{
  public function test(){

    $parJson = json_decode(file_get_contents('php://input'), true);
    $response['msg'] = 'ok';
    $response['server_REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'];
    $response['GET'] = $_GET;
    $response['POST'] = $_POST;
    $response['parJson'] = $parJson;
    return $response;
  }

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
      ["campo_name" => "orden", "order_dir" => "asc"],
    ];
    $registros = Modulos::getModulos($campos, null, $orders);
    return $registros;
  }

  public function create_modulo()
  {
    if($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 200);

    $pJson = json_decode(file_get_contents('php://input'), true);
    if(!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 200);

    // Validacion de modulo
    if(trim($pJson['descripcion']) == "") throwMiExcepcion("Descripción requerida", "warning", 200);

    if($pJson['padre_id'] && !$pJson['nombre']){
      throwMiExcepcion("Ingrese el nombre del módulo", "warning", 200);
    }

    // Comprobacion de duplicados
    $count = Modulos::countRecordsBy(["descripcion" => $pJson['descripcion']]);
    if($count) throwMiExcepcion("La descripción: " . $pJson['descripcion'] . ", ya existe!", "warning");

    if($pJson['nombre']){
      $count = Modulos::countRecordsBy(["nombre" => $pJson['nombre']]);
      if($count) throwMiExcepcion("El módulo de nombre: " . $pJson['nombre'] . ", ya existe!", "warning");
    }

    $params = [
      "nombre" => $pJson['nombre'] ? trimSpaces($pJson['nombre']) : null,
      "descripcion" => trimSpaces($pJson['descripcion']),
      "padre_id" => $pJson['padre_id'] ? $pJson['padre_id'] : 0,
      "icon_menu" => $pJson['icon_menu'] ? $pJson['icon_menu'] : "FaRegCircle",
      "orden" => $pJson['orden'] ? $pJson['orden'] : 0,
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

    $pJson = json_decode(file_get_contents('php://input'), true);
    if(!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 200);

    if(trim($pJson['descripcion']) == "") throwMiExcepcion("Descripción requerida", "warning", 200);
    
    // Comprobacion de duplicados
    $exclude = ["id" => $pJson['id']];
    $count = Modulos::countRecordsBy(["descripcion" => $pJson['descripcion']], $exclude);
    if($count) throwMiExcepcion("La descripción: " . $pJson['descripcion'] . ", ya existe!", "warning");

    $params = [
      "nombre" => $pJson['nombre'] ? trimSpaces($pJson['nombre']) : null,
      "descripcion" => trimSpaces($pJson['descripcion']),
      "padre_id" => $pJson['padre_id'] ? $pJson['padre_id'] : 0,
      "icon_menu" => $pJson['icon_menu'] ? $pJson['icon_menu'] : "FaRegCircle",
      "orden" => $pJson['orden'] ? $pJson['orden'] : 0,
      "id" => intval($pJson['id']),
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

    $pJson = json_decode(file_get_contents('php://input'), true);
    if(!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 200);

    // Contar hijos
    $count = Modulos::countRecordsBy(["padre_id" => $pJson['id']]);
    if($count) throwMiExcepcion("El módulo a eliminar no debe tener hijos", "warning");

    $params = [
      "id" => $pJson['id'],
    ];
    $resp = Modulos::deleteModulo( $params );
    if(!$resp) throwMiExcepcion("Ningún registro eliminado", "warning");

    $response['msgType'] = "success";
    $response['msg'] = "Registro eliminado";
    return $response;
  }

  public function sort_modulos()
  {
    if($_SERVER['REQUEST_METHOD'] != 'PUT') throwMiExcepcion("Método no permitido", "error", 200);

    $params = json_decode(file_get_contents('php://input'), true);
    Modulos::sortModulos($params);
    $response['error'] = false;
    $response['msgType'] = "success";
    $response['msg'] = "Módulos reordenados";
    return $response;
  }

  public function get_modulo_rol()
  {
    // $_SERVER['HTTP_ORIGIN'] ->http://localhost:5173
    setcookie("nombre", "Panchito",[
      'expires' => time() + 60*20,
      'path' => '/',
      // 'domain' => 'http://localhost:5173', // Dominio del backend (puedes usar un subdominio comodín)
      'secure' => true, // Solo enviar la cookie sobre HTTPS
      // 'httponly' => true, // No accesible desde JavaScript
      'samesite' => 'None', // Permitir cookies en solicitudes entre dominios
    ]);
    // setcookie("nombre", "steve", time() + 60*2, "/");
    $pJson = json_decode(file_get_contents('php://input'), true);
    $rol_id = $pJson["rol_id"];
    if($rol_id){
      $response = Modulos::getModuloRol($rol_id);
    }else{
      $response = null;
    }
    return $response;
  }

  public function get_modulos_sesion()
  {
    $modulosSesion = Modulos::getModulosSesion();
    return $modulosSesion;
  }

  public function update_modulos_roles()
  {
    $pJson = json_decode(file_get_contents('php://input'), true);
    $rol_id = $pJson["rol_id"];
    if(!$rol_id) throwMiExcepcion("No se guardaron los cambios", "warning", 200);
    $modulos = $pJson["modulos"];
    Modulos::updateModulosRoles($rol_id, $modulos);
    $response['error'] = false;
    $response['msgType'] = "success";
    $response['msg'] = "Modulos del rol actualizados";
    return $response;
  }

}

