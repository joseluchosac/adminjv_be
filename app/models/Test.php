<?php
require_once("Conexion.php");

class Test
{
  static public function filterUsers($campos, $where, $orderBy, $pagination, $isPaginated = true){
    $table = "users u";
    $dbh = Conexion::conectar();

    $sqlSelect = !empty($campos) ? "SELECT " . implode(", ", $campos)  : "";
    $join = " LEFT JOIN (SELECT id, rol FROM roles) r ON u.rol_id = r.id";
    $join .= " LEFT JOIN (SELECT id, descripcion as caja FROM cajas) c ON u.caja_id = c.id";

    $page = intval($pagination["page"]);
    $per_page = intval($pagination["per_page"]);


    $num_regs = self::num_regs($table, $join ,$where["sql"], $where["params"], $dbh);
    $pages = ceil($num_regs / $per_page);
    if ($page > $pages && $pages != 0)  throwMiExcepcion("Págian fuera de rango", "error", 200);
    $page = ($page <= $pages) ? $page : 1;
    $start_reg = $per_page * ($page - 1);

    $sqlLimit = $isPaginated ? " LIMIT $start_reg, $per_page" : "";
    $sql = $sqlSelect . " FROM $table" . $join . $where["sql"] . $orderBy . $sqlLimit;

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

static function getUsers($tabla, $campos, $whereEquals = null)
  {
    $sql = "SELECT " . implode(", ", $campos) . " FROM $tabla";
    $join = " LEFT JOIN (SELECT id as rol_id, rol FROM roles) r ON u.rol_id = r.rol_id";
    $join .= " LEFT JOIN (SELECT id as caja_id, descripcion as caja FROM cajas) c ON u.caja_id = c.caja_id";

    $sqlWhere = $whereEquals ? SqlWhere::and([SqlWhere::equalAnd($whereEquals)]) : "";
    $bindWhere = $whereEquals ? SqlWhere::arrMerge(["equal" => $whereEquals]) : null;

    $sql .= $join;
    $sql .= $sqlWhere;

    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($bindWhere);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $records;
  }
  // Metodos privados
  static private function num_regs($table, $join , $sqlWhere, $bindWhere, $dbh)
  {
    // Extraemos la cantidad de registros en total
    $sql = "SELECT COUNT(*) AS num_regs FROM $table" . $join . $sqlWhere;

    // $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($bindWhere);
    $rows = $stmt->fetch(PDO::FETCH_ASSOC);
    return $rows['num_regs'];
  }
}
