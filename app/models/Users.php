<?php
require_once("Conexion.php");
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
class Users
{
  static private $curUser = null;
  static private $activity = [];

  static public function filterUsers($campos, $paramWhere, $paramOrders, $pagination, $isPaginated = true)
  {
    $table = "users_v";

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

    $sqlSelect = !empty($campos) ? "SELECT " . implode(", ", $campos)  : "";
    $sqlOrderBy = getSqlOrderBy($paramOrders);
    $page = intval($pagination["page"]);
    $offset = intval($pagination["offset"]);

    $num_regs = self::num_regs($table, $sqlWhere, $bindWhere);
    $pages = ceil($num_regs / $offset);
    if($page > $pages && $pages != 0)  throwMiExcepcion("Págian fuera de rango", "error", 200);
    $page = ($page <= $pages) ? $page : 1;
    $start_reg = $offset * ($page - 1);

    $sqlLimit = $isPaginated ? " LIMIT $start_reg, $offset" : "";
    $sql = $sqlSelect . " FROM $table" . $sqlWhere . $sqlOrderBy . $sqlLimit;

    $dbh = Conexion::conectar();
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
    return $response;
  }

  static function getUsers($tabla, $campos, $whereEquals = null)
  {
    $sql = "SELECT " . implode(", ", $campos) . " FROM $tabla";

    $sqlWhere = $whereEquals ? SqlWhere::and([SqlWhere::equalAnd($whereEquals)]) : "";
    $bindWhere = $whereEquals ? SqlWhere::arrMerge(["equal" => $whereEquals]) : null;

    $sql .= $sqlWhere;

    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($bindWhere);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $records;
  }

  static function getUser($id){
    $sql = "SELECT
        u.id,
        u.nombres,
        u.apellidos,
        u.username,
        ifnull(u.email,'') AS email,
        u.rol_id,
        r.rol,
        u.caja_id,
        c.descripcion AS caja,
        u.estado,
        u.created_at,
        ifnull(u.updated_at, '') AS updated_at
      FROM users u
      LEFT JOIN roles r ON u.rol_id = r.id
      LEFT JOIN cajas c ON u.caja_id = c.id
      WHERE u.id = :id;
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute(['id' => $id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    return $record;
  }

  static function getUserBy($param){
    $sqlWhere = implode(" AND ", array_map(function($el){
      return "u.$el = :$el";
    },array_keys($param)));
    $sqlWhere = $sqlWhere ? " WHERE " . $sqlWhere : "";
    $sql = "SELECT
        u.id,
        u.nombres,
        u.apellidos,
        u.username,
        ifnull(u.email,'') AS email,
        u.rol_id,
        r.rol,
        u.caja_id,
        c.descripcion AS caja,
        u.estado,
        u.created_at,
        ifnull(u.updated_at, '') AS updated_at
      FROM users u
      LEFT JOIN roles r ON u.rol_id = r.id
      LEFT JOIN cajas c ON u.caja_id = c.id
      $sqlWhere;
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($param);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    return $record;
  }
 
  static function getUserSession(){
    $sql = "SELECT
        id,
        nombres,
        apellidos,
        username,
        ifnull(email,'') AS email,
        rol_id,
        caja_id
      FROM users
      WHERE id = :id;
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute(['id' => self::$curUser['id']]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    return $record;
  }

  static function updateUser($table, $paramCampos, $paramWhere)
  {
    $sql = sqlUpdate($table, $paramCampos, $paramWhere);
    $params = array_merge($paramCampos, $paramWhere);
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $resp = $stmt->rowCount();
    return $resp;
  }

  static function createUser($params)
  {
    $sql = sqlInsert("users", $params);
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $lastId = $dbh->lastInsertId();
    // $resp = $stmt->rowCount();
    return $lastId;
  }

  static function countRecordsBy($equal, $exclude = []){
    $where = " WHERE " . array_keys($equal)[0] . " = :". array_keys($equal)[0];
    if($exclude){
      $where .= " AND " . array_keys($exclude)[0] . " != :". array_keys($exclude)[0];
    }
    $sql = "SELECT COUNT(*) AS count FROM users" . $where;
    $param = array_merge($equal, $exclude);

    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($param);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);
    $response = $registro['count'];
    return $response;
  }

  static function setToken($token, $id){
    $sql = "UPDATE users SET 
      token = :token
      WHERE id = :id 
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array('token'=>$token, 'id' => $id));
  }

  static function getToken($id){
    $sql = "SELECT
      token,
      token_exp 
      FROM users
      WHERE id = :id 
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array('id' => $id));
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);
    return $registro;
  }

  static function checkToken($authorization)
  {
    try {
      $token = explode(" ", $authorization)[1];
      // --> JWT::decode: Decodifica el token y si hay error lanza una excepcion
      $token_decoded = JWT::decode($token, new Key($_ENV['JWT_KEY'], 'HS256'));
      // --> Etrae id del user del token decodificado y lo compara en la BD
      $id = $token_decoded->data->id;
      $sql = "SELECT COUNT(*) as count
        FROM users 
        WHERE id = :id AND token = :token AND estado = 1
      ";
      $dbh = Conexion::conectar();
      $stmt = $dbh->prepare($sql);
      $stmt->execute(['id' => $id, 'token'=>$token]);
      $registro = $stmt->fetch(PDO::FETCH_ASSOC);
      
      if($registro['count'] !== 1) throwMiExcepcion("Token no encontrado","errorToken");
      $response["id"] = $id;
      $response["rol_id"] = $token_decoded->data->rolId;
      return $response;
    } catch (Exception $e) {
      throwMiExcepcion($e->getMessage(), "errorToken", 200);
    }

  }
  
  static function deleteUser($params){
    $sql = "DELETE FROM users WHERE id = :id";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $resp = $stmt->rowCount();
    return $resp;
  }
  
  static function setCurUser($curUser){
    self::$curUser = $curUser;
  }
  static function getCurUser(){
    return self::$curUser;
  }
  static function setActivity($activity){
    self::$activity = $activity;
  }
  static function getActivity(){
    return self::$activity;
  }

  static function setActivityLog($detalle = ""){
    $sql = "INSERT INTO activity_log 
      (user_id, controller, accion, detalle)
      VALUES
      (:user_id, :controller, :accion, :detalle)
    ";
    $params = [
      "user_id" => self::$curUser["id"],
      "controller" => self::$activity["prefixController"],
      "accion" => self::$activity["accion"],
      "detalle" => $detalle,
    ];
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
  }
  // Metodos privados
  static private function num_regs($table, $sqlWhere, $bindWhere)
  {
    // Extraemos la cantidad de registros en total
    $sql = "SELECT COUNT(*) AS num_regs FROM $table" . $sqlWhere;

    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($bindWhere);
    $rows = $stmt->fetch(PDO::FETCH_ASSOC); 
    return $rows['num_regs']; 
  }

}
