<?php

class Middleware{
  static function check($authorization, $nombreModulo, $route){
    // echo "<pre>";
    // print_r($authorization);
    // echo "<br>";
    // print_r($moduloActual);
    // echo "<br>";
    // print_r($route);
    // echo "</pre>";
    if($authorization){
      $userSession = Users::checkToken($authorization); //['id'],['rol_id']
      $activity = [
        "prefixController" => $route["prefixController"],
        "accion" => $route["accion"],
      ];
      Users::setCurUser($userSession);
      Users::setActivity($activity);
    }else{
      switch ($route['prefixController']) {
        case 'users':{
          if($route['accion'] == 'sign_in') break;
          if($route['accion'] == 'sign_up') break;
        }
        default:{
          throwMiExcepcion("authErr: token empty", "error", 200);  
        }
      }
    }
  }
}