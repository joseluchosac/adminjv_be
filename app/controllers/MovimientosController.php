<?php
require_once('../../app/models/Movimientos.php');
require_once('../../app/models/Numeraciones.php');
require_once('../../app/models/Inventarios.php');

use Valitron\Validator;

class MovimientosController
{
  public function filter_movimientos($isPaginated = true)
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $pJson = json_decode(file_get_contents('php://input'), true);

    $campos = [
      'id',
      'establecimiento_id',
      'tipo',
      'concepto',
      'fecha',
      // 'serie',
      // 'correlativo',
      'numeracion',
      'tipo',
      'observacion',
      'user_id',
      'estado',
      'created_at',
      'updated_at',
    ];

    $search = $pJson['search'] ? "%" . $pJson['search'] . "%" : "";

    $paramWhere = [
      "paramLike" => [
        'fecha' => $search, 
        'numeracion' => $search, 
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
  
    $res = Movimientos::filterMovimientos($campos, $paramWhere, $paramOrders, $pagination, $isPaginated);
    return $res;
  }

  public function create_movimiento()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 200);

    $params = json_decode(file_get_contents('php://input'), true);
    if (!$params) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    $serie = "M001";
    $fechaActual = date("Y-m-d");
    // Obteniendo el correlativo
    $paramWhereCorrelativo = [
      'establecimiento_id' => $params['establecimiento_id'],
      'serie' => $serie,
    ];
    $correlativo = Numeraciones::getCorrelativo($paramWhereCorrelativo);

    // Generandon parametros para insertar en movimientos
    $paramMovimiento = [
      "establecimiento_id" => $params['establecimiento_id'],
      "tipo" => $params['tipo'],
      "concepto" => $params['concepto'],
      "fecha" => $fechaActual,
      "numeracion" => $serie . "-" . $correlativo,
      "observacion" => $params['observacion'],
    ];

    // Generando parametros para insertar en movimientos_detalle
    $paramMovimientoDetalle = [];
    foreach ($params['detalle'] as $value) {
      $item = [
        'movimiento_id' => 0, // Se actualizara despues
        'producto_id' => $value['producto_id'],
        'producto_descripcion' => $value['producto_descripcion'],
        'cantidad' => $value['cantidad'],
        'observacion' => $value['observacion'],
      ];
      array_push($paramMovimientoDetalle, $item);
    }

    // Generando parametros para insertar en inventarios
    $paramInventarios = [];
    foreach ($params['detalle'] as $value) {
      $ultimoInventario = Inventarios::getUltimoInventario($params['establecimiento_id'], $value['producto_id']);
      $exUnidadesAnterior = $ultimoInventario ? $ultimoInventario['ex_unidades'] : 0;
      $exCostoTotalAnterior = $ultimoInventario ? $ultimoInventario['ex_costo_total']:0;
      $costoUnitario = $value['precio_costo'];
      $tipo = $params['tipo'];
      $inUnidades = $tipo == "entrada" ? floatval($value['cantidad']) : 0;
      $outUnidades = $tipo == "salida" ? floatval($value['cantidad']) : 0;
      $exUnidades = $exUnidadesAnterior + $inUnidades - $outUnidades;
      $exCostoTotal = $exCostoTotalAnterior + ($inUnidades * $costoUnitario) - ($outUnidades * $costoUnitario);
      $item = [
        'establecimiento_id' => $params['establecimiento_id'],
        'fecha' => $fechaActual,
        "numeracion" => $serie . "-" . $correlativo,
        "producto_id" => $value['producto_id'],
        "tipo_movimiento" => $tipo,
        "concepto_movimiento" => $params['concepto'],
        "in_unidades" => $inUnidades,
        "in_costo_unitario" => $tipo == "entrada" ? $costoUnitario : 0,
        "in_costo_total" => $inUnidades * $costoUnitario,
        "out_unidades" => $outUnidades,
        "out_costo_unitario" => $tipo == "salida" ? $costoUnitario : 0,
        "out_costo_total" => $outUnidades * $costoUnitario,
        "ex_unidades" => $exUnidades,
        "ex_costo_unitario" => round($exCostoTotal/$exUnidades,2),
        "ex_costo_total" => $exCostoTotal,
      ];
      array_push($paramInventarios, $item);
    }

    // Validacion
    // $this->validateCreateMovimiento($params);

