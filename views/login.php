<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/login.css">
    <title>Login</title>

</head>
<body>
    <div class="login-container">
    <h1>Login</h1>
    
    <form action="#" method="post">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
        
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        
        <button type="submit">Login</button>
    </form>
    <?php
// procesar_login.php
session_start();
require_once 'db/conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = trim($_POST['username']);
    $pass = trim($_POST['password']);

    if (!empty($user) && !empty($pass)) {
        // Consultar el usuario en la base de datos
        $sql = "SELECT * FROM usuarios WHERE username = :username AND estado = 1";
        $stmt = $conexion->prepare($sql);
        $stmt->execute(['username' => $user]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            // Verificar contraseña encriptada
            if (password_verify($pass, $usuario['password'])) {
                // Registrar variables de sesión
                $_SESSION['id_usuario'] = $usuario['id_usuario'];
                $_SESSION['id_rol'] = $usuario['id_rol'];
                $_SESSION['username'] = $usuario['username'];

                    header("Location: views/gestion.php");
                
                exit();
            } else {
                echo "Contraseña incorrecta.";
            }
        } else {
            echo "El usuario no existe o está inactivo.";
        }
    } else {
        echo "Por favor, llene todos los campos.";
    }
}
?>

    <a href="../index.php?page=register" class="register-link">No tienes una cuenta? Registrate aqui.</a>

    </div>

</body>
</html>