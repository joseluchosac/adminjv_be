<?php
require_once('../../app/models/Productos.php');
require_once('../../app/models/Inventarios.php');

use Valitron\Validator;

class ProductosController
{
  public function prueba(){

  }

  
  public function filter_productos()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 200);
    $p = json_decode(file_get_contents('php://input'), true);
    $campos = [
      'id',
      'codigo',
      'barcode',
      'descripcion',
      'marca_id',
      'marca',
      'laboratorio_id',
      'laboratorio',
      'stocks',
      'unidad_medida_cod',
      'estado'
    ];

    $p["search"] = [
      "fieldsName" => ["descripcion"],
      "like" => trim($p["search"])
    ];

    $pagination = [
      "page" => $_GET["page"] ?? "1",
      "per_page" => $p['per_page']
    ];

    $where = MyORM::getWhere($p);
    $orderBy = MyORM::getOrder($p["order"]);

    $res = Productos::filterProductos($campos, $where, $orderBy, $pagination);
    return $res;
  }

  public function get_producto()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    $registro = Productos::getProducto($p['id']);
    if (!$registro) throwMiExcepcion("No se encontró el registro", "error", 404);
    $temp = substr($registro["categoria_ids"], 1, -1);
    $temp = array_map(function($el){return intval($el);},explode(",", $temp));
    $registro["categoria_ids"] = array_filter($temp);
    $registro['inventariable'] = boolval($registro['inventariable']);
    $registro['lotizable'] = boolval($registro['lotizable']);
    $response["content"] = $registro;
    return $response;
  }

  public function get_producto_by_code()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);
    if (!$p['codigo']) throwMiExcepcion("Ingrese el código", "error", 404);

    $paramWhere = ["estado" => 1];
    // Verificando si se busca por codigo o barcode
    if (strpos($p['codigo'], 'P') === 0) {
      $paramWhere['codigo'] = $p['codigo'];
    }else{
      $paramWhere['barcode'] = $p['codigo'];
    }

    $registro = Productos::getProductoBy($paramWhere);
    if (!$registro) throwMiExcepcion("No se encontró el registro", "error", 200);

    // Obteniendo datos del producto del ultimo inventario del establecimiento
    if($p['establecimiento_id'] && $registro['inventariable']){
      $ultimoInventario = Inventarios::getUltimoInventario($p['establecimiento_id'], $registro['id']);
      $registro['stock'] = $ultimoInventario ? floatval($ultimoInventario['ex_unidades']) : 0;
      $registro['precio_costo'] = $ultimoInventario ? floatval($ultimoInventario['ex_costo_unitario']) : floatval($registro['precio_costo']);
    }
    $response["content"] = $registro;
    return $response;
  }

  public function create_producto()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 200);

    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);
    // var_dump($p);
    // exit();
    $codigo = trimSpaces($p['codigo']);
    $barcode = trimSpaces($p['barcode']);
    $categoria_ids = $p['categoria_ids'] ? ",".implode(",", $p['categoria_ids'])."," : "";
    $descripcion = trimSpaces($p['descripcion']);
    $params = [
      "codigo" => $codigo ? $codigo : null,
      "barcode" => $barcode ? $barcode : null,
      "categoria_ids" => $categoria_ids,
      "descripcion" => $descripcion,
      "marca_id" => $p['marca_id'],
      "laboratorio_id" => $p['laboratorio_id'],
      "unidad_medida_cod" => $p['unidad_medida_cod'],
      "tipo_moneda_cod" => $p['tipo_moneda_cod'],
      "precio_venta" => $p['precio_venta'],
      "precio_costo" => $p['precio_costo'],
      "impuesto_id_igv" => $p['impuesto_id_igv'],
      "impuesto_id_icbper" => $p['impuesto_id_icbper'],
      "inventariable" => $p['inventariable'],
      "lotizable" => $p['lotizable'],
      "stock_min" => $p['stock_min'],
      "thumb" => $p['thumb'],
    ];
    // Validacion
    // $this->validateCreateProducto($params);

    // Buscando duplicados
    $count = Productos::countRecordsBy(["descripcion" => $descripcion]);
    if ($count) throwMiExcepcion("El producto: " . $descripcion . ", ya existe!", "warning");

    $lastId = Productos::createProducto($params);
    if (!$lastId) throwMiExcepcion("Ningún registro guardado", "warning");
    $registro = Productos::getProducto($lastId);
    $response['error'] = false;
    $response['msgType'] = "success";
    $response['msg'] = "Producto registrado";
    $response['content'] = $registro;
    return $response;
  }

  public function update_producto()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'PUT') throwMiExcepcion("Método no permitido", "error", 405);

    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 200);
    $codigo = trimSpaces($p['codigo']);
    $barcode = trimSpaces($p['barcode']);
    $categoria_ids = $p['categoria_ids'] ? ",".implode(",", $p['categoria_ids'])."," : "";
    $descripcion = trimSpaces($p['descripcion']);
    $paramCampos = [
      "codigo" => $codigo ? $codigo : null,
      "barcode" => $barcode ? $barcode : null,
      "categoria_ids" => $categoria_ids,
      "descripcion" => $descripcion,
      "marca_id" => $p['marca_id'],
      "laboratorio_id" => $p['laboratorio_id'],
      "unidad_medida_cod" => $p['unidad_medida_cod'],
      "tipo_moneda_cod" => $p['tipo_moneda_cod'],
      "precio_venta" => $p['precio_venta'],
      "precio_costo" => $p['precio_costo'],
      "impuesto_id_igv" => $p['impuesto_id_igv'],
      "impuesto_id_icbper" => $p['impuesto_id_icbper'],
      "inventariable" => $p['inventariable'],
      "lotizable" => $p['lotizable'],
      "stock_min" => $p['stock_min'],
      "thumb" => $p['thumb'],
      "estado" => $p['estado'],
    ];

    // Validacion
    // $this->validateUpdateProducto($paramCampos);

    // Buscando duplicados
    $exclude = ["id" => $p['id']];
    $count = Productos::countRecordsBy(["descripcion" => $descripcion], $exclude);
    if ($count) throwMiExcepcion("El usuario: " . $descripcion . ", ya existe!", "warning");

    $paramWhere = ["id" => $p['id']];

    $resp = Productos::updateProducto("productos", $paramCampos, $paramWhere);
    if (!$resp) throwMiExcepcion("Ningún registro modificado", "warning", 200);
    
    $registro = Productos::getProducto($p['id']);

    $response['msgType'] = "success";
    $response['msg'] = "Registro actualizado";
    $response['content'] = $registro;
    return $response;
  }

  public function update_estado(){
    if ($_SERVER['REQUEST_METHOD'] != 'PUT') throwMiExcepcion("Método no permitido", "error", 405);

    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 200);

    $paramCampos = ["estado" => $p['estado']];
    $paramWhere = ["id" => $p['id']];

    $resp = Productos::updateProducto("productos", $paramCampos, $paramWhere);
    if (!$resp) throwMiExcepcion("Ningún registro modificado", "warning", 200);
    
    $registro = Productos::getProducto($p['id']);

    $response['msgType'] = "success";
    $response['msg'] = "Registro actualizado";
    $response['content'] = $registro;
    return $response; 
  }

  public function delete_producto()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'DELETE') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    $params = [
      "id" => $p['id'],
    ];
    $resp = Productos::deleteProducto($params);
    if (!$resp) throwMiExcepcion("Ningún registro eliminado", "warning");

    $response['content'] = null;
    $response['error'] = "false";
    $response['msgType'] = "success";
    $response['msg'] = "Registro eliminado";
    return $response;
  }

  // private function validateCreateProducto($params){
  //   $v = new Validator($params);
  //   $v->addRule('iguales', function ($field, $value, array $params, array $fields) {
  //     return $fields['password'] === $fields["password_repeat"];
  //   });
  //   $v->addRule('sinEspacios', function ($field, $value, array $params, array $fields) {
  //     return strpos($value, ' ') === false; // Verificar que no haya espacios en el valor
  //   });
  //   $v->rule('required', 'nombres')->message('El nombre es requerido');
  //   $v->rule('lengthMin', 'nombres', 3)->message('El nombre debe tener al menos 3 caracteres.');
  //   $v->rule('lengthMax', 'nombres', 50)->message('El nombre no puede exceder los 50 caracteres.');
  //   $v->rule('required', 'apellidos')->message('Los apellidos son requeridos');
  //   $v->rule('lengthMin', 'apellidos', 3)->message('Los apellidos deben tener al menos 3 caracteres.');
  //   $v->rule('lengthMax', 'apellidos', 50)->message('Los apellidos no puede exceder los 50 caracteres.');
  //   $v->rule('required', 'productoname')->message('El usuario es requerido');
  //   $v->rule('lengthMin', 'productoname', 3)->message('El usuario debe tener al menos 3 caracteres.');
  //   $v->rule('lengthMax', 'productoname', 50)->message('El usuario no puede exceder los 50 caracteres.');
  //   $v->rule('sinEspacios', 'productoname')->message('El usuario no puede tener espacios');
  //   $v->rule('email', 'email')->message('Ingrese un formato de email válido');
  //   $v->rule('required', 'password')->message('La contraseña es obligatoria');
  //   $v->rule('regex', 'password', '/^[A-Za-z\d@$!%*?&]{6,}$/')->message('La contraseña debe tener al menos 6 caracteres, sin espacios');
  //   $v->rule('iguales', 'password')->message('Los passwords no son iguales');;
  //   if (!$v->validate()) {
  //     foreach ($v->errors() as $campo => $errores) {
  //       foreach ($errores as $error) {
  //         throwMiExcepcion($error, "warning", 200);
  //       }
  //     }
  //   }
  // }

  // private function validateUpdateProducto($params){
  //   $v = new Validator($params);
  //   $v->rule('required', 'nombres')->message('El nombre es requerido');
  //   $v->rule('lengthMin', 'nombres', 3)->message('El nombre debe tener al menos 3 caracteres.');
  //   $v->rule('lengthMax', 'nombres', 50)->message('El nombre no puede exceder los 50 caracteres.');
  //   $v->rule('required', 'apellidos')->message('Los apellidos son requeridos');
  //   $v->rule('lengthMin', 'apellidos', 3)->message('Los apellidos deben tener al menos 3 caracteres.');
  //   $v->rule('lengthMax', 'apellidos', 50)->message('Los apellidos no puede exceder los 50 caracteres.');
  //   if (!$v->validate()) {
  //     foreach ($v->errors() as $campo => $errores) {
  //       foreach ($errores as $error) {
  //         throwMiExcepcion($error, "warning", 200);
  //       }
  //     }
  //   }
  // }

}
