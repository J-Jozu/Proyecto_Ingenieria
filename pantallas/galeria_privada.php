<?php
session_start();
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../clases/Pago.php';
$pdo = DatabaseConnection::getInstance()->getConnection();

$error = '';
$success = '';
$galeria = null;
$sesion = null;
$saldo_pendiente = 0;

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id']) || !isset($_SESSION['galeria_token'])) {
    header('Location: acceso_galeria.php');
    exit;
}

// Verificar token de galería
$token = $_SESSION['galeria_token'];
$stmt = $pdo->prepare("SELECT g.*, s.id_sesion, s.tipo_sesion, s.saldo, s.total_pagar, s.abono_inicial, c.nombre_completo, c.correo FROM galerias g 
                      JOIN sesiones s ON g.sesion_id = s.id_sesion 
                      JOIN clientes c ON s.cliente_id = c.id_cliente 
                      WHERE g.token = ?");
$stmt->execute([$token]);
$galeria = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$galeria) {
    $error = 'Galería no encontrada o token inválido.';
    session_destroy();
    header('Location: acceso_galeria.php');
    exit;
}

// Verificar que el usuario logueado es el dueño de la galería
if ($_SESSION['username'] !== $galeria['correo']) {
    $error = 'No tienes permisos para acceder a esta galería.';
    session_destroy();
    header('Location: acceso_galeria.php');
    exit;
}

$sesion = $galeria;
$saldo_pendiente = $sesion['saldo'];

// Procesar pago
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['procesar_pago'])) {
    // Verificar que tenemos la información de la sesión
    if (!$galeria || !isset($galeria['id_sesion'])) {
        $error = 'Error: No se pudo obtener la información de la sesión.';
    } else {
        $metodo_pago = $_POST['metodo_pago'];
        $monto = (float)$saldo_pendiente;
    
    if ($metodo_pago === 'tarjeta') {
        // Validar campos de tarjeta
        $titular = trim($_POST['titular'] ?? '');
        $numero = preg_replace('/\s+/', '', $_POST['numero'] ?? '');
        $vencimiento = $_POST['vencimiento'] ?? '';
        $cvv = $_POST['cvv'] ?? '';
        
        $errores = [];
        if (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ ]+$/u', $titular) || strlen($titular) < 5) {
            $errores[] = 'El nombre del titular debe contener solo letras y espacios, mínimo 5 caracteres.';
        }
        if (!preg_match('/^\d{16}$/', $numero)) {
            $errores[] = 'El número de tarjeta debe tener 16 dígitos.';
        }
        if (!preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $vencimiento)) {
            $errores[] = 'El vencimiento debe tener formato MM/AA.';
        }
        if (!preg_match('/^\d{3,4}$/', $cvv)) {
            $errores[] = 'El CVV debe tener 3 o 4 dígitos.';
        }
        
        if (empty($errores)) {
            // Procesar pago con tarjeta
            $pago = Pago::seleccionarMetodoPago('tarjeta', $monto, $_POST);
            $numeroFactura = 'FCT-' . date('Ymd-His-') . rand(1000, 9999);
            $pago->registrarPagoSesion($sesion['id_sesion'], $numeroFactura);
            
            $success = "¡Pago procesado exitosamente! Factura: $numeroFactura. Ya puedes acceder a todas las funciones de tu galería.";
            $saldo_pendiente = 0; // Actualizar saldo
        } else {
            $error = implode('<br>', $errores);
        }
    } elseif ($metodo_pago === 'yappy') {
        // Simular redirección a Yappy
        $success = "Serás redirigido a Yappy para completar el pago de B/. " . number_format($monto, 2) . ". Una vez completado el pago, podrás acceder a todas las funciones.";
        // En un caso real, aquí se redirigiría a Yappy
    }
    }
}

// Procesar selección de fotos para edición
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['seleccionar_fotos'])) {
    $fotos_seleccionadas = $_POST['fotos_seleccionadas'] ?? [];
    $comentarios = trim($_POST['comentarios'] ?? '');
    
    if (empty($fotos_seleccionadas)) {
        $error = 'Debes seleccionar al menos una foto para editar.';
    } elseif (empty($comentarios)) {
        $error = 'Debes agregar comentarios sobre la edición que deseas.';
    } else {
        // Marcar fotos como seleccionadas para edición
        foreach ($fotos_seleccionadas as $foto_id) {
            $stmt = $pdo->prepare("UPDATE fotos SET is_selected = 1 WHERE id_foto = ? AND galeria_id = ?");
            $stmt->execute([$foto_id, $galeria['id_galeria']]);
        }
        
        // Guardar comentarios en la tabla comentarios_edicion
        $fotos_json = json_encode($fotos_seleccionadas);
        $stmt = $pdo->prepare("INSERT INTO comentarios_edicion (galeria_id, cliente_id, comentarios, fotos_seleccionadas) VALUES (?, ?, ?, ?)");
        $stmt->execute([$galeria['id_galeria'], $_SESSION['cliente_id'], $comentarios, $fotos_json]);
        
        $success = "Se han seleccionado " . count($fotos_seleccionadas) . " fotos para edición. Los administradores revisarán tus comentarios.";
    }
}

