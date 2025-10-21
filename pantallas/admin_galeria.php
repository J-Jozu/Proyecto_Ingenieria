<?php
session_start();
if (!isset($_SESSION['rol']) || strtolower($_SESSION['rol']) !== 'admin') {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../clases/Galeria.php';
$pdo = DatabaseConnection::getInstance()->getConnection();

// Procesar acciones de sesión y clientes
if (isset($_GET['accion'], $_GET['id'])) {
    $id = (int)$_GET['id'];
    switch ($_GET['accion']) {
        case 'completado':
            // Para sesiones - cambiar a completado
            $pdo->prepare("UPDATE sesiones SET estado='completado' WHERE id_sesion=?")->execute([$id]);
            break;
        case 'en_espera':
            // Para sesiones - cambiar a en_espera
            $pdo->prepare("UPDATE sesiones SET estado='en_espera' WHERE id_sesion=?")->execute([$id]);
            break;
        case 'cancelado':
            // Para sesiones - cambiar a cancelado
            $pdo->prepare("UPDATE sesiones SET estado='cancelado' WHERE id_sesion=?")->execute([$id]);
            break;
        case 'completada_impresion':
            // Para impresiones - cambiar a completada
            $pdo->prepare("UPDATE ordenes_impresion SET estado='completada' WHERE id_orden=?")->execute([$id]);
            break;
        case 'en_espera_impresion':
            // Para impresiones - cambiar a en espera
            $pdo->prepare("UPDATE ordenes_impresion SET estado='en espera' WHERE id_orden=?")->execute([$id]);
            break;
        case 'cancelado_impresion':
            // Para impresiones - cambiar a cancelado
            $pdo->prepare("UPDATE ordenes_impresion SET estado='cancelado' WHERE id_orden=?")->execute([$id]);
            break;
        case 'eliminar':
            $pdo->prepare("DELETE FROM sesiones WHERE id_sesion=?")->execute([$id]);
            break;
        case 'eliminar_impresion':
            $pdo->prepare("DELETE FROM ordenes_impresion WHERE id_orden=?")->execute([$id]);
            break;
    }
    header('Location: admin_galeria.php'); exit;
}

// Procesar subida de fotos y envío de correo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subir_fotos'])) {
    $sesion_id = (int)$_POST['sesion_id'];
    
    // Obtener información de la sesión y cliente
    $stmt = $pdo->prepare("SELECT s.*, c.nombre_completo, c.correo FROM sesiones s JOIN clientes c ON s.cliente_id = c.id_cliente WHERE s.id_sesion = ?");
    $stmt->execute([$sesion_id]);
    $sesion = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sesion) {
        // Crear galería
        $token = bin2hex(random_bytes(16));
        $stmt = $pdo->prepare("INSERT INTO galerias (sesion_id, usuario_id, tipo_galeria, estado, token) VALUES (?, ?, 'final', 'completada', ?)");
        $stmt->execute([$sesion_id, $_SESSION['user_id'], $token]);
        $galeria_id = $pdo->lastInsertId();
        
        // Procesar fotos subidas
        $fotos_subidas = 0;
        if (isset($_FILES['fotos']) && is_array($_FILES['fotos']['name'])) {
            $upload_dir = __DIR__ . '/../galeria/';
            
            for ($i = 0; $i < count($_FILES['fotos']['name']); $i++) {
                if ($_FILES['fotos']['error'][$i] === UPLOAD_ERR_OK) {
                    $filename = uniqid('img_') . '_' . time() . '_' . $i . '.' . pathinfo($_FILES['fotos']['name'][$i], PATHINFO_EXTENSION);
                    $filepath = $upload_dir . $filename;
                    
                    if (move_uploaded_file($_FILES['fotos']['tmp_name'][$i], $filepath)) {
                        // Guardar en base de datos
                        $stmt = $pdo->prepare("INSERT INTO fotos (galeria_id, filename, url, uploaded_at) VALUES (?, ?, ?, NOW())");
                        $stmt->execute([$galeria_id, $filename, $filename]);
                        $fotos_subidas++;
                    }
                }
            }
        }
        
        // Enviar correo al cliente
        if ($fotos_subidas > 0) {
            $galeria_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/galeria_privada.php?token=" . $token;
            
            require_once __DIR__ . '/../clases/Correo.php';
            
            $asunto = "¡Tu galería fotográfica está lista! - PhotoStudio";
            $cuerpo = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                    <h1 style='margin: 0; font-size: 28px;'>📷 PhotoStudio</h1>
                    <p style='margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;'>Tu galería fotográfica está lista</p>
                </div>
                
                <div style='background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
                    <h2 style='color: #333; margin-bottom: 20px;'>¡Hola {$sesion['nombre_completo']}!</h2>
                    
                    <p style='color: #666; line-height: 1.6; margin-bottom: 20px;'>
                        Nos complace informarte que tu sesión fotográfica ha sido procesada y tu galería privada está lista para que la revises.
                    </p>
                    
                    <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #667eea;'>
                        <h3 style='color: #333; margin: 0 0 10px 0;'>📊 Resumen de tu galería:</h3>
                        <ul style='color: #666; margin: 0; padding-left: 20px;'>
                            <li><strong>Fotos procesadas:</strong> $fotos_subidas imágenes</li>
                            <li><strong>Acceso:</strong> Galería privada y segura</li>
                            <li><strong>Disponibilidad:</strong> Acceso ilimitado</li>
                        </ul>
                    </div>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='$galeria_url' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);'>
                            🖼️ Ver Mi Galería
                        </a>
                    </div>
                    
                    <p style='color: #666; line-height: 1.6; margin-bottom: 20px;'>
                        <strong>Instrucciones:</strong><br>
                        1. Haz clic en el botón de arriba para acceder a tu galería<br>
                        2. Revisa todas las fotos de tu sesión<br>
                        3. Selecciona tus favoritas para edición final<br>
                        4. Contacta con nosotros si tienes alguna pregunta
                    </p>
                    
                    <div style='background: #e8f4fd; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2196f3;'>
                        <p style='margin: 0; color: #1976d2; font-size: 14px;'>
                            <strong>💡 Consejo:</strong> Te recomendamos revisar las fotos en un dispositivo con buena pantalla para apreciar todos los detalles.
                        </p>
                    </div>
                    
                    <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>
                    
                    <p style='color: #999; font-size: 14px; text-align: center; margin: 0;'>
                        Si tienes problemas para acceder a tu galería, copia y pega este enlace en tu navegador:<br>
                        <a href='$galeria_url' style='color: #667eea;'>$galeria_url</a>
                    </p>
                </div>
                
                <div style='text-align: center; padding: 20px; color: #999; font-size: 12px;'>
                    <p style='margin: 0;'>© 2025 PhotoStudio. Todos los derechos reservados.</p>
                    <p style='margin: 5px 0 0 0;'>Este es un correo automático, por favor no respondas a este mensaje.</p>
                </div>
            </div>";
            
            $enviado = CorreoFacade::enviarCorreo($sesion['correo'], $asunto, $cuerpo);
            
            if ($enviado) {
                $_SESSION['mensaje_exito'] = "Se subieron $fotos_subidas fotos y se envió el correo al cliente.";
            } else {
                $_SESSION['mensaje_exito'] = "Se subieron $fotos_subidas fotos pero hubo un problema al enviar el correo.";
            }
        } else {
            $_SESSION['mensaje_error'] = "No se pudieron subir las fotos.";
        }
    }
    
    header('Location: admin_galeria.php'); exit;
}

