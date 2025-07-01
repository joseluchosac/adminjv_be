<?php
require_once('../../app/models/Categorias.php');

class CategoriasController
{
  public function get_categorias(){
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $campos = [
      "id", 
      "descripcion", 
      "padre_id",
      "orden"
    ];
    $orders = [
      ["field_name" => "orden", "order_dir" => "asc"],
    ];
    $resp['content'] = Categorias::getCategorias($campos, $orders);
    return $resp;
  }

  // public function get_categorias_tree(){
  //   $response['data']=generateTree($this->get_categorias());
  //   return $response;
  // }

  public function sort_categorias()
  {
    if($_SERVER['REQUEST_METHOD'] != 'PUT') throwMiExcepcion("Método no permitido", "error", 200);

    $params = json_decode(file_get_contents('php://input'), true);

    Categorias::sortCategorias($params);

    $_SERVER["REQUEST_METHOD"] = "POST";
    $categorias_tree = generateTree($this->get_categorias()['content']);

    $response['error'] = false;
    $response['msgType'] = "success";
    $response['msg'] = "Categorías reordenadas";
    $response['content'] = $categorias_tree;
    return $response;
  }

  public function create_categoria()
  {
    if($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 200);

    $p = json_decode(file_get_contents('php://input'), true);
    if(!$p) throwMiExcepcion("No se enviaron parámetros", "error", 200);

    // Validacion de modulo
    if(trim($p['descripcion']) == "") throwMiExcepcion("Descripción requerida", "warning", 200);

    // Comprobacion de duplicados
    $count = Categorias::countRecordsBy(["descripcion" => $p['descripcion']]);
    if($count) throwMiExcepcion("La descripción: " . $p['descripcion'] . ", ya existe!", "warning");

    $params = [
      "descripcion" => trimSpaces($p['descripcion']),
      "padre_id" => $p['padre_id'] ? $p['padre_id'] : 0,
      "orden" => 0,
    ];

    $lastId = Categorias::createCategoria( $params );
    if(!$lastId) throwMiExcepcion("Ningún registro guardado", "warning");

    $categorias_tree = generateTree($this->get_categorias()['content']);
    $response['msgType'] = "success";
    $response['msg'] = "Módulo registrado";
    $response['content'] = $categorias_tree;
    return $response;
  }

  public function update_categoria()
  {
    if($_SERVER['REQUEST_METHOD'] != 'PUT') throwMiExcepcion("Método no permitido", "error", 200);

    $p = json_decode(file_get_contents('php://input'), true);
    if(!$p) throwMiExcepcion("No se enviaron parámetros", "error", 200);

    if(trim($p['descripcion']) == "") throwMiExcepcion("Descripción requerida", "warning", 200);
    
    // Comprobacion de duplicados
    $exclude = ["id" => $p['id']];
    $count = Categorias::countRecordsBy(["descripcion" => $p['descripcion']], $exclude);
    if($count) throwMiExcepcion("La descripción: " . $p['descripcion'] . ", ya existe!", "warning");

    $params = [
      "descripcion" => trimSpaces($p['descripcion']),
      "padre_id" => $p['padre_id'] ? $p['padre_id'] : 0,
      "id" => $p['id'],
    ];

    $resp = Categorias::updateCategoria( $params );
    if(!$resp) throwMiExcepcion("Ningún registro modificado", "warning", 200);
    $_SERVER["REQUEST_METHOD"] = "POST";
    $categorias_tree = generateTree($this->get_categorias()['content']);

    $response['msgType'] = "success";
    $response['msg'] = "Registro actualizado";
    $response['content'] = $categorias_tree;
    return $response;
  }

  public function delete_categoria()
  {
    if($_SERVER['REQUEST_METHOD'] != 'DELETE') throwMiExcepcion("Método no permitido", "error", 200);

    $p = json_decode(file_get_contents('php://input'), true);
    if(!$p) throwMiExcepcion("No se enviaron parámetros", "error", 200);

    // Contar hijos
    $count = Categorias::countRecordsBy(["padre_id" => $p['id']]);
    if($count) throwMiExcepcion("La categoria a eliminar no debe tener hijos", "warning");

    $params = [
      "id" => $p['id'],
    ];
    $resp = Categorias::deleteCategoria( $params );
    if(!$resp) throwMiExcepcion("Ningún registro eliminado", "warning");

    $_SERVER["REQUEST_METHOD"] = "POST";
    $categorias_tree = generateTree($this->get_categorias()['content']);

    $response['msgType'] = "success";
    $response['msg'] = "Registro eliminado";
    $response['content'] = $categorias_tree;
    return $response;
  }
}

