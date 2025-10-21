<?php
session_start();
if (!isset($_SESSION['rol']) || strtolower($_SESSION['rol']) !== 'admin') {
    header('Location: login.php'); exit;
}

require_once __DIR__ . '/../conexion/conexion.php';
$pdo = DatabaseConnection::getInstance()->getConnection();

if (!isset($_GET['comentario_id'])) {
    die('ID de comentario requerido');
}

$comentario_id = (int)$_GET['comentario_id'];

// Obtener información del comentario y las fotos seleccionadas
$stmt = $pdo->prepare("
    SELECT ce.*, c.nombre_completo, c.correo, s.tipo_sesion 
    FROM comentarios_edicion ce
    JOIN clientes c ON ce.cliente_id = c.id_cliente
    JOIN galerias g ON ce.galeria_id = g.id_galeria
    JOIN sesiones s ON g.sesion_id = s.id_sesion
    WHERE ce.id_comentario = ?
");
$stmt->execute([$comentario_id]);
$comentario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$comentario) {
    die('Comentario no encontrado');
}

$fotos_ids = json_decode($comentario['fotos_seleccionadas'], true);

if (empty($fotos_ids)) {
    die('No hay fotos seleccionadas');
}

// Obtener información de las fotos
$placeholders = str_repeat('?,', count($fotos_ids) - 1) . '?';
$stmt = $pdo->prepare("SELECT id_foto, filename, uploaded_at FROM fotos WHERE id_foto IN ($placeholders)");
$stmt->execute($fotos_ids);
$fotos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Crear archivo ZIP
$zipname = 'fotos_seleccionadas_' . $comentario_id . '_' . date('Y-m-d_H-i-s') . '.zip';
$zip = new ZipArchive();
$zip->open($zipname, ZipArchive::CREATE);

// Agregar archivo de información
$info_content = "INFORMACIÓN DE LA SOLICITUD DE EDICIÓN\n";
$info_content .= "=====================================\n\n";
$info_content .= "Cliente: " . $comentario['nombre_completo'] . "\n";
$info_content .= "Correo: " . $comentario['correo'] . "\n";
$info_content .= "Tipo de Sesión: " . $comentario['tipo_sesion'] . "\n";
$info_content .= "Fecha de Solicitud: " . date('d/m/Y H:i', strtotime($comentario['fecha_creacion'])) . "\n";
$info_content .= "Estado: " . $comentario['estado'] . "\n\n";
$info_content .= "COMENTARIOS DEL CLIENTE:\n";
$info_content .= "------------------------\n";
$info_content .= $comentario['comentarios'] . "\n\n";
$info_content .= "FOTOS SELECCIONADAS:\n";
$info_content .= "--------------------\n";

foreach ($fotos as $foto) {
    $info_content .= "- ID: " . $foto['id_foto'] . " | Archivo: " . $foto['filename'] . " | Fecha: " . date('d/m/Y H:i', strtotime($foto['uploaded_at'])) . "\n";
}

$zip->addFromString('informacion_solicitud.txt', $info_content);

// Agregar las fotos al ZIP
$galeria_dir = __DIR__ . '/../galeria/';
foreach ($fotos as $foto) {
    $filepath = $galeria_dir . $foto['filename'];
    if (file_exists($filepath)) {
        $zip->addFile($filepath, 'fotos/' . $foto['filename']);
    }
}

$zip->close();

// Enviar el archivo ZIP
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipname . '"');
header('Content-Length: ' . filesize($zipname));
header('Pragma: no-cache');
header('Expires: 0');

readfile($zipname);
unlink($zipname); // Eliminar el archivo temporal
exit;
?> 