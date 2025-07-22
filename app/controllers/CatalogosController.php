<?php
require_once('../../app/models/Catalogos.php');


class CatalogosController
{
  public function get_catalogos(){
    $tablas = [
      // ["table" => "roles", "sql" => "SELECT id, rol, estado FROM roles"],
      // ["table" => "cajas", "sql" => "SELECT id, descripcion, estado FROM cajas"],
      // ["table" => "formas_pago", "sql" => "SELECT id, descripcion, estado FROM formas_pago"],
      // ["table" => "impuestos", 
      //   "sql" => "SELECT 
      //     id,
      //     afectacion_igv_cod,
      //     afectacion_igv_desc,
      //     letra_tributo,
      //     codigo_tributo,
      //     nombre_tributo,
      //     tipo_tributo,
      //     CAST(porcentaje AS FLOAT) AS porcentaje,
      //     CAST(importe AS FLOAT) AS importe,
      //     pred,
      //     estado
      //   FROM impuestos"
      // ],
      // ["table" => "motivos_nota", 
      //   "sql" => "SELECT 
      //     id,
      //     tipo_comprobante_cod,
      //     tipo,
      //     codigo,
      //     descripcion,
      //     estado
      //   FROM motivos_nota"
      // ],
      // ["table" => "tipos_comprobante", 
      //   "sql" => "SELECT 
      //     id,
      //     codigo,
      //     descripcion,
      //     serie_pre,
      //     descripcion_doc,
      //     estado
      //   FROM tipos_comprobante"
      // ],
      // ["table" => "tipos_documento", 
      //   "sql" => "SELECT 
      //     id,
      //     codigo,
      //     descripcion,
      //     descripcion_ext,
      //     estado
      //   FROM tipos_documento"
      // ],
      // ["table" => "tipos_moneda", 
      //   "sql" => "SELECT 
      //     id,
      //     codigo,
      //     descripcion,
      //     simbolo,
      //     pred,
      //     estado
      //   FROM tipos_moneda"
      // ],
      // ["table" => "tipos_movimiento_caja", 
      //   "sql" => "SELECT 
      //     id,
      //     descripcion,
      //     estado
      //   FROM tipos_movimiento_caja"
      // ],
      // ["table" => "tipos_movimiento", 
      //   "sql" => "SELECT 
      //       id,
      //       tipo,
      //       concepto,
      //       origen,
      //       estado
      //     FROM tipos_movimiento
      //     Order by tipo, concepto
      //   "
      // ],
      // ["table" => "tipos_operacion", 
      //   "sql" => "SELECT 
      //     codigo,
      //     descripcion,
      //     estado
      //   FROM tipos_operacion"
      // ],
      // ["table" => "unidades_medida", 
      //   "sql" => "SELECT 
      //     codigo,
      //     descripcion,
      //     descripcion_abv,
      //     estado
      //   FROM unidades_medida"
      // ],
      // ["table" => "categorias", 
      //   "sql" => "SELECT 
      //     id, 
      //     descripcion, 
      //     padre_id,
      //     orden
      //   FROM categorias ORDER BY orden"
      // ],
      // ["table" => "departamentos", 
      //   "sql" => "SELECT DISTINCT
      //     departamento
      //   FROM ubigeos"
      // ],
      // ["table" => "establecimientos", 
      //   "sql" => "SELECT
      //     id,
      //     tipo,
      //     codigo,
      //     descripcion,
      //     direccion,
      //     ubigeo_inei,
      //     dis_prov_dep,
      //     telefono,
      //     email,
      //     estado
      //   FROM establecimientos_v"
      // ],
      // ["table" => "numeraciones", 
      //   "sql" => "SELECT
      //     id,
      //     establecimiento_id,
      //     descripcion_doc,
      //     serie_pre,
      //     serie,
      //     correlativo,
      //     estado
      //   FROM numeraciones ORDER BY id"
      // ],
    ];
    $catalogos = Catalogos::getCatalogos($tablas);
    $catalogos['tipos_establecimiento'] = ['SUCURSAL', 'DEPOSITO'];
    // $catalogos['categorias'] = generateTree($catalogos['categorias']);
    $resp['error'] = false;
    $resp['content'] = $catalogos;
    return $resp;
  }

