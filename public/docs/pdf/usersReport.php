<?php
  $p = $_GET["p"] ?? null;
  $params = json_decode(urldecode(base64_decode($p)), true);
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
    'rol_id',
    'rol',
    'caja_id',
    'caja',
    'estado',
    'created_at',
    'updated_at'
  ];

  $search = $params['search'] ? "%" . $params['search'] . "%" : "";

  $paramWhere = [
    "paramLike" => ['nombres' => $search, 'apellidos' => $search, 'username' => $search, "email" => $search,],
    "paramEquals" => $params['equals'], // [[]] 
    "paramBetween" => [
      "campo" => $params['between']['campo_name'],
      "rango" => $params['between']['range'] // "2024-12-18 00:00:00, 2024-12-19 23:59:59"
    ]
  ];

  $paramOrders = $params['orders'];

  $pagination = [
    "page" => $_GET["page"] ?? "1",
    "offset" => $params['offset']
  ];

  $res = Users::filterUsers($campos, $paramWhere, $paramOrders, $pagination, false);

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reporte</title>
  <link rel="stylesheet" href="<?php echo getBaseUrl() . dirname($_SERVER["PHP_SELF"]); ?>/../../assets/css/fonts.css">
  <link rel="stylesheet" href="<?php echo getBaseUrl() . dirname($_SERVER["PHP_SELF"]); ?>/../../assets/css/style.css">

</head>
<body>
<div class="border border-pink-600 rounded-md mb-4">
  <table class="robotto w-full">
    <tr>
      <td class="w-40">
        <img class="" src="<?php echo getBaseUrl() . dirname($_SERVER["PHP_SELF"]); ?>/../../assets/images.jpg" alt="">
      </td>
      <td class="p-4">
        <div class="text-center">REPORTE DE USUARIOS</div>
        <p class="text-center">Lista de usuarios que fueron registrados entre este anio y el anio pasado incluyendo los pasajeros</p>
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
<div class="">
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
          $muted =  $item['estado'] == 1 ? "" : "text-gray-500";
          echo "
            <tr class='border-b border-b-orange-600 $muted'>
              <td class='px-1 py-2'>".$item['nombres']."</td>
              <td class='px-1 py-2'>".$item['apellidos']."</td>
              <td class='px-1 py-2'>".$item['username']."</td>
              <td class='px-1 py-2'>".$item['email']."</td>
              <td class='px-1 py-2'>".$item['rol']."</td>
              <td class='px-1 py-2'>$estado</td>
              <td class='px-1 py-2 whitespace-nowrap'>$created_at</td>
              <td class='px-1 py-2 whitespace-nowrap'>$updated_at</td>
            </tr>
          ";
        };
      ?>
    </tbody>
  </table>
</div>
</body>

</html>