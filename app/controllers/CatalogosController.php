<?php
require_once('../../app/models/Catalogos.php');


class CatalogosController
{
  public function get_catalogos(){
    $tablas = [
      ["table" => "roles", "sql" => "SELECT id, rol, estado FROM roles"],
      ["table" => "cajas", "sql" => "SELECT id, descripcion, estado FROM cajas"],
      ["table" => "formas_pago", "sql" => "SELECT id, descripcion, estado FROM formas_pago"],
      ["table" => "impuestos", 
        "sql" => "SELECT 
          id,
          afectacion_igv_cod,
          afectacion_igv_desc,
          letra_tributo,
          codigo_tributo,
          nombre_tributo,
          tipo_tributo,
          CAST(porcentaje AS FLOAT) AS porcentaje,
          CAST(importe AS FLOAT) AS importe,
          pred,
          estado
        FROM impuestos"
      ],
      ["table" => "motivos_nota", 
        "sql" => "SELECT 
          id,
          tipo_comprobante_cod,
          tipo,
          codigo,
          descripcion,
          estado
        FROM motivos_nota"
      ],
      ["table" => "tipos_comprobante", 
        "sql" => "SELECT 
          id,
          codigo,
          descripcion,
          estado
        FROM tipos_comprobante"
      ],
      ["table" => "tipos_documento", 
        "sql" => "SELECT 
          id,
          codigo,
          descripcion,
          descripcion_ext,
          estado
        FROM tipos_documento"
      ],
      ["table" => "tipos_moneda", 
        "sql" => "SELECT 
          id,
          codigo,
          descripcion,
          simbolo,
          pred,
          estado
        FROM tipos_moneda"
      ],
      ["table" => "tipos_movimiento_caja", 
        "sql" => "SELECT 
          id,
          descripcion,
          estado
        FROM tipos_movimiento_caja"
      ],
      ["table" => "tipos_movimiento_producto", 
        "sql" => "SELECT 
          id,
          codigo,
          tipo,
          tipo_operacion_cod,
          descripcion,
          documento,
          estado
        FROM tipos_movimiento_producto"
      ],
      ["table" => "tipos_operacion", 
        "sql" => "SELECT 
          codigo,
          descripcion,
          estado
        FROM tipos_operacion"
      ],
      ["table" => "unidades_medida", 
        "sql" => "SELECT 
          codigo,
          descripcion,
          descripcion_abv,
          estado
        FROM unidades_medida"
      ],
      ["table" => "categorias", 
        "sql" => "SELECT 
          id, 
          descripcion, 
          padre_id,
          orden
        FROM categorias ORDER BY orden"
      ],
      ["table" => "departamentos", 
        "sql" => "SELECT DISTINCT
          departamento
        FROM ubigeos"
      ],
    ];
    $catalogos = Catalogos::getCatalogos($tablas);
    // $catalogos['categorias'] = generateTree($catalogos['categorias']);
    return $catalogos;
  }

  public function create_tipo_comprobante(){
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 200);
    $paramCampos = [
      "codigo" => trimSpaces($pJson['codigo']),
      "descripcion" => trimSpaces($pJson['descripcion']),
      "estado" => "1",
    ];
    // Validacion
    // $this->validateCreateTipoComprobante($params);
    $lastId = Catalogos::createTipoComprobante($paramCampos);
    if (!$lastId) throwMiExcepcion("Ningún registro creado", "warning");
    $tablas=[["table" => "tipos_comprobante", 
      "sql" => "SELECT 
        id,
        codigo,
        descripcion,
        estado
      FROM tipos_comprobante"
    ],];
    $registros = Catalogos::getCatalogos($tablas);
    $response['msgType'] = "success";
    $response['msg'] = "Registro creado";
    $response['registro'] = $registros["tipos_comprobante"];
    return $response;
  }

  public function update_tipo_comprobante(){
    if ($_SERVER['REQUEST_METHOD'] != 'PUT') throwMiExcepcion("Método no permitido", "error", 405);
    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 200);

    $paramCampos = [
      "codigo" => trimSpaces($pJson['codigo']),
      "descripcion" => trimSpaces($pJson['descripcion']),
      "estado" => $pJson['estado'],
    ];
    // Validacion
    // $this->validateUpdateUser($paramCampos);

    $paramWhere = ["id" => $pJson['id']];

    $resp = Catalogos::updateTipoComprobante($paramCampos, $paramWhere);
    if (!$resp) throwMiExcepcion("Ningún registro modificado", "warning", 200);
    $tablas=[["table" => "tipos_comprobante", 
      "sql" => "SELECT 
        id,
        codigo,
        descripcion,
        estado
      FROM tipos_comprobante"
    ],];
    $registros = Catalogos::getCatalogos($tablas);

    $response['msgType'] = "success";
    $response['msg'] = "Registro actualizado";
    $response['registro'] = $registros["tipos_comprobante"];
    return $response;
  }

  public function delete_tipo_comprobante(){
    if ($_SERVER['REQUEST_METHOD'] != 'DELETE') throwMiExcepcion("Método no permitido", "error", 405);
    $pJson = json_decode(file_get_contents('php://input'), true);
    if (!$pJson) throwMiExcepcion("No se enviaron parámetros", "error", 400);
    $params = [
      "id" => $pJson['id'],
    ];
    $count = Catalogos::deleteTipoComprobante($params);
    if (!$count) throwMiExcepcion("Ningún registro eliminado", "warning");

    $response['msgType'] = "success";
    $response['msg'] = "Registro eliminado";
    $response['id'] = $pJson['id'];
    return $response;
  }

  public function get_provincias(){
    $params = json_decode(file_get_contents('php://input'), true);
    $provincias = Catalogos::getProvincias($params['departamento']);
    return $provincias;
  }

  public function get_distritos(){
    $params = json_decode(file_get_contents('php://input'), true);
    $distritos = Catalogos::getDistritos($params['departamento'], $params['provincia']);
    return $distritos;
  }
}

