<?php
// ✅ GENERA Y DEVUELVE EN STRING O ARRAY LOS STOCKS DE LA TABLA PRODUCTOS
class StocksDB
{
  static public function getStocksArr($str){
    $rows=[];
    foreach(explode(" ", $str) as $row){
        $cols=[];
        foreach(explode(":", $row) as $idx => $col){
            if($idx === 0){
                $cols["establecimiento_id"] = $col;
            }else{
                $cols["stock"] = $col;
            }
        }
        $rows[] = $cols;
    }
    return $rows;
  }

  static public function getStocksStr($arr){
    $res = array_map(function($el){
        return $el["establecimiento_id"] . ":" . $el["stock"];
    }, $arr);
    return implode(" ", $res);
  }

}

class MiExcepcion extends Exception {
  // USAGE MiExcepcion
    /* try {
        Lanzar la excepción personalizada con datos adicionales
        throw new MiExcepcion("Se produjo un error", ["param1" => "Valor adicional 1", "param2" => "Valor adicional 2"]);
    } catch (MiExcepcion $e) {
        Acceder a los datos adicionales
        echo "Error: " . $e->getMessage() . "<br>";
        $params = $e->getParams();
        echo "Parámetro adicional 1: " . $params["param1"] . "<br>";
        echo "Parámetro adicional 2: " . $params["param2"];
    } */
 //
  private $params;
  public function __construct($message, $params, $code = 0, Exception $previous = null) {
      $this->params = $params;
      parent::__construct($message, $code, $previous);
  }

  public function getParams() {
      return $this->params;
  }
}

// ✅ GENERA EXPRESIONES SQL Y ARRAYS PREPARADOS PARA FILTRAR REGISTROS
class MyORM
{
  // EJEMPLO DE PARAMETROS JSON
    /* $pJson = '{
      "offset":25,
      "search": {
        "fieldsName":["apellidos","nombres"],
        "like":"cabrera"
      },
      "equal":[
        {"field_name":"rol_id","field_value":[2,3]},
        {"field_name":"estado","field_value":1}
      ],
      "between":[
        {"field_name":"created_at","from":"2024-12-17", "to":"2024-12-19"},
        {"field_name":"updated_at","from":"2025-03-21", "to":"2025-12-18"}
      ],
      "order":[
        {"field_name": "apellidos", "order_dir": "ASC"},
        {"field_name": "nombres", "order_dir": "DESC"}
      ]
    }' */
  //
  static public function getWhere($p)
  {
    $where = [];
    // Seccion Like
    $search = $p['search'] ?? null;
    $likeParams = [];
    if ($search && strlen($search['like']) !== 0) {
      foreach ($search['fieldsName'] as $key => $value) {
        $likeParams[$value] = "%" . $search['like'] . "%";
      };
    }
    $likeSql = self::getLikeSql($likeParams);

    // Seccion Equal
    $pEqual = [];
    foreach ($p['equal'] as $value) {
      $pEqual[$value['field_name']] = $value['field_value'];
    }

    $equalParams = self::getEqualParams($pEqual);
    $equalSql = self::getEqualSql($pEqual);

    // Seccion Between
    $betweenParams = self::getBetweenParams($p['between']);
    $betweenSql = self::getBetweenSql($p['between']);
    // devolviendo los parametros
    $where['params'] =  array_merge($likeParams, $equalParams, $betweenParams);
    $where['sql'] = implode(" AND ", array_merge($likeSql, $equalSql, $betweenSql));
    $where['sql'] = $where['sql'] ? " WHERE " . $where['sql'] : "";
    return $where;
  }

  static public function getOrder($pOrder)
  {
    $arr = [];
    foreach ($pOrder as $el) {
      $arr[] = $el['field_name'] . " " . $el['order_dir'];
    };
    $res = implode(", ", $arr);
    return $res ? " ORDER BY " . $res : " ORDER BY id DESC";
  }

  static private function getLikeSql($paramsLike)
  {
    if (!count($paramsLike)) return [];
    $arrayLike = array_map(function ($el) {
      return "$el like :$el";
    }, array_keys($paramsLike));
    $like[] = $arrayLike ? "(" . implode(" OR ", $arrayLike) . ")" : "";
    return $like;
  }

  static private function getEqualParams($pEqual)
  {
    $paramsEqualOr = array_filter($pEqual, function ($el) {
      return gettype($el) === "array";
    });
    $or = [];
    foreach ($paramsEqualOr as $campo => $valorAnd) {
      foreach ($valorAnd as $idx => $valorOr) {
        $or[$campo . $idx] = $valorOr;
      }
    }

    $paramsEqualAnd = array_filter($pEqual, function ($el) {
      return gettype($el) !== "array";
    });
    return array_merge($paramsEqualAnd, $or);
  }

  static private function getEqualSql($pEqual)
  {
    $paramsEqualAnd = array_filter($pEqual, function ($el) {
      return gettype($el) !== "array";
    });

    $arrayAnd0 = array_map(function ($el) {
      return "$el = :$el";
    }, array_keys($paramsEqualAnd));

    $paramsEqualOr = array_filter($pEqual, function ($el) {
      return gettype($el) === "array";
    });

    $arrayOr = [];
    $arrayAnd1 = [];
    foreach ($paramsEqualOr as $campo => $valorAnd) {
      $arrayOr = array_map(function ($el) use ($campo) {
        return "$campo = :$campo$el";
      }, array_keys($valorAnd));
      $arrayAnd1[] = "(" . implode(" OR ", $arrayOr) . ")";
    }

    return array_merge($arrayAnd0, $arrayAnd1);
  }

  static private function getBetweenParams($pBetewen)
  {
    $par = [];
    foreach ($pBetewen as $key => $value) {
      $par["from$key"] = $value['from'];
      $par["to$key"] = $value['to'];
    }
    return $par;
  }

  static private function getBetweenSql($pBetewen)
  {
    $sql = [];
    foreach ($pBetewen as $key => $value) {
      $sql[] = $value['field_name'] . " BETWEEN " . ":from$key AND :to$key";
    }
    return $sql;
  }
}

// para deprecar
class SqlWhere{
  //✅ USO SqlWhere 
      // PARAMETROS WHERE del front
      /* $parLike = [
        "nombre" => "jose",
        "apellido" => "paco",
      ];

      $parEquals = [
        ["field_name" => "estado", "field_value"=>1],
        ["field_name" => "sexo", "field_value"=>"f"],
      ];
      
      $between = [
        "campo" => "created_at",
        "rango" => "2024, 2025"
      ];

      // GENERANDO EL SQL
      echo SqlWhere::and([
        SqlWhere::likeAnd($parLike),
        SqlWhere::equalAnd($parEquals),
        SqlWhere::between($between),
      ]); */

      //// RESULTADO DEL SQL
      // WHERE (nombre LIKE :nombre AND apellido LIKE :apellido) AND (estado = :estado AND sexo = :sexo) AND (created_at BETWEEN :from AND :to)

      //// GENERANDO LOS ARRAYS DE LOS PARAMTROS
      // $print_r(SqlWhere::arrMerge($parLike, $parEquals, $between));
      /* $bindWhere = SqlWhere::arrMerge([
          "like" => $parLike, 
          "equal" => $parEquals, 
          "between" => $between
        ]); */
      //// RESULTADO DEL ARREGLO MERGEADO
      /* (
        [nombre] => jose
        [apellido] => paco
        [estado] => 1
        [sexo] => f
        [from] => 2023
        [to] => 2024
      ) */
  //
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
          if (strlen(trim($item["field_value"])) === 0) continue;
          $equals[$item["field_name"]] = $item["field_value"];
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


