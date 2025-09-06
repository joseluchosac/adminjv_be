<?php
require_once("Conexion.php");
class Roles
{

  static function getRoles($campos, $whereEquals = null)
  {
    $sql = "SELECT " . implode(", ", $campos) . " FROM roles";

    $sqlWhere = $whereEquals ? SqlWhere::and([SqlWhere::equalAnd($whereEquals)]) : "";
    $bindWhere = $whereEquals ? SqlWhere::arrMerge(["equal" => $whereEquals]) : null;

    $sql .= $sqlWhere;

    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($bindWhere);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $records;
  }

  static function getRol($id){
    $sql = "SELECT
        id,
        rol,
        estado
      FROM roles
      WHERE id = :id;
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute(['id' => $id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    return $record;
  }

  static function createRol($params)
  {
    $sql = "INSERT INTO roles
      (rol) values (:rol)
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $lastId = $dbh->lastInsertId();
    // agregar los modulos predeterminados al rol
    $sql = "INSERT INTO modulos_roles
      (modulo_id, rol_id) values (:modulo_id, :rol_id)
    ";
    $stmt = $dbh->prepare($sql);
    foreach ([1,2] as $modulo_id) {
      $stmt->execute(["modulo_id" => $modulo_id, "rol_id" => $lastId]);
    }

    return $lastId;
  }

  static function updateRol($params)
  {
    $sql = "UPDATE roles SET
        rol = :rol
      WHERE id = :id
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $resp = $stmt->rowCount();
    return $resp;
  }

  static function deleteRol($params){
    $sql = "DELETE FROM roles WHERE id = :id";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $count = $stmt->rowCount();
    // borrande en modulos_roles
    $sql = "DELETE FROM modulos_roles WHERE rol_id = :rol_id";
    $stmt = $dbh->prepare($sql);
    $stmt->execute(["rol_id" => $params['id']]);
    return $count;
  }
  
  static function countRecordsBy($equal, $exclude = []){
    $where = " WHERE " . array_keys($equal)[0] . " = :". array_keys($equal)[0];
    if($exclude){
      $where .= " AND " . array_keys($exclude)[0] . " != :". array_keys($exclude)[0];
    }
    $sql = "SELECT COUNT(*) AS count FROM roles" . $where;
    $param = array_merge($equal, $exclude);

    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($param);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);
    $response = $registro['count'];
    return $response;
  }
}
