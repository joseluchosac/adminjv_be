<?php
require_once("Conexion.php");
class Catalogos
{
  // static function obtenerCatalogos()
  // {
  //   $dbh = Conexion::conectar();
  //   // roles
  //   $sql = "SELECT 
  //       id,
  //       rol,
  //       estado
  //     FROM roles 
  //   ";
  //   $stmt = $dbh->prepare($sql);
  //   $stmt->execute();
  //   $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
  //   // cajas
  //   $sql = "SELECT 
  //       id,
  //       descripcion,
  //       estado
  //     FROM cajas 
  //   ";
  //   $stmt = $dbh->prepare($sql);
  //   $stmt->execute(null);
  //   $cajas = $stmt->fetchAll(PDO::FETCH_ASSOC);
  //   // formas_pago
  //   $sql = "SELECT 
  //       id,
  //       descripcion,
  //       estado
  //     FROM formas_pago 
  //   ";
  //   $stmt = $dbh->prepare($sql);
  //   $stmt->execute();
  //   $formas_pago = $stmt->fetchAll(PDO::FETCH_ASSOC);  
  //   // impuestos
  //   $sql = "SELECT 
  //       id,
  //       afectacion_igv_cod,
  //       afectacion_igv_desc,
  //       letra_tributo,
  //       codigo_tributo,
  //       nombre_tributo,
  //       tipo_tributo,
  //       CAST(porcentaje AS FLOAT) AS porcentaje,
  //       CAST(importe AS FLOAT) AS importe,
  //       estado
  //     FROM impuestos 
  //   ";
  //   $stmt = $dbh->prepare($sql);
  //   $stmt->execute();
  //   $impuestos = $stmt->fetchAll(PDO::FETCH_ASSOC);    
  //   // motivos_nota
  //   $sql = "SELECT 
  //       id,
  //       tipo_comprobante_cod,
  //       tipo,
  //       codigo,
  //       descripcion,
  //       estado
  //     FROM motivos_nota 
  //   ";
  //   $stmt = $dbh->prepare($sql);
  //   $stmt->execute();
  //   $motivos_nota = $stmt->fetchAll(PDO::FETCH_ASSOC);

  //   // tipos_comprobante
  //   $sql = "SELECT 
  //       id,
  //       codigo,
  //       descripcion,
  //       estado
  //     FROM tipos_comprobante 
  //   ";
  //   $stmt = $dbh->prepare($sql);
  //   $stmt->execute();
  //   $tipos_comprobante = $stmt->fetchAll(PDO::FETCH_ASSOC);
  //   // tipo_documento
  //   $sql = "SELECT 
  //       id,
  //       codigo,
  //       descripcion,
  //       descripcion_ext,
  //       estado
  //     FROM tipos_documento 
  //   ";
  //   $stmt = $dbh->prepare($sql);
  //   $stmt->execute();
  //   $tipos_documento = $stmt->fetchAll(PDO::FETCH_ASSOC);
  //   // tipos_moneda
  //   $sql = "SELECT 
  //       id,
  //       codigo,
  //       descripcion,
  //       simbolo,
  //       estado
  //     FROM tipos_moneda 
  //   ";
  //   $stmt = $dbh->prepare($sql);
  //   $stmt->execute();
  //   $tipos_moneda = $stmt->fetchAll(PDO::FETCH_ASSOC);
  //   // tipos_movimiento_caja
  //   $sql = "SELECT 
  //       id,
  //       descripcion,
  //       estado
  //     FROM tipos_movimiento_caja 
  //   ";
  //   $stmt = $dbh->prepare($sql);
  //   $stmt->execute();
  //   $tipos_movimiento_caja = $stmt->fetchAll(PDO::FETCH_ASSOC);
  //   // tipos_movimiento_producto
  //   $sql = "SELECT 
  //       id,
  //       codigo,
  //       tipo,
  //       tipo_operacion_cod,
  //       descripcion,
  //       documento,
  //       estado
  //     FROM tipos_movimiento_producto 
  //   ";
  //   $stmt = $dbh->prepare($sql);
  //   $stmt->execute();
  //   $tipos_movimiento_producto = $stmt->fetchAll(PDO::FETCH_ASSOC);
  //   // tipos_operacion
  //   $sql = "SELECT 
  //       codigo,
  //       descripcion,
  //       estado
  //     FROM tipos_operacion 
  //   ";
  //   $stmt = $dbh->prepare($sql);
  //   $stmt->execute();
  //   $tipos_operacion = $stmt->fetchAll(PDO::FETCH_ASSOC);
  //   // unidades_medida
  //   $sql = "SELECT 
  //       codigo,
  //       descripcion,
  //       descripcion_abv,
  //       estado
  //     FROM unidades_medida 
  //   ";
  //   $stmt = $dbh->prepare($sql);
  //   $stmt->execute();
  //   $unidades_medida = $stmt->fetchAll(PDO::FETCH_ASSOC);
  //   // departamentos
  //   $sql = "SELECT DISTINCT
  //       departamento
  //     FROM ubigeos 
  //   ";
  //   $stmt = $dbh->prepare($sql);
  //   $stmt->execute();
  //   $departamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
  //   // $catalogos['modulos'] = $modulos;
  //   $catalogos['roles'] = $roles;
  //   $catalogos['cajas'] = $cajas;
  //   $catalogos['formas_pago'] = $formas_pago;
  //   $catalogos['impuestos'] = $impuestos;
  //   $catalogos['motivos_nota'] = $motivos_nota;
  //   $catalogos['tipos_comprobante'] = $tipos_comprobante;
  //   $catalogos['tipos_documento'] = $tipos_documento;
  //   $catalogos['tipos_moneda'] = $tipos_moneda;
  //   $catalogos['tipos_movimiento_caja'] = $tipos_movimiento_caja;
  //   $catalogos['tipos_movimiento_producto'] = $tipos_movimiento_producto;
  //   $catalogos['tipos_operacion'] = $tipos_operacion;
  //   $catalogos['unidades_medida'] = $unidades_medida;
  //   $catalogos['departamentos'] = $departamentos;

  //   return $catalogos;
  // }

  static function obtenerCatalogos($tablas)
  {

    $dbh = Conexion::conectar();
    foreach ($tablas as $value) {
      $stmt = $dbh->prepare($value["sql"]);
      $stmt->execute();
      $res[$value['table']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
  static function obtenerProvincias($departamento){
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
  
  static function obtenerDistritos($departamento, $provincia){
    $sql = "SELECT
        ubigeo_inei,
        distrito 
      FROM ubigeos WHERE departamento = :departamento AND provincia = :provincia;
    ";
    $dbh = Conexion::conectar();
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array("departamento" => $departamento, "provincia" => $provincia));
    $distritos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $distritos;

  }
}
