<?php
require_once('../../app/models/Configuraciones.php');

use Firebase\JWT\JWT;
use Valitron\Validator;

class UsersController
{
  public function filtrar_users($isPaginated = true)
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $pJson = json_decode(file_get_contents('php://input'), true);

    $campos = [
      'id',
      'nombres',
      'apellidos',
      'username',
      'email',
      'rol_id',
      'rol',
      'caja_id',
      'caja',
      'estado',
      'created_at',
      'updated_at'
    ];

    $search = $pJson['search'] ? "%" . $pJson['search'] . "%" : "";

    $paramWhere = [
      "paramLike" => [
        'nombres' => $search, 
        'apellidos' => $search, 
        'username' => $search, 
        "email" => $search
      ],
      "paramEquals" => $pJson['equals'], // [["campo_name" => "id", "value"=>1]] 
      "paramBetween" => [
        "campo" => $pJson['between']['campo_name'],
        "rango" => $pJson['between']['range'] // "2024-12-18 00:00:00, 2024-12-19 23:59:59"
      ]
    ];

    $paramOrders = $pJson['orders'];

    $pagination = [
      "page" => $_GET["page"] ?? "1",
      "offset" => $pJson['offset']
    ];

    $res = Users::filtrarUsers($campos, $paramWhere, $paramOrders, $pagination, $isPaginated);
    return $res;
  }

  public function filter_users_full() // sin paginacion
  {
    $res =  self::filtrar_users(false);
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
    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    $registro = Users::getUser($pJson['id']);
    return $registro;
  }

  public function get_user_session()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    $registro = Users::getUserSession();
    return $registro;
  }

  public function create_user()
  {
    
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 200);

    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);
    $params = [
      "nombres" => trimSpaces($pJson['nombres']),
      "apellidos" => trimSpaces($pJson['apellidos']),
      "username" => $pJson['username'],
      "password" => $pJson['password'],
      "password_repeat" => $pJson['password_repeat'], // eliminar despues de validar
      "email" => $pJson['email'] ? $pJson['email'] : null,
      "rol_id" => $pJson['rol_id'] ?? 19,
      "caja_id" => $pJson['caja_id'] ?? 1,
    ];
    // Validacion
    $this->validateCreateUser($params);

    $params["password"] = crypt($params['password'], $_ENV['SALT_PSW']);
    unset($params["password_repeat"]);

    // Buscando duplicados
    $count = Users::countRecordsBy(["username" => $pJson['username']]);
    if ($count) throwMiExcepcion("El usuario: " . $pJson['username'] . ", ya existe!", "warning");
    if ($pJson['email']) {
      $count = Users::countRecordsBy(["email" => $pJson['email']]);
      if ($count) throwMiExcepcion("El email: " . $pJson['email'] . ", ya existe!", "warning");
    }

    $lastId = Users::registrarUser($params);
    if (!$lastId) throwMiExcepcion("Ningún registro guardado", "warning");
    Users::setActivityLog("Creación de registro en la tabla usuarios: " . $params["username"]);
    $registro = Users::getUser($lastId);
    $response['error'] = false;
    $response['msgType'] = "success";
    $response['msg'] = "Usuario registrado";
    $response['registro'] = $registro;
    return $response;
  }

  public function sign_up() // registrarse
  {
    // throwMiExcepcion("error de prueba", "error");
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 200);

    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    // Validacion de user
    if (trim($pJson['nombres']) == "") throwMiExcepcion("Nombres son requeridos", "warning", 200);
    if (trim($pJson['apellidos']) == "") throwMiExcepcion("Apellidos son requeridos", "warning", 200);

    // Buscando duplicados
    $count = Users::countRecordsBy(["username" => $pJson['username']]);
    if ($count) throwMiExcepcion("El usuario: " . $pJson['username'] . ", ya existe!", "warning");
    if ($pJson['email']) {
      $count = Users::countRecordsBy(["email" => $pJson['email']]);
      if ($count) throwMiExcepcion("El email: " . $pJson['email'] . ", ya existe!", "warning");
    }

    $params = [
      "nombres" => trimSpaces($pJson['nombres']),
      "apellidos" => trimSpaces($pJson['apellidos']),
      "username" => $pJson['username'],
      "password" => crypt($pJson['password'], $_ENV['SALT_PSW']),
      "email" => $pJson['email'] ? $pJson['email'] : null,
      "rol_id" => 19,
      "caja_id" => 1,
    ];

    $lastId = Users::registrarUser($params);
    $curUser = ["id" => $lastId, "rol_id" => $params["rol_id"]];
    Users::setCurUser($curUser);

    $jwt = $this->generateToken($lastId, $params["rol_id"]);
    Users::setToken($jwt, $lastId);
    $modulosSesion = Modulos::obtenerModulosSesion();
    unset($params["password"]);
    $params["id"] = $lastId;

    $response['error'] = false;
    $response['msg'] = "Registro satisfactorio";
    $response['msgType'] = "success";
    $response['token'] = $jwt;
    $response['registro'] = $params;
    $response['modulosSesion'] = $modulosSesion;
    return $response;
  }

  public function update_user()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'PUT') throwMiExcepcion("Método no permitido", "error", 405);

    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 200);

    $paramCampos = [
      "nombres" => trimSpaces($pJson['nombres']),
      "apellidos" => trimSpaces($pJson['apellidos']),
      "rol_id" => $pJson['rol_id'],
      "caja_id" => $pJson['caja_id'],
      "estado" => $pJson['estado'],
    ];

    // Validacion
    $this->validateUpdateUser($paramCampos);

    // Buscando duplicados
    $exclude = ["id" => $pJson['id']];
    $count = Users::countRecordsBy(["username" => $pJson['username']], $exclude);
    if ($count) throwMiExcepcion("El usuario: " . $pJson['username'] . ", ya existe!", "warning");
    $count = Users::countRecordsBy(["email" => $pJson['email']], $exclude);
    if ($count) throwMiExcepcion("El email: " . $pJson['email'] . ", ya existe!", "warning");

    $paramWhere = ["id" => $pJson['id']];

    $resp = Users::actualizarUser("users", $paramCampos, $paramWhere);
    if (!$resp) throwMiExcepcion("Ningún registro modificado", "warning", 200);
    
    $registro = Users::getUser($pJson['id']);
    Users::setActivityLog("Modificación de registro en la tabla usuarios: " . $registro["username"]);

    $response['msgType'] = "success";
    $response['msg'] = "Registro actualizado";
    $response['registro'] = $registro;
    return $response;
  }

  public function update_user_session()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'PUT') throwMiExcepcion("Método no permitido", "error", 405);

    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 200);

    if (trim($pJson['nombres']) == "") throwMiExcepcion("Nombres son requeridos", "warning", 200);
    if (trim($pJson['apellidos']) == "") throwMiExcepcion("Apellidos son requeridos", "warning", 200);
    if (trim($pJson['username']) == "") throwMiExcepcion("El usuario es requerido", "warning", 200);

    // Buscando duplicados
    $exclude = ["id" => $pJson['id']];
    $count = Users::countRecordsBy(["username" => $pJson['username']], $exclude);
    if ($count) throwMiExcepcion("El usuario: " . $pJson['username'] . ", ya existe!", "warning");
    $count = Users::countRecordsBy(["email" => $pJson['email']], $exclude);
    if ($count) throwMiExcepcion("El email: " . $pJson['email'] . ", ya existe!", "warning");

    $paramCampos = [
      "nombres" => trimSpaces($pJson['nombres']),
      "apellidos" => trimSpaces($pJson['apellidos']),
      "username" => trimSpaces($pJson['username']),
      "email" => $pJson['email'],
    ];

    if ($pJson['password']) {
      $paramCampos["password"] = crypt($pJson['password'], $_ENV['SALT_PSW']);
    }

    $paramWhere = ["id" => $pJson['id']];


    $resp = Users::actualizarUser("users", $paramCampos, $paramWhere);
    if (!$resp) throwMiExcepcion("Ningún registro modificado", "warning", 200);

    $registro = Users::getUser($pJson['id']);

    $response['msgType'] = "success";
    $response['msg'] = "Datos actualizados";
    $response['registro'] = $registro;
    return $response;
  }

  public function delete_user()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'DELETE') throwMiExcepcion("Método no permitido", "error", 405);
    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    $params = [
      "id" => $pJson['id'],
    ];
    $resp = Users::eliminarUser($params);
    if (!$resp) throwMiExcepcion("Ningún registro eliminado", "warning");

    $response['msgType'] = "success";
    $response['msg'] = "Registro eliminado";
    return $response;
  }

  public function get_email_by_username()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);

    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);

    $params = ["username" => $pJson['username'],];

    $userByUsername = Users::getUserBy($params);
    if (!$userByUsername) throwMiExcepcion("No se encontró al usuario " . $pJson['username'], "error");

    if (!$userByUsername["email"]) throwMiExcepcion("El usuario " . $pJson['username'] . " no tiene una cuenta de correo asociada", "error");

    $response['error'] = false;
    $response['msgType'] = "success";
    $response['msg'] = "El usuario " . $pJson['username'] . " tiene una cuenta de correo asociada";
    $response['email'] = $userByUsername["email"];
    return $response;
  }

  // Envía código de restauración de contraseña al email
  public function send_code_restoration()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);

    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);
    $email = $pJson['email'];
    $username = $pJson['username'];
    // Generando código de 6 dígitos.
    $code = rand(100000, 999999);
    // Enviando código al email.
    $body = "Esta es la clave para restablecer su contraseña: <strong>$code</strong>";
    $respuesta = Mailer::sendMail($email, "Restablecer contraseña", $body);
    if ($respuesta['error']) throwMiExcepcion($respuesta['msg'], "error");

    // Actualizando el usuario con el código de restauración
    $count = Users::actualizarUser("users", ["code_restore" => $code], ["username" => $username]);
    if (!$count) throwMiExcepcion("No se pudo completar la operación", "error");
    $response['error'] = false;
    $response['msgType'] = "success";
    $response['msg'] = "Se envió el código de restauración al correo: " . $email;
    return $response;
  }

  public function restore_password()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);

    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);
    $code = $pJson['code'];
    $new_password = $pJson['new_password'];
    $new_password_repeat = $pJson['new_password_repeat'];
    if ($new_password !== $new_password_repeat) throwMiExcepcion("Las contraseñas no son iguales", "error");
    $userByCode = Users::getUserBy(["code_restore" => $code]);
    if (!$userByCode) throwMiExcepcion("Código expirado o inválido", "error");

    // Actualizando el usuario con el código de restauración
    $id = $userByCode["id"];
    $password = crypt($new_password, $_ENV['SALT_PSW']);
    $paramCampos = [
      "password" => $password,
      "code_restore" => null
    ];
    $count = Users::actualizarUser("users", $paramCampos, ["id" => $id]);
    if (!$count) throwMiExcepcion("No se pudo completar la operación", "error");
    $response['error'] = false;
    $response['msgType'] = "success";
    $response['msg'] = "Se cambió la contraseña con éxito.";
    return $response;
  }
  //--> Inicia sesion, devuelve al user y los modulos asociados a su rol
  public function sign_in() // Logeaese
  {
    $parJson = json_decode(file_get_contents('php://input'), true) ?? [];

    $username = $parJson['username'] ?? '';
    $password = $parJson['password'] ?? '';
    if (!$username || !$password) throwMiExcepcion("Usuario y password requeridos", "warning", 200);

    $campos = [
      "id", 
      "nombres", 
      "apellidos", 
      "username", 
      "email", 
      "rol_id", 
      "caja_id", 
      "created_at", 
      "updated_at", 
      "estado"
    ];
    // Encriptando el password
    $password = crypt($parJson['password'], $_ENV['SALT_PSW']);

    // Obteniendo al user
    $equals = [
      ["campo_name" => "username", "value" => $username],
      ["campo_name" => "password", "value" => $password],
      ["campo_name" => "estado", "value" => 1],
    ];

    $registros = Users::getUsers("users", $campos, $equals);

    if (!$registros) throwMiExcepcion("Usuario o contrasenña incorrectos", "error", 200);

    $id = $registros[0]['id'];
    $rol_id = $registros[0]['rol_id'];
    // Generacion del token
    $jwt = $this->generateToken($id, $rol_id);
    // Guardando el token
    Users::setToken($jwt, $id);
    $curUser = ["id" => $id, "rol_id"=>$rol_id];
    Users::setCurUser($curUser);
    // Obteniendo el usuario y los modulos asociados al rol
    $registro = Users::getUserSession();
    $modulosSesion = Modulos::obtenerModulosSesion();

    $empresaSession = Configuraciones::obtenerEmpresaSession();

    $response['error'] = false;
    $response['msg'] = "Usuario logueado";
    $response['msgType'] = "success";
    $response['token'] = $jwt;
    $response['registro'] = $registro;
    $response['empresaSession'] = $empresaSession;
    $response['modulosSesion'] = $modulosSesion;

    return $response;
  }

  //--> Chekea token, devuelve al user del token y
  //--> los modulos aciciados a su rol
  public function check_auth()
  {
    $userSession = Users::getUserSession();

    $empresaSession = Configuraciones::obtenerEmpresaSession();

    $response['msgType'] = "success";
    $response['error'] = false;
    $response['msg'] = "Usuario autorizado";
    $response['registro'] = $userSession;
    $response['empresaSession'] = $empresaSession;
    return $response;
  }

  //--> Chekea token,
  public function check_token()
  {
    $response['msgType'] = "success";
    $response['error'] = false;
    $response['msg'] = "Session validada";
  }

  public function check_password()
  {
    $parJson = json_decode(file_get_contents('php://input'), true) ?? [];
    $password = $parJson['password'] ?? '';

    $campos = ["id"];
    // Encriptando el password
    $password = crypt($parJson['password'], $_ENV['SALT_PSW']);
    $user_id = Users::getCurUser()["id"];
    $equals = [
      ["campo_name" => "id", "value" => $user_id],
      ["campo_name" => "password", "value" => $password],
    ];

    $registros = Users::getUsers("users", $campos, $equals);
    if (!$registros) throwMiExcepcion("Contraseña incorrecta", "error", 200);
    $response["msg"] = "Contraseña correcta";
    $response["msgType"] = "success";
    return $response;
  }

  private function generateToken($id, $rolId)
  {
    $payload = [
      'iat' => time(),
      'exp' => time() + (60 * 60 * 3), // 3 horas
      // 'exp' => time() + (60 * 5), // 5 min
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

  private function validateCreateUser($params){
    $v = new Validator($params);
    $v->addRule('iguales', function ($field, $value, array $params, array $fields) {
      return $fields['password'] === $fields["password_repeat"];
    });
    $v->addRule('sinEspacios', function ($field, $value, array $params, array $fields) {
      return strpos($value, ' ') === false; // Verificar que no haya espacios en el valor
    });
    $v->rule('required', 'nombres')->message('El nombre es requerido');
    $v->rule('lengthMin', 'nombres', 3)->message('El nombre debe tener al menos 3 caracteres.');
    $v->rule('lengthMax', 'nombres', 50)->message('El nombre no puede exceder los 50 caracteres.');
    $v->rule('required', 'apellidos')->message('Los apellidos son requeridos');
    $v->rule('lengthMin', 'apellidos', 3)->message('Los apellidos deben tener al menos 3 caracteres.');
    $v->rule('lengthMax', 'apellidos', 50)->message('Los apellidos no puede exceder los 50 caracteres.');
    $v->rule('required', 'username')->message('El usuario es requerido');
    $v->rule('lengthMin', 'username', 3)->message('El usuario debe tener al menos 3 caracteres.');
    $v->rule('lengthMax', 'username', 50)->message('El usuario no puede exceder los 50 caracteres.');
    $v->rule('sinEspacios', 'username')->message('El usuario no puede tener espacios');
    $v->rule('email', 'email')->message('Ingrese un formato de email válido');
    $v->rule('required', 'password')->message('La contraseña es obligatoria');
    $v->rule('regex', 'password', '/^[A-Za-z\d@$!%*?&]{6,}$/')->message('La contraseña debe tener al menos 6 caracteres, sin espacios');
    $v->rule('iguales', 'password')->message('Los passwords no son iguales');;
    if (!$v->validate()) {
      foreach ($v->errors() as $campo => $errores) {
        foreach ($errores as $error) {
          throwMiExcepcion($error, "warning", 200);
        }
      }
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
      foreach ($v->errors() as $campo => $errores) {
        foreach ($errores as $error) {
          throwMiExcepcion($error, "warning", 200);
        }
      }
    }
  }
}
