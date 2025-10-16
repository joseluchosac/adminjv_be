<?php
require_once('../../app/models/Laboratorios.php');
require_once('../../app/models/Productos.php');
require_once('../../app/models/Proveedores.php');
require_once('../../app/models/Test.php');

class TestController
{
  public function test()
  {

    $id = 44;
    $campos = [
      'u.id',
      'u.nombres',
      'u.apellidos',
      'u.username',
      'u.email',
      'r.rol',
      'c.caja',
      'u.estado',
      'u.created_at',
      'u.updated_at'
    ];
    $equals = [
      ["field_name" => "id", "field_value" => $id],
    ];
    $user = Test::getUsers("users u", $campos, $equals)[0];
    return $user;
  }

  public function sql_creator()
  {
    /*
      '{
        "per_page":25,
        "search": "us",
        "equal":[
          {"field_name":"rol_id","field_value":[2,3]},
          {"field_name":"estado","field_value":1}
        ],
        "between":[
          {"field_name":"created_at","from":"2024-12-17", "to":"2024-12-19"},
          {"field_name":"updated_at","from":"2025-03-21", "to":"2025-12-18"}
        ],
        "order":[
          {"field_name": "apellidos", "order_dir": "ASC"},
          {"field_name": "nombres", "order_dir": "DESC"}
        ]
      }'
    */

    $p = json_decode(file_get_contents('php://input'), true);
    $campos = [
      'u.id',
      'u.nombres',
      'u.apellidos',
      'u.username',
      'u.email',
      'u.rol_id',
      'r.rol',
      "u.caja_id",
      "c.caja",
      "u.estado",
      "u.created_at",
    ];

    $p["search"] = [
      "fieldsName" => ["nombres", "apellidos"],
      "like" => trim($p["search"])
    ];

    $pagination = [
      "page" => $_GET["page"] ?? "1",
      "per_page" => $p['per_page']
    ];

    $where = MyORM::getWhere($p);
    // print_r($where);
    // exit;
    $orderBy = MyORM::getOrder($p["order"]);

    $res = Test::filterUsers($campos, $where, $orderBy, $pagination);
    return $res;
  }






}
