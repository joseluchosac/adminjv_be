<?php
require_once('../../app/models/Laboratorios.php');
require_once('../../app/models/Productos.php');
require_once('../../app/models/Proveedores.php');

class TestController
{
  public function test(){
    $p = json_decode(file_get_contents('php://input'), true);
    // $campos = ["tipo_documento_cod"=>"1", "nro_documento"=> "20604998396"];
    $data = Proveedores::getProveedoresBy($p);
    if(!$data) throwMiExcepcion("no se encontraron registros", "warning", 200);
    return $data;
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
  
    $res = Productos::filterProductos($campos, $paramWhere, $paramOrders, $pagination, $isPaginated);
    // print_r($res);
    return $res;
  }

}
