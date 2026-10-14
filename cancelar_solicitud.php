<?php

session_start();

require_once "config/conexion.php";

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}

/* Solo se acepta el envío por POST */
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: mis_solicitudes.php");
    exit();
}

function volver($tipo, $texto)
{
    $_SESSION["mensaje_mis_solicitudes"] = ["tipo" => $tipo, "texto" => $texto];
    header("Location: mis_solicitudes.php");
    exit();
}

$id_usuario = $_SESSION["id_usuario"];
$id_solicitud = filter_input(INPUT_POST, "id_solicitud", FILTER_VALIDATE_INT);

if (!$id_solicitud) {
    volver("error", "La solicitud no es válida.");
}

/* Solo se elimina si es del usuario y todavía está pendiente */
$stmt = $conexion->prepare(
    "DELETE FROM solicitudes
     WHERE id_solicitud = ?
     AND id_usuario = ?
     AND estado = 'pendiente'"
);
$stmt->bind_param("ii", $id_solicitud, $id_usuario);

if (!$stmt->execute()) {
    volver("error", "No fue posible cancelar la solicitud.");
}

if ($stmt->affected_rows > 0) {
    volver("exito", "Tu solicitud fue cancelada correctamente.");
}

volver("error", "No se pudo cancelar: la solicitud no existe, no es tuya o el proveedor ya la atendió.");