<?php
require_once('../../app/models/Laboratorios.php');
require_once('../../app/models/Productos.php');
require_once('../../app/models/Proveedores.php');
require_once('../../app/models/Test.php');

class TestController
{
  public function test(){

    $prev1 = '';
    $prev2 = '[{"e":1,"s":155}]';
    $prev3 = '[{"e":1,"s":100},{"e":3,"s":300}]';
    $esId = 3;
    $stock = 20;

    function up($prevJson, $establecimientoId, $stock){
      $currentStock = ["e"=>$establecimientoId, "s"=>$stock];
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
      if($idx === null){// Insertar
        $prevStocks[] = $currentStock;
      }else{// Actualizar
        $prevStocks[$idx] = $currentStock;
      }
      return json_encode($prevStocks);
    }

    $json = up($prev1,$esId,$stock);
    var_dump($json);
    echo "<br>";
    // print_r(json_decode($prev2, true));
    echo "<br>";
  }
  public function filter_laboratorios($isPaginated = true)
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);

    $campos = [
      'id',
      'nombre',
      'estado',
    ];

    $search = $p['search'] ? "%" . $p['search'] . "%" : "";

    $paramWhere = [
      "paramLike" => ['nombre' => $search],
      "paramEquals" => $p['equals'], // [["field_name" => "id", "field_value"=>1]] 
      "paramBetween" => [
        "campo" => $p['between']['field_name'],
        "rango" => $p['between']['range'] // "2024-12-18 00:00:00, 2024-12-19 23:59:59"
      ]
    ];

    $paramOrders = $p['orders'];

    $pagination = [
      "page" => $_GET["page"] ?? "1",
      "offset" => $p['offset']
    ];

    $res = Laboratorios::filterLaboratorios($campos, $paramWhere, $paramOrders, $pagination, $isPaginated);
    return $res;
  }

    public function filter_productos($isPaginated = true)
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);

    $campos = [
      'id',
      'establecimiento_id',
      'codigo',
      'barcode',
      'descripcion',
      'marca_id',
      'marca',
      'laboratorio_id',
      'laboratorio',
      'stock',
      'unidad',
      'estado',
      'created_at',
      'updated_at',
    ];

    $search = $p['search'] ? "%" . $p['search'] . "%" : "";


    $paramWhere = [
      "paramLike" => [
        'descripcion' => $search, 
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
  
    $inicio = microtime(true);
    $res = Test::filterProductos($campos, $paramWhere, $paramOrders, $pagination, $isPaginated);
    $fin = microtime(true);
    $tiempo_transcurrido = $fin - $inicio;
    $res['tiempo'] = "Tiempo de ejecución de la consulta: " . $tiempo_transcurrido . " segundos";

    // print_r($res);
    return $res;
  }

}
