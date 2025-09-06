<?php
require_once("Conexion.php");

class Test
{
  static public function filterUsers($campos, $where, $orderBy, $pagination, $isPaginated = true){
    $table = "users";
    $dbh = Conexion::conectar();

    $sqlSelect = !empty($campos) ? "SELECT " . implode(", ", $campos)  : "";

    $page = intval($pagination["page"]);
    $per_page = intval($pagination["per_page"]);

    $num_regs = self::num_regs($table, $where["sql"], $where["params"], $dbh);
    $pages = ceil($num_regs / $per_page);
    if ($page > $pages && $pages != 0)  throwMiExcepcion("Págian fuera de rango", "error", 200);
    $page = ($page <= $pages) ? $page : 1;
    $start_reg = $per_page * ($page - 1);

    $sqlLimit = $isPaginated ? " LIMIT $start_reg, $per_page" : "";
    $sql = $sqlSelect . " FROM $table" . $where["sql"] . $orderBy . $sqlLimit;

    $stmt = $dbh->prepare($sql);
    $stmt->execute($where["params"]);
    $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['filas'] = $filas;
    $response['num_regs'] = $num_regs;
    $response['pages'] = $pages;
    $response['page'] = ($pages != 0) ? $page : 0;
    $response['next'] = ($pages > $page) ? $page + 1 : 0;
    $response['previous'] = ($pages > 1) ? $page - 1 : 0;
    $response['per_page'] = $per_page;
    return $response;
  }


  // Metodos privados
  static private function num_regs($table, $sqlWhere, $bindWhere, $dbh)
  {
    // Extraemos la cantidad de registros en total
    $sql = "SELECT COUNT(*) AS num_regs FROM $table" . $sqlWhere;

    // $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($bindWhere);
    $rows = $stmt->fetch(PDO::FETCH_ASSOC);
    return $rows['num_regs'];
  }
}
