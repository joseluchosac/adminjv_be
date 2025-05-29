<?php
require_once("Conexion.php");
class Categorias
{

  static function getCategorias($campos, $orders = null)
  {
    $sql = "SELECT " . implode(", ", $campos) . " FROM categorias";
    $sqlOrder = $orders ? getSqlOrderBy($orders) : "";
    $sql .= $sqlOrder;

    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $records;
  }

  static function getCategoria($id){
    $sql = "SELECT
        id,
        descripcion,
        padre_id,
        orden
      FROM categorias
      WHERE id = :id;
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute(['id' => $id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    return $record;
  }

  static function sortCategorias($params){
    try {
      $sql = "UPDATE categorias SET
          orden = :orden
        WHERE id = :id
      ";
      $dbh = Conexion::conectar();
      $dbh->beginTransaction();
      $stmt = $dbh->prepare($sql);
      foreach ($params as $fila) {
        $stmt->execute(['orden' => $fila['orden'], 'id' => $fila['id']]);
      }
      $dbh->commit();
    } catch (PDOException $e) {
      $dbh->rollBack();
      throwMiExcepcion($e->getMessage(), "error", 200);
    }
  }

  static function createCategoria($params)
  {
    $sql = "INSERT INTO categorias
      (descripcion, padre_id, orden)
      values
      (:descripcion, :padre_id, :orden)
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $lastId = $dbh->lastInsertId();
    return $lastId;
  }

  static function updateCategoria($params)
  {
    $sql = "UPDATE categorias SET
        descripcion = :descripcion,
        padre_id = :padre_id
      WHERE id = :id
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $resp = $stmt->rowCount();
    return $resp;
  }

  static function deleteCategoria($params){
    $sql = "DELETE FROM categorias WHERE id = :id";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $resp = $stmt->rowCount();
    return $resp;
  }

  static function countRecordsBy($equal, $exclude = []){
    $where = " WHERE " . array_keys($equal)[0] . " = :". array_keys($equal)[0];
    if($exclude){
      $where .= " AND " . array_keys($exclude)[0] . " != :". array_keys($exclude)[0];
    }
    $sql = "SELECT COUNT(*) AS count FROM categorias" . $where;
    $param = array_merge($equal, $exclude);

    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($param);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);
    $response = $registro['count'];
    return $response;
  }
}
