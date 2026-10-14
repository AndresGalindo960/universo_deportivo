<?php

session_start();

require_once "config/conexion.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $correo = trim($_POST["correo"]);
    $password = $_POST["password"];

    if (empty($correo) || empty($password)) {

        $error = "Por favor, completa todos los campos.";

    } else {

        $sql = "SELECT id_usuario, nombre, apellido, correo, password, rol 
                FROM usuarios 
                WHERE correo = ?";

        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $correo);
        $stmt->execute();

        $resultado = $stmt->get_result();

        if ($resultado->num_rows == 1) {

            $usuario = $resultado->fetch_assoc();

            if (password_verify($password, $usuario["password"])) {

                                // Regenerar el ID de sesión al iniciar sesión (más seguro)
                session_regenerate_id(true);

                $_SESSION["id_usuario"] = $usuario["id_usuario"];
                $_SESSION["nombre"] = $usuario["nombre"];
                $_SESSION["apellido"] = $usuario["apellido"];
                $_SESSION["correo"] = $usuario["correo"];
                $_SESSION["rol"] = $usuario["rol"];

                // Redirigir según el rol
                if ($usuario["rol"] === "admin") {
                    header("Location: admin_dashboard.php");
                } elseif ($usuario["rol"] === "proveedor") {
                    header("Location: proveedor_dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();

            } else {

                $error = "Correo o contraseña incorrectos.";
            }

        } else {

            $error = "Correo o contraseña incorrectos.";
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Iniciar sesión - Universo Deportivo</title>

    <link rel="stylesheet" href="css/estilos.css">
</head>

<body>

    <div class="contenedor-formulario">

        <div class="tarjeta-formulario">

            <h1>Iniciar sesión</h1>

            <p>Ingresa a tu cuenta de Universo Deportivo</p>

            <?php if (!empty($error)): ?>

                <div class="mensaje-error">
                    <?php echo $error; ?>
                </div>

            <?php endif; ?>

            <form method="POST">

                <div class="campo">
                    <label for="correo">Correo electrónico</label>

                    <input
                        type="email"
                        id="correo"
                        name="correo"
                        placeholder="ejemplo@correo.com"
                        required
                    >
                </div>

                <div class="campo">
                    <label for="password">Contraseña</label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Ingresa tu contraseña"
                        required
                    >
                </div>

                <button type="submit" class="boton-formulario">
                    Iniciar sesión
                </button>

            </form>

            <p class="texto-registro">
                ¿No tienes una cuenta?
                <a href="registro.php">Regístrate aquí</a>
            </p>

        </div>

    </div>

</body>
</html>