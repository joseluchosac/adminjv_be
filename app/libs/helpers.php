<?php
function route($url)
{
  if (!$url) {
    throwMiExcepcion("404 Not Found", "error", 404);
  } else {

    $urlArr = array_filter(explode("/", $url));

    if (!isset($urlArr[0])) {
      throwMiExcepcion("Error controlador es necesario", "error", 404);
    };
    if (!isset($urlArr[1])) {
      throwMiExcepcion("Error la accion es necesaria", "error", 404);
    }
    if (isset($urlArr[2])) {
      throwMiExcepcion("Demasiados parametros", "error", 404);
    }
    $prefixController = ucfirst($urlArr[0]);
    $controllerName = $prefixController . "Controller";
  }
  $controllerPathname = "../../app/controllers/" . $controllerName . ".php";
  if (!file_exists($controllerPathname)) {
    throwMiExcepcion("No existe el archivo controlador", "error", 404);
  }
  $route['prefixController'] = strtolower($prefixController); // ej. users
  $route['controllerName'] = $controllerName; // ej. UsersController
  $route['controllerPathname'] = $controllerPathname; // ej. .../controllers/UsersController.php
  $route['accion'] = $urlArr[1]; // ej. get_user
  //   $route['accionParam'] = $urlArr[2] ?? '';
  return $route;
}
// Funcion que devuelve http o https: https://josvelsac.com
function getBaseUrl()
{
  $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
  return $protocolo . $_SERVER['SERVER_NAME'];
}
// Función que devuelve un nuevo arreglo quitando
// los elementos que tengan como valor vacio o null
function limpiarArreglo($array)
{
  $newArray = array();
  foreach ($array as $key => $value) {
    if ($value !== null && $value !== "") $newArray[$key] = $value;
  }
  return $newArray;
}

// trimSpaces elimina mas de dos espacios en el inicio, final y entre palabras
// puede recibir como parámetro una cadena o un arreglo, (este último solo toma en cuenta las cadenas)
function trimSpaces($variable)
{
  if (!function_exists("trimear")) {
    function trimear($str)
    {
      return preg_replace(['/\s\s+/', '/^\s|\s$/'], [' ', ''], $str);
    }
  }

  if (gettype($variable) === "array") {
    $newArray = [];
    foreach ($variable as $key => $value) {
      if (gettype($value) === "string") {
        $newArray[$key] = trimear($value);
      } else {
        $newArray[$key] = $value;
      }
    }
    return $newArray;
  } else {
    if (gettype($variable) === "string") {
      return trimear($variable);
    } else {
      return $variable;
    }
  }
}

function removeSpecialChar($str)
{
  $res = preg_replace('/[^A-Za-z0-9\-ñÑáéíóúÁÉÍÓÚüÜ\s]/', '', $str);
  return $res;
}

function validateEmail($str)
{
  return filter_var($str, FILTER_VALIDATE_EMAIL);
}
// Genera una sentencia SQL INSERT
function sqlInsert($table, $columns)
{
    if(!$columns || !$table) return "";
  $sql = "INSERT INTO $table";
  $camposArr = array_keys($columns);
  $campos = implode(", ", $camposArr);
  $params = "";
  foreach($camposArr as $campo){
      $params .= ":" . $campo . ", ";
  }
  $params = substr($params, 0, -2);
  return $sql . " (" . $campos . ") VALUES (" . $params . ")";
}

// Genera una sentencia SQL UPDATE
function sqlUpdate($table, $parSet, $parWhere)
{
  $sqlSet = '';
  forEach(array_keys($parSet) as $value){
      $sqlSet .= $value . " = :" . $value . ", ";
  }
  $sqlSet = " SET ".substr($sqlSet, 0, -2);
  $sqlWhere = '';
  forEach(array_keys($parWhere) as $value){
      $sqlWhere .= $value . " = :" . $value . " AND ";
  }
  $sqlWhere = " WHERE ".substr($sqlWhere, 0, -5);
  return "UPDATE " . $table . $sqlSet .$sqlWhere; 
}

