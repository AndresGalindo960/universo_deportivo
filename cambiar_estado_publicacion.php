<?php

session_start();

require_once "config/conexion.php";

/* Solo proveedores con sesión */
if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}

if (($_SESSION["rol"] ?? "") !== "proveedor") {
    header("Location: dashboard.php");
    exit();
}

/* Solo se acepta el envío por POST */
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: mis_publicaciones.php");
    exit();
}

function volver($tipo, $texto)
{
    $_SESSION["mensaje_publicacion"] = ["tipo" => $tipo, "texto" => $texto];
    header("Location: mis_publicaciones.php");
    exit();
}

$id_usuario = $_SESSION["id_usuario"];
$id_publicacion = filter_input(INPUT_POST, "id_publicacion", FILTER_VALIDATE_INT);
$nuevo_estado = $_POST["nuevo_estado"] ?? "";

if (!$id_publicacion || !in_array($nuevo_estado, ["activo", "inactivo"], true)) {
    volver("error", "Los datos recibidos no son válidos.");
}

/* Proveedor de la sesión */
$stmt = $conexion->prepare("SELECT id_proveedor FROM proveedores WHERE id_usuario = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows == 0) {
    volver("error", "No se encontró información del proveedor.");
}

$id_proveedor = $res->fetch_assoc()["id_proveedor"];
$stmt->close();

/* Actualizar solo si la publicación es de este proveedor */
$stmt = $conexion->prepare(
    "UPDATE publicaciones
     SET estado = ?
     WHERE id_publicacion = ?
     AND id_proveedor = ?"
);
$stmt->bind_param("sii", $nuevo_estado, $id_publicacion, $id_proveedor);

if (!$stmt->execute()) {
    volver("error", "No fue posible actualizar la publicación.");
}

if ($stmt->affected_rows > 0) {
    $accion = ($nuevo_estado === "activo") ? "activada" : "inactivada";
    volver("exito", "La publicación fue " . $accion . " correctamente.");
}

volver("error", "No se realizó ningún cambio: la publicación no existe, no es tuya o ya tenía ese estado.");