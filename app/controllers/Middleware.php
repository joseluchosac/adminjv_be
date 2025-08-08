<?php

class Middleware{
  static function check($route, $authorization, $attachedDataJson){
    if($attachedDataJson){
      $attachedData = json_decode($attachedDataJson, true);
      $curEstab = $attachedData["curEstab"] ?? 0;
      // $curModulo = $attachedData["curModulo"] ?? 0;
      Users::setCurEstab($curEstab);
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
        case 'test':{
          if($route['accion'] == 'sql_creator') break;
          if($route['accion'] == 'filter_laboratorios') break;
          if($route['accion'] == 'filter_productos') break;
          if($route['accion'] == 'test') break;
        }
        case 'config':{
          if($route['accion'] == 'get_empresa_session') break;
        }
        case 'establecimientos':{
          if($route['accion'] == 'get_establecimientos_options') break;
        }
        default:{
          throwMiExcepcion("authErr: token empty", "error", 200);  
        }
      }
    }
  }
}