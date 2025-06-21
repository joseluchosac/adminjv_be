<?php
require_once("Conexion.php");

class Inventarios
{

  static function getUltimoInventario($establecimiento_id, $producto_id){
    $sql = "SELECT 
        ex_unidades, 
        ex_costo_unitario, 
        ex_costo_total 
      FROM inventarios 
      WHERE establecimiento_id = :establecimiento_id AND producto_id = :producto_id
      ORDER BY id DESC
      LIMIT 1
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute([
      "establecimiento_id" => $establecimiento_id,
      "producto_id" => $producto_id
    ]);
    $getUltimoInventario = $stmt->fetch(PDO::FETCH_ASSOC);
    return $getUltimoInventario;
  }


}
