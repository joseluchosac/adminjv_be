<?php require_once(__DIR__ . "/../app/libs/helpers.php") ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
</head>
<body>
  <div>
    <?php echo getBaseUrl() . dirname($_SERVER["PHP_SELF"]) . "/assets/img/logo1.png" ?>
  </div>
  <div>
    <?php echo dirname($_SERVER["PHP_SELF"]) != "\\" ? dirname($_SERVER["PHP_SELF"]) : "" ?>
  </div>
  <img src="<?php echo getBaseUrl() . dirname($_SERVER["PHP_SELF"]) . "/assets/img/logo1.png" ?>" alt="">
</body>
</html>
