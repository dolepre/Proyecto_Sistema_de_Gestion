<?php
// Asegurarnos de incluir la conexión y el controlador de gestión
session_start();
require_once "../db/conexion.php"; // Ajusta la ruta si es necesario

// Procesar el formulario si se envía por POST
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['registrar_cobro'])) {
    $id_responsable = $_SESSION['id_usuario'] ?? 1; // ID del cajero logueado
    $nombres = trim($_POST['nombres']);
    $apellidos = trim($_POST['apellidos']);
    $cedula = trim($_POST['cedula_identidad']);
    $monto = trim($_POST['monto']);
    $metodo_pago = $_POST['metodo_pago']; // Efectivo, Transferencia o Pago Móvil

    if (!empty($nombres) && !empty($cedula) && !empty($monto)) {
        try {
            // Guardamos el método de pago en 'datos_contacto'
            $sql = "INSERT INTO entidad_gestion (id_usuario_responsable, nombres, apellidos, cedula_identidad, datos_contacto, fecha_registro) 
                    VALUES (:resp, :nombres, :apellidos, :cedula, :pago, NOW())";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([
                'resp' => $id_responsable,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'cedula' => $cedula,
                'pago' => "$metodo_pago - Monto: $$monto"
            ]);
            $mensaje = "¡Cobro registrado con éxito!";
        } catch (PDOException $e) {
            $error = "Error al registrar: " . $e->getMessage();
        }
    } else {
        $error = "Por favor, complete los campos obligatorios.";
    }
}

// Obtener los registros de la base de datos para mostrarlos en la tabla
$filtro = $_GET['filtro'] ?? '';
if ($filtro != '') {
    $sql_sel = "SELECT * FROM entidad_gestion WHERE datos_contacto LIKE :filtro ORDER BY fecha_registro DESC";
    $stmt_sel = $conexion->prepare($sql_sel);
    $stmt_sel->execute(['filtro' => "%$filtro%"]);
} else {
    $sql_sel = "SELECT * FROM entidad_gestion ORDER BY fecha_registro DESC";
    $stmt_sel = $conexion->query($sql_sel);
}
$registros = $stmt_sel->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2>Módulo de Gestión de Cobros - ASCARDIO</h2>
    <p class="text-muted">Control de cuotas mensuales de afiliación, servicios médicos y ambulancia[cite: 7].</p>

    <?php if (isset($mensaje)): ?>
        <div class="alert alert-success"><?php echo $mensaje; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Formulario de Registro -->
    <div class="card p-4 mb-4 shadow-sm">
        <h4>Registrar Nueva Transacción</h4>
        <form action="#" method="POST">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label>Nombres del Paciente/Afiliado:</label>
                    <input type="text" name="nombres" class="form-control" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label>Apellidos:</label>
                    <input type="text" name="apellidos" class="form-control">
                </div>
                <div class="col-md-2 mb-3">
                    <label>Cédula:</label>
                    <input type="text" name="cedula_identidad" class="form-control" maxlength="8" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                </div>
                <div class="col-md-2 mb-3">
                    <label>Monto ($):</label>
                    <input type="number" step="0.01" name="monto" class="form-control" required>
                </div>
                <div class="col-md-2 mb-3">
                    <label>Método de Pago:</label>
                    <select name="metodo_pago" class="form-control" required>
                        <option value="Efectivo">Efectivo</option>
                        <option value="Transferencia">Transferencia</option>
                        <option value="Pago Móvil">Pago Móvil</option>
                    </select>
                </div>
            </div>
            <button type="submit" name="registrar_cobro" class="btn btn-primary">Registrar Cobro</button>
        </form>
    </div>

    <!-- Filtros Dinámicos (Requerimiento del informe) -->
    <div class="mb-3">
        <strong>Filtrar por método: </strong>
        <a href="#" class="btn btn-sm btn-secondary">Todos</a>
        <a href="#" class="btn btn-sm btn-success">Efectivo</a>
        <a href="#" class="btn btn-sm btn-info">Transferencias</a>
        <a href="#" class="btn btn-sm btn-warning">Pago Móvil</a>
    </div>

    <!-- Tabla de Registros -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Nombres y Apellidos</th>
                    <th>Cédula</th>
                    <th>Detalles / Método de Pago</th>
                    <th>Fecha de Registro</th>
                    <?php if($_SESSION['id_rol'] == 1): ?>
                        <th>Responsable (ID)</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (count($registros) > 0): ?>
                    <?php foreach ($registros as $row): ?>
                        <tr>
                            <td><?php echo $row['id_registro']; ?></td>
                            <td><?php echo $row['nombres'] . " " . $row['apellidos']; ?></td>
                            <td><?php echo $row['cedula_identidad']; ?></td>
                            <td><?php echo $row['datos_contacto']; ?></td>
                            <td><?php echo $row['fecha_registro']; ?></td>
                            
                            <td>
                                <?php
                            if($_SESSION['id_rol'] == 1) {
                                echo "-------- " . $row['id_usuario_responsable'];
                            }
                            ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No hay registros de cobros encontrados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<a href="../index.php?page=login" class="btn btn-danger mt-3">Cerrar Sesión</a>

<?php

if ($_SESSION['id_rol'] == 1) {
    echo "<a href='../index.php?page=admin' class='btn btn-secondary mt-3'>Ir al Panel de Administración</a>";
}

?>