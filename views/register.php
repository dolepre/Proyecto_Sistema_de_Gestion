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

    <form action="../controller/process_register.php" method="post">
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

        <a href="index.php?page=login" class="login-link">Ya tienes una cuenta? Inicia sesión aquí.</a>

    </div>

    

</body>
</html>