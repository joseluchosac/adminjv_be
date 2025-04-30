<?php
require_once("Conexion.php");
class Modulos
{

  static function getModulos($campos, $whereEquals = null, $orders = null)
  {
    $sql = "SELECT " . implode(", ", $campos) . " FROM modulos";

    $sqlWhere = $whereEquals ? SqlWhere::and([SqlWhere::equalAnd($whereEquals)]) : "";
    $bindWhere = $whereEquals ? SqlWhere::arrMerge(["equal" => $whereEquals]) : null;

    $sqlOrder = $orders ? getSqlOrderBy($orders) : "";

    $sql .= $sqlWhere . $sqlOrder;

    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($bindWhere);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $records;
  }

  static function getModulo($id){
    $sql = "SELECT
        id,
        nombre,
        descripcion,
        padre_id,
        icon_menu,
        orden
      FROM modulos
      WHERE id = :id;
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute(['id' => $id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    return $record;
  }

  static function registrarModulo($params)
  {
    $sql = "INSERT INTO modulos
      (nombre, descripcion, padre_id, icon_menu, orden)
      values
      (:nombre, :descripcion, :padre_id, :icon_menu, :orden)
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $lastId = $dbh->lastInsertId();
    // $resp = $stmt->rowCount();
    return $lastId;
  }

  static function actualizarModulo($params)
  {
    $sql = "UPDATE modulos SET
        nombre = :nombre,
        descripcion = :descripcion,
        padre_id = :padre_id,
        icon_menu = :icon_menu,
        orden = :orden
      WHERE id = :id
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $resp = $stmt->rowCount();
    return $resp;
  }

  static function eliminarModulo($params){
    $sql = "DELETE FROM modulos WHERE id = :id";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $resp = $stmt->rowCount();
    return $resp;
  }

  static function sortModulos($params){
    try {
      $sql = "UPDATE modulos SET
          orden = :orden
        WHERE id = :id
      ";
      $dbh = Conexion::conectar();
      $dbh->beginTransaction();
      $stmt = $dbh->prepare($sql);
      foreach ($params as $fila) {
        $stmt->execute($fila);
      }
      $dbh->commit();
    } catch (PDOException $e) {
      $dbh->rollBack();
      throwMiExcepcion($e->getMessage(), "error", 200);
    }
  }

  static function getModuloRol($rol_id){
    $sql = "SELECT
        m.id,
        ifnull(m.nombre,'') AS nombre,
        m.descripcion,
        m.icon_menu,
        m.padre_id,
        m.orden,
        ifnull(mr.rol_id, 0) AS assign
      FROM modulos m
      LEFT JOIN (
	      SELECT * FROM modulos_roles 
	      WHERE rol_id = :rol_id
      ) mr ON m.id = mr.modulo_id
      ORDER BY m.orden
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute(['rol_id' => $rol_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($records as $key => $value) {
      if($value["assign"]){
        $records[$key]['assign'] = true;
      }else{
        $records[$key]['assign'] = false;
      }
    }
    return $records;
  }

  static function obtenerModulosSesion(){
    $sql = "SELECT DISTINCT
	      m.id,
	      IFNULL(m.nombre,'') AS nombre,
	      m.descripcion,
	      m.padre_id,
	      m.icon_menu,
	      m.orden
      FROM modulos m
      INNER JOIN (
      	SELECT m.id, m.padre_id FROM modulos m
	      INNER JOIN modulos_roles mr ON m.id = mr.modulo_id
	      WHERE mr.rol_id = :rol_id
		) md ON m.id = md.padre_id
		UNION 
      SELECT
        m.id,
        IFNULL(m.nombre,'') AS nombre,
        m.descripcion,
        m.padre_id,
        m.icon_menu,
        m.orden
      FROM modulos m
      INNER JOIN modulos_roles mr ON m.id = mr.modulo_id
      WHERE m.nombre IS NOT NULL AND mr.rol_id = :rol_id
      ORDER BY orden;
    ";
    $rol_id = Users::getCurUser()['rol_id'];
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute(['rol_id' => $rol_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $records;
  }

  static function actualizarModulosRoles($rol_id, $modulos){
    try {
      $sqlDelete = "DELETE FROM modulos_roles WHERE rol_id = :rol_id";
      $sqlInsert = "INSERT INTO modulos_roles
        (modulo_id, rol_id) values (:modulo_id, :rol_id)
      ";
      $dbh = Conexion::conectar();
      $dbh->beginTransaction();
      $stmt = $dbh->prepare($sqlDelete);
      $stmt->execute(['rol_id' => $rol_id]);
      $stmt = $dbh->prepare($sqlInsert);
      foreach ($modulos as $fila) {
        $stmt->execute(["modulo_id" => $fila["modulo_id"], "rol_id" => $rol_id]);
      }
      $dbh->commit();
    } catch (PDOException $e) {
      $dbh->rollBack();
      throwMiExcepcion($e->getMessage(), "error", 200);
    }
  }

  static function countRecordsBy($equal, $exclude = []){
    $where = " WHERE " . array_keys($equal)[0] . " = :". array_keys($equal)[0];
    if($exclude){
      $where .= " AND " . array_keys($exclude)[0] . " != :". array_keys($exclude)[0];
    }
    $sql = "SELECT COUNT(*) AS count FROM modulos" . $where;
    $param = array_merge($equal, $exclude);

    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($param);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);
    $response = $registro['count'];
    return $response;
  }

}
