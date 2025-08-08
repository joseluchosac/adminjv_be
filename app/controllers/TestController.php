<?php
require_once('../../app/models/Laboratorios.php');
require_once('../../app/models/Productos.php');
require_once('../../app/models/Proveedores.php');
require_once('../../app/models/Test.php');

class TestController
{
  public function test()
  {

    $prev1 = '';
    $prev2 = '[{"e":1,"s":155}]';
    $prev3 = '[{"e":1,"s":100},{"e":3,"s":300}]';
    $esId = 3;
    $stock = 20;

    function up($prevJson, $establecimientoId, $stock)
    {
      $currentStock = ["e" => $establecimientoId, "s" => $stock];
      if (!$prevJson) {
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
      if ($idx === null) { // Insertar
        $prevStocks[] = $currentStock;
      } else { // Actualizar
        $prevStocks[$idx] = $currentStock;
      }
      return json_encode($prevStocks);
    }

    $json = up($prev1, $esId, $stock);
    var_dump($json);
    echo "<br>";
    // print_r(json_decode($prev2, true));
    echo "<br>";
  }

  public function sql_creator()
  {
    $p = json_decode(file_get_contents('php://input'), true);
    $campos = [
      'id',
      'nombres',
      'apellidos',
      'username',
      'email',
      'rol_id',
      'caja_id',
      'estado',
      'created_at',
      'updated_at'
    ];

    $p["search"] = [
      "fieldsName" => ["apellidos", "nombres"],
      "like" => trim($p["search"])
    ];

    $pagination = [
      "page" => $_GET["page"] ?? "1",
      "offset" => $p['offset']
    ];

    $where = MyORM::getWhere($p);
    $orderBy = MyORM::getOrder($p["order"]);

    $res = Test::filterUsers($campos, $where, $orderBy, $pagination);
    return $res;
  }






}
