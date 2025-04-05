<?php
require_once('../../app/models/Catalogos.php');


class CatalogosController
{
  public function obtener_catalogos(){
    $catalogos = Catalogos::obtenerCatalogos();
    return $catalogos;
  }

  public function obtener_provincias(){
    $params = json_decode(file_get_contents('php://input'), true);
    $provincias = Catalogos::obtenerProvincias($params['departamento']);
    return $provincias;
  }

  public function obtener_distritos(){
    $params = json_decode(file_get_contents('php://input'), true);
    $distritos = Catalogos::obtenerDistritos($params['departamento'], $params['provincia']);
    return $distritos;
  }
}

