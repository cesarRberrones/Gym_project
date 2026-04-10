<?php
$host = "localhost";
$user = "appuser";
$pass = "1234";
$db   = "gym_sistema";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>