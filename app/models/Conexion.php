<?php
// $localTimezone = Cfg::getLocalTimezone();
class Conexion
{
  static function conectar()
  {
    $dsn = "mysql:host=" . $_ENV['DB_HOST'] . ";dbname="  . $_ENV['DB_NAME'] . ";charset=utf8";

    $conn = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS']);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET time_zone = '+0:00'"); // UTC
    return $conn;
  }
}
