<?php
session_start();

// Incluir la clase Impresiones y el strategy
require_once __DIR__ . '/../clases/Impresiones.php';

// Definición de tipos de impresión (mantener en array)
$printTypes = [
    'standard' => (new Impresiones('standard'))->obtenerTipoImpresion(),
    'premium'  => (new Impresiones('premium'))->obtenerTipoImpresion(),
    'canvas'   => (new Impresiones('canvas'))->obtenerTipoImpresion(),
    'metal'    => (new Impresiones('metal'))->obtenerTipoImpresion(),
];

// Cargar tamaños desde la base de datos
require_once __DIR__ . '/../conexion/conexion.php';
$sizes = [];
// Usa PDO en vez de $conn->query()
$sql = "SELECT id, nombre, precio, popular FROM tamanos_impresion";
$stmt = $pdo->prepare($sql);
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $sizes[$row['id']] = [
        'name' => $row['nombre'],
        'price' => floatval($row['precio']),
        'popular' => $row['popular'] == 1
    ];
}

// Definición de tipos de impresión y tamaños
// Inicializar variables de sesión
if (!isset($_SESSION['order'])) {
    $_SESSION['order'] = [
        'items' => [],
        'total' => 0,
        'status' => 'selecting',
        'invoice' => '',
    ];
}

// Paso actual
$step = isset($_POST['step']) ? intval($_POST['step']) : 1;


// Incluir la clase Pago
require_once __DIR__ . '/../clases/Pago.php';

// Inicializar abonos en la sesión si no existen
if (!isset($_SESSION['order']['abonos'])) {
    // Eliminado: abonos
}

// Manejo de pasos y lógica
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['reset'])) {
        $_SESSION['order'] = [
            'items' => [],
            'total' => 0,
            'status' => 'selecting',
            'invoice' => '',
            'abonos' => [],
        ];
        unset($_SESSION['selectedType'], $_SESSION['selectedPhoto']);
        $step = 1;
    } elseif (isset($_POST['remove_item'])) {
        $idx = intval($_POST['remove_item']);
        if (isset($_SESSION['order']['items'][$idx])) {
            $_SESSION['order']['total'] -= $_SESSION['order']['items'][$idx]['price'];
            array_splice($_SESSION['order']['items'], $idx, 1);
        }
    } elseif (isset($_POST['process_payment'])) {
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
        if ($titular === '' || $numero === '' || $vencimiento === '' || $cvv === '') {
            $errores[] = 'Todos los campos de la tarjeta son obligatorios.';
        }
        if (empty($errores)) {
            $_SESSION['order']['status'] = 'completed';
            $_SESSION['order']['invoice'] = 'INV-' . time();
            $_SESSION['order']['pago'] = [
                'titular' => $titular,
                'numero' => substr($numero, -4),
                'vencimiento' => $vencimiento,
                'cvv' => $cvv,
                'fecha' => date('d/m/Y H:i'),
            ];
        } else {
            $_SESSION['order']['error_pago'] = implode('<br>', $errores);
        }
    } elseif ($step === 1 && isset($_POST['type'])) {
        $_SESSION['selectedType'] = $_POST['type'];
        $step = 2;
    } elseif ($step === 2) {
        if (isset($_POST['back_to_step1'])) {
            unset($_SESSION['selectedType'], $_SESSION['selectedPhoto']);
            $step = 1;
        } elseif (isset($_POST['continue_step2'])) {
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../galeria/';
                $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $filename = uniqid('img_') . '.' . $ext;
                move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $filename);
                $_SESSION['selectedPhoto'] = $filename;
                $step = 3;
            } else {
                // Si no se subió foto, permanecer en el paso 2
                $step = 2;
            }
        }
    } elseif ($step === 3) {
        if (isset($_POST['back_to_step2'])) {
            unset($_SESSION['selectedPhoto']);
            $step = 2;
        } elseif (isset($_POST['add_to_order']) && isset($_POST['size'], $_POST['quantity'])) {
            $selectedType = $_SESSION['selectedType'] ?? '';
            $selectedPhoto = $_SESSION['selectedPhoto'] ?? '';
            $selectedSize = $_POST['size'];
            $quantity = max(1, intval($_POST['quantity']));
            $sizePrice = $sizes[$selectedSize]['price'] ?? 0;
            // Usar el strategy para el multiplicador
            $impresion = new Impresiones($selectedType);
            $typeMultiplier = $impresion->obtenerTipoImpresion()['multiplier'];
            $total = $sizePrice * $typeMultiplier * $quantity;
            $_SESSION['order']['items'][] = [
                'type' => $selectedType,
                'photo' => $selectedPhoto,
                'size' => $selectedSize,
                'quantity' => $quantity,
                'price' => $total,
            ];
            $_SESSION['order']['total'] += $total;
            unset($_SESSION['selectedType'], $_SESSION['selectedPhoto']);
            $step = 1;
        }
    }
}

