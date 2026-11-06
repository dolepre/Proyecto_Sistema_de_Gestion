<?php
// controlador_gestion.php
require_once 'conexion.php';
session_start();

// Determinar la acción (por POST o GET)
$accion = $_REQUEST['accion'] ?? 'leer';

switch ($accion) {
    case 'crear':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id_responsable = $_SESSION['id_usuario'];
            $nombres = $_POST['nombres'];
            $apellidos = $_POST['apellidos'];
            $cedula = $_POST['cedula_identidad'];
            $contacto = $_POST['datos_contacto'];

            $sql = "INSERT INTO entidad_gestion (id_usuario_responsable, nombres, apellidos, cedula_identidad, datos_contacto) 
                    VALUES (:resp, :nombres, :apellidos, :cedula, :contacto)";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([
                'resp' => $id_responsable,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'cedula' => $cedula,
                'contacto' => $contacto
            ]);

            header("Location: index.php?mensaje=registrado");
            exit();
        }
        break;

    case 'leer':
        // Retorna todos los registros para mostrarlos en una tabla HTML
        $sql = "SELECT e.*, u.username AS responsable FROM entidad_gestion e 
                LEFT JOIN usuarios u ON e.id_usuario_responsable = u.id_usuario";
        $stmt = $conexion->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 'actualizar':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id_registro = $_POST['id_registro'];
            $nombres = $_POST['nombres'];
            $apellidos = $_POST['apellidos'];
            $contacto = $_POST['datos_contacto'];

            $sql = "UPDATE entidad_gestion SET nombres = :nombres, apellidos = :apellidos, datos_contacto = :contacto 
                    WHERE id_registro = :id";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'contacto' => $contacto,
                'id' => $id_registro
            ]);

            header("Location: index.php?mensaje=actualizado");
            exit();
        }
        break;

    case 'eliminar':
        if (isset($_GET['id'])) {
            $id_registro = $_GET['id'];

            $sql = "DELETE FROM entidad_gestion WHERE id_registro = :id";
            $stmt = $conexion->prepare($sql);
            $stmt->execute(['id' => $id_registro]);

            header("Location: index.php?mensaje=eliminado");
            exit();
        }
        break;
}
?>