// Cargar fotos de la galería
$stmt = $pdo->prepare("SELECT * FROM fotos WHERE galeria_id = ? ORDER BY uploaded_at DESC");
$stmt->execute([$galeria['id_galeria']]);
$fotos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galería Privada - PhotoStudio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            border: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .photo-card {
            transition: transform 0.3s ease;
        }
        .photo-card:hover {
            transform: scale(1.05);
        }
        .photo-selected {
            border: 3px solid #28a745;
            box-shadow: 0 0 15px rgba(40, 167, 69, 0.3);
        }
        .payment-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header text-center">
                        <h3 class="mb-0"><i class="bi bi-images"></i> Tu Galería Privada</h3>
                        <p class="mb-0"><?= htmlspecialchars($galeria['nombre_completo']) ?> - <?= htmlspecialchars($galeria['tipo_sesion']) ?></p>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <p class="mb-0">
                                    <i class="bi bi-person-circle"></i> 
                                    <strong>Cliente:</strong> <?= htmlspecialchars($galeria['nombre_completo']) ?>
                                </p>
                                <p class="mb-0">
                                    <i class="bi bi-envelope"></i> 
                                    <strong>Correo:</strong> <?= htmlspecialchars($galeria['correo']) ?>
                                </p>
                            </div>
                            <div class="col-md-6 text-end">
                                <a href="cerrar_sesion.php" class="btn btn-outline-danger">
                                    <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mensajes -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <script>
                // Cerrar modal de pago si está abierto
                const pagoModal = document.getElementById('pagoModal');
                if (pagoModal) {
                    const modal = bootstrap.Modal.getInstance(pagoModal);
                    if (modal) {
                        modal.hide();
                    }
                }
            </script>
        <?php endif; ?>

        <!-- Sección de Pago -->
        <?php if ($saldo_pendiente > 0): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card payment-section">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-credit-card"></i> Pago Pendiente</h5>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h6>Saldo Pendiente: <span class="text-danger">B/. <?= number_format($saldo_pendiente, 2) ?></span></h6>
                                    <p class="text-muted mb-0">Debes completar el pago para acceder a todas las funciones.</p>
                                </div>
                                <div class="col-md-6 text-end">
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#pagoModal">
                                        <i class="bi bi-credit-card"></i> Pagar Ahora
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card payment-section" style="background: rgba(40, 167, 69, 0.1); border-left: 4px solid #28a745;">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-12">
                                    <h6 class="text-success mb-0">
                                        <i class="bi bi-check-circle"></i> Pago Completado
                                    </h6>
                                    <p class="text-muted mb-0">Ya puedes acceder a todas las funciones de tu galería.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Galería de fotos -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body p-4">
                        <?php if (empty($fotos)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-images" style="font-size: 4rem; color: #ccc;"></i>
                                <h5 class="mt-3 text-muted">No hay fotos disponibles</h5>
                                <p class="text-muted">Las fotos de tu sesión aparecerán aquí pronto.</p>
                            </div>
                        <?php else: ?>
                            <form method="post" id="seleccionForm">
                                <div class="row g-4">
                                    <?php foreach ($fotos as $foto): ?>
                                        <div class="col-md-6 col-lg-4">
                                            <div class="card photo-card h-100 <?= $foto['is_selected'] ? 'photo-selected' : '' ?>" 
                                                 onclick="toggleSelection(<?= $foto['id_foto'] ?>)">
                                                <img src="../galeria/<?= htmlspecialchars($foto['filename']) ?>" 
                                                     class="card-img-top" alt="Foto de la sesión"
                                                     style="height: 250px; object-fit: cover;">
                                                <div class="card-body">
                                                    <p class="card-text small text-muted">
                                                        <i class="bi bi-calendar"></i>
                                                        Subida: <?= date('d/m/Y H:i', strtotime($foto['uploaded_at'])) ?>
                                                    </p>
                                                    
                                                    <?php if ($saldo_pendiente <= 0): ?>
                                                        <div class="d-grid gap-2">
                                                            <a href="../galeria/<?= htmlspecialchars($foto['filename']) ?>" 
                                                               class="btn btn-sm btn-outline-primary"
                                                               download="<?= htmlspecialchars($foto['filename']) ?>">
                                                                <i class="bi bi-download"></i> Descargar
                                                            </a>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" 
                                                                       name="fotos_seleccionadas[]" 
                                                                       value="<?= $foto['id_foto'] ?>" 
                                                                       id="foto_<?= $foto['id_foto'] ?>"
                                                                       <?= $foto['is_selected'] ? 'checked' : '' ?>>
                                                                <label class="form-check-label" for="foto_<?= $foto['id_foto'] ?>">
                                                                    Seleccionar para edición
                                                                </label>
                                                            </div>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="alert alert-warning small">
                                                            <i class="bi bi-lock"></i> Completa el pago para descargar
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <?php if ($saldo_pendiente <= 0): ?>
                                    <div class="row mt-4">
                                        <div class="col-12">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h6><i class="bi bi-pencil"></i> Comentarios para Edición</h6>
                                                    <textarea class="form-control" name="comentarios" rows="4" 
                                                              placeholder="Describe qué tipo de edición deseas en las fotos seleccionadas..."></textarea>
                                                    <div class="mt-3">
                                                        <button type="submit" name="seleccionar_fotos" class="btn btn-success">
                                                            <i class="bi bi-check-circle"></i> Enviar Selección para Edición
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </form>
                            
                            <div class="mt-4 text-center">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i>
                                    <strong>Total de fotos:</strong> <?= count($fotos) ?> imágenes disponibles
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="row mt-4">
            <div class="col-12 text-center">
                <a href="P_Menu_principal.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Volver al Menú Principal
                </a>
            </div>
        </div>
    </div>

    <!-- Modal de Pago -->
    <div class="modal fade" id="pagoModal" tabindex="-1" aria-labelledby="pagoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pagoModalLabel">
                        <i class="bi bi-credit-card"></i> Completar Pago
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Monto a pagar:</strong> B/. <?= number_format($saldo_pendiente, 2) ?>
                    </div>
                    
                    <form method="post" id="pagoForm">
                        <div class="mb-3">
                            <label class="form-label">Método de Pago</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="metodo_pago" id="tarjeta" value="tarjeta" checked>
                                <label class="form-check-label" for="tarjeta">
                                    <i class="bi bi-credit-card"></i> Tarjeta de Crédito/Débito
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="metodo_pago" id="yappy" value="yappy">
                                <label class="form-check-label" for="yappy">
                                    <i class="bi bi-phone"></i> Yappy
                                </label>
                            </div>
                        </div>

                        <!-- Campos de Tarjeta -->
                        <div id="camposTarjeta">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label for="titular" class="form-label">Nombre del Titular</label>
                                    <input type="text" class="form-control" id="titular" name="titular" 
                                           placeholder="Nombre completo del titular">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label for="numero" class="form-label">Número de Tarjeta</label>
                                    <input type="text" class="form-control" id="numero" name="numero" 
                                           placeholder="1234 5678 9012 3456" maxlength="19">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label for="vencimiento" class="form-label">Vencimiento</label>
                                    <input type="text" class="form-control" id="vencimiento" name="vencimiento" 
                                           placeholder="MM/AA" maxlength="5">
                                </div>
                                <div class="col-6 mb-3">
                                    <label for="cvv" class="form-label">CVV</label>
                                    <input type="text" class="form-control" id="cvv" name="cvv" 
                                           placeholder="123" maxlength="4">
                                </div>
                            </div>
                        </div>

                        <!-- Mensaje Yappy -->
                        <div id="mensajeYappy" style="display: none;">
                            <div class="alert alert-warning">
                                <i class="bi bi-info-circle"></i>
                                <strong>Pago con Yappy</strong><br>
                                Serás redirigido a la aplicación de Yappy para completar el pago de B/. <?= number_format($saldo_pendiente, 2) ?>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="pagoForm" name="procesar_pago" class="btn btn-primary">
                        <i class="bi bi-credit-card"></i> Procesar Pago
                    </button>
                </div>
                <div class="modal-body" id="procesandoPago" style="display: none;">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Procesando...</span>
                        </div>
                        <p class="mt-3">Procesando tu pago, por favor espera...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Formatear número de tarjeta
        document.getElementById('numero').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = value.replace(/\s/g, '').replace(/(\d{4})/g, '$1 ').trim();
            e.target.value = formattedValue;
        });

        // Formatear vencimiento
        document.getElementById('vencimiento').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            e.target.value = value;
        });

        // Mostrar/ocultar campos según método de pago
        document.querySelectorAll('input[name="metodo_pago"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                const camposTarjeta = document.getElementById('camposTarjeta');
                const mensajeYappy = document.getElementById('mensajeYappy');
                
                if (this.value === 'tarjeta') {
                    camposTarjeta.style.display = 'block';
                    mensajeYappy.style.display = 'none';
                } else {
                    camposTarjeta.style.display = 'none';
                    mensajeYappy.style.display = 'block';
                }
            });
        });

        // Toggle selección de fotos
        function toggleSelection(fotoId) {
            const checkbox = document.getElementById('foto_' + fotoId);
            const card = checkbox.closest('.photo-card');
            
            checkbox.checked = !checkbox.checked;
            
            if (checkbox.checked) {
                card.classList.add('photo-selected');
            } else {
                card.classList.remove('photo-selected');
            }
        }

        // Mostrar spinner durante el pago
        document.getElementById('pagoForm').addEventListener('submit', function() {
            const procesandoDiv = document.getElementById('procesandoPago');
            const modalBody = document.querySelector('#pagoModal .modal-body');
            const modalFooter = document.querySelector('#pagoModal .modal-footer');
            
            // Ocultar contenido original y mostrar spinner
            modalBody.style.display = 'none';
            modalFooter.style.display = 'none';
            procesandoDiv.style.display = 'block';
        });
    </script>
</body>
</html>