// Concatena elementos de un arreglo
function concatenar($arg, $separator = ' ')
{
  $resultado = '';
  if (count($arg) === 0) return "";
  foreach ($arg as $key => $value) {
    if ($key === 0) {
      $resultado .= $value ? $value : "";
    } else {
      if ($resultado) {
        $resultado .= $value ? $separator . $value : "";
      } else {
        $resultado .= $value ? $value : "";
      }
    }
  }
  return $resultado;
}

// Devuelve numero de 25465.5458 a 25,465.55
function numeroMoneda($numero)
{
  return number_format(round($numero, 2), 2);
}

// Devuelve fecha en formato amigable ejem: 2013-03-15 15:45:02
// "d/m/Y" -> 15/03/2013
// "d/m/Y H:i:s" -> 15/03/2013 15:45:02
// "d/m/Y h:i:s a" -> 15/03/2013 03:45:02 pm
// "d/m/Y h:i a" -> 15/03/2013 03:45 pm
function fechaFormato($fecha_str, $format = 'd/m/Y')
{
  return date_format(date_create($fecha_str), $format);
}

function numeroALetra($arg)
{
  function convertirNumeroLetra($numero)
  {
    $numeroEntero = floor($numero);
    $numf = milmillon($numeroEntero);
    //parte fraccion
    $fraction = round(($numero - $numeroEntero) * 100);
    if ($fraction < 10) {
      $fraction = "0" . $fraction;
    }
    if ($numf == "") {
      return $fraction . "/100 SOLES";
    } else {
      return $numf . " CON " . $fraction . "/100 SOLES";
    }
  }
  function milmillon($nummierod)
  {
    if ($nummierod >= 1000000000 && $nummierod < 2000000000) {
      $num_letrammd = "MIL " . (cienmillon($nummierod % 1000000000));
    }
    if ($nummierod >= 2000000000 && $nummierod < 10000000000) {
      $num_letrammd = unidad(Floor($nummierod / 1000000000)) . " MIL " . (cienmillon($nummierod % 1000000000));
    }
    if ($nummierod < 1000000000)
      $num_letrammd = cienmillon($nummierod);

    return $num_letrammd;
  }
  function cienmillon($numcmeros)
  {
    if ($numcmeros == 100000000)
      $num_letracms = "CIEN MILLONES";
    if ($numcmeros >= 100000000 && $numcmeros < 1000000000) {
      $num_letracms = centena(Floor($numcmeros / 1000000)) . " MILLONES " . (millon($numcmeros % 1000000));
    }
    if ($numcmeros < 100000000)
      $num_letracms = decmillon($numcmeros);
    return $num_letracms;
  }
  function decmillon($numerodm)
  {
    if ($numerodm == 10000000)
      $num_letradmm = "DIEZ MILLONES";
    if ($numerodm > 10000000 && $numerodm < 20000000) {
      $num_letradmm = decena(Floor($numerodm / 1000000)) . "MILLONES " . (cienmiles($numerodm % 1000000));
    }
    if ($numerodm >= 20000000 && $numerodm < 100000000) {
      $num_letradmm = decena(Floor($numerodm / 1000000)) . " MILLONES " . (millon($numerodm % 1000000));
    }
    if ($numerodm < 10000000)
      $num_letradmm = millon($numerodm);

    return $num_letradmm;
  }
  function millon($nummiero)
  {
    if ($nummiero >= 1000000 && $nummiero < 2000000) {
      $num_letramm = "UN MILLON " . (cienmiles($nummiero % 1000000));
    }
    if ($nummiero >= 2000000 && $nummiero < 10000000) {
      $num_letramm = unidad(Floor($nummiero / 1000000)) . " MILLONES " . (cienmiles($nummiero % 1000000));
    }
    if ($nummiero < 1000000)
      $num_letramm = cienmiles($nummiero);

    return $num_letramm;
  }
  function cienmiles($numcmero)
  {
    if ($numcmero == 100000)
      $num_letracm = "CIEN MIL";
    if ($numcmero >= 100000 && $numcmero < 1000000) {
      $num_letracm = centena(Floor($numcmero / 1000)) . " MIL " . (centena($numcmero % 1000));
    }
    if ($numcmero < 100000)
      $num_letracm = decmiles($numcmero);
    return $num_letracm;
  }
  function decmiles($numdmero)
  {
    if ($numdmero == 10000)
      $numde = "DIEZ MIL";
    if ($numdmero > 10000 && $numdmero < 20000) {
      $numde = decena(Floor($numdmero / 1000)) . "MIL " . (centena($numdmero % 1000));
    }
    if ($numdmero >= 20000 && $numdmero < 100000) {
      $numde = decena(Floor($numdmero / 1000)) . " MIL " . (miles($numdmero % 1000));
    }
    if ($numdmero < 10000)
      $numde = miles($numdmero);

    return $numde;
  }
  function miles($nummero)
  {
    if ($nummero >= 1000 && $nummero < 2000) {
      $numm = "MIL " . (centena($nummero % 1000));
    }
    if ($nummero >= 2000 && $nummero < 10000) {
      $numm = unidad(Floor($nummero / 1000)) . " MIL " . (centena($nummero % 1000));
    }
    if ($nummero < 1000)
      $numm = centena($nummero);

    return $numm;
  }
  function centena($numc)
  {
    if ($numc >= 100) {
      if ($numc >= 900 && $numc <= 999) {
        $numce = "NOVECIENTOS ";
        if ($numc > 900)
          $numce = $numce . (decena($numc - 900));
      } else if ($numc >= 800 && $numc <= 899) {
        $numce = "OCHOCIENTOS ";
        if ($numc > 800)
          $numce = $numce . (decena($numc - 800));
      } else if ($numc >= 700 && $numc <= 799) {
        $numce = "SETECIENTOS ";
        if ($numc > 700)
          $numce = $numce . (decena($numc - 700));
      } else if ($numc >= 600 && $numc <= 699) {
        $numce = "SEISCIENTOS ";
        if ($numc > 600)
          $numce = $numce . (decena($numc - 600));
      } else if ($numc >= 500 && $numc <= 599) {
        $numce = "QUINIENTOS ";
        if ($numc > 500)
          $numce = $numce . (decena($numc - 500));
      } else if ($numc >= 400 && $numc <= 499) {
        $numce = "CUATROCIENTOS ";
        if ($numc > 400)
          $numce = $numce . (decena($numc - 400));
      } else if ($numc >= 300 && $numc <= 399) {
        $numce = "TRESCIENTOS ";
        if ($numc > 300)
          $numce = $numce . (decena($numc - 300));
      } else if ($numc >= 200 && $numc <= 299) {
        $numce = "DOSCIENTOS ";
        if ($numc > 200)
          $numce = $numce . (decena($numc - 200));
      } else if ($numc >= 100 && $numc <= 199) {
        if ($numc == 100)
          $numce = "CIEN ";
        else
          $numce = "CIENTO " . (decena($numc - 100));
      }
    } else
      $numce = decena($numc);

    return $numce;
  }
  function decena($numdero)
  {

    if ($numdero >= 90 && $numdero <= 99) {
      $numd = "NOVENTA ";
      if ($numdero > 90)
        $numd = $numd . "Y " . (unidad($numdero - 90));
    } else if ($numdero >= 80 && $numdero <= 89) {
      $numd = "OCHENTA ";
      if ($numdero > 80)
        $numd = $numd . "Y " . (unidad($numdero - 80));
    } else if ($numdero >= 70 && $numdero <= 79) {
      $numd = "SETENTA ";
      if ($numdero > 70)
        $numd = $numd . "Y " . (unidad($numdero - 70));
    } else if ($numdero >= 60 && $numdero <= 69) {
      $numd = "SESENTA ";
      if ($numdero > 60)
        $numd = $numd . "Y " . (unidad($numdero - 60));
    } else if ($numdero >= 50 && $numdero <= 59) {
      $numd = "CINCUENTA ";
      if ($numdero > 50)
        $numd = $numd . "Y " . (unidad($numdero - 50));
    } else if ($numdero >= 40 && $numdero <= 49) {
      $numd = "CUARENTA ";
      if ($numdero > 40)
        $numd = $numd . "Y " . (unidad($numdero - 40));
    } else if ($numdero >= 30 && $numdero <= 39) {
      $numd = "TREINTA ";
      if ($numdero > 30)
        $numd = $numd . "Y " . (unidad($numdero - 30));
    } else if ($numdero >= 20 && $numdero <= 29) {
      if ($numdero == 20)
        $numd = "VEINTE ";
      else
        $numd = "VEINTI" . (unidad($numdero - 20));
    } else if ($numdero >= 10 && $numdero <= 19) {
      switch ($numdero) {
        case 10: {
            $numd = "DIEZ ";
            break;
          }
        case 11: {
            $numd = "ONCE ";
            break;
          }
        case 12: {
            $numd = "DOCE ";
            break;
          }
        case 13: {
            $numd = "TRECE ";
            break;
          }
        case 14: {
            $numd = "CATORCE ";
            break;
          }
        case 15: {
            $numd = "QUINCE ";
            break;
          }
        case 16: {
            $numd = "DIECISEIS ";
            break;
          }
        case 17: {
            $numd = "DIECISIETE ";
            break;
          }
        case 18: {
            $numd = "DIECIOCHO ";
            break;
          }
        case 19: {
            $numd = "DIECINUEVE ";
            break;
          }
      }
    } else
      $numd = unidad($numdero);
    return $numd;
  }
  function unidad($numuero)
  {
    switch ($numuero) {
      case 9: {
          $numu = "NUEVE";
          break;
        }
      case 8: {
          $numu = "OCHO";
          break;
        }
      case 7: {
          $numu = "SIETE";
          break;
        }
      case 6: {
          $numu = "SEIS";
          break;
        }
      case 5: {
          $numu = "CINCO";
          break;
        }
      case 4: {
          $numu = "CUATRO";
          break;
        }
      case 3: {
          $numu = "TRES";
          break;
        }
      case 2: {
          $numu = "DOS";
          break;
        }
      case 1: {
          $numu = "UN";
          break;
        }
      case 0: {
          $numu = "";
          break;
        }
    }
    return $numu;
  }
  return convertirNumeroLetra($arg);
}


