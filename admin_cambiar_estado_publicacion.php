<?php

session_start();

require_once "config/conexion.php";

/* Solo usuarios con sesión y rol admin */
if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}

if (($_SESSION["rol"] ?? "") !== "admin") {
    header("Location: dashboard.php");
    exit();
}

/* Solo se acepta el envío por POST */
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: admin_publicaciones.php");
    exit();
}

/* Validar los datos recibidos */
$id_publicacion = filter_input(INPUT_POST, "id_publicacion", FILTER_VALIDATE_INT);
$nuevo_estado = $_POST["nuevo_estado"] ?? "";

if (!$id_publicacion || !in_array($nuevo_estado, ["activo", "inactivo"], true)) {
    $_SESSION["mensaje_admin"] = [
        "tipo" => "error",
        "texto" => "Los datos recibidos no son válidos."
    ];
    header("Location: admin_publicaciones.php");
    exit();
}

/* Actualizar solo el estado, con consulta preparada */
$stmt = $conexion->prepare(
    "UPDATE publicaciones SET estado = ? WHERE id_publicacion = ?"
);
$stmt->bind_param("si", $nuevo_estado, $id_publicacion);

if ($stmt->execute()) {
    $accion = ($nuevo_estado === "activo") ? "activada" : "inactivada";
    $_SESSION["mensaje_admin"] = [
        "tipo" => "exito",
        "texto" => "La publicación #" . $id_publicacion . " fue " . $accion . " correctamente."
    ];
} else {
    $_SESSION["mensaje_admin"] = [
        "tipo" => "error",
        "texto" => "No fue posible actualizar la publicación."
    ];
}

$stmt->close();

header("Location: admin_publicaciones.php");
exit();