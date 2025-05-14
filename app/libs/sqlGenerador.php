<?php

class SqlWhere{
  private static function arrFilter($arr){
      return array_filter($arr, function($valor) {
          // Eliminar elementos con valor null o cadena vacía
          return $valor !== null && $valor !== '';
      });
  }
  private static function getSqlWhere($datos, $operator, $conector){
      $datos = self::arrFilter($datos);
      $res = array_map(function($el) use($operator){
          return "$el $operator :$el";
      },array_keys($datos));
      
      return !empty($datos) 
          ? '(' . implode(" $conector ", $res) . ')' 
          : '';
  }
  static function likeAnd($datos){
      $dato = self::getSqlWhere($datos, 'LIKE', "AND");
      return $dato ? $dato : "";
  }
  static function likeOr($datos){
      $dato = self::getSqlWhere($datos, 'LIKE', "OR");
      return $dato ? $dato : "";
  }
  static function equalAnd($datos){
      $arr = self::arrEqual($datos);
      $dato = self::getSqlWhere($arr, "=", "AND");
      return $dato ? $dato : "";
  }
  static function equalOr($datos){
      $dato = self::getSqlWhere($datos, "=", "OR");
      return $dato ? $dato : "";
  }
  static function notEqualAnd($datos){
      $dato = self::getSqlWhere($datos, "!=", "AND");
      return $dato ? $dato : "";
  }
  static function notEqualOr($datos){
      $dato = self::getSqlWhere($datos, "!=", "OR");
      return $dato ? $dato : "";
  }
  static function between($datos){
      return $datos['campo'] 
          ? "(" . $datos['campo'] . " BETWEEN :from AND :to)"
          : '';
  }
  static function and($arr){
      $arr = array_filter($arr);
      return !empty($arr) ? " WHERE " . implode(" AND ",$arr) : '';
  }
  static function or($arr){
      $arr = array_filter($arr);
      return !empty($arr) ? " WHERE " . implode(" OR ",$arr) : '';
  }
  static function arrEqual($datos){
      $equals = [];
      foreach($datos as $item){
          if (strlen(trim($item["value"])) === 0) continue;
          $equals[$item["campo_name"]] = $item["value"];
      }
      return self::arrFilter($equals);
  }
  static function arrBetween($datos){
      if(!$datos['campo'] || !$datos['rango']) return [];
      $between['from'] = explode(", ", $datos['rango'])[0];
      $between['to'] = explode(", ", $datos['rango'])[1];
      return self::arrFilter($between);
  }
  static function arrMerge($arr){
      $like = isset($arr['like']) ? self::arrFilter($arr['like']) : [];
      $arrEqual = isset($arr['equal']) ? self::arrEqual($arr['equal']) : [];
      $arrBetween = isset($arr['between']) ? self::arrBetween($arr['between']) : [];
      return array_merge($like, $arrEqual, $arrBetween);
  }
}
////////// USO SqlWhere ///////////
    // PARAMETROS WHERE del front
    // $parLike = [
    //   "nombre" => "jose",
    //   "apellido" => "paco",
    // ];

    // $parEquals = [
    //   ["campo_name" => "estado", "value"=>1],
    //   ["campo_name" => "sexo", "value"=>"f"],
    // ];
    
    // $between = [
    //   "campo" => "created_at",
    //   "rango" => "2024, 2025"
    // ];

    //// GENERANDO EL SQL
    // echo SqlWhere::and([
    //   SqlWhere::likeAnd($parLike),
    //   SqlWhere::equalAnd($parEquals),
    //   SqlWhere::between($between),
    // ]);

    //// RESULTADO DEL SQL
    // WHERE (nombre LIKE :nombre AND apellido LIKE :apellido) AND (estado = :estado AND sexo = :sexo) AND (created_at BETWEEN :from AND :to)

    //// GENERANDO LOS ARRAYS DE LOS PARAMTROS
    // $print_r(SqlWhere::arrMerge($parLike, $parEquals, $between));
    // $bindWhere = SqlWhere::arrMerge([
    //     "like" => $parLike, 
    //     "equal" => $parEquals, 
    //     "between" => $between
    //   ]);
    //// RESULTADO DEL ARREGLO MERGEADO
    // (
    //   [nombre] => jose
    //   [apellido] => paco
    //   [estado] => 1
    //   [sexo] => f
    //   [from] => 2023
    //   [to] => 2024
    // )
/////////////////

// CONSTRUCTION DE LA SENTENCIA ORDER BY EN MYSQL
function getSqlOrderBy($orders)
{
  if (!$orders) {
    return "";
  }
  $sql = "";
  foreach ($orders as $valor) {
    $sql = $sql . $valor['campo_name'] . " " . $valor['order_dir'] . ", ";
  }
  $sql = " ORDER BY " . substr(trim($sql), 0, -1);
  return $sql;
}
///////////// USO getSqlOrderBy ////////
    // $orders = [
    //   ["campo_name" => "apellidos", "order_dir" => "asc"],
    //   ["campo_name" => "nombres", "order_dir" => "desc"],
    // ];
/////////////////////////
