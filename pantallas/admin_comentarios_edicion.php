<?php
session_start();
if (!isset($_SESSION['rol']) || strtolower($_SESSION['rol']) !== 'admin') {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../conexion/conexion.php';
$pdo = DatabaseConnection::getInstance()->getConnection();

// Procesar cambios de estado
if (isset($_GET['accion'], $_GET['id'])) {
    $id = (int)$_GET['id'];
    switch ($_GET['accion']) {
        case 'en_proceso':
            $pdo->prepare("UPDATE comentarios_edicion SET estado='en_proceso' WHERE id_comentario=?")->execute([$id]);
            break;
        case 'completado':
            $pdo->prepare("UPDATE comentarios_edicion SET estado='completado' WHERE id_comentario=?")->execute([$id]);
            break;
        case 'pendiente':
            $pdo->prepare("UPDATE comentarios_edicion SET estado='pendiente' WHERE id_comentario=?")->execute([$id]);
            break;
    }
    header('Location: admin_comentarios_edicion.php'); exit;
}

// Cargar comentarios de edición
$comentarios = $pdo->query(
    "SELECT ce.*, c.nombre_completo, c.correo, g.token, s.tipo_sesion 
     FROM comentarios_edicion ce
     JOIN clientes c ON ce.cliente_id = c.id_cliente
     JOIN galerias g ON ce.galeria_id = g.id_galeria
     JOIN sesiones s ON g.sesion_id = s.id_sesion
     ORDER BY ce.fecha_creacion DESC"
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comentarios de Edición - PhotoStudio Admin</title>
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
        .badge-status {
            font-size: 0.9em;
            padding: 0.5em 1em;
        }
        .btn-sm {
            font-size: 0.8em;
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
                        <h3 class="mb-0"><i class="bi bi-pencil-square"></i> Comentarios de Edición</h3>
                        <p class="mb-0">Gestionar solicitudes de edición de fotos</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Comentarios -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body p-4">
                        <?php if (empty($comentarios)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-chat-dots" style="font-size: 4rem; color: #ccc;"></i>
                                <h5 class="mt-3 text-muted">No hay comentarios de edición</h5>
                                <p class="text-muted">Los clientes aparecerán aquí cuando soliciten ediciones.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-primary">
                                        <tr>
                                            <th>ID</th>
                                            <th>Cliente</th>
                                            <th>Sesión</th>
                                            <th>Fotos Seleccionadas</th>
                                            <th>Comentarios</th>
                                            <th>Estado</th>
                                            <th>Fecha</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($comentarios as $comentario): ?>
                                            <tr>
                                                <td><?= $comentario['id_comentario'] ?></td>
                                                <td>
                                                    <strong><?= htmlspecialchars($comentario['nombre_completo']) ?></strong><br>
                                                    <small class="text-muted"><?= htmlspecialchars($comentario['correo']) ?></small>
                                                </td>
                                                <td><?= htmlspecialchars($comentario['tipo_sesion']) ?></td>
                                                <td>
                                                    <?php 
                                                    $fotos = json_decode($comentario['fotos_seleccionadas'], true);
                                                    echo count($fotos) . ' foto(s)';
                                                    
                                                    // Mostrar miniaturas de las primeras 3 fotos
                                                    if (!empty($fotos)) {
                                                        echo '<div class="d-flex gap-1 mt-1">';
                                                        $contador = 0;
                                                        foreach ($fotos as $foto_id) {
                                                            if ($contador >= 3) break; // Solo mostrar 3
                                                            
                                                            $stmt = $pdo->prepare("SELECT filename FROM fotos WHERE id_foto = ?");
                                                            $stmt->execute([$foto_id]);
                                                            $foto_info = $stmt->fetch(PDO::FETCH_ASSOC);
                                                            
                                                            if ($foto_info) {
                                                                echo '<img src="../galeria/' . htmlspecialchars($foto_info['filename']) . '" 
                                                                           alt="Miniatura" 
                                                                           style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">';
                                                            }
                                                            $contador++;
                                                        }
                                                        if (count($fotos) > 3) {
                                                            echo '<span class="badge bg-secondary">+' . (count($fotos) - 3) . '</span>';
                                                        }
                                                        echo '</div>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-info" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#comentarioModal<?= $comentario['id_comentario'] ?>">
                                                        <i class="bi bi-eye"></i> Ver
                                                    </button>
                                                </td>
                                                <td>
                                                    <?php
                                                    $estado = strtolower($comentario['estado']);
                                                    if ($estado === 'pendiente') {
                                                        echo '<span class="badge badge-status bg-warning text-dark"><i class="bi bi-clock"></i> Pendiente</span>';
                                                    } elseif ($estado === 'en_proceso') {
                                                        echo '<span class="badge badge-status bg-info"><i class="bi bi-gear"></i> En Proceso</span>';
                                                    } elseif ($estado === 'completado') {
                                                        echo '<span class="badge badge-status bg-success"><i class="bi bi-check-circle"></i> Completado</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td><?= date('d/m/Y H:i', strtotime($comentario['fecha_creacion'])) ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button class="btn btn-sm btn-outline-warning" 
                                                                data-bs-toggle="dropdown">
                                                            <i class="bi bi-arrow-repeat"></i> Estado
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li><a class="dropdown-item" href="?accion=pendiente&id=<?= $comentario['id_comentario'] ?>">
                                                                <i class="bi bi-clock"></i> Pendiente
                                                            </a></li>
                                                            <li><a class="dropdown-item" href="?accion=en_proceso&id=<?= $comentario['id_comentario'] ?>">
                                                                <i class="bi bi-gear"></i> En Proceso
                                                            </a></li>
                                                            <li><a class="dropdown-item" href="?accion=completado&id=<?= $comentario['id_comentario'] ?>">
                                                                <i class="bi bi-check-circle"></i> Completado
                                                            </a></li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="row mt-4">
            <div class="col-12 text-center">
                <a href="admin_galeria.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Volver al Panel Admin
                </a>
            </div>
        </div>
    </div>

    <!-- Modales para ver comentarios -->
    <?php foreach ($comentarios as $comentario): ?>
    <div class="modal fade" id="comentarioModal<?= $comentario['id_comentario'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-chat-dots"></i> Comentarios de Edición
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Información del Cliente</h6>
                            <p><strong>Nombre:</strong> <?= htmlspecialchars($comentario['nombre_completo']) ?></p>
                            <p><strong>Correo:</strong> <?= htmlspecialchars($comentario['correo']) ?></p>
                            <p><strong>Sesión:</strong> <?= htmlspecialchars($comentario['tipo_sesion']) ?></p>
                            <p><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($comentario['fecha_creacion'])) ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Estado</h6>
                            <?php
                            $estado = strtolower($comentario['estado']);
                            if ($estado === 'pendiente') {
                                echo '<span class="badge bg-warning text-dark">Pendiente</span>';
                            } elseif ($estado === 'en_proceso') {
                                echo '<span class="badge bg-info">En Proceso</span>';
                            } elseif ($estado === 'completado') {
                                echo '<span class="badge bg-success">Completado</span>';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <h6>Fotos Seleccionadas</h6>
                        <?php 
                        $fotos = json_decode($comentario['fotos_seleccionadas'], true);
                        echo '<p><strong>Cantidad:</strong> ' . count($fotos) . ' foto(s)</p>';
                        
                        if (!empty($fotos)) {
                            echo '<div class="row g-2 mt-2">';
                            foreach ($fotos as $foto_id) {
                                // Obtener información de la foto
                                $stmt = $pdo->prepare("SELECT filename, uploaded_at FROM fotos WHERE id_foto = ?");
                                $stmt->execute([$foto_id]);
                                $foto_info = $stmt->fetch(PDO::FETCH_ASSOC);
                                
                                if ($foto_info) {
                                    echo '<div class="col-md-4 col-lg-3">';
                                    echo '<div class="card h-100">';
                                    echo '<img src="../galeria/' . htmlspecialchars($foto_info['filename']) . '" 
                                               class="card-img-top" alt="Foto seleccionada" 
                                               style="height: 150px; object-fit: cover;">';
                                    echo '<div class="card-body p-2">';
                                    echo '<small class="text-muted">ID: ' . $foto_id . '</small><br>';
                                    echo '<small class="text-muted">' . date('d/m/Y H:i', strtotime($foto_info['uploaded_at'])) . '</small>';
                                    echo '</div>';
                                    echo '</div>';
                                    echo '</div>';
                                }
                            }
                            echo '</div>';
                        }
                        ?>
                    </div>
                    
                    <div class="mb-3">
                        <h6>Comentarios del Cliente</h6>
                        <div class="alert alert-info">
                            <?= nl2br(htmlspecialchars($comentario['comentarios'])) ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" onclick="descargarFotosSeleccionadas(<?= $comentario['id_comentario'] ?>)">
                        <i class="bi bi-download"></i> Descargar Fotos Seleccionadas
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function descargarFotosSeleccionadas(comentarioId) {
            // Crear un enlace temporal para descargar las fotos
            const link = document.createElement('a');
            link.href = 'descargar_fotos_seleccionadas.php?comentario_id=' + comentarioId;
            link.download = 'fotos_seleccionadas_' + comentarioId + '.zip';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html> 