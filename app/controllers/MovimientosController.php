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
    $p = json_decode(file_get_contents('php://input'), true);

    $campos = [
      'id',
      'establecimiento_id',
      'establecimiento',
      'tipo',
      'concepto',
      'fecha',
      'numeracion',
      'tipo',
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
    $params = json_decode(file_get_contents('php://input'), true);

    if (!$params) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    // VALIDACIONES
    $this->validateCreateMovimiento($params);

    $mov1 = $this->prepareMovimiento($params);
    $mov2 = null;

    if(strtolower($params['concepto']) === "traspaso"){
      $params['establecimiento_id'] = $params['destino_id'];
      $params['tipo'] = 'entrada';
      $mov2 = $this->prepareMovimiento($params);
    }

    Movimientos::createMovimiento($mov1, $mov2);
    $response['error'] = false;
    $response['msgType'] = "success";
    $response['msg'] = "Movimiento registrado";
    $response['content'] = null;
    return $response;
  }

  private function prepareMovimiento($params){

    $serie = Numeraciones::getSerie($params['serie_pre'], $params['establecimiento_id']);
    $serie = $serie['serie'];
  
    $fechaActual = date("Y-m-d");
    // OBTENIENDO CORRELATIVO
    $pwhereCorrelativo = [
      'establecimiento_id' => $params['establecimiento_id'],
      'serie' => $serie,
    ];
    $correlativo = Numeraciones::getCorrelativo($pwhereCorrelativo);

    // PARAMETROS CAMPOS MOVIMIENTOS
    $movimiento = [
      "establecimiento_id" => $params['establecimiento_id'],
      "tipo" => $params['tipo'], // tras
      "concepto" => $params['concepto'],
      "fecha" => $fechaActual,
      "numeracion" => $serie . "-" . $correlativo, // tras
      "observacion" => $params['observacion'],
    ];

    // PARAMETROS CAMPOS MOVIMIENTOS_DETALLE
    $movimientoDetalle = [];
    foreach ($params['detalle'] as $value) {
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
    $inventarios = [];
    $stocks = [];
    foreach ($params['detalle'] as $value) {
      $ultimoInventario = Inventarios::getUltimoInventario($params['establecimiento_id'], $value['producto_id']); // tras
      $exUnidadesAnterior = $ultimoInventario ? $ultimoInventario['ex_unidades'] : 0;
      $exCostoTotalAnterior = $ultimoInventario ? $ultimoInventario['ex_costo_total']:0;
      $costoUnitario = $value['precio_costo'];
      $tipo = $params['tipo'];
      $inUnidades = $tipo == "entrada" ? floatval($value['cantidad']) : 0;
      $outUnidades = $tipo == "salida" ? floatval($value['cantidad']) : 0;
      $exUnidades = $exUnidadesAnterior + $inUnidades - $outUnidades;
      $exCostoTotal = $exCostoTotalAnterior + ($inUnidades * $costoUnitario) - ($outUnidades * $costoUnitario);
      $inventario = [
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

      $stock = [
        'establecimiento_id' => $params['establecimiento_id'],
        'producto_id' => $value['producto_id'],
        'stock' => $exUnidades,
      ];

      array_push($stocks, $stock);
      array_push($inventarios, $inventario);
    }

    // PARAMETROS PARA STOCK

    return [
      'movimiento' => $movimiento,
      'movimientoDetalle' => $movimientoDetalle,
      'inventarios' => $inventarios,
      'stocks' => $stocks,
    ];
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
