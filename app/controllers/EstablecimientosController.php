<?php
require_once('../../app/models/Establecimientos.php');
class EstablecimientosController
{
    public function filter_sucursales($isPaginated = true)
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $pJson = json_decode(file_get_contents('php://input'), true);

    $campos = [
      'id',
      'codigo',
      'descripcion',
      'direccion',
      'ubigeo_inei',
      'dis_prov_dep',
      'telefono',
      'email',
      'estado',
    ];

    $search = $pJson['search'] ? "%" . $pJson['search'] . "%" : "";

    $paramWhere = [
      "paramLike" => ['descripcion' => $search],
      "paramEquals" => $pJson['equals'], // [["field_name" => "id", "field_value"=>1]] 
      "paramBetween" => [
        "campo" => $pJson['between']['field_name'],
        "rango" => $pJson['between']['range'] // "2024-12-18 00:00:00, 2024-12-19 23:59:59"
      ]
    ];

    $paramOrders = $pJson['orders'];

    $pagination = [
      "page" => $_GET["page"] ?? "1",
      "offset" => $pJson['offset']
    ];

    $res = Establecimientos::filterSucursales($campos, $paramWhere, $paramOrders, $pagination, $isPaginated);
    return $res;
  }
}