$order = $_SESSION['order'];

function printTypeCard($id, $type, $selectedType) {
    $selected = $selectedType === $id ? 'border-3 border-primary shadow-lg' : 'border-2 border-secondary';
    echo "<div class='col-md-6 mb-3'>
        <button type='submit' name='type' value='$id' class='card $selected w-100 h-100 text-center p-4' style='cursor:pointer;'>
            <div class='mb-3'><span class='icon-circle {$type['color']}'><i class='bi {$type['icon']} text-white' style='font-size:2rem;'></i></span></div>
            <div class='fw-bold'>{$type['name']}</div>
            <div class='small text-muted'>{$type['description']}</div>
            ".($selectedType === $id ? "<div class='mt-2 text-success'><i class='bi bi-check-circle'></i> Seleccionado</div>" : '')."
        </button>
    </div>";
}

function printOrderSummary($order, $printTypes, $sizes) {
    if (empty($order['items'])) {
        echo "<div class='text-center py-5 text-muted'>
            <i class='bi bi-cart4' style='font-size:2rem;'></i><br>Tu carrito está vacío<br><small>Agrega artículos para ver el resumen</small>
        </div>";
    } else {
        $allowDelete = ($order['status'] !== 'completed');
        foreach ($order['items'] as $idx => $item) {
            $type = $printTypes[$item['type']]['name'] ?? '';
            $sizeName = isset($sizes[$item['size']]) ? $sizes[$item['size']]['name'] : 'Tamaño desconocido';
            echo "<div class='bg-light rounded p-2 mb-2 border-start border-primary border-4'>
                <div class='d-flex justify-content-between align-items-center'>
                    <div><b>$type</b> <br><small>$sizeName</small></div>
                    <div class='text-end'>
                        <span class='badge bg-primary'>{$item['quantity']}</span><br>
                        <span class='fw-bold text-primary'>$".number_format($item['price'],2)."</span>";
            if ($allowDelete) {
                echo "<br><form method='post' style='display:inline;'>
                            <input type='hidden' name='remove_item' value='$idx'>
                            <button type='submit' class='btn btn-sm btn-danger mt-1' title='Eliminar'><i class='bi bi-trash'></i></button>
                        </form>";
            }
            echo "</div>
                </div>
            </div>";
        }
        echo "<div class='bg-primary text-white rounded p-3 mt-3 d-flex justify-content-between'>
            <span>Total:</span><span class='fw-bold'>$".number_format($order['total'],2)."</span>
        </div>";
        if ($order['status'] !== 'completed') {
            echo "<form method='post' class='mt-3'>
                <div class='mb-2'><label>Titular de la tarjeta:</label>
                    <input type='text' name='titular' class='form-control' pattern='[A-Za-zÁÉÍÓÚáéíóúÑñ ]{5,}' title='Solo letras y espacios, mínimo 5 caracteres' required></div>
                <div class='mb-2'><label>Número de tarjeta:</label>
                    <input type='text' name='numero' id='numero-tarjeta' class='form-control' maxlength='19' inputmode='numeric' title='16 dígitos' required />
                </div>
                <div class='mb-2'><label>Vencimiento (MM/AA):</label>
                    <input type='text' name='vencimiento' id='vencimiento-tarjeta' class='form-control' maxlength='5' title='MM/AA' required />
                </div>
                <div class='mb-2'><label>CVV:</label>
                    <input type='text' name='cvv' class='form-control' maxlength='4' pattern='\d{3,4}' title='3 o 4 dígitos' required></div>
                <button type='submit' name='process_payment' class='btn btn-success w-100'>Pagar y Generar Factura</button>
            </form>";
            if (!empty($order['error_pago'])) {
                echo "<div class='alert alert-danger mt-2 text-center'>" . $order['error_pago'] . "</div>";
                unset($_SESSION['order']['error_pago']);
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Impresiones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .icon-circle { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 2rem; }
        .bg-purple { background: #9c27b0 !important; }
    </style>
</head>
<body class="bg-light">
    <div class="bg-primary text-white py-4 mb-4">
        <div class="container text-center">
            <h1 class="mb-1">📸 Sistema de Impresiones</h1>
            <div>Convierte tus recuerdos en obras de arte</div>
        </div>
    </div>
    <div class="container mb-5">
        <div class="row g-4">
            <div class="col-lg-8">
                <?php if ($order['status'] === 'completed'): ?>
                    <div class="card shadow-lg mb-4">
                        <div class="card-header bg-success text-white text-center">
                            <h4 class="mb-0"><i class="bi bi-file-earmark-text"></i> Factura Generada Exitosamente</h4>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <b>Número de Factura:</b><br>
                                    <span class="fs-4 text-primary"><?php echo $order['invoice']; ?></span>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <b>Fecha:</b><br>
                                    <span><?php echo $order['pago']['fecha'] ?? date('d/m/Y'); ?></span>
                                </div>
                            </div>
                            <div class="mb-3"><b>Titular:</b> <?php echo $order['pago']['titular'] ?? ''; ?></div>
                            <div class="mb-3"><b>Tarjeta:</b> **** **** **** <?php echo $order['pago']['numero'] ?? ''; ?></div>
                            <div class="mb-3"><b>Vencimiento:</b> <?php echo $order['pago']['vencimiento'] ?? ''; ?></div>
                            <div class="mb-3"><b>CVV:</b> <?php echo $order['pago']['cvv'] ?? ''; ?></div>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-primary">
                                        <tr><th>Tipo</th><th>Tamaño</th><th>Cantidad</th><th>Precio</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($order['items'] as $item): ?>
                                            <tr>
                                                <td><?php echo $printTypes[$item['type']]['name'] ?? ''; ?></td>
                                                <td><?php echo isset($sizes[$item['size']]) ? $sizes[$item['size']]['name'] : 'Tamaño desconocido'; ?></td>
                                                <td><?php echo $item['quantity']; ?></td>
                                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="bg-primary text-white rounded p-3 mt-3 d-flex justify-content-between">
                                <span>Total Pagado:</span><span class="fw-bold fs-4">$<?php echo number_format($order['total'], 2); ?></span>
                            </div>
                            <form method="post" class="mt-4 text-center">
                                <button name="reset" class="btn btn-lg btn-primary">Nueva Solicitud</button>
                            </form>
                            <a href="descargar_factura.php?factura=<?php echo $order['invoice']; ?>" class="btn btn-success w-100 mt-3" target="_blank"><i class="bi bi-download"></i> Descargar Factura</a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php if ($step === 1): ?>
                        <form method="post">
                            <input type="hidden" name="step" value="1">
                            <div class="card shadow mb-4">
                                <div class="card-header bg-primary text-white"><b>Paso 1: Selecciona el Tipo de Impresión</b></div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php foreach ($printTypes as $id => $type) printTypeCard($id, $type, $_SESSION['selectedType'] ?? ''); ?>
                                    </div>
                                    <div class="text-end mt-3">
                                        <button class="btn btn-primary" type="submit" <?php if (empty($_SESSION['selectedType'])) echo 'disabled'; ?>>Continuar &raquo;</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    <?php elseif ($step === 2): ?>
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="step" value="2">
                            <div class="card shadow mb-4">
                                <div class="card-header bg-primary text-white"><b>Paso 2: Sube tu Foto</b></div>
                                <div class="card-body text-center">
<input type="file" name="photo" accept="image/*" class="form-control mb-3">
                                    <div class="text-end">
                                        <button class="btn btn-secondary" type="submit" name="back_to_step1">&laquo; Atrás</button>
                                        <button class="btn btn-primary" type="submit" name="continue_step2">Continuar &raquo;</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    <?php elseif ($step === 3): ?>
                        <form method="post">
                            <input type="hidden" name="step" value="3">
                            <div class="card shadow mb-4">
                                <div class="card-header bg-primary text-white"><b>Paso 3: Selecciona Tamaño y Cantidad</b></div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Tamaño de Impresión:</label>
                                            <select name="size" class="form-select" required>
                                                <option value="">Selecciona un tamaño</option>
                                                <?php foreach ($sizes as $id => $size): ?>
                                                    <option value="<?php echo $id; ?>"><?php echo $size['name']; ?> - $<?php echo number_format($size['price'],2); ?><?php if ($size['popular']) echo ' ⭐ Popular'; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Cantidad:</label>
                                            <input type="number" name="quantity" min="1" max="100" value="1" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button class="btn btn-secondary" type="submit" name="back_to_step2">&laquo; Atrás</button>
                                        <button class="btn btn-success" type="submit" name="add_to_order">Agregar al Pedido</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="col-lg-4">
                <div class="card shadow sticky-top mb-3">
                    <div class="card-header bg-success text-white"><b>Resumen del Pedido</b></div>
                    <div class="card-body">
                        <?php printOrderSummary($order, $printTypes, $sizes); ?>
                    </div>
                </div>
                <!-- Eliminada sección de abonos -->
                <a href="P_Menu_principal.php" class="btn btn-secondary w-100 mt-3" onclick="sessionStorage.clear();"><i class="bi bi-arrow-left"></i> Volver al Menú Principal</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php
