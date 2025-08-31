<?php
require_once('../../app/models/Ubigeos.php');

class UbigeosController
{

  public function filter_ubigeos()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 200);
    $p = json_decode(file_get_contents('php://input'), true);
    $campos = [
      'ubigeo_inei',
      'ubigeo_reniec',
      'dis_prov_dep',
    ];

    $p["search"] = [
      "fieldsName" => ["ubigeo_inei", "dis_prov_dep"],
      "like" => trim($p["search"])
    ];

    $pagination = [
      "page" => $_GET["page"] ?? "1",
      "offset" => $p['offset']
    ];

    $where = MyORM::getWhere($p);
    $orderBy = MyORM::getOrder([["field_name" => "orden", "order_dir" => "ASC"]]);

    $res = Ubigeos::filterUbigeos($campos, $where, $orderBy, $pagination);
    return $res;
  }
}
