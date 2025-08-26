<?php
require_once('../../app/models/Catalogos.php');


class CatalogosController
{

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

  public function get_distritos(){
    $params = json_decode(file_get_contents('php://input'), true);
    $distritos = Catalogos::getDistritos($params['departamento'], $params['provincia']);
    return $distritos;
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