function array_find($array, $callback) {
  foreach ($array as $value) {
      if ($callback($value)) {
          return $value;
      }
  }
  return null; // Si no encuentra nada, retorna null.
}

function dateUTCToLocal($format, $strTimezone, $date = 'now')
{
  // ej strTimezone = 'America/Lima'
  $fecha_utc = new DateTime($date, new DateTimeZone('UTC'));
  $fecha_utc->setTimezone(new DateTimeZone($strTimezone));
  return $fecha_utc->format($format);
}
// DEVUELVE FACHA DE LOCAL A UTC
function dateLocalToUTC($format,  $strTimezone, $date = 'now')
{
  // ej strTimezone = 'America/Lima'
  $fecha_utc = new DateTime($date, new DateTimeZone($strTimezone));
  $fecha_utc->setTimezone(new DateTimeZone('UTC'));
  return $fecha_utc->format($format);
}

function getDateToDatetime($strDate)
{
  $arrayFecha = explode(" ", $strDate);
  return $arrayFecha[0] . " " . ($arrayFecha[1] ?? '00:00:00');
}


function throwMiExcepcion( $msg, $msgType = "error", $responseCode = 400, $content=null)
{
  throw new MiExcepcion(
    $msg, 
    [
      "msgType" => $msgType,
      "responseCode" => $responseCode,
      "content" => $content,
    ]
  );
}