// Cargar sesiones
$sessions = $pdo->query(
  "SELECT s.id_sesion, s.tipo_sesion, s.fecha_sesion,
          s.total_pagar, s.abono_inicial, s.estado,
          c.nombre_completo, c.correo,
          s.descripcion_sesion, s.duracion_sesion,
          s.lugar_sesion, s.direccion_sesion,
          s.estilo_fotografia, s.servicios_adicionales, s.otros_datos
     FROM sesiones s
     JOIN clientes c ON s.cliente_id=c.id_cliente
     ORDER BY s.fecha_sesion DESC"
)->fetchAll(PDO::FETCH_ASSOC);

// Cargar clientes
$clientes = $pdo->query(
  "SELECT id_cliente, nombre_completo, correo, telefono_celular FROM clientes ORDER BY nombre_completo ASC"
)->fetchAll(PDO::FETCH_ASSOC);

// Cargar ordenes de impresión
$impresiones = $pdo->query(
  "SELECT o.id_orden, o.cliente_id, o.foto_id, o.cantidad, o.tamanio_id, o.precio_unitario, o.subtotal, o.estado, o.fecha_solicitud, c.nombre_completo FROM ordenes_impresion o JOIN clientes c ON o.cliente_id = c.id_cliente ORDER BY o.fecha_solicitud DESC"
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>📷 Panel de Administrador - PhotoStudio</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <style>
    body { background: #eceff1; }
    .header { background: #37474f; color: #fff; padding: 1rem 1.5rem; }
    .header h1 { margin: 0; font-size: 1.5rem; }
    .table-wrapper { background: #fff; border-radius: .5rem; box-shadow: 0 1px 5px rgba(0,0,0,.1); padding: 1rem; }
    .badge-status { font-weight: 600; }
    .btn-sm { font-size: .8rem; }
    .modal-body p { margin-bottom: .5rem; }
    .actions button { margin-right: .25rem; }
  </style>
</head>
<body>
  <div class="d-flex" style="min-height:100vh;">
    <!-- Sidebar -->
    <nav class="sidebar bg-dark text-white p-3" style="width:260px;min-height:100vh;box-shadow:0 0 20px rgba(0,0,0,.08);">
      <div class="d-flex align-items-center mb-4">
        <i class="bi bi-camera2" style="font-size:2em;"></i>
        <span class="ms-2 fw-bold fs-5">PhotoStudio Admin</span>
      </div>
      <ul class="nav flex-column gap-2">
        <li class="nav-item">
          <a class="nav-link text-white active" id="sesiones-tab" data-bs-toggle="tab" data-bs-target="#sesiones" href="#sesiones">
            <i class="bi bi-calendar-check me-2"></i>Sesiones
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white" id="clientes-tab" data-bs-toggle="tab" data-bs-target="#clientes" href="#clientes">
            <i class="bi bi-people me-2"></i>Clientes
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white" id="impresiones-tab" data-bs-toggle="tab" data-bs-target="#impresiones" href="#impresiones">
            <i class="bi bi-printer me-2"></i>Impresiones
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white" href="admin_comentarios_edicion.php">
            <i class="bi bi-pencil-square me-2"></i>Comentarios Edición
          </a>
        </li>
      </ul>
      <div class="mt-auto pt-4">
        <a href="logout.php" class="btn btn-outline-light w-100">
          <i class="bi bi-box-arrow-right"></i> Salir
        </a>
      </div>
    </nav>
    <!-- Main Content -->
    <div class="flex-grow-1 p-4" style="background:#f5f7fa;">
      <!-- Mensajes de éxito/error -->
      <?php if (isset($_SESSION['mensaje_exito'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="bi bi-check-circle"></i> <?= $_SESSION['mensaje_exito'] ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['mensaje_exito']); ?>
      <?php endif; ?>
      
      <?php if (isset($_SESSION['mensaje_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="bi bi-exclamation-triangle"></i> <?= $_SESSION['mensaje_error'] ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['mensaje_error']); ?>
      <?php endif; ?>
      
      <div class="tab-content" id="adminTabsContent">
      <!-- TAB SESIONES -->
      <div class="tab-pane fade show active" id="sesiones" role="tabpanel">
        <div class="table-responsive rounded shadow-sm bg-white p-3 mb-4">
          <table class="table table-bordered table-hover align-middle mb-0">
            <thead class="table-primary">
              <tr class="align-middle text-center">
                <th>ID</th>
                <th>Tipo</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Correo</th>
                <th>Estado</th>
                <th>Total</th>
                <th>Abono</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($sessions as $s): ?>
              <tr class="text-center">
                <td><?= $s['id_sesion'] ?></td>
                <td><?= htmlspecialchars($s['tipo_sesion']) ?></td>
                <td><?= $s['fecha_sesion'] ?></td>
                <td><?= htmlspecialchars($s['nombre_completo']) ?></td>
                <td><?= htmlspecialchars($s['correo']) ?></td>
                <td>
                  <?php
                  $estado = strtolower($s['estado']);
                  if ($estado === 'en_espera') {
                    echo '<span class="badge badge-status bg-warning text-dark"><i class="bi bi-hourglass-split"></i> En espera</span>';
                  } elseif ($estado === 'completado') {
                    echo '<span class="badge badge-status bg-success"><i class="bi bi-check-circle"></i> Completado</span>';
                  } elseif ($estado === 'cancelado') {
                    echo '<span class="badge badge-status bg-danger"><i class="bi bi-x-circle"></i> Cancelado</span>';
                  } else {
                    echo htmlspecialchars($s['estado']);
                  }
                  ?>
                </td>
                <td>B/. <?= number_format($s['total_pagar'],2) ?></td>
                <td>B/. <?= number_format($s['abono_inicial'],2) ?></td>
                <td>
                  <!-- Ver Detalles -->
                  <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#verSesionModal<?= $s['id_sesion'] ?>"><i class="bi bi-eye"></i></button>
                  <!-- Cambiar Estado -->
                  <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#estadoSesionModal<?= $s['id_sesion'] ?>"><i class="bi bi-arrow-repeat"></i></button>
                  <!-- Crear galería si completado -->
                  <?php if (strtolower($s['estado']) === 'completado'): ?>
                    <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#crearGaleriaModal<?= $s['id_sesion'] ?>"><i class="bi bi-images"></i></button>
                  <?php endif; ?>
                  <!-- Editar (decorativo) -->
                  <button class="btn btn-sm btn-outline-warning" disabled><i class="bi bi-pencil"></i></button>
                  <!-- Eliminar -->
                  <a href="?accion=eliminar&id=<?= $s['id_sesion'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Seguro que deseas eliminar esta sesión?');"><i class="bi bi-trash"></i></a>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <!-- TAB CLIENTES -->
      <div class="tab-pane fade" id="clientes" role="tabpanel">
        <div class="table-responsive rounded shadow-sm bg-white p-3">
          <table class="table table-bordered table-hover align-middle mb-0">
            <thead class="table-primary">
              <tr class="align-middle text-center">
                <th>ID</th>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Teléfono</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($clientes as $c): ?>
              <tr class="text-center">
                <td><?= $c['id_cliente'] ?></td>
                <td><?= htmlspecialchars($c['nombre_completo']) ?></td>
                <td><?= htmlspecialchars($c['correo']) ?></td>
                <td><?= htmlspecialchars($c['telefono_celular']) ?></td>
                <td>
                  <!-- Ver Detalles -->
                  <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#verClienteModal<?= $c['id_cliente'] ?>"><i class="bi bi-eye"></i></button>
                  <!-- Editar (decorativo) -->
                  <button class="btn btn-sm btn-outline-warning" disabled><i class="bi bi-pencil"></i></button>
                  <!-- Eliminar -->
                  <a href="?accion=eliminar_cliente&id=<?= $c['id_cliente'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Seguro que deseas eliminar este cliente?');"><i class="bi bi-trash"></i></a>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <!-- TAB IMPRESIONES -->
      <div class="tab-pane fade" id="impresiones" role="tabpanel">
        <div class="table-responsive rounded shadow-sm bg-white p-3">
          <table class="table table-bordered table-hover align-middle mb-0">
            <thead class="table-primary">
              <tr class="align-middle text-center">
                <th>ID</th>
                <th>Cliente</th>
                <th>Foto</th>
                <th>Cantidad</th>
                <th>Tamaño</th>
                <th>Precio Unitario</th>
                <th>Subtotal</th>
                <th>Estado</th>
                <th>Fecha Solicitud</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($impresiones as $i): ?>
              <tr class="text-center">
                <td><?= $i['id_orden'] ?></td>
                <td><?= htmlspecialchars($i['nombre_completo']) ?></td>
                <td><?= $i['foto_id'] ?></td>
                <td><?= $i['cantidad'] ?></td>
                <td><?= $i['tamanio_id'] ?></td>
                <td>B/. <?= number_format($i['precio_unitario'],2) ?></td>
                <td>B/. <?= number_format($i['subtotal'],2) ?></td>
                <td>
                  <?php
                  $estado = strtolower($i['estado']);
                  if ($estado === 'en espera') {
                    echo '<span class="badge badge-status bg-warning text-dark"><i class="bi bi-hourglass-split"></i> En espera</span>';
                  } elseif ($estado === 'completada') {
                    echo '<span class="badge badge-status bg-success"><i class="bi bi-check-circle"></i> Completada</span>';
                  } elseif ($estado === 'cancelado') {
                    echo '<span class="badge badge-status bg-danger"><i class="bi bi-x-circle"></i> Cancelado</span>';
                  } else {
                    echo htmlspecialchars($i['estado']);
                  }
                  ?>
                </td>
                <td><?= $i['fecha_solicitud'] ?></td>
                <td>
                  <!-- Ver Detalles -->
                  <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#verImpresionModal<?= $i['id_orden'] ?>"><i class="bi bi-eye"></i></button>
                  <!-- Cambiar Estado -->
                  <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#estadoImpresionModal<?= $i['id_orden'] ?>"><i class="bi bi-arrow-repeat"></i></button>
                  <!-- Editar (decorativo) -->
                  <button class="btn btn-sm btn-outline-warning" disabled><i class="bi bi-pencil"></i></button>
                  <!-- Eliminar -->
                  <a href="?accion=eliminar_impresion&id=<?= $i['id_orden'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Seguro que deseas eliminar esta impresión?');"><i class="bi bi-trash"></i></a>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <!-- Modales Sesiones -->
    <?php foreach ($sessions as $s): ?>
    <div class="modal fade" id="verSesionModal<?= $s['id_sesion'] ?>" tabindex="-1" aria-labelledby="verSesionLabel<?= $s['id_sesion'] ?>" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="verSesionLabel<?= $s['id_sesion'] ?>">Detalles de la Sesión</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <p><strong>ID:</strong> <?= $s['id_sesion'] ?></p>
            <p><strong>Tipo:</strong> <?= htmlspecialchars($s['tipo_sesion']) ?></p>
            <p><strong>Fecha:</strong> <?= $s['fecha_sesion'] ?></p>
            <p><strong>Cliente:</strong> <?= htmlspecialchars($s['nombre_completo']) ?></p>
            <p><strong>Correo:</strong> <?= htmlspecialchars($s['correo']) ?></p>
            <p><strong>Estado:</strong> <?= htmlspecialchars($s['estado']) ?></p>
            <p><strong>Total:</strong> B/. <?= number_format($s['total_pagar'],2) ?></p>
            <p><strong>Abono:</strong> B/. <?= number_format($s['abono_inicial'],2) ?></p>
            <p><strong>Descripción:</strong> <?= htmlspecialchars($s['descripcion_sesion']) ?></p>
            <p><strong>Duración:</strong> <?= htmlspecialchars($s['duracion_sesion']) ?></p>
            <p><strong>Lugar:</strong> <?= htmlspecialchars($s['lugar_sesion']) ?></p>
            <p><strong>Dirección:</strong> <?= htmlspecialchars($s['direccion_sesion']) ?></p>
            <p><strong>Estilo Fotografía:</strong> <?= htmlspecialchars($s['estilo_fotografia']) ?></p>
            <p><strong>Servicios Adicionales:</strong> <?= htmlspecialchars($s['servicios_adicionales']) ?></p>
            <p><strong>Otros Datos:</strong> <?= htmlspecialchars($s['otros_datos']) ?></p>
          </div>
        </div>
      </div>
    </div>
    <!-- Modal Estado Sesión -->
    <div class="modal fade" id="estadoSesionModal<?= $s['id_sesion'] ?>" tabindex="-1" aria-labelledby="estadoSesionLabel<?= $s['id_sesion'] ?>" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="estadoSesionLabel<?= $s['id_sesion'] ?>">Cambiar Estado de la Sesión</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <form method="get" action="">
              <input type="hidden" name="id" value="<?= $s['id_sesion'] ?>">
              <div class="mb-3">
                <label for="estado" class="form-label">Estado</label>
                <select class="form-select" name="accion" id="estado">
                  <option value="completado" <?= strtolower($s['estado'])==='completado'?'selected':'' ?>>Completado</option>
                  <option value="en_espera" <?= strtolower($s['estado'])==='en_espera'?'selected':'' ?>>En espera</option>
                  <option value="cancelado" <?= strtolower($s['estado'])==='cancelado'?'selected':'' ?>>Cancelado</option>
                </select>
              </div>
              <button type="submit" class="btn btn-primary">Guardar</button>
            </form>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    <!-- Modales Clientes -->
    <?php foreach ($clientes as $c): ?>
    <div class="modal fade" id="verClienteModal<?= $c['id_cliente'] ?>" tabindex="-1" aria-labelledby="verClienteLabel<?= $c['id_cliente'] ?>" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="verClienteLabel<?= $c['id_cliente'] ?>">Detalles del Cliente</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <p><strong>ID:</strong> <?= $c['id_cliente'] ?></p>
            <p><strong>Nombre:</strong> <?= htmlspecialchars($c['nombre_completo']) ?></p>
            <p><strong>Correo:</strong> <?= htmlspecialchars($c['correo']) ?></p>
            <p><strong>Teléfono:</strong> <?= htmlspecialchars($c['telefono_celular']) ?></p>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    <!-- Modales Impresiones -->
    <?php foreach ($impresiones as $i): ?>
    <div class="modal fade" id="verImpresionModal<?= $i['id_orden'] ?>" tabindex="-1" aria-labelledby="verImpresionLabel<?= $i['id_orden'] ?>" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="verImpresionLabel<?= $i['id_orden'] ?>">Detalles de la Impresión</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <p><strong>ID:</strong> <?= $i['id_orden'] ?></p>
            <p><strong>Cliente:</strong> <?= htmlspecialchars($i['nombre_completo']) ?></p>
            <p><strong>Foto:</strong> <?= $i['foto_id'] ?></p>
            <p><strong>Cantidad:</strong> <?= $i['cantidad'] ?></p>
            <p><strong>Tamaño:</strong> <?= $i['tamanio_id'] ?></p>
            <p><strong>Precio Unitario:</strong> B/. <?= number_format($i['precio_unitario'],2) ?></p>
            <p><strong>Subtotal:</strong> B/. <?= number_format($i['subtotal'],2) ?></p>
            <p><strong>Estado:</strong> <?= htmlspecialchars($i['estado']) ?></p>
            <p><strong>Fecha Solicitud:</strong> <?= $i['fecha_solicitud'] ?></p>
          </div>
        </div>
      </div>
    </div>
    <!-- Modal Estado Impresión -->
    <div class="modal fade" id="estadoImpresionModal<?= $i['id_orden'] ?>" tabindex="-1" aria-labelledby="estadoImpresionLabel<?= $i['id_orden'] ?>" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="estadoImpresionLabel<?= $i['id_orden'] ?>">Cambiar Estado de la Impresión</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <form method="get" action="">
              <input type="hidden" name="id" value="<?= $i['id_orden'] ?>">
              <div class="mb-3">
                <label for="estado" class="form-label">Estado</label>
                <select class="form-select" name="accion" id="estado">
                  <option value="completada_impresion" <?= strtolower($i['estado'])==='completada'?'selected':'' ?>>Completada</option>
                  <option value="en_espera_impresion" <?= strtolower($i['estado'])==='en espera'?'selected':'' ?>>En espera</option>
                  <option value="cancelado_impresion" <?= strtolower($i['estado'])==='cancelado'?'selected':'' ?>>Cancelado</option>
                </select>
              </div>
              <button type="submit" class="btn btn-primary">Guardar</button>
            </form>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    <!-- Modal Crear Galería -->
    <?php foreach ($sessions as $s): ?>
    <?php if (strtolower($s['estado']) === 'completado'): ?>
    <div class="modal fade" id="crearGaleriaModal<?= $s['id_sesion'] ?>" tabindex="-1" aria-labelledby="crearGaleriaLabel<?= $s['id_sesion'] ?>" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="crearGaleriaLabel<?= $s['id_sesion'] ?>">Crear Galería - Sesión #<?= $s['id_sesion'] ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-info">
              <i class="bi bi-info-circle"></i>
              <strong>Cliente:</strong> <?= htmlspecialchars($s['nombre_completo']) ?><br>
              <strong>Correo:</strong> <?= htmlspecialchars($s['correo']) ?><br>
              <strong>Tipo de Sesión:</strong> <?= htmlspecialchars($s['tipo_sesion']) ?>
            </div>
            
            <form method="post" enctype="multipart/form-data">
              <input type="hidden" name="sesion_id" value="<?= $s['id_sesion'] ?>">
              
              <div class="mb-3">
                <label for="fotos" class="form-label">Seleccionar Fotos de la Sesión</label>
                <input type="file" class="form-control" name="fotos[]" id="fotos" multiple accept="image/*" required>
                <div class="form-text">Puedes seleccionar múltiples fotos. Formatos: JPG, PNG, GIF</div>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Acciones que se realizarán:</label>
                <ul class="list-group list-group-flush">
                  <li class="list-group-item"><i class="bi bi-check-circle text-success"></i> Crear galería privada para el cliente</li>
                  <li class="list-group-item"><i class="bi bi-check-circle text-success"></i> Subir las fotos seleccionadas</li>
                  <li class="list-group-item"><i class="bi bi-check-circle text-success"></i> Enviar correo con link de la galería</li>
                  <li class="list-group-item"><i class="bi bi-check-circle text-success"></i> El cliente podrá acceder con su correo</li>
                </ul>
              </div>
              
              <div class="d-grid gap-2">
                <button type="submit" name="subir_fotos" class="btn btn-success">
                  <i class="bi bi-upload"></i> Subir Fotos y Enviar Galería
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>
  </div>
    </div>
  </div>
</div>
<style>
  body {
    background: linear-gradient(120deg,#e3f2fd 0%,#f5f7fa 100%);
  }
  .sidebar .nav-link.active, .sidebar .nav-link:hover {
    background: #1976d2;
    color: #fff;
    border-radius: .4rem;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
    letter-spacing: .5px;
    transition: background .2s;
  }
  .sidebar {
    border-right: 1px solid #e0e0e0;
    background: linear-gradient(120deg,#263238 0%,#37474f 100%);
  }
  .sidebar .nav-link {
    color: #b0bec5;
    font-size: 1.08em;
    margin-bottom: .2em;
  }
  .sidebar .nav-link i {
    font-size: 1.2em;
  }
  .table thead th {
    background: #e3f2fd;
    color: #263238;
    font-weight: 700;
    border-bottom: 2px solid #90caf9;
    letter-spacing: .5px;
  }
  .table tbody tr {
    transition: box-shadow .2s;
  }
  .table tbody tr:hover {
    box-shadow: 0 2px 12px rgba(33,150,243,.08);
    background: #f1f8e9;
  }
  .badge-status {
    font-size: .95em;
    padding: .45em .9em;
    border-radius: .7em;
    box-shadow: 0 1px 4px rgba(0,0,0,.07);
    letter-spacing: .5px;
  }
  .btn-sm {
    font-size: .85em;
    font-weight: 500;
    border-radius: .3em;
    letter-spacing: .5px;
  }
  .modal-header {
    border-bottom: 1px solid #90caf9;
    background: linear-gradient(120deg,#1976d2 0%,#2196f3 100%);
  }
  .modal-footer {
    border-top: 1px solid #e0e0e0;
  }
  .modal-content {
    border-radius: .7em;
    box-shadow: 0 4px 24px rgba(33,150,243,.09);
  }
</style>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

