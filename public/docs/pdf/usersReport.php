<?php
  $p = $_GET["p"] ?? null;
  $params = json_decode(urldecode(base64_decode($p)), true);
  // echo "<pre>";
  // print_r($params);
  // echo "</pre>";
  // exit;
  if($p === null || $params === null){
    echo "<pre>Error param</pre>";
    return;
  }

  require_once "../../../app/models/Users.php";

  $campos = [
    'id',
    'nombres',
    'apellidos',
    'username',
    'email',
    'rol',
    'caja',
    'estado',
    'created_at',
    'updated_at'
  ];

  $params["search"] = [
    "fieldsName" => ["apellidos", "nombres", "username", "email"],
    "like" => trim($params["search"])
  ];

  $pagination = [
    "page" => $_GET["page"] ?? "1",
    "offset" => $params['offset']
  ];

  $where = MyORM::getWhere($params);
  $orderBy = MyORM::getOrder($params["order"]);

  $res = Users::filterUsers($campos, $where, $orderBy, $pagination, false);
  $empresa = Config::getEmpresa();
 
  // $res = Users::filterUsers($campos, $paramWhere, $paramOrders, $pagination, false);
  // print_r($res);
  // exit;
  $infoFilter = infoFilter($params);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reporte de Usuarios</title>
  
  <link 
    rel="stylesheet" 
    href="<?php echo getBaseUrl() . dirname($_SERVER["PHP_SELF"]); ?>/../../assets/css/fonts.css"
  >
  <link 
    rel="stylesheet" 
    href="<?php echo getBaseUrl() . dirname($_SERVER["PHP_SELF"]); ?>/../../assets/css/style.css"
  >

</head>
<body>
<div class="border border-pink-600 rounded-md mb-4 overflow-hidden">
  <table class="w-full">
    <tr>
      <td class="w-40">
        <img 
          class="" 
          src="<?php echo getBaseUrl() . dirname($_SERVER["PHP_SELF"]); ?>/../../store/img/empresa/<?php echo $empresa['logo']; ?>"
        >
      </td>
      <td class="p-4">
        <div class="text-center text-lg">REPORTE DE USUARIOS</div>
        <div class="text-center"><?php echo $infoFilter["between"]; ?></div>
        <div class="text-center"><?php echo $infoFilter["equal"]; ?></div>
      </td>
    </tr>
  </table>
</div>
<div class="my-3">
  <table class="">
    <tr>
      <td class="h-4 w-[150px] bg-slate-400"></td>
      <td class="w-[400px] bg-slate-500"></td>
      <td class="w-[150px] bg-slate-600"></td>
    </tr>
  </table>
</div>
<div class="roboto-condensed-regular">
  <table class="w-full text-xs">
    <thead>
      <tr class=" border-b-2 border-b-black">
        <th class="px-1 pb-2 text-left">Nombres</th>
        <th class="px-1 pb-2 text-left">Apellidos</th>
        <th class="px-1 pb-2 text-left">Usuario</th>
        <th class="px-1 pb-2 text-left">Email</th>
        <th class="px-1 pb-2 text-left">Rol</th>
        <th class="px-1 pb-2 text-left">Estado</th>
        <th class="px-1 pb-2 text-left">Creado</th>
        <th class="px-1 pb-2 text-left">Modif</th>
      </tr>
    </thead>
    <tbody class="fs-10p">
      <?php
        foreach($res["filas"] as $item){
          $created_at = explode(" ", $item['created_at'])[0];
          $updated_at = explode(" ", $item['updated_at'])[0];
          $estado = $item['estado'] == 1 ? "activo" : "inactivo";
          $muted =  $item['estado'] == 1 ? "" : "text-red-500";
          echo "
            <tr class='border-b border-b-blue-600 $muted'>
              <td class='p-1'>".$item['nombres']."</td>
              <td class='p-1'>".$item['apellidos']."</td>
              <td class='p-1'>".$item['username']."</td>
              <td class='p-1'>".$item['email']."</td>
              <td class='p-1'>".$item['rol']."</td>
              <td class='p-1'>$estado</td>
              <td class='p-1 whitespace-nowrap'>$created_at</td>
              <td class='p-1 whitespace-nowrap'>$updated_at</td>
            </tr>
          ";
        };
      ?>
    </tbody>
  </table>
</div>
</body>

</html>