function encriptacion($type, $data){
  $key = hash('sha256', "jose", true);
  $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
  if($type === "encrypt"){
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
    return urlencode(base64_encode($encrypted . '::' . $iv));
  }else if($type === "decrypt"){
    $array = explode('::', base64_decode(urldecode($data)), 2);
    if(count($array) !== 2) return null;
    list($encrypted_data, $iv) = $array;
    return openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 0, $iv);
  } else {
    return null;
  }
}
// Busca el indice de un arreglo 
function findIndex($array, $callback) {
  foreach ($array as $index => $value) {
      if ($callback($value)) {
          return $index;
      }
  }
  return -1; // Retorna -1 si no se encuentra el valor
}


function transponerArreglo($arreglo)
{
  $transpuesto = array();
  foreach ($arreglo as $subArreglo) {
    $transpuesto[$subArreglo['clave']] = $subArreglo['valor'];
  }
  return $transpuesto;
}

function normalize_url_path($path) {
  $path = str_replace(array('/', '\\'), "/", $path);
  $parts = array_filter(explode("/", $path), 'strlen');
  $absolutes = array();
  foreach ($parts as $part) {
      if ('.' == $part) continue;
      if ('..' == $part) {
          array_pop($absolutes);
      } else {
          $absolutes[] = $part;
      }
  }
  return "/".implode("/", $absolutes);
}


