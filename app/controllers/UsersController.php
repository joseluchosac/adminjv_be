<?php
require_once('../../app/models/Config.php');

use Firebase\JWT\JWT;
use Valitron\Validator;

class UsersController
{
  public function filter_users()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 200);
    $p = json_decode(file_get_contents('php://input'), true);
    $campos = [
      'id',
      'nombres',
      'apellidos',
      'username',
      'email',
      'rol',
      'caja',
      'estado',
      'created_at',
      'updated_at'
    ];

    $p["search"] = [
      "fieldsName" => ["apellidos", "nombres", "username", "email"],
      "like" => trim($p["search"])
    ];

    $pagination = [
      "page" => $_GET["page"] ?? "1",
      "offset" => $p['offset']
    ];

    $where = MyORM::getWhere($p);
    $orderBy = MyORM::getOrder($p["order"]);

    $res = Users::filterUsers($campos, $where, $orderBy, $pagination);
    return $res;
  }

  public function filter_users_full() // sin paginacion
  {
    $res =  self::filter_users(false);
    unset($res["next"]);
    unset($res["offset"]);
    unset($res["page"]);
    unset($res["pages"]);
    unset($res["previous"]);
    return $res;
  }

  public function get_user()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    // throwMiExcepcion("Error de prueba", "error", 200);
    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    $registro = Users::getUser($p['id']);
    if (!$registro) throwMiExcepcion("No se encontró el registro", "error", 404);
    // $response["content"] = $registro;
    return $registro;
  }

  public function get_user_session()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);

    $userSession = Users::getUserSession();
    return $userSession;
  }

  public function get_profile()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    // $p = json_decode(file_get_contents('php://input'), true);
    // if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    $profile = Users::getProfile();
    return $profile;
  }

  public function create_user()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 200);

    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);
    $params = [
      "nombres" => trimSpaces($p['nombres']),
      "apellidos" => trimSpaces($p['apellidos']),
      "username" => $p['username'],
      "password" => $p['password'],
      "confirm_password" => $p['confirm_password'], // eliminar despues de validar
      "email" => $p['email'] ? $p['email'] : null,
      "rol_id" => $p['rol_id'] ?? 19,
      "caja_id" => $p['caja_id'] ?? 1,
    ];
    // Validacion
    $this->validateCreateUser($params);

    $params["password"] = password_hash($p['password'], PASSWORD_DEFAULT);
    unset($params["confirm_password"]);

    // Buscando duplicados
    $count = Users::countRecordsBy(["username" => $p['username']]);
    if ($count) throwMiExcepcion("El usuario: " . $p['username'] . ", ya existe!", "warning", 200);
    if ($p['email']) {
      $count = Users::countRecordsBy(["email" => $p['email']]);
      if ($count) throwMiExcepcion("El email: " . $p['email'] . ", ya existe!", "warning", 200);
    }

    $lastId = Users::createUser($params);
    if (!$lastId) throwMiExcepcion("Ningún registro guardado", "warning", 200);
    Users::setActivityLog("Creación de registro en la tabla usuarios: " . $params["username"]);

    // Obteniendo el usuaro actualizado
    $campos = [
      'id',
      'nombres',
      'apellidos',
      'username',
      'email',
      'rol',
      'caja',
      'estado',
      'created_at',
      'updated_at'
    ];
    $equals = [
      ["field_name" => "id", "field_value" => $lastId],
    ];
    $user = Users::getUsers("users_v", $campos, $equals)[0];

    $response['error'] = false;
    $response['msgType'] = "success";
    $response['msg'] = "Usuario registrado";
    $response['content'] = $user;
    return $response;
  }

  public function sign_up() // registrarse
  {
    // throwMiExcepcion("error de prueba", "error");
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 200);

    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    // Buscando duplicados
    $count = Users::countRecordsBy(["username" => $p['username']]);
    if ($count) throwMiExcepcion("El usuario: " . $p['username'] . ", ya existe!", "warning");
    if ($p['email']) {
      $count = Users::countRecordsBy(["email" => $p['email']]);
      if ($count) throwMiExcepcion("El email: " . $p['email'] . ", ya existe!", "warning");
    }

    $params = [
      "username" => $p['username'],
      "password" => $p['password'],
      "confirm_password" => $p['confirm_password'],
      "email" => $p['email'] ? $p['email'] : null,
      "rol_id" => 19,
      "caja_id" => 1,
    ];

    // validation
    $this->validateSignUp($params);
    unset($params['confirm_password']);
    $params['password'] = password_hash($p['password'], PASSWORD_DEFAULT);

    $lastId = Users::createUser($params);
    $curUser = ["id" => $lastId, "rol_id" => $params["rol_id"]];
    Users::setCurUser($curUser);

    $jwt = $this->generateToken($lastId, $params["rol_id"]);
    Users::setToken($jwt, $lastId);

    $response['error'] = false;
    $response['msg'] = "Registro satisfactorio";
    $response['msgType'] = "success";
    $response['token'] = $jwt;
    
    return $response;
  }

  public function sign_in() // Logeaese
  {
    //--> Inicia sesion, devuelve al user y los modulos asociados a su rol
    $p = json_decode(file_get_contents('php://input'), true);
    $username = $p['username'] ?? '';
    $password = $p['password'] ?? '';
    if (!$username || !$password) throwMiExcepcion("Usuario y password requeridos", "warning", 200);

    $campos = [
      "id", 
      "username", 
      "password",
      "email", 
      "rol_id", 
    ];

    // Obteniendo al user
    $equals = [
      ["field_name" => "username", "field_value" => $username],
      ["field_name" => "estado", "field_value" => 1],
    ];

    $registros = Users::getUsers("users", $campos, $equals);
    // echo "<br>";
    // print_r($p);
    // exit();
    if (!$registros) throwMiExcepcion("Usuario o contraseña incorrectos", "error", 200);
    if($registros[0]['password'] === ""){
      if($p['password'] !== "123456") throwMiExcepcion("Usuario o contraseña incorrectos", "error", 200);
    }else if(!password_verify($p['password'], $registros[0]['password'])){
      throwMiExcepcion("Usuario o contraseña incorrectos", "error", 200);
    }

    $id = $registros[0]['id'];
    $rol_id = $registros[0]['rol_id'];
    // Generacion del token
    $jwt = $this->generateToken($id, $rol_id);
    // Guardando el token
    Users::setToken($jwt, $id);
    $curUser = ["id" => $id, "rol_id"=>$rol_id];
    Users::setCurUser($curUser);

    $response['error'] = false;
    $response['msg'] = "Usuario logueado";
    $response['msgType'] = "success";
    $response['token'] = $jwt;

    return $response;
  }

  public function update_user()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'PUT') throwMiExcepcion("Método no permitido", "error", 405);

    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 200);

    $paramCampos = [
      "nombres" => trimSpaces($p['nombres']),
      "apellidos" => trimSpaces($p['apellidos']),
      "rol_id" => $p['rol_id'],
      "caja_id" => $p['caja_id'],
    ];

    // Validacion
    $this->validateUpdateUser($paramCampos);

    // Buscando duplicados
    $exclude = ["id" => $p['id']];
    $count = Users::countRecordsBy(["username" => $p['username']], $exclude);
    if ($count) throwMiExcepcion("El usuario: " . $p['username'] . ", ya existe!", "warning", 200);
    $count = Users::countRecordsBy(["email" => $p['email']], $exclude);
    if ($count) throwMiExcepcion("El email: " . $p['email'] . ", ya existe!", "warning", 200);

    $paramWhere = ["id" => $p['id']];

    $resp = Users::updateUser("users", $paramCampos, $paramWhere);
    if (!$resp) throwMiExcepcion("Ningún registro modificado", "warning", 200);
    
    // $registro = Users::getUser($p['id']);
    // Obteniendo el usuaro actualizado
    $campos = [
      'id',
      'nombres',
      'apellidos',
      'username',
      'email',
      'rol',
      'caja',
      'estado',
      'created_at',
      'updated_at'
    ];
    $equals = [
      ["field_name" => "id", "field_value" => $p['id']],
    ];
    $user = Users::getUsers("users_v", $campos, $equals)[0];
    Users::setActivityLog("Modificación de registro en la tabla usuarios: " . $user["username"]);

    $response['msgType'] = "success";
    $response['msg'] = "Registro actualizado";
    $response['content'] = $user;
    return $response;
  }

  public function set_state_user()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'PUT') throwMiExcepcion("Método no permitido", "error", 405);

    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 200);

    $paramCampos = [
      "estado" => $p['estado'],
    ];

    $paramWhere = ["id" => $p['id']];

    $resp = Users::updateUser("users", $paramCampos, $paramWhere);
    if (!$resp) throwMiExcepcion("Ningún registro modificado", "warning", 200);
    
    // Obteniendo el usuaro actualizado
    $campos = [
      'id',
      'nombres',
      'apellidos',
      'username',
      'email',
      'rol',
      'caja',
      'estado',
      'created_at',
      'updated_at'
    ];
    $equals = [
      ["field_name" => "id", "field_value" => $p['id']],
    ];
    $user = Users::getUsers("users_v", $campos, $equals)[0];

    Users::setActivityLog("Modificación de registro en la tabla usuarios: " . $user["username"]);

    $response['msgType'] = "success";
    $response['msg'] = "Registro actualizado";
    $response['content'] = $user;
    return $response;
  }

  public function update_profile()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'PUT') throwMiExcepcion("Método no permitido", "error", 405);

    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 200);

    if (trim($p['nombres']) == "") throwMiExcepcion("Nombres son requeridos", "warning", 200);
    if (trim($p['apellidos']) == "") throwMiExcepcion("Apellidos son requeridos", "warning", 200);
    if (trim($p['username']) == "") throwMiExcepcion("El usuario es requerido", "warning", 200);

    // Buscando duplicados
    $exclude = ["id" => $p['id']];
    $count = Users::countRecordsBy(["email" => $p['email']], $exclude);
    if ($count) throwMiExcepcion("El email: " . $p['email'] . ", ya existe!", "warning");

    $paramCampos = [
      "nombres" => trimSpaces($p['nombres']),
      "apellidos" => trimSpaces($p['apellidos']),
      "email" => $p['email'],
    ];

    // Validar password si se desea cambiar
    if ($p['new_password']) {
      $paramCampos["password"] = password_hash($p['new_password'], PASSWORD_DEFAULT);
    }

    $paramWhere = ["id" => $p['id']];


    $resp = Users::updateUser("users", $paramCampos, $paramWhere);
    if (!$resp) throwMiExcepcion("Ningún registro modificado", "warning", 200);

    $registro = Users::getProfile($p['id']);

    $response['msgType'] = "success";
    $response['msg'] = "Datos actualizados";
    $response['content'] = $registro;
    return $response;
  }

  public function delete_user()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'DELETE') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    $params = [
      "id" => $p['id'],
    ];
    $resp = Users::deleteUser($params);
    if (!$resp) throwMiExcepcion("Ningún registro eliminado", "warning");

    $response['error'] = "false";
    $response['msgType'] = "success";
    $response['msg'] = "Registro eliminado";
    $response['content'] = $p['id'];
    return $response;
  }

  public function get_email_by_username()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);

    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    $params = ["username" => $p['username'],];

    $userByUsername = Users::getUserBy($params);
    if (!$userByUsername) throwMiExcepcion("No se encontró al usuario " . $p['username'], "error", 200 );

    if (!$userByUsername["email"]) throwMiExcepcion("El usuario " . $p['username'] . " no tiene una cuenta de correo asociada", "error", 200);

    $response['error'] = false;
    $response['msgType'] = "success";
    $response['msg'] = "El usuario " . $p['username'] . " tiene una cuenta de correo asociada";
    $response['email'] = $userByUsername["email"];
    return $response;
  }

  // Envía código de restauración de contraseña al email
  public function send_code_restoration()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);

    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);
    $email = $p['email'];
    $username = $p['username'];
    // Generando código de 6 dígitos.
    $code = rand(100000, 999999);
    // Enviando código al email.
    $body = "Esta es la clave para restablecer su contraseña: <strong>$code</strong>";
    $respuesta = MailerHostinger::sendMail($email, "Restablecer contraseña", $body);
    if ($respuesta['error']) throwMiExcepcion($respuesta['msg'], "error", 200);

    // Actualizando el usuario con el código de restauración
    $count = Users::updateUser("users", ["code_restore" => $code], ["username" => $username]);
    if (!$count) throwMiExcepcion("No se pudo completar la operación", "error", 200);
    $response['error'] = false;
    $response['msgType'] = "success";
    $response['msg'] = "Se envió el código de restauración al correo: " . $email;
    return $response;
  }

  public function restore_password()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);

    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);
    $code = $p['code'];
    $new_password = $p['new_password'];
    $new_confirm_password = $p['new_confirm_password'];
    if ($new_password !== $new_confirm_password) throwMiExcepcion("Las contraseñas no son iguales", "error");
    $userByCode = Users::getUserBy(["code_restore" => $code]);
    if (!$userByCode) throwMiExcepcion("Código expirado o inválido", "error");

    // Actualizando el usuario con el código de restauración
    $id = $userByCode["id"];
    $password = password_hash($new_password, PASSWORD_DEFAULT);
    $paramCampos = [
      "password" => $password,
      "code_restore" => null
    ];
    $count = Users::updateUser("users", $paramCampos, ["id" => $id]);
    if (!$count) throwMiExcepcion("No se pudo completar la operación", "error");
    $response['error'] = false;
    $response['msgType'] = "success";
    $response['msg'] = "Se cambió la contraseña con éxito.";
    return $response;
  }
  

  //--> Chekea token, devuelve al user del token y
  //--> los modulos asociados a su rol
  public function check_auth()
  {
    $profile = Users::getProfile();
    $response['msgType'] = "success";
    $response['error'] = false;
    $response['msg'] = "Usuario autorizado";
    $response['profile'] = $profile;
    return $response;
  }


  public function check_password()
  {
    $p = json_decode(file_get_contents('php://input'), true);

    $campos = ["id", "password"];
    // Encriptando el password
    $user_id = Users::getCurUser()["id"];
    $equals = [
      ["field_name" => "id", "field_value" => $user_id],
    ];

    $registros = Users::getUsers("users", $campos, $equals);
    if (!$registros) throwMiExcepcion("Contraseña incorrecta", "error", 200);
    if($registros[0]['password'] === ""){
      if($p['password'] !== "123456") throwMiExcepcion("Contraseña incorrecta", "error", 200);
    }else if(!password_verify($p['password'], $registros[0]['password'])){
      throwMiExcepcion("Contraseña incorrecta", "error", 200);
    }
    $response["msg"] = "Contraseña correcta";
    $response["msgType"] = "success";
    return $response;
  }

  private function generateToken($id, $rolId)
  {
    $dias = 1;
    $exp = strtotime(date('Y-m-d', time() + ($dias*24*60*60))); // Dia entero (00:00:00)
    $payload = [
      'iat' => time(),
      'exp' => $exp, 
      // 'exp' => time() + (3 * 60 * 60), // 3 h
      // 'exp' => time() + (5 * 60), // 5 min
      // 'exp' => time() + (60), // 1 min
      'nbf' => time(),
      'data' => array(
        "id" => $id,
        "rolId" => $rolId
      )
    ];
    $jwt = JWT::encode($payload, $_ENV['JWT_KEY'], 'HS256');
    return $jwt;
  }

  private function validateSignUp($params){
        $v = new Validator($params);
    $v->addRule('iguales', function ($field, $value, array $params, array $fields) {
      return $fields['password'] === $fields["confirm_password"];
    });
    $v->addRule('sinEspacios', function ($field, $value, array $params, array $fields) {
      return strpos($value, ' ') === false; // Verificar que no haya espacios en el valor
    });
    $v->rule('required', 'username')->message('El usuario es requerido');
    $v->rule('lengthMin', 'username', 2)->message('El usuario debe tener al menos 2 caracteres.');
    $v->rule('lengthMax', 'username', 15)->message('El usuario no puede exceder los 15 caracteres.');
    $v->rule('sinEspacios', 'username')->message('El usuario no puede tener espacios');
    $v->rule('email', 'email')->message('Ingrese un formato de email válido');
    $v->rule('required', 'password')->message('La contraseña es obligatoria');
    $v->rule('regex', 'password', '/^[A-Za-z\d@$!%*?&]{6,}$/')->message('La contraseña debe tener al menos 6 caracteres, sin espacios');
    $v->rule('iguales', 'password')->message('Los passwords no son iguales');;
    if (!$v->validate()) {
      $errors = $v->errors();
      throwMiExcepcion("Error de validación", "warning", 200, "validation" , $errors);
    }
  }
  private function validateCreateUser($params){
    $v = new Validator($params);
    $v->addRule('iguales', function ($field, $value, array $params, array $fields) {
      return $fields['password'] === $fields["confirm_password"];
    });
    $v->addRule('sinEspacios', function ($field, $value, array $params, array $fields) {
      return strpos($value, ' ') === false; // Verificar que no haya espacios en el valor
    });
    $v->rule('required', 'nombres')->message('El nombre es requerido');
    $v->rule('lengthMin', 'nombres', 2)->message('El nombre debe tener al menos 2 caracteres.');
    $v->rule('lengthMax', 'nombres', 50)->message('El nombre no puede exceder los 50 caracteres.');
    $v->rule('required', 'apellidos')->message('Los apellidos son requeridos');
    $v->rule('lengthMin', 'apellidos', 2)->message('Los apellidos deben tener al menos 2 caracteres.');
    $v->rule('lengthMax', 'apellidos', 50)->message('Los apellidos no puede exceder los 50 caracteres.');
    $v->rule('required', 'username')->message('El usuario es requerido');
    $v->rule('lengthMin', 'username', 2)->message('El usuario debe tener al menos 2 caracteres.');
    $v->rule('lengthMax', 'username', 50)->message('El usuario no puede exceder los 50 caracteres.');
    $v->rule('sinEspacios', 'username')->message('El usuario no puede tener espacios');
    $v->rule('email', 'email')->message('Ingrese un formato de email válido');
    $v->rule('required', 'password')->message('La contraseña es obligatoria');
    $v->rule('regex', 'password', '/^[A-Za-z\d@$!%*?&]{6,}$/')->message('La contraseña debe tener al menos 6 caracteres, sin espacios');
    $v->rule('iguales', 'password')->message('Los passwords no son iguales');;
    if (!$v->validate()) {
      $errors = $v->errors();
      throwMiExcepcion("Error de validación", "warning", 200, "validation" , $errors);
    }
  }

  private function validateUpdateUser($params){
    $v = new Validator($params);
    $v->rule('required', 'nombres')->message('El nombre es requerido');
    $v->rule('lengthMin', 'nombres', 3)->message('El nombre debe tener al menos 3 caracteres.');
    $v->rule('lengthMax', 'nombres', 50)->message('El nombre no puede exceder los 50 caracteres.');
    $v->rule('required', 'apellidos')->message('Los apellidos son requeridos');
    $v->rule('lengthMin', 'apellidos', 3)->message('Los apellidos deben tener al menos 3 caracteres.');
    $v->rule('lengthMax', 'apellidos', 50)->message('Los apellidos no puede exceder los 50 caracteres.');
    if (!$v->validate()) {
      $errors = $v->errors();
      throwMiExcepcion("Error de validación", "warning", 200, "validation", $errors);
    }
  }
}
