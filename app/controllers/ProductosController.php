<?php
// require_once('../../app/models/Config.php');
require_once('../../app/models/Productos.php');

use Valitron\Validator;

class ProductosController
{
  public function filter_productos($isPaginated = true)
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $pJson = json_decode(file_get_contents('php://input'), true);

    $campos = [
      'id',
      'codigo',
      'descripcion',
      'marca_id',
      'marca',
      'laboratorio_id',
      'laboratorio',
      'categoria_ids',
      'barcode',
      'precio_venta',
      'stock',
      'unidad_medida_cod',
      'estado',
      'created_at',
      'updated_at',
    ];

    $search = $pJson['search'] ? "%" . $pJson['search'] . "%" : "";

    $paramWhere = [
      "paramLike" => [
        'descripcion' => $search, 
      ],
      "paramEquals" => $pJson['equals'], // [["field_name" => "id", "field_value"=>1]] 
      "paramBetween" => [
        "campo" => $pJson['between']['field_name'],
        "rango" => $pJson['between']['range'] // "2024-12-18 00:00:00, 2024-12-19 23:59:59"
      ]
    ];

    // $paramOrders = $pJson['orders'];
    $paramOrders = count($pJson['orders']) 
      ? $pJson['orders'] 
      : [["field_name"=>"id","order_dir"=>"DESC", "text" => "Id"]];
      
    $pagination = [
      "page" => $_GET["page"] ?? "1",
      "offset" => $pJson['offset']
    ];
  
