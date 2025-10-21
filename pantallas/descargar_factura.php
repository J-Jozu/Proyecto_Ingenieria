<?php
// descargar_factura.php
// Genera y descarga la factura en PDF usando FPDF
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../clases/Impresiones.php';
require_once __DIR__ . '/../clases/Pago.php';

session_start();

// Verifica que la factura exista en la sesión
$factura = $_GET['factura'] ?? '';
$order = $_SESSION['order'] ?? null;
if (!$order || $order['invoice'] !== $factura || $order['status'] !== 'completed') {
    die('Factura no encontrada o no válida.');
}

// Incluye FPDF
require_once(__DIR__ . '/../fpdf/fpdf.php');

class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial','B',16);
        $this->SetTextColor(40, 167, 69); // Verde Bootstrap
        $this->Cell(0,10,utf8_decode('Estudio Fotográfico - Factura'),0,1,'C');
        $this->SetTextColor(0,0,0);
        $this->SetFont('Arial','',10);
        $this->Cell(0,8,utf8_decode('Convierte tus recuerdos en obras de arte'),0,1,'C');
        $this->Ln(2);
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial','',12);

$pdf->Cell(0,8,utf8_decode('Número de Factura: ') . $order['invoice'],0,1);
$pdf->Cell(0,8,utf8_decode('Fecha: ') . ($order['pago']['fecha'] ?? date('d/m/Y')),0,1);
$pdf->Cell(0,8,utf8_decode('Titular: ') . utf8_decode($order['pago']['titular'] ?? ''),0,1);
$pdf->Cell(0,8,utf8_decode('Tarjeta: **** **** **** ') . ($order['pago']['numero'] ?? ''),0,1);
$pdf->Cell(0,8,utf8_decode('Vencimiento: ') . ($order['pago']['vencimiento'] ?? ''),0,1);
$pdf->Ln(5);

// Tabla de artículos
$pdf->SetFont('Arial','B',12);
$pdf->SetFillColor(13,110,253); // Azul Bootstrap
$pdf->SetTextColor(255,255,255);
$pdf->Cell(50,8,utf8_decode('Tipo'),1,0,'C',true);
$pdf->Cell(50,8,utf8_decode('Tamaño'),1,0,'C',true);
$pdf->Cell(30,8,utf8_decode('Cantidad'),1,0,'C',true);
$pdf->Cell(40,8,utf8_decode('Precio'),1,1,'C',true);
$pdf->SetFont('Arial','',12);
$pdf->SetTextColor(0,0,0);

// Cargar tamaños
$sizes = [];
$sql = "SELECT id, nombre FROM tamanos_impresion";
$stmt = $pdo->prepare($sql);
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $sizes[$row['id']] = utf8_decode($row['nombre']);
}

foreach ($order['items'] as $item) {
    $type = utf8_decode((new Impresiones($item['type']))->obtenerTipoImpresion()['name'] ?? '');
    $sizeName = $sizes[$item['size']] ?? utf8_decode('Tamaño desconocido');
    $pdf->Cell(50,8,$type,1);
    $pdf->Cell(50,8,$sizeName,1);
    $pdf->Cell(30,8,$item['quantity'],1);
    $pdf->Cell(40,8,'$'.number_format($item['price'],2),1,1);
}
$pdf->Ln(5);
$pdf->SetFont('Arial','B',14);
$pdf->SetTextColor(40, 167, 69);
$pdf->Cell(0,10,utf8_decode('Total Pagado: $').number_format($order['total'],2),0,1,'R');

// Descargar PDF
$pdf->Output('D', 'Factura_'.$order['invoice'].'.pdf');
exit;
