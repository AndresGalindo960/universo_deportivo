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
    header("Location: solicitudes_proveedor.php");
    exit();
}

$id_usuario = $_SESSION["id_usuario"];
$id_solicitud = filter_input(INPUT_POST, "id_solicitud", FILTER_VALIDATE_INT);
$nuevo_estado = $_POST["estado"] ?? "";

/* Cambios de estado permitidos */
$transiciones = [
    "pendiente" => ["aceptada", "rechazada"],
    "aceptada" => ["finalizada"]
];

function volver($tipo, $texto)
{
    $_SESSION["mensaje_solicitud"] = ["tipo" => $tipo, "texto" => $texto];
    header("Location: solicitudes_proveedor.php");
    exit();
}

if (!$id_solicitud) {
    volver("error", "La solicitud no es válida.");
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

/* La solicitud debe pertenecer a una publicación de este proveedor */
$sql = "SELECT s.estado
        FROM solicitudes s
        INNER JOIN publicaciones p
            ON s.id_publicacion = p.id_publicacion
        WHERE s.id_solicitud = ?
        AND p.id_proveedor = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("ii", $id_solicitud, $id_proveedor);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows == 0) {
    volver("error", "La solicitud no existe o no pertenece a tus publicaciones.");
}

$estado_actual = $res->fetch_assoc()["estado"];
$stmt->close();

/* Validar el cambio de estado */
if (!in_array($nuevo_estado, $transiciones[$estado_actual] ?? [], true)) {
    volver("error", "No se puede cambiar la solicitud al estado indicado.");
}

/* Actualizar */
$stmt = $conexion->prepare("UPDATE solicitudes SET estado = ? WHERE id_solicitud = ?");
$stmt->bind_param("si", $nuevo_estado, $id_solicitud);

if ($stmt->execute()) {
    volver("exito", "La solicitud #" . $id_solicitud . " ahora está " . $nuevo_estado . ".");
}

volver("error", "No fue posible actualizar la solicitud.");