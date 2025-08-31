<?php
require_once("Conexion.php");
class Catalogos
{

  static function getCatalogos($tablas)
  {
    $dbh = Conexion::conectar();
    foreach ($tablas as $value) {
      $stmt = $dbh->prepare($value["sql"]);
      $stmt->execute();
      if($value['table'] === 'categorias'){
        $res["categorias_tree"] = generateTree($stmt->fetchAll(PDO::FETCH_ASSOC));
      }else{
        $res[$value['table']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
      }
    }
    return $res;
  }



  static function createTipoComprobante($paramCampos){
    $sql = sqlInsert("tipos_comprobante", $paramCampos);
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($paramCampos);
    $lastId = $dbh->lastInsertId();
    return $lastId;
  }

  static function updateTipoComprobante($paramCampos, $paramWhere){
    $sql = sqlUpdate("tipos_comprobante", $paramCampos, $paramWhere);
    $params = array_merge($paramCampos, $paramWhere);
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $resp = $stmt->rowCount();
    return $resp;
  }

  static function deleteTipoComprobante($params){
    $sql = "DELETE FROM tipos_comprobante WHERE id = :id";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $count = $stmt->rowCount();
    return $count;
  }

  static function getProvincias($departamento){
    $sql = "SELECT DISTINCT 
        provincia 
      FROM ubigeos WHERE departamento = :departamento;
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array("departamento" => $departamento));
    $provincias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $provincias;
  }
 
  static function getCajas()
  {
    $sql = "SELECT id, establecimiento_id, descripcion, estado FROM cajas;
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $cajas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $cajas;
  }

  // static function getCategorias()
  // {
  //   $sql = "SELECT 
  //       id, 
  //       descripcion, 
  //       padre_id,
  //       orden
  //     FROM categorias ORDER BY orden;
  //   ";
  //   $dbh = Conexion::conectar();
  //   $stmt = $dbh->prepare($sql);
  //   $stmt->execute();
  //   $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
  //   return $categorias; 
  // }

  static function getCategoriasTree()
  {
    $sql = "SELECT 
        id, 
        descripcion, 
        padre_id,
        orden
      FROM categorias ORDER BY orden;
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $categoriasTree = generateTree($stmt->fetchAll(PDO::FETCH_ASSOC));
    return $categoriasTree; 
  }

  static function getUnidadesMedida()
  {
    $sql = "SELECT 
        codigo,
        descripcion,
        descripcion_abv,
        estado
      FROM unidades_medida;
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $unidadesMedida = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $unidadesMedida;
  }
  static function getImpuestos()
  {
    $sql = "SELECT 
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
        FROM impuestos;
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $umpuestos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $umpuestos;
  }
  static function getMotivosNota()
  {
    $sql = "SELECT 
          id,
          tipo_comprobante_cod,
          tipo,
          codigo,
          descripcion,
          estado
        FROM motivos_nota;
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $motivosNota = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $motivosNota;
  }
  static function getTiposComprobante()
  {
    $sql = "SELECT 
        id,
        codigo,
        descripcion,
        serie_pre,
        descripcion_doc,
        estado
      FROM tipos_comprobante;
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $tiposComprobante = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $tiposComprobante;
  }
  static function getTiposDocumento()
  {
    $sql = "SELECT 
        id,
        codigo,
        descripcion,
        descripcion_ext,
        estado
      FROM tipos_documento;
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $tiposDocumento = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $tiposDocumento;
  }
  static function getTiposMovimiento()
  {
    $sql = "SELECT 
        id,
        tipo,
        concepto,
        origen,
        estado
      FROM tipos_movimiento
      Order by tipo, concepto;
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $tiposMovimiento = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $tiposMovimiento;
  }
  static function getTiposMovimientoCaja()
  {
    $sql = "SELECT 
        id,
        descripcion,
        estado
      FROM tipos_movimiento_caja;
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $tiposMovimientoCaja = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $tiposMovimientoCaja;
  }
  static function getTiposOperacion()
  {
    $sql = "SELECT 
        codigo,
        descripcion,
        estado
      FROM tipos_operacion;
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $tiposOperacion = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $tiposOperacion;
  }
  static function getFormasPago()
  {
    $sql = "SELECT id, descripcion, estado FROM formas_pago;";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $formasPago = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $formasPago;
  }
  static function getTiposMoneda()
  {
    $sql = "SELECT 
        id,
        codigo,
        descripcion,
        simbolo,
        pred,
        estado
      FROM tipos_moneda;
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $tiposMoneda = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $tiposMoneda;
  }

}
