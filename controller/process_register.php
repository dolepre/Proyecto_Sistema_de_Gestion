<?php
// controller/process_register.php

// Incluir la conexión a la base de datos (ajusta la ruta según tu estructura)
require_once __DIR__ . '/../db/conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Capturar los datos del formulario
    $username = trim($_POST['username']);
    $password_en_texto = trim($_POST['password']);
    $id_rol = $_POST['id_rol']; // 1 para Administrador, 2 para Cajero/Empleado, etc.

    // Validar que los campos no estén vacíos
    if (!empty($username) && !empty($password_en_texto)) {
        
        // ENCRIPTAR LA CONTRASEÑA de forma segura
        $password_segura = password_hash($password_en_texto, PASSWORD_DEFAULT);

        try {
            // Preparar la consulta SQL para insertar el usuario
            $sql = "INSERT INTO usuarios (id_rol, username, password, estado) VALUES (:rol, :username, :password, 1)";
            $stmt = $conexion->prepare($sql);
            
            // Ejecutar pasando el hash de la contraseña (NUNCA el texto plano)
            $stmt->execute([
                'rol' => $id_rol,
                'username' => $username,
                'password' => $password_segura
            ]);

            echo "¡Usuario registrado y contraseña encriptada con éxito!";
            // Opcional: Redirigir al login
            // header("Location: ../index.php?page=login");
            // exit();

        } catch (PDOException $e) {
            // Manejar error si el nombre de usuario ya existe (por el UNIQUE de la BD)
            if ($e->getCode() == 23000) {
                echo "El nombre de usuario ya está registrado.";
            } else {
                echo "Error en el registro: " . $e->getMessage();
            }
        }
    } else {
        echo "Por favor, complete todos los campos.";
    }
}
?>