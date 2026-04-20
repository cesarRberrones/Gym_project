<?php
// Archivo al inicio de las páginas protegidas
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit();
}

//función verificar rol específico
function verificarRol($roles_permitidos) {
    if (!in_array($_SESSION['rol'], $roles_permitidos)) {
        header("Location: ../sin_acceso.php");
        exit();
    }
}
?>