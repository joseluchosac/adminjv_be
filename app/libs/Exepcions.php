<?php
class MiExcepcion extends Exception {
    private $params;

    public function __construct($message, $params, $code = 0, Exception $previous = null) {
        $this->params = $params;
        parent::__construct($message, $code, $previous);
    }

    public function getParams() {
        return $this->params;
    }
}
// USAGE
// try {
    // Lanzar la excepción personalizada con datos adicionales
    // throw new MiExcepcion("Se produjo un error", ["param1" => "Valor adicional 1", "param2" => "Valor adicional 2"]);
// } catch (MiExcepcion $e) {
    // Acceder a los datos adicionales
    // echo "Error: " . $e->getMessage() . "<br>";
    // $params = $e->getParams();
    // echo "Parámetro adicional 1: " . $params["param1"] . "<br>";
    // echo "Parámetro adicional 2: " . $params["param2"];
// }
?>
