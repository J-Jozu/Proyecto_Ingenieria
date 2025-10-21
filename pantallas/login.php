<?php
session_start();

// Si ya hay una sesión activa, redirigir según el rol
if (isset($_SESSION['rol'])) {
    $rol = strtolower($_SESSION['rol']);
    if ($rol === 'admin') {
        header('Location: admin_galeria.php');
        exit;
    } elseif ($rol === 'cliente') {
        header('Location: P_Menu_principal.php');
        exit;
    }
}

// Incluimos la clase de conexión
require_once __DIR__ . '/../conexion/conexion.php';

$error = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = DatabaseConnection::getInstance()->getConnection();

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Por favor, complete todos los campos.';
    } else {
        try {
            // Ahora consultamos usando los nombres reales de columnas
            $stmt = $conn->prepare("
                SELECT
                  id_usuario   AS id,
                  nombre_usuario AS username,
                  rol
                FROM usuarios
                WHERE nombre_usuario = ?
                  AND contrasena       = SHA2(?, 256)
                LIMIT 1
            ");
            $stmt->execute([$username, $password]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Guardar información del usuario en la sesión
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['rol']      = $user['rol'];

                // Redirigir según el rol
                if (strtolower($user['rol']) === 'admin') {
                    header('Location: admin_galeria.php');
                } else {
                    header('Location: P_Menu_principal.php');
                }
                exit;
            } else {
                $error = 'Usuario o contraseña incorrectos.';
            }
        } catch (PDOException $e) {
            $error = 'Error al intentar iniciar sesión. Por favor, intente nuevamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - PhotoStudio</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Tus estilos... -->
</head>
<body>
  <div class="container">
    <div class="login-container">
      <div class="brand text-center my-4">
        <i class="bi bi-camera fs-1 text-primary"></i>
        <h1 class="h3 mt-2">PhotoStudio</h1>
      </div>
      <div class="login-card mx-auto p-4 shadow-sm" style="max-width: 400px;">
        <h2 class="text-center mb-4">Iniciar Sesión</h2>
        <?php if ($error): ?>
          <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>
        <?php if ($successMessage): ?>
          <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <?= htmlspecialchars($successMessage) ?>
          </div>
        <?php endif; ?>
        <form method="POST" action="">
          <div class="form-floating mb-3">
            <input type="text"
                   class="form-control"
                   id="username"
                   name="username"
                   placeholder="Usuario"
                   required>
            <label for="username">Usuario</label>
          </div>
          <div class="form-floating mb-4 position-relative">
            <input type="password"
                   class="form-control"
                   id="password"
                   name="password"
                   placeholder="Contraseña"
                   required>
            <label for="password">Contraseña</label>
            <button type="button"
                    class="btn btn-outline-secondary position-absolute top-50 end-0 translate-middle-y me-2"
                    id="togglePassword"
                    tabindex="-1"
                    style="z-index:2;">
              <i class="bi bi-eye" id="iconPassword"></i>
            </button>
          </div>
          <div class="d-flex justify-content-between mb-3">
            <a href="recuperar_contrasena.php" class="link-primary text-decoration-none">
              ¿Olvidaste tu contraseña?
            </a>
          </div>
          <button type="submit" class="btn btn-primary w-100 mb-2">
            <i class="bi bi-box-arrow-in-right me-2"></i> Iniciar Sesión
          </button>
          <button type="button"
                  class="btn btn-outline-secondary w-100"
                  onclick="window.location.href='P_Menu_principal.php'">
            <i class="bi bi-x-circle me-2"></i> Cancelar
          </button>
        </form>
        <div class="text-center mt-3">
          ¿No tienes una cuenta? <a href="registro.php">Regístrate aquí</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS y toggle password -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.getElementById('togglePassword').addEventListener('click', function() {
      const pwd = document.getElementById('password');
      const icon = document.getElementById('iconPassword');
      if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
      } else {
        pwd.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
      }
    });
  </script>
</body>
</html>
