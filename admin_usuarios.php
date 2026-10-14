<?php

session_start();

require_once "config/conexion.php";

function e($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}

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

// Mensaje de la última acción
$mensaje = $_SESSION["mensaje_admin"] ?? null;
unset($_SESSION["mensaje_admin"]);

/* =========================
   CONSULTAR USUARIOS
========================= */

$sql = "SELECT
            id_usuario,
            nombre,
            apellido,
            correo,
            telefono,
            rol,
            fecha_registro
        FROM usuarios
        ORDER BY fecha_registro DESC";

$resultado = $conexion->query($sql);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gestionar usuarios - Universo Deportivo</title>

    <link rel="stylesheet" href="css/estilos.css">

</head>

<body>

<header class="encabezado">

    <div class="contenedor-header">

        <a href="index.php" class="logo">
            Universo Deportivo
        </a>

        <nav>

            <a href="admin_dashboard.php">Panel administrador</a>
            <a href="admin_publicaciones.php">Publicaciones</a>
            <a href="perfil.php">Mi perfil</a>
            <a href="logout.php">Cerrar sesión</a>

        </nav>

    </div>

</header>

<main>

    <section class="contenedor-solicitudes">

        <h2 class="titulo-solicitudes">
            Gestión de usuarios
        </h2>

        <p>
            Desde aquí el administrador puede consultar los usuarios registrados
            en Universo Deportivo y cambiar su rol.
        </p>

        <?php if ($mensaje): ?>

            <div class="<?php echo $mensaje["tipo"] === "exito" ? "mensaje-exito" : "mensaje-error"; ?>">
                <?php echo e($mensaje["texto"]); ?>
            </div>

        <?php endif; ?>

        <input
            type="text"
            id="buscar-usuario"
            class="buscador-tabla"
            placeholder="Buscar por nombre, correo o rol..."
            autocomplete="off"
        >

        <div class="tabla-admin">

            <table id="tabla-usuarios">

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Teléfono</th>
                        <th>Rol</th>
                        <th>Fecha de registro</th>
                        <th>Acciones</th>
                    </tr>

                </thead>

                <tbody>

                    <?php if ($resultado->num_rows > 0): ?>

                        <?php while ($usuario = $resultado->fetch_assoc()): ?>

                            <?php $nombre_completo = $usuario["nombre"] . " " . $usuario["apellido"]; ?>

                            <tr data-buscar="<?php echo e($nombre_completo . " " . $usuario["correo"] . " " . $usuario["rol"]); ?>">

                                <td>
                                    <?php echo (int) $usuario["id_usuario"]; ?>
                                </td>

                                <td>
                                    <?php echo e($nombre_completo); ?>
                                </td>

                                <td>
                                    <?php echo e($usuario["correo"]); ?>
                                </td>

                                <td>
                                    <?php echo e($usuario["telefono"] ?? "No registrado"); ?>
                                </td>

                                <td>
                                    <?php echo e(ucfirst($usuario["rol"])); ?>
                                </td>

                                <td>
                                    <?php echo date("d/m/Y H:i", strtotime($usuario["fecha_registro"])); ?>
                                </td>

                                <td>

                                    <?php if ($usuario["id_usuario"] != $_SESSION["id_usuario"]): ?>

                                        <form method="POST"
                                              action="admin_cambiar_rol.php"
                                              class="form-rol"
                                              data-confirmar="¿Cambiar el rol de <?php echo e($nombre_completo); ?>? Si era proveedor, sus publicaciones se inactivarán.">

                                            <input
                                                type="hidden"
                                                name="id_usuario"
                                                value="<?php echo (int) $usuario["id_usuario"]; ?>"
                                            >

                                            <select name="rol">

                                                <option value="usuario"
                                                    <?php echo ($usuario["rol"] == "usuario") ? "selected" : ""; ?>>
                                                    Usuario
                                                </option>

                                                <option value="proveedor"
                                                    <?php echo ($usuario["rol"] == "proveedor") ? "selected" : ""; ?>>
                                                    Proveedor
                                                </option>

                                                <option value="admin"
                                                    <?php echo ($usuario["rol"] == "admin") ? "selected" : ""; ?>>
                                                    Administrador
                                                </option>

                                            </select>

                                            <button type="submit" class="btn-principal">
                                                Guardar
                                            </button>

                                        </form>

                                    <?php else: ?>

                                        <span>Administrador actual</span>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                        <tr id="sin-resultados" style="display: none;">
                            <td colspan="7">
                                Ningún usuario coincide con tu búsqueda.
                            </td>
                        </tr>

                    <?php else: ?>

                        <tr>
                            <td colspan="7">
                                No hay usuarios registrados.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        <div class="acciones-admin">

            <a href="admin_dashboard.php" class="btn-principal">
                ← Volver al panel
            </a>

        </div>

    </section>

</main>

<footer>

    <p>
        © 2026 Universo Deportivo - Proyecto académico
    </p>

</footer>


<script>
document.addEventListener("DOMContentLoaded", function () {

    // Confirmación antes de cambiar un rol
    document.querySelectorAll("form[data-confirmar]").forEach(function (formulario) {
        formulario.addEventListener("submit", function (evento) {
            if (!confirm(formulario.dataset.confirmar)) {
                evento.preventDefault();
            }
        });
    });

    // Buscador dinámico de la tabla (sin tildes ni mayúsculas)
    var buscador = document.getElementById("buscar-usuario");
    var filas = document.querySelectorAll("#tabla-usuarios tbody tr[data-buscar]");
    var sinResultados = document.getElementById("sin-resultados");

    function normalizar(texto) {
        return texto.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    }

    buscador.addEventListener("input", function () {

        var consulta = normalizar(buscador.value.trim());
        var visibles = 0;

        filas.forEach(function (fila) {
            var coincide = normalizar(fila.dataset.buscar).indexOf(consulta) !== -1;
            fila.style.display = coincide ? "" : "none";
            if (coincide) {
                visibles++;
            }
        });

        if (sinResultados) {
            sinResultados.style.display = (visibles === 0) ? "" : "none";
        }
    });
});
</script>

</body>

</html>