    $lastId = Movimientos::createMovimiento($paramMovimiento, $paramMovimientoDetalle, $paramInventarios);
    if (!$lastId) throwMiExcepcion("Ningún registro guardado", "warning");
    // $registro = Movimientos::getMovimiento($lastId);
    $response['error'] = false;
    $response['msgType'] = "success";
    $response['msg'] = "Movimiento registrado";
    // $response['content'] = $registro;
    return $response;
  }

  // public function filter_movimientos_full() // sin paginacion
  // {
  //   $res =  self::filter_movimientos(false);
  //   unset($res["next"]);
  //   unset($res["offset"]);
  //   unset($res["page"]);
  //   unset($res["pages"]);
  //   unset($res["previous"]);
  //   return $res;
  // }

  // public function get_movimiento()
  // {
  //   if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
  //   $pJson = json_decode(file_get_contents('php://input'), true);
  //   if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);

  //   $registro = Movimientos::getMovimiento($pJson['id']);
  //   if (!$registro) throwMiExcepcion("No se encontró el registro", "error", 404);
  //   $temp = substr($registro["categoria_ids"], 1, -1);
  //   $temp = array_map(function($el){return intval($el);},explode(",", $temp));
  //   $registro["categoria_ids"] = array_filter($temp);
  //   $registro['inventariable'] = boolval($registro['inventariable']);
  //   $registro['lotizable'] = boolval($registro['lotizable']);
  //   $response["content"] = $registro;
  //   return $response;
  // }



  // public function update_movimiento()
  // {
  //   if ($_SERVER['REQUEST_METHOD'] != 'PUT') throwMiExcepcion("Método no permitido", "error", 405);

  //   $pJson = json_decode(file_get_contents('php://input'), true);
  //   if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 200);
  //   $codigo = trimSpaces($pJson['codigo']);
  //   $barcode = trimSpaces($pJson['barcode']);
  //   $categoria_ids = $pJson['categoria_ids'] ? ",".implode(",", $pJson['categoria_ids'])."," : "";
  //   $descripcion = trimSpaces($pJson['descripcion']);
  //   $paramCampos = [
  //     "codigo" => $codigo ? $codigo : null,
  //     "barcode" => $barcode ? $barcode : null,
  //     "categoria_ids" => $categoria_ids,
  //     "descripcion" => $descripcion,
  //     "marca_id" => $pJson['marca_id'],
  //     "laboratorio_id" => $pJson['laboratorio_id'],
  //     "unidad_medida_cod" => $pJson['unidad_medida_cod'],
  //     "tipo_moneda_cod" => $pJson['tipo_moneda_cod'],
  //     "precio_venta" => $pJson['precio_venta'],
  //     "precio_costo" => $pJson['precio_costo'],
  //     "impuesto_id_igv" => $pJson['impuesto_id_igv'],
  //     "impuesto_id_icbper" => $pJson['impuesto_id_icbper'],
  //     "inventariable" => $pJson['inventariable'],
  //     "lotizable" => $pJson['lotizable'],
  //     "stock" => $pJson['stock'],
  //     "stock_min" => $pJson['stock_min'],
  //     "imagen" => $pJson['imagen'],
  //     "estado" => $pJson['estado'],
  //   ];

  //   // Validacion
  //   // $this->validateUpdateMovimiento($paramCampos);

  //   // Buscando duplicados
  //   $exclude = ["id" => $pJson['id']];
  //   $count = Movimientos::countRecordsBy(["descripcion" => $descripcion], $exclude);
  //   if ($count) throwMiExcepcion("El usuario: " . $descripcion . ", ya existe!", "warning");

  //   $paramWhere = ["id" => $pJson['id']];

  //   $resp = Movimientos::updateMovimiento("movimientos", $paramCampos, $paramWhere);
  //   if (!$resp) throwMiExcepcion("Ningún registro modificado", "warning", 200);
    
  //   $registro = Movimientos::getMovimiento($pJson['id']);

  //   $response['msgType'] = "success";
  //   $response['msg'] = "Registro actualizado";
  //   $response['content'] = $registro;
  //   return $response;
  // }

  // public function update_estado(){
  //   if ($_SERVER['REQUEST_METHOD'] != 'PUT') throwMiExcepcion("Método no permitido", "error", 405);

  //   $pJson = json_decode(file_get_contents('php://input'), true);
  //   if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 200);

  //   $paramCampos = ["estado" => $pJson['estado']];
  //   $paramWhere = ["id" => $pJson['id']];

  //   $resp = Movimientos::updateMovimiento("movimientos", $paramCampos, $paramWhere);
  //   if (!$resp) throwMiExcepcion("Ningún registro modificado", "warning", 200);
    
  //   $registro = Movimientos::getMovimiento($pJson['id']);

  //   $response['msgType'] = "success";
  //   $response['msg'] = "Registro actualizado";
  //   $response['content'] = $registro;
  //   return $response; 
  // }

  // public function delete_movimiento()
  // {
  //   if ($_SERVER['REQUEST_METHOD'] != 'DELETE') throwMiExcepcion("Método no permitido", "error", 405);
  //   $pJson = json_decode(file_get_contents('php://input'), true);
  //   if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);

  //   $params = [
  //     "id" => $pJson['id'],
  //   ];
  //   $resp = Movimientos::deleteMovimiento($params);
  //   if (!$resp) throwMiExcepcion("Ningún registro eliminado", "warning");

  //   $response['content'] = null;
  //   $response['error'] = "false";
  //   $response['msgType'] = "success";
  //   $response['msg'] = "Registro eliminado";
  //   return $response;
  // }

  private function validateCreateMovimiento($params){
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
    $v->rule('required', 'movimientoname')->message('El usuario es requerido');
    $v->rule('lengthMin', 'movimientoname', 3)->message('El usuario debe tener al menos 3 caracteres.');
    $v->rule('lengthMax', 'movimientoname', 50)->message('El usuario no puede exceder los 50 caracteres.');
    $v->rule('sinEspacios', 'movimientoname')->message('El usuario no puede tener espacios');
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

  private function validateUpdateMovimiento($params){
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
