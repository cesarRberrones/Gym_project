<?php
include "conexion.php";
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_usuario = (int)$_POST['id_usuario'];
    
    //configuración de la subida
    $target_dir = "uploads/";
    $file_name = time() . "_" . basename($_FILES["foto"]["name"]);
    $target_file = $target_dir . $file_name;
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    //verificar si es una imagen real
    if(isset($_POST["submit"])) {
        $check = getimagesize($_FILES["foto"]["tmp_name"]);
        if($check !== false) {
            $uploadOk = 1;
        } else {
            $_SESSION['mensaje'] = "El archivo no es una imagen.";
            $_SESSION['tipo'] = "error";
            $uploadOk = 0;
        }
    }
    
    //verificar tamaño (máximo 2MB)
    if ($_FILES["foto"]["size"] > 2000000) {
        $_SESSION['mensaje'] = "La imagen es demasiado grande (máximo 2MB).";
        $_SESSION['tipo'] = "error";
        $uploadOk = 0;
    }
    
    //permitir formatos
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
    && $imageFileType != "gif" ) {
        $_SESSION['mensaje'] = "Solo se permiten archivos JPG, JPEG, PNG y GIF.";
        $_SESSION['tipo'] = "error";
        $uploadOk = 0;
    }
    
    //Subir archivo
    if ($uploadOk == 1) {
        if (move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file)) {
            //obtener la foto anterior para eliminarla
            $result = $conn->query("SELECT foto FROM usuarios WHERE ID_Usuario = $id_usuario");
            $row = $result->fetch_assoc();
            $old_foto = $row['foto'];
            
            //actualizar la base de datos
            $sql = "UPDATE usuarios SET foto = '$file_name' WHERE ID_Usuario = $id_usuario";
            if ($conn->query($sql)) {
                //eliminar foto anterior si no es la default
                if ($old_foto != 'default.jpg' && file_exists("uploads/" . $old_foto)) {
                    unlink("uploads/" . $old_foto);
                }
                $_SESSION['mensaje'] = "Foto subida exitosamente.";
                $_SESSION['tipo'] = "success";
            } else {
                $_SESSION['mensaje'] = "Error al actualizar la base de datos.";
                $_SESSION['tipo'] = "error";
            }
        } else {
            $_SESSION['mensaje'] = "Error al subir el archivo.";
            $_SESSION['tipo'] = "error";
        }
    }
    
    // Redirigir de vuelta al detalle del socio
    $result = $conn->query("SELECT ID_Socios FROM socios WHERE ID_Usuario = $id_usuario");
    $row = $result->fetch_assoc();
    $id_socio = $row['ID_Socios'];
    
    header("Location: socio_detalle.php?id=" . $id_socio);
    exit();
}
?>