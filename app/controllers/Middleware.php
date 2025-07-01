<?php

class Middleware{
  static function check($authorization, $nombreModulo, $attachedDataJson, $route){
    // echo "<pre>";
    // print_r($authorization);
    // echo "<br>";
    // print_r($moduloActual);
    // echo "<br>";
    // var_dump($attachedDataJson);
    // echo "</pre>";
    if($attachedDataJson){
      $attachedData = json_decode($attachedDataJson, true);
      $thisTerm = $attachedData["thisTerm"] ?? null;
      Users::setCurTerm($thisTerm);
    }

    if($authorization){
      $token = explode(" ", $authorization)[1];
      $userSession = Users::checkToken($token); //['id'],['rol_id']
      $activity = [
        "prefixController" => $route["prefixController"],
        "accion" => $route["accion"],
      ];
      Users::setCurUser($userSession);
      Users::setActivity($activity);
    }else{
      switch ($route['prefixController']) { // Evita comprobación de autorization (token)
        case 'users':{
          if($route['accion'] == 'sign_in') break;
          if($route['accion'] == 'sign_up') break;
          if($route['accion'] == 'get_email_by_username') break; // Para recuperar cuenta
        }
        case 'productos':{
          if($route['accion'] == 'prueba') break;
        }
        default:{
          throwMiExcepcion("authErr: token empty", "error", 200);  
        }
      }
    }
  }
}