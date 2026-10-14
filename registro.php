<?php

require_once "config/conexion.php";

// Opciones válidas para el tipo de proveedor (campo obligatorio en la BD)
$tipos_proveedor = [
    "Entrenador personal",
    "Tienda deportiva",
    "Gimnasio",
    "Club deportivo",
    "Escuela deportiva",
    "Otro"
];

function e($valor)
{
    return htmlspecialchars($valor, ENT_QUOTES, "UTF-8");
}

$mensaje = "";
$tipo_mensaje = "";

$nombre = "";
$apellido = "";
$correo = "";
$telefono = "";
$tipo_cuenta = "usuario";
$nombre_negocio = "";
$tipo_proveedor = "";
$direccion = "";
$descripcion = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre = trim($_POST["nombre"] ?? "");
    $apellido = trim($_POST["apellido"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    $password = $_POST["password"] ?? "";
    $telefono = trim($_POST["telefono"] ?? "");

    $tipo_cuenta = $_POST["tipo_cuenta"] ?? "usuario";
    $nombre_negocio = trim($_POST["nombre_negocio"] ?? "");
    $tipo_proveedor = $_POST["tipo_proveedor"] ?? "";
    $direccion = trim($_POST["direccion"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");

    // Validar campos obligatorios
    if ($nombre === "" || $apellido === "" || $correo === "" || $password === "") {

        $mensaje = "Por favor completa todos los campos obligatorios.";
        $tipo_mensaje = "error";

    } elseif (!in_array($tipo_cuenta, ["usuario", "proveedor"], true)) {

        $mensaje = "El tipo de cuenta no es válido.";
        $tipo_mensaje = "error";

    } elseif (
        $tipo_cuenta === "proveedor" &&
        ($nombre_negocio === "" || !in_array($tipo_proveedor, $tipos_proveedor, true))
    ) {

        $mensaje = "Para registrarte como proveedor indica el nombre del negocio y el tipo de proveedor.";
        $tipo_mensaje = "error";

    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {

        $mensaje = "El correo electrónico no tiene un formato válido.";
        $tipo_mensaje = "error";

    } elseif (strlen($password) < 6) {

        $mensaje = "La contraseña debe tener mínimo 6 caracteres.";
        $tipo_mensaje = "error";

    } else {

        // Verificar si el correo ya existe
        $sql_verificar = "SELECT id_usuario
                          FROM usuarios
                          WHERE correo = ?";

        $stmt_verificar = mysqli_prepare($conexion, $sql_verificar);
        mysqli_stmt_bind_param($stmt_verificar, "s", $correo);
        mysqli_stmt_execute($stmt_verificar);
        $resultado = mysqli_stmt_get_result($stmt_verificar);

        if (mysqli_num_rows($resultado) > 0) {

            $mensaje = "Este correo ya está registrado.";
            $tipo_mensaje = "error";

        } else {

            // Encriptar contraseña
            $password_seguro = password_hash($password, PASSWORD_DEFAULT);

            // El rol sale de la lista blanca ya validada (nunca "admin")
            $rol = $tipo_cuenta;

            // Todo o nada: usuario + proveedor en una transacción
            mysqli_begin_transaction($conexion);

            try {

                $sql_insertar = "INSERT INTO usuarios
                                (nombre, apellido, correo, password, telefono, rol)
                                VALUES (?, ?, ?, ?, ?, ?)";

                $stmt_insertar = mysqli_prepare($conexion, $sql_insertar);

                mysqli_stmt_bind_param(
                    $stmt_insertar,
                    "ssssss",
                    $nombre,
                    $apellido,
                    $correo,
                    $password_seguro,
                    $telefono,
                    $rol
                );

                if (!mysqli_stmt_execute($stmt_insertar)) {
                    throw new Exception("Error al insertar el usuario.");
                }

                $id_nuevo_usuario = mysqli_insert_id($conexion);
                mysqli_stmt_close($stmt_insertar);

                if ($rol === "proveedor") {

                    // Los campos opcionales vacíos se guardan como NULL
                    $descripcion_bd = ($descripcion !== "") ? $descripcion : null;
                    $telefono_bd = ($telefono !== "") ? $telefono : null;
                    $direccion_bd = ($direccion !== "") ? $direccion : null;

                    $sql_proveedor = "INSERT INTO proveedores
                                     (id_usuario, nombre_negocio, tipo_proveedor, descripcion, telefono, direccion)
                                     VALUES (?, ?, ?, ?, ?, ?)";

                    $stmt_proveedor = mysqli_prepare($conexion, $sql_proveedor);

                    mysqli_stmt_bind_param(
                        $stmt_proveedor,
                        "isssss",
                        $id_nuevo_usuario,
                        $nombre_negocio,
                        $tipo_proveedor,
                        $descripcion_bd,
                        $telefono_bd,
                        $direccion_bd
                    );

                    if (!mysqli_stmt_execute($stmt_proveedor)) {
                        throw new Exception("Error al insertar el proveedor.");
                    }

                    mysqli_stmt_close($stmt_proveedor);
                }

                mysqli_commit($conexion);

                $mensaje = "Registro exitoso. Ya puedes iniciar sesión.";
                $tipo_mensaje = "exito";

                // Limpiar variables del formulario
                $nombre = "";
                $apellido = "";
                $correo = "";
                $telefono = "";
                $tipo_cuenta = "usuario";
                $nombre_negocio = "";
                $tipo_proveedor = "";
                $direccion = "";
                $descripcion = "";

            } catch (Throwable $error) {

                mysqli_rollback($conexion);

                $mensaje = "Ocurrió un error al registrar la cuenta. Inténtalo de nuevo.";
                $tipo_mensaje = "error";
            }
        }

        mysqli_stmt_close($stmt_verificar);
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registro - Universo Deportivo</title>

    <link rel="stylesheet" href="css/estilos.css">

</head>

<body>

    <header class="encabezado">

        <div class="contenedor-header">

            <a href="index.php" class="logo">
                Universo Deportivo
            </a>

        </div>

    </header>


    <main class="pagina-registro">

        <div class="registro-card">

            <h1>Crear cuenta</h1>

            <p class="registro-descripcion">
                Regístrate para encontrar actividades, servicios y productos deportivos,
                o para publicar los tuyos como proveedor.
            </p>


            <?php if (!empty($mensaje)): ?>

                <div class="mensaje <?php echo e($tipo_mensaje); ?>">
                    <?php echo e($mensaje); ?>
                </div>

            <?php endif; ?>


            <form method="POST" action="registro.php">

                <div class="campo">

                    <label for="tipo_cuenta">
                        Tipo de cuenta *
                    </label>

                    <select id="tipo_cuenta" name="tipo_cuenta" required>

                        <option value="usuario"
                            <?php if ($tipo_cuenta === "usuario") echo "selected"; ?>>
                            Usuario (quiero encontrar servicios y productos)
                        </option>

                        <option value="proveedor"
                            <?php if ($tipo_cuenta === "proveedor") echo "selected"; ?>>
                            Proveedor (quiero publicar servicios y productos)
                        </option>

                    </select>

                </div>


                <div class="form-grid">

                    <div class="campo">

                        <label for="nombre">
                            Nombre *
                        </label>

                        <input
                            type="text"
                            id="nombre"
                            name="nombre"
                            maxlength="50"
                            value="<?php echo e($nombre); ?>"
                            required
                        >

                    </div>


                    <div class="campo">

                        <label for="apellido">
                            Apellido *
                        </label>

                        <input
                            type="text"
                            id="apellido"
                            name="apellido"
                            maxlength="50"
                            value="<?php echo e($apellido); ?>"
                            required
                        >

                    </div>

                </div>


                <div class="campo">

                    <label for="correo">
                        Correo electrónico *
                    </label>

                    <input
                        type="email"
                        id="correo"
                        name="correo"
                        maxlength="100"
                        value="<?php echo e($correo); ?>"
                        required
                    >

                </div>


                <div class="campo">

                    <label for="password">
                        Contraseña *
                    </label>

                    <div class="campo-password">

                        <input
                            type="password"
                            id="password"
                            name="password"
                            minlength="6"
                            required
                        >

                        <button type="button" id="ver-password" class="btn-ver-password">
                            Mostrar
                        </button>

                    </div>

                    <small>
                        Mínimo 6 caracteres.
                    </small>

                </div>


                <div class="campo">

                    <label for="telefono">
                        Teléfono
                    </label>

                    <input
                        type="tel"
                        id="telefono"
                        name="telefono"
                        maxlength="20"
                        value="<?php echo e($telefono); ?>"
                    >

                </div>


                <!-- Datos del proveedor: solo visibles si el tipo de cuenta es proveedor -->
                <div id="seccion-proveedor"
                     class="<?php echo ($tipo_cuenta === "proveedor") ? "" : "oculto"; ?>">

                    <h2>Datos del negocio</h2>

                    <div class="campo">

                        <label for="nombre_negocio">
                            Nombre del negocio *
                        </label>

                        <input
                            type="text"
                            id="nombre_negocio"
                            name="nombre_negocio"
                            maxlength="100"
                            value="<?php echo e($nombre_negocio); ?>"
                        >

                    </div>


                    <div class="campo">

                        <label for="tipo_proveedor">
                            Tipo de proveedor *
                        </label>

                        <select id="tipo_proveedor" name="tipo_proveedor">

                            <option value="">Selecciona una opción</option>

                            <?php foreach ($tipos_proveedor as $opcion): ?>

                                <option value="<?php echo e($opcion); ?>"
                                    <?php if ($tipo_proveedor === $opcion) echo "selected"; ?>>
                                    <?php echo e($opcion); ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="campo">

                        <label for="direccion">
                            Dirección
                        </label>

                        <input
                            type="text"
                            id="direccion"
                            name="direccion"
                            maxlength="150"
                            value="<?php echo e($direccion); ?>"
                        >

                    </div>


                    <div class="campo">

                        <label for="descripcion">
                            Descripción del negocio
                        </label>

                        <textarea
                            id="descripcion"
                            name="descripcion"
                            rows="3"
                        ><?php echo e($descripcion); ?></textarea>

                    </div>

                </div>


                <button type="submit" class="btn-registro">
                    Crear cuenta
                </button>

            </form>


            <div class="registro-pie">

                <span>¿Ya tienes una cuenta?</span>

                <a href="login.php">
                    Iniciar sesión
                </a>

            </div>

        </div>

    </main>


    <script>
    document.addEventListener("DOMContentLoaded", function () {

        var selectCuenta = document.getElementById("tipo_cuenta");
        var seccionProveedor = document.getElementById("seccion-proveedor");
        var campoNegocio = document.getElementById("nombre_negocio");
        var campoTipo = document.getElementById("tipo_proveedor");

        // Mostrar u ocultar los datos del proveedor
        function actualizarSeccionProveedor() {
            var esProveedor = selectCuenta.value === "proveedor";
            seccionProveedor.classList.toggle("oculto", !esProveedor);
            campoNegocio.required = esProveedor;
            campoTipo.required = esProveedor;
        }

        selectCuenta.addEventListener("change", actualizarSeccionProveedor);
        actualizarSeccionProveedor();

        // Mostrar u ocultar la contraseña
        var inputPassword = document.getElementById("password");
        var botonVer = document.getElementById("ver-password");

        botonVer.addEventListener("click", function () {
            var visible = inputPassword.type === "text";
            inputPassword.type = visible ? "password" : "text";
            botonVer.textContent = visible ? "Mostrar" : "Ocultar";
        });
    });
    </script>

</body>

</html>