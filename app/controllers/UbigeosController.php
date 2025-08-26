<?php
require_once('../../app/models/Ubigeos.php');

class UbigeosController
{
  public function filter_ubigeos($isPaginated = true)
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);

    $campos = [
      'ubigeo_inei',
      'ubigeo_reniec',
      'dis_prov_dep',
    ];

    $search = $p['search'] ? "%" . $p['search'] . "%" : "";

    $paramWhere = [
      "paramLike" => [
        'ubigeo_inei' => $search,
        // 'ubigeo_reniec' => $search,
        'dis_prov_dep' => $search,
      ],
      "paramEquals" => $p['equals'], // [["field_name" => "id", "field_value"=>1]] 
      "paramBetween" => [
        "campo" => $p['between']['field_name'],
        "rango" => $p['between']['range'] // "2024-12-18 00:00:00, 2024-12-19 23:59:59"
      ]
    ];

    $paramOrders = count($p['orders']) 
      ? $p['orders'] 
      : [
          ["field_name"=>"orden","order_dir"=>"ASC", "text" => "orden"],
          ["field_name"=>"dep_prov_dis","order_dir"=>"ASC", "text" => "dep_prov_dis"],
        ];
    // $paramOrders = $p['orders'];

    $pagination = [
      "page" => $_GET["page"] ?? "1",
      "offset" => $p['offset']
    ];

    $res = Ubigeos::filterUbigeos($campos, $paramWhere, $paramOrders, $pagination, $isPaginated);
    return $res;
  }
}