// ✅ FUNCION RECURSIVA QUE DEVUELVE UN NUEVO ARREGLO DE LOS ANCESTROS DE UN
// ELEMENTO A PARTIR DE UN ARREGLO DE ELEMENTOS
// [..., elementoAbuelo, elementoPadre, ElementoHijo]
// $arreglo = [
//     ['id'=>1, 'desc'=>'medicinas', "padre_id"=>0],
//     ['id'=>2, 'desc'=>'de marca', "padre_id"=>1],
//     ['id'=>3, 'desc'=>'generico', "padre_id"=>1],
//     ['id'=>4, 'desc'=>'antibioticos', "padre_id"=>2],
//     ['id'=>5, 'desc'=>'antibioticos', "padre_id"=>3],
//     ['id'=>6, 'desc'=>'ciprofloxacino', "padre_id"=>4],
// ];
function getBranch($id, $arreglo, $branch=[]){
    $elemento = [];
    foreach($arreglo as $value){
        if($value["id"] === $id){
            $elemento = $value;
            array_unshift($branch, $value);
            break;
        }
    }
    if($elemento){
        return getBranch($elemento["padre_id"], $arreglo, $branch);
    }else{
        return $branch;
    };
};
// ✅ UTILIDADES PARA CAMBIAR EL TEXTO A ARREGLO Y VICEVERSA DEL CAMPO categoria_ids DE LA TABLA productos
// $texto = ",7,2,13,4,";
// var_dump(array_map(function($el){
//     return intval($el);
// },array_filter(explode(",",$texto))));

// $arreglo = [7,2,13,4];
// var_dump(",".implode(",", $arreglo).",");



// ✅ FUNCION QUE DEVUELVE UN SLUG A PARTIR DE UN STRING
// $titulo = "Título de mi publicación con caracteres especiales, espacios y  ¡mucho más!";
// $slug = generarSlug($titulo);
// echo $slug; // Output: titulo-de-mi-publicacion-con-caracteres-especiales-espacios-y-mucho-mas
function generarSlug(string $texto): string
{
  $slug = strtolower($texto);  // 1. Convertir a minúsculas
  $slug = preg_replace('/[^a-z0-9\-_\s]/', '', $slug);  // 2. Eliminar caracteres especiales
  $slug = str_replace(' ', '-', $slug);  // 3. Reemplazar espacios con guiones
  $slug = trim($slug, '-');  // 4. Recortar guiones
  $slug = iconv('utf-8', 'ascii//TRANSLIT', $slug);  // 5. (Opcional) Eliminar caracteres no deseados
  return $slug;
}

// ✅ FUNCION QUE DEVUELVE UN ARREGLO JERARQUIZADO
function generateTree($arreglo, $padre_id = 0) {
  $arbol = array();
  foreach ($arreglo as $categoria) {
      if ($categoria['padre_id'] == $padre_id) {
          $children = generateTree($arreglo, $categoria['id']);
          if ($children) {
              $categoria['children'] = $children;
          }else{
            $categoria['children'] = [];
          }
          $arbol[] = $categoria;
      }
  }
  return $arbol;
}

// ✅ FUNCION QUE APLANA UN ARREGLO JERARQUIZADO
function flattenTree($tree, $padre_id = 0, &$resultado = []) {
  foreach ($tree as $nodo) {
      // Extraemos los hijos si existen y los eliminamos del nodo actual
      $children = isset($nodo['children']) ? $nodo['children'] : [];
      unset($nodo['children']);

      // Establecemos el padre_id correcto
      $nodo['padre_id'] = $padre_id;
      
      // Añadimos el nodo actual al resultado
      $resultado[] = $nodo;
      
      // Si tiene children, los procesamos recursivamente
      if (!empty($children)) {
          flattenTree($children, $nodo['id'], $resultado);
      }
  }
  
  return $resultado;
}
