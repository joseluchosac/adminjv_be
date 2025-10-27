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
      "per_page" => $p['per_page']
    ];

    $where = MyORM::getWhere($p);
    $orderBy = MyORM::getOrder([
      ["field_name" => "orden", "order_dir" => "ASC"],
      ["field_name" => "departamento", "order_dir" => "ASC"],
      ["field_name" => "provincia", "order_dir" => "ASC"],
      ["field_name" => "distrito", "order_dir" => "ASC"],
    ]);

    $res = Ubigeos::filterUbigeos($campos, $where, $orderBy, $pagination);
    return $res;
  }

  public function get_departamentos()
  {
    $res = Ubigeos::getDepartamentos();
    return $res;
  }

  public function get_provincias()
  {
    $departamento = $_GET["departamento"] ?? "";
    $res = Ubigeos::getProvincias($departamento);
    return $res;
  }
  public function get_distritos()
  {
    $departamento = $_GET["departamento"] ?? "";
    $provincia = $_GET["provincia"] ?? "";
    $res = Ubigeos::getDistritos($departamento, $provincia);
    return $res;
  }

  public function get_ubigeos_options(){
    $departamento = $_GET["departamento"] ?? "";
    $provincia = $_GET["provincia"] ?? "";
    $res['provincias'] = Ubigeos::getProvincias($departamento);
    $res['distritos'] = Ubigeos::getDistritos($departamento, $provincia);
    return $res;
  }
}
