<?php

session_start();

require_once "config/conexion.php";

/* =========================
   VERIFICAR SESIÓN Y ROL ADMIN
========================= */

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}

if (($_SESSION["rol"] ?? "") !== "admin") {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: admin_usuarios.php");
    exit();
}

function volver($tipo, $texto)
{
    $_SESSION["mensaje_admin"] = ["tipo" => $tipo, "texto" => $texto];
    header("Location: admin_usuarios.php");
    exit();
}

/* =========================
   VALIDAR DATOS
========================= */

$id_usuario = filter_input(INPUT_POST, "id_usuario", FILTER_VALIDATE_INT);
$nuevo_rol = $_POST["rol"] ?? "";

$roles_permitidos = ["usuario", "proveedor", "admin"];

if (!$id_usuario || !in_array($nuevo_rol, $roles_permitidos, true)) {
    volver("error", "Los datos recibidos no son válidos.");
}

if ($id_usuario == $_SESSION["id_usuario"]) {
    volver("error", "No puedes cambiar tu propio rol.");
}

/* =========================
   USUARIO A MODIFICAR
========================= */

$stmt = $conexion->prepare(
    "SELECT nombre, apellido, rol FROM usuarios WHERE id_usuario = ?"
);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows == 0) {
    volver("error", "El usuario no existe.");
}

$usuario = $res->fetch_assoc();
$stmt->close();

$rol_actual = $usuario["rol"];

if ($rol_actual === $nuevo_rol) {
    volver("error", "El usuario ya tiene ese rol.");
}

/* =========================
   ACTUALIZAR (TODO O NADA)
========================= */

$conexion->begin_transaction();

try {

    // 1. Cambiar el rol
    $stmt = $conexion->prepare("UPDATE usuarios SET rol = ? WHERE id_usuario = ?");
    $stmt->bind_param("si", $nuevo_rol, $id_usuario);

    if (!$stmt->execute()) {
        throw new Exception("No se pudo actualizar el rol.");
    }

    $stmt->close();

    if ($nuevo_rol === "proveedor") {

        // 2. Si pasa a proveedor y no tiene registro, se crea uno básico
        $stmt = $conexion->prepare("SELECT id_proveedor FROM proveedores WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $existe = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if (!$existe) {

            $nombre_negocio = mb_substr(trim($usuario["nombre"] . " " . $usuario["apellido"]), 0, 100);
            $tipo_proveedor = "Otro";

            $stmt = $conexion->prepare(
                "INSERT INTO proveedores (id_usuario, nombre_negocio, tipo_proveedor)
                 VALUES (?, ?, ?)"
            );
            $stmt->bind_param("iss", $id_usuario, $nombre_negocio, $tipo_proveedor);

            if (!$stmt->execute()) {
                throw new Exception("No se pudo crear el proveedor.");
            }

            $stmt->close();
        }

    } elseif ($rol_actual === "proveedor") {

        // 3. Si deja de ser proveedor, sus publicaciones se inactivan (no se borran)
        $stmt = $conexion->prepare(
            "UPDATE publicaciones
             SET estado = 'inactivo'
             WHERE id_proveedor IN (
                 SELECT id_proveedor FROM proveedores WHERE id_usuario = ?
             )"
        );
        $stmt->bind_param("i", $id_usuario);

        if (!$stmt->execute()) {
            throw new Exception("No se pudieron inactivar las publicaciones.");
        }

        $stmt->close();
    }

    $conexion->commit();

} catch (Throwable $error) {

    $conexion->rollback();
    volver("error", "No fue posible cambiar el rol. No se realizó ningún cambio.");
}

volver("exito", "El rol de " . $usuario["nombre"] . " " . $usuario["apellido"] . " ahora es " . $nuevo_rol . ".");