    $res = Productos::filterProductos($campos, $paramWhere, $paramOrders, $pagination, $isPaginated);
    // print_r($res);
    return $res;
  }

  public function filter_productos_full() // sin paginacion
  {
    $res =  self::filter_productos(false);
    unset($res["next"]);
    unset($res["offset"]);
    unset($res["page"]);
    unset($res["pages"]);
    unset($res["previous"]);
    return $res;
  }

  public function get_producto()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    $registro = Productos::getProducto($pJson['id']);
    if (!$registro) throwMiExcepcion("No se encontró el registro", "error", 404);
    $temp = substr($registro["categoria_ids"], 1, -1);
    $temp = array_map(function($el){return intval($el);},explode(",", $temp));
    $registro["categoria_ids"] = array_filter($temp);
    // $registro['inventariable'] = $registro['inventariable'] ? true : false;
    $response["content"] = $registro;
    return $response;
  }

  public function create_producto()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 200);

    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);
    // var_dump($pJson);
    // exit();
    $codigo = trimSpaces($pJson['codigo']);
    $barcode = trimSpaces($pJson['barcode']);
    $categoria_ids = $pJson['categoria_ids'] ? ",".implode(",", $pJson['categoria_ids'])."," : "";
    $descripcion = trimSpaces($pJson['descripcion']);
    $params = [
      "codigo" => $codigo ? $codigo : null,
      "barcode" => $barcode ? $barcode : null,
      "categoria_ids" => $categoria_ids,
      "descripcion" => $descripcion,
      "marca_id" => $pJson['marca_id'],
      "laboratorio_id" => $pJson['laboratorio_id'],
      "unidad_medida_cod" => $pJson['unidad_medida_cod'],
      "tipo_moneda_cod" => $pJson['tipo_moneda_cod'],
      "precio_venta" => $pJson['precio_venta'],
      "precio_costo" => $pJson['precio_costo'],
      "impuesto_id_igv" => $pJson['impuesto_id_igv'],
      "impuesto_id_icbper" => $pJson['impuesto_id_icbper'],
      "inventariable" => $pJson['inventariable'],
      "lotizable" => $pJson['lotizable'],
      "stock" => $pJson['stock'],
      "stock_min" => $pJson['stock_min'],
      "imagen" => $pJson['imagen'],
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

    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 200);
    $codigo = trimSpaces($pJson['codigo']);
    $barcode = trimSpaces($pJson['barcode']);
    $categoria_ids = $pJson['categoria_ids'] ? ",".implode(",", $pJson['categoria_ids'])."," : "";
    $descripcion = trimSpaces($pJson['descripcion']);
    $paramCampos = [
      "codigo" => $codigo ? $codigo : null,
      "barcode" => $barcode ? $barcode : null,
      "categoria_ids" => $categoria_ids,
      "descripcion" => $descripcion,
      "marca_id" => $pJson['marca_id'],
      "laboratorio_id" => $pJson['laboratorio_id'],
      "unidad_medida_cod" => $pJson['unidad_medida_cod'],
      "tipo_moneda_cod" => $pJson['tipo_moneda_cod'],
      "precio_venta" => $pJson['precio_venta'],
      "precio_costo" => $pJson['precio_costo'],
      "impuesto_id_igv" => $pJson['impuesto_id_igv'],
      "impuesto_id_icbper" => $pJson['impuesto_id_icbper'],
      "inventariable" => $pJson['inventariable'],
      "lotizable" => $pJson['lotizable'],
      "stock" => $pJson['stock'],
      "stock_min" => $pJson['stock_min'],
      "imagen" => $pJson['imagen'],
      "estado" => $pJson['estado'],
    ];

    // Validacion
    // $this->validateUpdateProducto($paramCampos);

    // Buscando duplicados
    $exclude = ["id" => $pJson['id']];
    $count = Productos::countRecordsBy(["descripcion" => $descripcion], $exclude);
    if ($count) throwMiExcepcion("El usuario: " . $descripcion . ", ya existe!", "warning");

    $paramWhere = ["id" => $pJson['id']];

    $resp = Productos::updateProducto("productos", $paramCampos, $paramWhere);
    if (!$resp) throwMiExcepcion("Ningún registro modificado", "warning", 200);
    
    $registro = Productos::getProducto($pJson['id']);

    $response['msgType'] = "success";
    $response['msg'] = "Registro actualizado";
    $response['content'] = $registro;
    return $response;
  }

  public function update_estado(){
    if ($_SERVER['REQUEST_METHOD'] != 'PUT') throwMiExcepcion("Método no permitido", "error", 405);

    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 200);

    $paramCampos = ["estado" => $pJson['estado']];
    $paramWhere = ["id" => $pJson['id']];

    $resp = Productos::updateProducto("productos", $paramCampos, $paramWhere);
    if (!$resp) throwMiExcepcion("Ningún registro modificado", "warning", 200);
    
    $registro = Productos::getProducto($pJson['id']);

    $response['msgType'] = "success";
    $response['msg'] = "Registro actualizado";
    $response['content'] = $registro;
    return $response; 
  }

  public function delete_producto()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'DELETE') throwMiExcepcion("Método no permitido", "error", 405);
    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    $params = [
      "id" => $pJson['id'],
    ];
    $resp = Productos::deleteProducto($params);
    if (!$resp) throwMiExcepcion("Ningún registro eliminado", "warning");

    $response['content'] = null;
    $response['error'] = "false";
    $response['msgType'] = "success";
    $response['msg'] = "Registro eliminado";
    return $response;
  }

  private function validateCreateProducto($params){
    $v = new Validator($params);
    $v->addRule('iguales', function ($field, $value, array $params, array $fields) {
      return $fields['password'] === $fields["password_repeat"];
    });
    $v->addRule('sinEspacios', function ($field, $value, array $params, array $fields) {
      return strpos($value, ' ') === false; // Verificar que no haya espacios en el valor
    });
    $v->rule('required', 'nombres')->message('El nombre es requerido');
    $v->rule('lengthMin', 'nombres', 3)->message('El nombre debe tener al menos 3 caracteres.');
    $v->rule('lengthMax', 'nombres', 50)->message('El nombre no puede exceder los 50 caracteres.');
    $v->rule('required', 'apellidos')->message('Los apellidos son requeridos');
    $v->rule('lengthMin', 'apellidos', 3)->message('Los apellidos deben tener al menos 3 caracteres.');
    $v->rule('lengthMax', 'apellidos', 50)->message('Los apellidos no puede exceder los 50 caracteres.');
    $v->rule('required', 'productoname')->message('El usuario es requerido');
    $v->rule('lengthMin', 'productoname', 3)->message('El usuario debe tener al menos 3 caracteres.');
    $v->rule('lengthMax', 'productoname', 50)->message('El usuario no puede exceder los 50 caracteres.');
    $v->rule('sinEspacios', 'productoname')->message('El usuario no puede tener espacios');
    $v->rule('email', 'email')->message('Ingrese un formato de email válido');
    $v->rule('required', 'password')->message('La contraseña es obligatoria');
    $v->rule('regex', 'password', '/^[A-Za-z\d@$!%*?&]{6,}$/')->message('La contraseña debe tener al menos 6 caracteres, sin espacios');
    $v->rule('iguales', 'password')->message('Los passwords no son iguales');;
    if (!$v->validate()) {
      foreach ($v->errors() as $campo => $errores) {
        foreach ($errores as $error) {
          throwMiExcepcion($error, "warning", 200);
        }
      }
    }
  }

  private function validateUpdateProducto($params){
    $v = new Validator($params);
    $v->rule('required', 'nombres')->message('El nombre es requerido');
    $v->rule('lengthMin', 'nombres', 3)->message('El nombre debe tener al menos 3 caracteres.');
    $v->rule('lengthMax', 'nombres', 50)->message('El nombre no puede exceder los 50 caracteres.');
    $v->rule('required', 'apellidos')->message('Los apellidos son requeridos');
    $v->rule('lengthMin', 'apellidos', 3)->message('Los apellidos deben tener al menos 3 caracteres.');
    $v->rule('lengthMax', 'apellidos', 50)->message('Los apellidos no puede exceder los 50 caracteres.');
    if (!$v->validate()) {
      foreach ($v->errors() as $campo => $errores) {
        foreach ($errores as $error) {
          throwMiExcepcion($error, "warning", 200);
        }
      }
    }
  }

}
