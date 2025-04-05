<?php
  // variables de entorno;
  require_once __DIR__ . "/../../../vendor/autoload.php";
  $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/../../..");
  $dotenv->load();

  require_once "../../../app/libs/helpers.php";
  require_once "../../../app/models/Users.php";
  if(!isset($_GET['p']) || !$_GET['p']){
    echo "No permitido";
    return;
  }

  $fechaPedido = base64_decode(urldecode($_GET['p']));

  $user = Users::getUserBy(["forgot" => $fechaPedido]);
  // $paramUrlDec = encriptacion("decrypt", $_GET['p']);
  print_r($user);
  if(!$user){
    echo "No hay datos para comprobar";
    return;
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Restore Password</title>
  <style>
    .formulario{
      display: flex;
      flex-direction: column;
    }
  </style>
</head>
<body>
  <form action="" class="formulario">
    <div>
      <label for="password">Nueva contraseña</label>
      <input type="password" name="password">
    </div>
    <div>
      <label for="password_repeat">Repita la nueva contraseña</label>
      <input type="password" name="password_repeat">
    </div>
    <div>
      
    </div>
  </form>
</body>
</html>