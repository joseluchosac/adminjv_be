<?php

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
