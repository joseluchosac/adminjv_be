<?php
  // variables de entorno;
  require_once __DIR__ . "/../../../vendor/autoload.php";
  require_once __DIR__ . "/../../../app/libs/helpers.php";
  require_once __DIR__ . "/../../../app/libs/MyClasses.php";
  require_once __DIR__ . "/../../../app/models/config.php";
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
    $options->set('isRemoteEnabled', true); // Para imagenes remotas
    $options->set('isHtml5ParserEnabled', true);
    
    $dompdf = new Dompdf($options);
    // Registrar la fuente
    $fontDir = __DIR__ . '/../../assets/fonts';
    $fontCache = __DIR__ . '/../../assets/fonts/cache';
    $options->set('fontDir', $fontDir);
    $options->set('fontCache', $fontCache);
    // $options->set('defaultFont', 'saira_condensed'); // optional font default

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



