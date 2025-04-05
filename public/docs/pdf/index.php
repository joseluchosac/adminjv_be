<?php
  // variables de entorno;
  require_once __DIR__ . "/../../../vendor/autoload.php";
  require_once __DIR__ . "/../../../app/libs/helpers.php";
  require_once __DIR__ . "/../../../app/libs/sqlGenerador.php";
  $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/../../..");
  $dotenv->load();
  
  $action = $_GET["action"] ?? "";

  use Dompdf\Dompdf;
  use Dompdf\Options;

  if($action === "users_report"){

    ob_start();
    require_once "usersReport.php";
    $html = ob_get_clean();
  
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    // $options->set('isHtml5ParserEnabled', true);
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    // Output the generated PDF to Browser
    $dompdf->stream("ejemplo.pdf", array("Attachment" => false));
  }
  elseif($action === "otro_report"){
    require_once "usersReport.php";
  }
  else{
    echo "<pre>Error 404, not found</pre>";
  }

?>



