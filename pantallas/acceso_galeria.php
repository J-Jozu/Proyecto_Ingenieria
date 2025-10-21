<?php
session_start();
require_once __DIR__ . '/../conexion/conexion.php';
$pdo = DatabaseConnection::getInstance()->getConnection();

$mensaje = '';
$error = '';

// Procesar formulario de acceso
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    
    if ($correo && $contrasena) {
        // Verificar si el cliente existe en la tabla clientes
        $stmt = $pdo->prepare("SELECT * FROM clientes WHERE correo = ?");
        $stmt->execute([$correo]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cliente) {
            // Si no tiene contraseña, es primera vez
            if (!$cliente['contrasena_hash']) {
                // Validar que la contraseña tenga al menos 6 caracteres
                if (strlen($contrasena) < 6) {
                    $error = 'La contraseña debe tener al menos 6 caracteres.';
                } else {
                    // Crear usuario en tabla usuarios
                    $contrasena_hash = hash('sha256', $contrasena);
                    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre_usuario, contrasena, rol) VALUES (?, ?, 'cliente')");
                    
                    if ($stmt->execute([$correo, $contrasena_hash])) {
                        // Actualizar cliente con contraseña
                        $stmt = $pdo->prepare("UPDATE clientes SET contrasena_hash = ?, esta_activo = 1, fecha_confirmacion = NOW() WHERE id_cliente = ?");
                        $stmt->execute([$contrasena_hash, $cliente['id_cliente']]);
                        
                        // Buscar galería del cliente
                        $stmt = $pdo->prepare("SELECT g.* FROM galerias g 
                                              JOIN sesiones s ON g.sesion_id = s.id_sesion 
                                              WHERE s.cliente_id = ? ORDER BY g.creado_en DESC LIMIT 1");
                        $stmt->execute([$cliente['id_cliente']]);
                        $galeria = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($galeria) {
                            // Iniciar sesión
                            $_SESSION['user_id'] = $pdo->lastInsertId();
                            $_SESSION['username'] = $correo;
                            $_SESSION['rol'] = 'cliente';
                            $_SESSION['cliente_id'] = $cliente['id_cliente'];
                            $_SESSION['galeria_token'] = $galeria['token'];
                            
                            $mensaje = 'Contraseña configurada exitosamente. Bienvenido a tu galería.';
                            
                            // Redirigir a la galería privada
                            header('Location: galeria_privada.php?token=' . $galeria['token']);
                            exit;
                        } else {
                            $error = 'No se encontró ninguna galería para tu cuenta.';
                        }
                    } else {
                        $error = 'Error al crear el usuario. Inténtalo de nuevo.';
                    }
                }
            } else {
                // Verificar contraseña existente
                $contrasena_hash = hash('sha256', $contrasena);
                if ($contrasena_hash === $cliente['contrasena_hash']) {
                    // Buscar usuario en tabla usuarios
                    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE nombre_usuario = ? AND rol = 'cliente'");
                    $stmt->execute([$correo]);
                    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($usuario) {
                        // Buscar galería del cliente
                        $stmt = $pdo->prepare("SELECT g.* FROM galerias g 
                                              JOIN sesiones s ON g.sesion_id = s.id_sesion 
                                              WHERE s.cliente_id = ? ORDER BY g.creado_en DESC LIMIT 1");
                        $stmt->execute([$cliente['id_cliente']]);
                        $galeria = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($galeria) {
                            // Iniciar sesión
                            $_SESSION['user_id'] = $usuario['id_usuario'];
                            $_SESSION['username'] = $correo;
                            $_SESSION['rol'] = 'cliente';
                            $_SESSION['cliente_id'] = $cliente['id_cliente'];
                            $_SESSION['galeria_token'] = $galeria['token'];
                            
                            // Redirigir a la galería privada
                            header('Location: galeria_privada.php?token=' . $galeria['token']);
                            exit;
                        } else {
                            $error = 'No se encontró ninguna galería para tu cuenta.';
                        }
                    } else {
                        $error = 'Error en la cuenta. Contacta al administrador.';
                    }
                } else {
                    $error = 'Contraseña incorrecta.';
                }
            }
        } else {
            $error = 'No se encontró una cuenta con ese correo electrónico.';
        }
    } else {
        $error = 'Por favor completa todos los campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso a Galería Privada - PhotoStudio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="login-card p-4 p-md-5">
                    <div class="text-center mb-4">
                        <i class="bi bi-camera text-primary" style="font-size: 3rem;"></i>
                        <h2 class="mt-3 mb-2">PhotoStudio</h2>
                        <p class="text-muted">Acceso a tu Galería Privada</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($mensaje): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i>
                            <?php echo htmlspecialchars($mensaje); ?>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="mb-3">
                            <label for="correo" class="form-label">
                                <i class="bi bi-envelope me-2"></i>Correo Electrónico
                            </label>
                            <input type="email" class="form-control" id="correo" name="correo" 
                                   value="<?php echo htmlspecialchars($_POST['correo'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-4">
                            <label for="contrasena" class="form-label">
                                <i class="bi bi-lock me-2"></i>Contraseña
                            </label>
                            <input type="password" class="form-control" id="contrasena" name="contrasena" required>
                            <div class="form-text">
                                <i class="bi bi-info-circle me-1"></i>
                                Si es tu primera vez, esta será tu contraseña (mínimo 6 caracteres)
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="bi bi-box-arrow-in-right me-2"></i>
                            Acceder a mi Galería
                        </button>

                        <div class="text-center">
                            <a href="P_Menu_principal.php" class="text-decoration-none">
                                <i class="bi bi-arrow-left me-1"></i>
                                Volver al inicio
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 