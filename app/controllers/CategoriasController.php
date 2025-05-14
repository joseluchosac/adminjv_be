<?php
require_once('../../app/models/Categorias.php');

class CategoriasController
{
  public function get_categorias(){
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $campos = [
      "id", 
      "nombre", 
      "descripcion", 
      "padre_id",
      "icon",
      "orden",
      "estado"
    ];
    $orders = [
      ["campo_name" => "orden", "order_dir" => "asc"],
    ];
    $registro = Categorias::getCategorias($campos, $orders);
    return $registro;
  }

  public function sort_categorias()
  {
    if($_SERVER['REQUEST_METHOD'] != 'PUT') throwMiExcepcion("Método no permitido", "error", 200);

    $params = json_decode(file_get_contents('php://input'), true);

    Categorias::sortCategorias($params);
    $response['error'] = false;
    $response['msgType'] = "success";
    $response['msg'] = "Categorías reordenadas";
    return $response;
  }

  public function create_categoria()
  {
    if($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 200);

    $pJson = json_decode(file_get_contents('php://input'), true);
    if(!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 200);

    // Validacion de modulo
    if(trim($pJson['descripcion']) == "") throwMiExcepcion("Descripción requerida", "warning", 200);

    if(trim($pJson['nombre']) == "") throwMiExcepcion("Ingrese el nombre de la categoría", "warning", 200);

    // Comprobacion de duplicados
    $count = Categorias::countRecordsBy(["descripcion" => $pJson['descripcion']]);
    if($count) throwMiExcepcion("La descripción: " . $pJson['descripcion'] . ", ya existe!", "warning");

    if($pJson['nombre']){
      $count = Categorias::countRecordsBy(["nombre" => $pJson['nombre']]);
      if($count) throwMiExcepcion("La categoría de nombre: " . $pJson['nombre'] . ", ya existe!", "warning");
    }

    $params = [
      "nombre" => $pJson['nombre'] ? trimSpaces($pJson['nombre']) : null,
      "descripcion" => trimSpaces($pJson['descripcion']),
      "padre_id" => $pJson['padre_id'] ? $pJson['padre_id'] : 0,
      "icon" => $pJson['icon'] ? $pJson['icon'] : "FaRegCircle",
      "orden" => 0,
    ];

    $lastId = Categorias::createCategoria( $params );
    if(!$lastId) throwMiExcepcion("Ningún registro guardado", "warning");

    $registro = Categorias::getCategoria($lastId);
    $response['msgType'] = "success";
    $response['msg'] = "Módulo registrado";
    $response['registro'] = $registro;
    return $response;
  }

  public function update_categoria()
  {
    if($_SERVER['REQUEST_METHOD'] != 'PUT') throwMiExcepcion("Método no permitido", "error", 200);

    $pJson = json_decode(file_get_contents('php://input'), true);
    if(!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 200);

    if(trim($pJson['descripcion']) == "") throwMiExcepcion("Descripción requerida", "warning", 200);
    
    // Comprobacion de duplicados
    $exclude = ["id" => $pJson['id']];
    $count = Categorias::countRecordsBy(["descripcion" => $pJson['descripcion']], $exclude);
    if($count) throwMiExcepcion("La descripción: " . $pJson['descripcion'] . ", ya existe!", "warning");

    $params = [
      "nombre" => $pJson['nombre'] ? trimSpaces($pJson['nombre']) : null,
      "descripcion" => trimSpaces($pJson['descripcion']),
      "padre_id" => $pJson['padre_id'] ? $pJson['padre_id'] : 0,
      "icon" => $pJson['icon'] ? $pJson['icon'] : "FaRegCircle",
      "id" => intval($pJson['id']),
    ];

    $resp = Categorias::updateCategoria( $params );
    if(!$resp) throwMiExcepcion("Ningún registro modificado", "warning", 200);

    $registro = Categorias::getCategoria($params['id']);

    $response['msgType'] = "success";
    $response['msg'] = "Registro actualizado";
    $response['registro'] = $registro;
    return $response;
  }

  public function delete_categoria()
  {
    if($_SERVER['REQUEST_METHOD'] != 'DELETE') throwMiExcepcion("Método no permitido", "error", 200);

    $pJson = json_decode(file_get_contents('php://input'), true);
    if(!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 200);

    // Contar hijos
    $count = Categorias::countRecordsBy(["padre_id" => $pJson['id']]);
    if($count) throwMiExcepcion("La categoria a eliminar no debe tener hijos", "warning");

    $params = [
      "id" => $pJson['id'],
    ];
    $resp = Categorias::deleteCategoria( $params );
    if(!$resp) throwMiExcepcion("Ningún registro eliminado", "warning");

    $response['msgType'] = "success";
    $response['msg'] = "Registro eliminado";
    return $response;
  }
}

