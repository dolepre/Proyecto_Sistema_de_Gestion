<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/register.css">
    <title>Register</title>

</head>
<body>
    <div class="register-container">
    <h1>Registro</h1>

    <form action="#" method="post">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
        
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        
        <label for="id_rol">Rol:</label>
        <select id="id_rol" name="id_rol" required>
            <option value="2">Cajero</option>
        </select>

        <button type="submit">Register</button>
        
    </form>

    <?php

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

        <a href="../index.php?page=login" class="login-link">Ya tienes una cuenta? Inicia sesión aquí.</a>

    </div>

    

</body>
</html>