<?php
require_once("Conexion.php");

class Test
{
  static public function filterProductos($campos, $paramWhere, $paramOrders, $pagination, $isPaginated = true)
  {
    $table = "productos2_v";

    $sqlWhere = SqlWhere::and([
      SqlWhere::likeOr($paramWhere['paramLike']),
      SqlWhere::equalAnd($paramWhere['paramEquals']),
      SqlWhere::between($paramWhere['paramBetween']),
    ]);
    $bindWhere = SqlWhere::arrMerge([
      "like" => $paramWhere['paramLike'], 
      "equal" => $paramWhere['paramEquals'], 
      "between" => $paramWhere['paramBetween']
    ]);    
    // se podra inyectar aca el where establecimiento id?
    $curEstab = Users::getCurEstab();
    $sqlWhere .= $sqlWhere
      ? " AND (establecimiento_id = :establecimiento_id1 OR establecimiento_id = :establecimiento_id2)" 
      : " WHERE (establecimiento_id = :establecimiento_id1 OR establecimiento_id = :establecimiento_id2)";

    $bindWhere["establecimiento_id1"] = $curEstab;
    $bindWhere["establecimiento_id2"] = 0;

    $sqlSelect = !empty($campos) ? "SELECT " . implode(", ", $campos)  : "";
    $sqlOrderBy = getSqlOrderBy($paramOrders);
    $page = intval($pagination["page"]);
    $offset = intval($pagination["offset"]);

    $dbh = Conexion::conectar();
    $num_regs = self::num_regs($table, $sqlWhere, $bindWhere, $dbh);
    
    $pages = ceil($num_regs / $offset);
    if($page > $pages && $pages != 0)  throwMiExcepcion("Págian fuera de rango", "error", 200);
    $page = ($page <= $pages) ? $page : 1;
    $start_reg = $offset * ($page - 1);

    $sqlLimit = $isPaginated ? " LIMIT $start_reg, $offset" : "";
    $sql = $sqlSelect . " FROM $table" . $sqlWhere . $sqlOrderBy . $sqlLimit;

    $stmt = $dbh->prepare($sql);
    $stmt->execute($bindWhere);
    $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);


    $response['filas'] = $filas;
    $response['num_regs'] = $num_regs;
    $response['pages'] = $pages;
    $response['page'] = ($pages != 0) ? $page : 0;
    $response['next'] = ($pages > $page) ? $page + 1 : 0;
    $response['previous'] = ($pages > 1) ? $page - 1 : 0;
    $response['offset'] = $offset;
    $response['statement'] = $sql;

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