  public function create_tipo_comprobante(){
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 200);
    $paramCampos = [
      "codigo" => trimSpaces($p['codigo']),
      "descripcion" => trimSpaces($p['descripcion']),
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
    $response['content'] = $registros["tipos_comprobante"];
    return $response;
  }

  public function update_tipo_comprobante(){
    if ($_SERVER['REQUEST_METHOD'] != 'PUT') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 200);

    $paramCampos = [
      "codigo" => trimSpaces($p['codigo']),
      "descripcion" => trimSpaces($p['descripcion']),
      "estado" => $p['estado'],
    ];
    // Validacion
    // $this->validateUpdateUser($paramCampos);

    $paramWhere = ["id" => $p['id']];

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
    $response['content'] = $registros["tipos_comprobante"];
    return $response;
  }

  public function delete_tipo_comprobante(){
    if ($_SERVER['REQUEST_METHOD'] != 'DELETE') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throwMiExcepcion("No se enviaron parámetros", "error", 400);
    $params = [
      "id" => $p['id'],
    ];
    $count = Catalogos::deleteTipoComprobante($params);
    if (!$count) throwMiExcepcion("Ningún registro eliminado", "warning");

    $response['content']['id'] = $p['id'];
    $response['msgType'] = "success";
    $response['msg'] = "Registro eliminado";
    return $response;
  }

  // public function get_provincias(){
  //   $params = json_decode(file_get_contents('php://input'), true);
  //   $provincias = Catalogos::getProvincias($params['departamento']);
  //   $res['content'] = $provincias;
  //   return $res;
  // }

  public function get_distritos(){
    $params = json_decode(file_get_contents('php://input'), true);
    $distritos = Catalogos::getDistritos($params['departamento'], $params['provincia']);
    $res['content'] = $distritos;
    return $res;
  }

  public function get_cajas(){
    $cajas = Catalogos::getCajas();
    $res['content'] = $cajas;
    return $res;
  }
  public function get_categorias_tree(){
    $categoriasTree = Catalogos::getCategoriasTree();
    $res['content'] = $categoriasTree;
    return $res;
  }

  public function get_unidades_medida(){
    $unidadesMedida = Catalogos::getUnidadesMedida();
    $res['content'] = $unidadesMedida;
    return $res;
  }

  public function get_impuestos(){
    $impuestos = Catalogos::getImpuestos();
    $res['content'] = $impuestos;
    return $res;
  }
  public function get_motivos_nota(){
    $motivosNota = Catalogos::getMotivosNota();
    $res['content'] = $motivosNota;
    return $res;
  }
  public function get_tipos_comprobante(){
    $tiposComprobante = Catalogos::getTiposComprobante();
    $res['content'] = $tiposComprobante;
    return $res;
  }
  public function get_tipos_documento(){
    $tiposDocumento = Catalogos::getTiposDocumento();
    $res['content'] = $tiposDocumento;
    return $res;
  }
  public function get_tipos_movimiento(){
    $tiposMovimiento = Catalogos::getTiposMovimiento();
    $res['content'] = $tiposMovimiento;
    return $res;
  }
  public function get_tipos_movimiento_caja(){
    $tiposMovimientoCaja = Catalogos::getTiposMovimientoCaja();
    $res['content'] = $tiposMovimientoCaja;
    return $res;
  }
  public function get_tipos_operacion(){
    $tiposOperacion = Catalogos::getTiposOperacion();
    $res['content'] = $tiposOperacion;
    return $res;
  }
  public function get_formas_pago(){
    $formasPago = Catalogos::getFormasPago();
    $res['content'] = $formasPago;
    return $res;
  }
  public function get_tipos_moneda(){
    $tiposMoneda = Catalogos::getTiposMoneda();
    $res['content'] = $tiposMoneda;
    return $res;
  }
  public function get_tipos_establecimiento(){
    $res['content'] = ['SUCURSAL', 'DEPOSITO'];
    return $res;
  }

}

