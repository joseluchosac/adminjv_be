<?php
////// Lista de dominios permitidos

$allowedOrigins = [
  'http://localhost:5173',
  'https://admincli.josvelsac.com',
];
////// Obtener el dominio de origen de la solicitud
$origin = $_SERVER['HTTP_ORIGIN'];
////// Verificar si el dominio de origen está en la lista de permitidos
if (in_array($origin, $allowedOrigins)) {
  header("Access-Control-Allow-Origin: $origin");
  // echo "Es aceptable";
}

////// Permite peticiones desde cualquier origen
header("Access-Control-Allow-Credentials: true"); // Permitir el envío de credenciales (cookies)
////// Permite todos los encabezados
// header("Access-Control-Allow-Headers: *");

////// Permite encabezados específicos
header("Access-Control-Allow-Headers: nombre-modulo, Authorization, attached-data, X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");

// header("Content-Type:application/json");

////// Permite métodos HTTP específicos
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");

////// Si la solicitud es de tipo OPTIONS, termina la ejecución
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

require __DIR__ . '/../vendor/autoload.php';
require_once '../../app/libs/helpers.php';
require_once '../../app/libs/sqlGenerador.php';
require_once '../../app/libs/Exepcions.php';
require_once '../../app/services/MailerHostinger.php';
require_once('../../app/models/Users.php');
require_once('../../app/models/Modulos.php');
require_once('../../app/controllers/Middleware.php');


$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->load();

$authorization = apache_request_headers()['Authorization'] ?? null;
$nombreModulo = apache_request_headers()["nombre-modulo"] ?? null;
$attachedDataJson = apache_request_headers()["attached-data"] ?? null;
// echo "<pre>";
// print_r($authorization);
// echo "</pre>";
// exit;

try {
  $url = $_GET['url'] ?? '';
  $route = route($url); // ['prefixController'], ['controllerName'],['controllerPathname'],['accion']
  if($url) unset($_GET['url']);
  require_once($route['controllerPathname']); // ej require_once(controllers/ModulosController.php)
  
  if (method_exists($route['controllerName'], $route['accion'])) {
    ////// MIDDLEWARE
    Middleware::check($authorization, $nombreModulo, $attachedDataJson, $route);
    $currentController = new $route['controllerName'](); // ej: new ModulosController()
    $response = $currentController->{$route['accion']}(); // ej: $currentController->get_modulos()
    if(!$response) throwMiExcepcion("No hay datos de respuesta", "error", 404);
  } else {
    throwMiExcepcion("Accion no encontrada en controlador", "error", 404);
  }
} catch (MiExcepcion $e) {
  $params = $e->getParams();
  http_response_code($params['responseCode']);
  $response['error'] = true;
  $response['msg'] = $e->getMessage();
  $response['msgType'] = $params['msgType'];
  $response['content'] = $params['content'];
  if($e->getMessage() === "Expired token"){
    $response['msgType'] = "errorToken";
  }
}catch (\Throwable $e) {
  http_response_code(400);
  $response['content'] = null;
  $response['error'] = true;
  $response['msgType'] = "error";
  $response['msg'] = $e->getMessage();
}

echo json_encode($response);
