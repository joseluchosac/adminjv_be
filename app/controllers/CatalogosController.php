<?php
require_once('../../app/models/Catalogos.php');


class CatalogosController
{

  public function get_tipo_comprobante(){
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throwMiExcepcion("Método no permitido", "error", 405);
    $p = json_decode(file_get_contents('php://input'), true);

    $tipoComprobante = Catalogos::getTipoComprobante(["id" => $p['id']]);
    return $tipoComprobante;
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

  public function get_cajas(){
    // throwMiExcepcion("Error de prueba", "error", 200);
    $cajas = Catalogos::getCajas();
    return $cajas;
  }

  // public function get_categorias(){
  //   $categorias = Catalogos::getCategorias();
  //   $response['tree'] = generateTree($categorias);
  //   $response['list'] = flattenTree($response["tree"]);
  //   return $response;
  // }

  public function get_formas_pago(){
    $formasPago = Catalogos::getFormasPago();
    return $formasPago;
  }
  
  public function get_impuestos(){
    $impuestos = Catalogos::getImpuestos();
    return $impuestos;
  }

  public function get_motivos_nota(){
    $motivosNota = Catalogos::getMotivosNota();
    return $motivosNota;
  }

  public function get_tipos_comprobante(){
    $tiposComprobante = Catalogos::getTiposComprobante();
    return $tiposComprobante;
  }

  public function get_tipos_documento(){
    $tiposDocumento = Catalogos::getTiposDocumento();
    return $tiposDocumento;
  }

  public function get_tipos_establecimiento(){
    return ['SUCURSAL', 'DEPOSITO'];
  }

  public function get_tipos_moneda(){
    $tiposMoneda = Catalogos::getTiposMoneda();
    return $tiposMoneda;
  }

  public function get_tipos_movimiento(){
    $tiposMovimiento = Catalogos::getTiposMovimiento();
    return $tiposMovimiento;
  }

  public function get_tipos_movimiento_caja(){
    $tiposMovimientoCaja = Catalogos::getTiposMovimientoCaja();
    return $tiposMovimientoCaja;
  }

  public function get_tipos_operacion(){
    $tiposOperacion = Catalogos::getTiposOperacion();
    return $tiposOperacion;
  }

  public function get_unidades_medida(){
    $unidadesMedida = Catalogos::getUnidadesMedida();
    return $unidadesMedida;
  }
}

