<?php
require_once('../../app/models/Movimientos.php');
require_once('../../app/models/Numeraciones.php');
require_once('../../app/models/Inventarios.php');
require_once('../../app/models/Productos.php');

use Valitron\Validator;

class MovimientosController
{
  public function filter_movimientos($isPaginated = true)
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);

    $campos = [
      'id',
      'establecimiento_id',
      'establecimiento',
      'tipo',
      'concepto',
      'fecha',
      'numeracion',
      'estado',
      'created_at',
    ];

    $search = $p['search'] ? "%" . $p['search'] . "%" : "";

    $paramWhere = [
      "paramLike" => [
        'fecha' => $search, 
        'numeracion' => $search, 
      ],
      "paramEquals" => $p['equals'], // [["field_name" => "id", "field_value"=>1]] 
      "paramBetween" => [
        "campo" => $p['between']['field_name'],
        "rango" => $p['between']['range'] // "2024-12-18 00:00:00, 2024-12-19 23:59:59"
      ]
    ];

    // $paramOrders = $p['orders'];
    $paramOrders = count($p['orders']) 
      ? $p['orders'] 
      : [["field_name"=>"id","order_dir"=>"DESC", "text" => "Id"]];
      
    $pagination = [
      "page" => $_GET["page"] ?? "1",
      "offset" => $p['offset']
    ];
  
    $res = Movimientos::filterMovimientos($campos, $paramWhere, $paramOrders, $pagination, $isPaginated);
    return $res;
  }

  public function create_movimiento()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 400);
    $p = json_decode(file_get_contents('php://input'), true);

    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    // VALIDACIONES
    $this->validateCreateMovimiento($p);


    $mov1 = $this->prepareMovimiento($p);
    $mov2 = null; // $mov2 solo para ingreso en traspasos

    if(strtolower($p['concepto']) === "traspaso"){ // Si es traspaso
      $p['establecimiento_id'] = $p['destino_id'];
      $p['tipo'] = 'entrada';
      $mov2 = $this->prepareMovimiento($p);
    }

    // print_r($mov1);
    // print_r($mov2);
    // exit();
    Movimientos::createMovimiento($mov1, $mov2);

    $response['error'] = false;
    $response['msgType'] = "success";
    $response['msg'] = "Movimiento registrado";
    $response['content'] = null;
    return $response;
  }

  private function prepareMovimiento($p){
    $serie = Numeraciones::getSerie($p['serie_pre'], $p['establecimiento_id']);
    $serie = $serie['serie'];
  
    $fechaActual = date("Y-m-d");
    // OBTENIENDO CORRELATIVO
    $pwhereCorrelativo = [
      'establecimiento_id' => $p['establecimiento_id'],
      'serie' => $serie,
    ];
    $correlativo = Numeraciones::getCorrelativo($pwhereCorrelativo);

    // PARAMETROS CAMPOS MOVIMIENTOS, INSERT
    $movimiento = [
      "establecimiento_id" => $p['establecimiento_id'],
      "tipo" => $p['tipo'], // tras
      "concepto" => $p['concepto'],
      "fecha" => $fechaActual,
      "numeracion" => $serie . "-" . $correlativo, // tras
      "observacion" => $p['observacion'],
    ];

    // PARAMETROS CAMPOS MOVIMIENTOS_DETALLE, INSERT
    $movimientoDetalle = [];
    foreach ($p['detalle'] as $value) {
      $itemDetalle = [
        'movimiento_id' => 0, // Se actualizara despues
        'producto_id' => $value['producto_id'],
        'producto_descripcion' => $value['producto_descripcion'],
        'cantidad' => $value['cantidad'],
        'observacion' => $value['observacion'],
      ];
      array_push($movimientoDetalle, $itemDetalle);
    }

    // PARAMETROS CAMPOS INVENTARIOS y STOCKS
    $inventarios = []; // Insert
    $productos = []; // Update
    $stocks = []; // Insert o update
    foreach ($p['detalle'] as $el) {
      $ultimoInventario = Inventarios::getUltimoInventario($p['establecimiento_id'], $el['producto_id']); // tras
      $exUnidadesAnterior = $ultimoInventario ? $ultimoInventario['ex_unidades'] : 0;
      $exCostoTotalAnterior = $ultimoInventario ? $ultimoInventario['ex_costo_total']:0;
      $costoUnitario = floatval($el['precio_costo']);
      $tipo = $p['tipo'];
      $inUnidades = $tipo == "entrada" ? floatval($el['cantidad']) : 0; // Ingreso productos
      $outUnidades = $tipo == "salida" ? floatval($el['cantidad']) : 0; // Salida productos
      $exUnidades = floatval($exUnidadesAnterior) + $inUnidades - floatval($outUnidades); // nuevo stock
      $exCostoTotal = floatval($exCostoTotalAnterior) + ($inUnidades * $costoUnitario) - ($outUnidades * $costoUnitario);
      // PARAMETROS PARA AGREGAR EN LA TABLA INVENTARIOS
      $inventario = [
        'establecimiento_id' => $p['establecimiento_id'],
        'fecha' => $fechaActual,
        "numeracion" => $serie . "-" . $correlativo,
        "producto_id" => $el['producto_id'],
        "tipo_movimiento" => $tipo,
        "concepto_movimiento" => $p['concepto'],
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
      // PARAMETROS PARA ACTUALIZAR CAMPO STOCKS DE CADA PRODUCTO EN LA TABLA PRODUCTOS
      // Si el movimiento del producto no es traspaso y entrada para
      // que no se envie los parametros en $mov2 y no se ejecute 2 veces en caso de traspaso
      if(!(strtolower($p['concepto']) === "traspaso" && $p['tipo'] === 'entrada')){
        $prevStocksJson = Productos::getStocks($el['producto_id']); // stocks actual
        $newsStocksJson = $this::updateStocksJson($prevStocksJson, $p['establecimiento_id'], $exUnidades);
        // Si el concepto es traspaso se vuelve a acualizar $newsStocksJson para actualizar el stock del destino
        if(strtolower($p['concepto']) === "traspaso"){
          $ultimoInventarioDest = Inventarios::getUltimoInventario($p['destino_id'], $el['producto_id']);
          $inUnidades = floatval($el['cantidad']); // Ingreso productos
          $exUnidadesAnteriorDes = $ultimoInventarioDest ? $ultimoInventarioDest['ex_unidades'] : 0;
          $exUnidadesDes = floatval($exUnidadesAnteriorDes) + $inUnidades; // nuevo stock para el destino
          $newsStocksJson = $this::updateStocksJson($newsStocksJson, $p['destino_id'], $exUnidadesDes);
        }
        $producto = [
          'id' => $el['producto_id'],
          'stocks' => $newsStocksJson,
        ];
        array_push($productos, $producto);
      }
      // PARAMETROS PARA AGREGAR O ACTUALIZAR TABLA STOCK
      $stock = [
        'establecimiento_id' => $p['establecimiento_id'],
        'producto_id' => $el['producto_id'],
        'stock' => $exUnidades,
      ];

      array_push($inventarios, $inventario);
      array_push($stocks, $stock);
    }

    return [
      'movimiento' => $movimiento,
      'movimientoDetalle' => $movimientoDetalle,
      'inventarios' => $inventarios,
      'productos' => $productos,
      'stocks' => $stocks,
    ];
  }

  // Devuelve la actualizacion del valor del capo stocks de la tabla productos
  private function updateStocksJson($prevJson, $establecimientoId, $nuevoStock){
    $currentStock = ["e"=>$establecimientoId, "s"=>$nuevoStock];
    if(!$prevJson){
      return json_encode([$currentStock]);
    }
    $prevStocks = json_decode($prevJson, true);
    // Verificar si existe el establecimiento en el previo
    $idx = null;
    foreach ($prevStocks as $indice => $el) {
      if ($el['e'] == $establecimientoId) {
        $idx = $indice;
        break;
      }
    }
    if($idx === null){// Inserta
      $prevStocks[] = $currentStock;
    }else{// Actualiza
      $prevStocks[$idx] = $currentStock;
    }
    return json_encode($prevStocks);
  }

  private function validateCreateMovimiento($params){
    if(!$params['detalle']) throwMiExcepcion("Falta el detalle del movimiento", "warning", 200);
    $v = new Validator($params);
    $v->rule('required', 'establecimiento_id')->message('Ingrese el establecimiento');
    $v->rule('required', 'tipo')->message('Ingrese el tipo de movimiento');
    $v->rule('required', 'concepto')->message('Ingrese el concepto de movimiento');
    if(strtolower($params['concepto']) === "traspaso"){
      $v->rule('notIn', 'destino_id', [0])->message('Ingrese el destino del traspaso'); // si el destino trae el valor de 0
      $v->rule('different', 'establecimiento_id', 'destino_id')->message('Eliga otro destino');
    }
    if (!$v->validate()) {
      foreach ($v->errors() as $campo => $errores) {
        foreach ($errores as $error) {
          throwMiExcepcion($error, "warning", 200);
        }
      }
    }
    // Validacion del detalle
    foreach($params['detalle'] as $value){
      if(!$value["producto_descripcion"]) throwMiExcepcion("Ingrese la descripcion del producto","warning", 200);
      if($value["cantidad"] < 0.10) throwMiExcepcion("Ingrese una cantidad válida del producto " . cropText($value["producto_descripcion"],30) ,"warning", 200);
      if($value["precio_costo"] < 0) throwMiExcepcion("Ingrese un precio válido del producto " . cropText($value["producto_descripcion"],30) ,"warning", 200);
    }

  }


}
