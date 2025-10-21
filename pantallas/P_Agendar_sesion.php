<?php
// Iniciar la sesión para poder usar $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Genera un número de factura único para cada pago de sesión
function generarNumeroFactura() {
    // Ejemplo: FCT-YYYYMMDD-HHMMSS-aleatorio
    $fecha = date('Ymd-His');
    $rand = rand(1000,9999);
    return 'FCT-' . $fecha . '-' . $rand;
}
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../clases/Sesiones.php';
require_once __DIR__ . '/../clases/Cliente.php';
require_once __DIR__ . '/../clases/Pago.php';

/* ...existing code... */

// PRG: Si se envió el formulario de confirmación, guardar y redirigir con success=1
if (isset($_POST['paso'], $_POST['confirmar_pago']) && $_POST['paso'] === 'confirmacion' && isset($_SESSION['reserva_temp'])) {
    $data = $_SESSION['reserva_temp'];
    // Instanciar Cliente con el nuevo constructor (sin dirección)
    $cliente = new Cliente(
        $data['cedula'],
        $data['nombre'] ?? '',
        $data['telefono'] ?? '',
        $data['email'] ?? ''
    );

    // Transacción para coherencia
    $pdo->beginTransaction();
    try {
        // Registrar o leer cliente
        $idCliente = $cliente->obtenerORegistrarCliente();

        // Obtener la estrategia para conseguir los datos de la sesión
        $sessionManager = SessionManager::getInstance();
        $strategy = $sessionManager->getStrategy($data['tipo']);

        // Preparar datos para la sesión (ajustando nombres)
        $datosSesion = [
            'tipo_sesion'           => $data['tipo'],
            'descripcion_sesion'    => $strategy->getDescription(),
            'duracion_sesion'       => $strategy->getDuration(),
            'lugar_sesion'          => $data['lugar_sesion'],
            'direccion_sesion'      => $data['lugar'] ?? '',
            'estilo_fotografia'     => $data['estilo_fotografia'],
            'servicios_adicionales' => is_array($data['servicios_adicionales']) ? implode(',', $data['servicios_adicionales']) : $data['servicios_adicionales'],
            'otros_datos'           => $data['otros_datos'],
            'total_pagar'           => $data['total'],
            'abono_inicial'         => $data['total'] * 0.3,
            'fecha_sesion'          => $data['fecha'],
            'hora_sesion'           => $data['hora']
        ];

        // Crear la sesión
        $idSesion = $cliente->reservarSesion($datosSesion, $idCliente);


        // Nuevo flujo de pago usando la factory
        $monto         = (float) $datosSesion['abono_inicial'];
        $metodo        = $data['metodo_pago'] ?? 'tarjeta';
        $pago          = Pago::seleccionarMetodoPago($metodo, $monto, $data);
        $facturaData   = $pago->obtenerFactura();
        $numeroFactura = generarNumeroFactura();
        $pago->registrarPagoSesion($idSesion, $numeroFactura);

        // Si es Yappy, redirige antes de mostrar confirmación
        if ($pago instanceof Yappy) {
            $pago->redirigir();
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }

    // Limpiar datos temporales de la sesión
    unset($_SESSION['reserva_temp']);

    // Redirigir para evitar reenvío y mostrar mensaje
    header('Location: P_Agendar_sesion.php?success=1&factura=' . urlencode($numeroFactura));
    exit;
}

// Guardar cliente si se envió el formulario de detalles personales (paso 3)
$conn = DatabaseConnection::getInstance()->getConnection();

// Procesar el formulario del paso 3 (antes de calcular $paso)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nombre'], $_POST['cedula']) && !isset($_POST['confirmar_pago'])) {
    if (!isset($_GET['tipo'], $_GET['fecha'], $_GET['hora'])) {
        // Si faltan parámetros requeridos, volver al paso 1
        header('Location: P_Agendar_sesion.php');
        exit;
    }

    $sessionType = $_GET['tipo'];
    $sessionManager = SessionManager::getInstance();
    $strategy = $sessionManager->getStrategy($sessionType);
    
    // Calcular precios
    $basePrice = intval(str_replace(['$', ','], '', $strategy->getPrice()));
    $total = $basePrice;
    
    // Sumar servicios adicionales
    if (isset($_POST['servicios_adicionales'])) {
        foreach ($_POST['servicios_adicionales'] as $s) {
            if ($s == 'maquillaje') $total += 50;
            if ($s == 'vestuario') $total += 30;
            if ($s == 'edicion') $total += 40;
            if ($s == 'impresiones') $total += 25;
        }
    }
    
    $restante = $total * 0.7; // 70% restante después del abono inicial
    $abono = $total * 0.3;    // 30% de abono inicial

    // Guardar todos los datos en la sesión
    $_SESSION['reserva_temp'] = [
        // Datos del cliente
        'cedula' => $_POST['cedula'],
        'nombre' => $_POST['nombre'],
        'telefono' => $_POST['telefono'],
        'email' => $_POST['email'],
        // dirección ya no se guarda

        // Datos de la sesión fotográfica
        'tipo' => $_GET['tipo'],
        'fecha' => $_GET['fecha'],
        'hora' => $_GET['hora'],

        // Campos de la estrategia
        'descripcion_sesion' => $strategy->getDescription(),
        'duracion' => $strategy->getDuration(),

        // Personalización
        'lugar_sesion' => $_POST['lugar_sesion'] ?? '',
        'estilo_fotografia' => $_POST['estilo_fotografia'] ?? '',
        'lugar' => $_POST['lugar'] ?? '', // dirección_sesion
        'otros_datos' => $_POST['otros_datos'] ?? '',
        'servicios_adicionales' => $_POST['servicios_adicionales'] ?? [],

        // Campos calculados
        'total' => $total,
        'restante' => $restante
    ];

    // Redirigir al paso de pago
    header('Location: P_Agendar_sesion.php?tipo=' . urlencode($_GET['tipo']) . 
           '&fecha=' . urlencode($_GET['fecha']) . 
           '&hora=' . urlencode($_GET['hora']) . 
           '&paso=pago');
    exit;
}

// Las funciones getSessionStrategy y getAllSessionStrategies ahora están en Sesiones.php
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reserva tu Sesión Fotográfica</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* Tus estilos existentes para el stepper, tarjetas, etc. */
        .stepper { display: flex; justify-content: center; align-items: center; margin-bottom: 32px; gap: 10px; }
        .stepper .step { font-weight: 500; font-size: 16px; color: #bdbdbd; }
        .stepper .step.active { color: #1976d2; text-decoration: underline; }
        .stepper .arrow { color: #bdbdbd; font-size: 20px; }
        .card-type { cursor: pointer; border: 2px solid transparent; transition: border 0.2s, background 0.2s; }
        .card-type.selected, .card-type:hover { border: 2px solid #1976d2; background: #e3f0fc; }
        .card-type input[type="radio"] { margin-right: 8px; }
        .submit-btn { min-width: 180px; font-weight: 600; }

        /* Estilos del modern-stepper */
        .modern-stepper { display: flex; justify-content: center; align-items: center; gap: 32px; margin-bottom: 32px; }
        .modern-step { display: flex; flex-direction: column; align-items: center; color: #bdbdbd; font-weight: 500; font-size: 15px; min-width: 90px; }
        .modern-step .circle { width: 36px; height: 36px; border-radius: 50%; background: #f5f6fa; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 4px; border: 2px solid #bdbdbd; transition: all 0.2s; }
        .modern-step.active .circle { background: #1976d2; color: #fff; border-color: #1976d2; }
        .modern-step.completed .circle { background: #e3f0fc; color: #1976d2; border-color: #1976d2; }
        .modern-step.active, .modern-step.completed { color: #1976d2; }
        .modern-stepper .modern-line { flex: 1; height: 2px; background: #e0e0e0; margin: 0 0.5rem; min-width: 24px; }
        @media (max-width: 600px) {
            .modern-stepper { gap: 8px; }
            .modern-step { min-width: 60px; font-size: 12px; }
            .modern-step .circle { width: 28px; height: 28px; font-size: 15px; }
        }

        /* Estilos de validación Bootstrap */
        .was-validated .form-control:invalid { border-color: #dc3545; }
        .was-validated .form-control:valid   { border-color: #28a745; }

        /* Overlay flotante de confirmación */
        #overlay.hidden { display: none; }
        #overlay {
          position: fixed; top: 0; left: 0;
          width: 100%; height: 100%;
          background: rgba(0,0,0,0.5);
          display: flex; align-items: center; justify-content: center;
          z-index: 1000;
        }
        #popup {
          background: #fff; padding: 1.5rem; border-radius: .5rem;
          max-width: 400px; width: 90%;
          position: relative;
          box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        #closePopup {
          position: absolute; top: .5rem; right: .5rem;
          background: transparent; border: none; font-size: 1.5rem;
          cursor: pointer;
        }
    </style>
    <script>
        // Paso 1: seleccionar tarjeta
        function selectCard(card, radioId) {
            document.querySelectorAll('.card-type').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            document.getElementById(radioId).checked = true;
        }

        // Manejar visibilidad de campos de ubicación
        document.addEventListener('DOMContentLoaded', function() {
            const lugarSelect = document.getElementById('lugar_sesion');
            const direccionGroup = document.getElementById('direccionGroup');
            const ubicacionExteriorGroup = document.getElementById('ubicacionExteriorGroup');
            const lugarDomicilioInput = document.getElementById('lugar_domicilio');
            const lugarExteriorInput = document.getElementById('lugar_exterior');

            function actualizarCampos() {
                const selectedValue = lugarSelect.value;
                
                // Ocultar y deshabilitar todos los campos primero
                direccionGroup.style.display = 'none';
                ubicacionExteriorGroup.style.display = 'none';
                if (lugarDomicilioInput) {
                    lugarDomicilioInput.required = false;
                    if (selectedValue !== 'domicilio') lugarDomicilioInput.value = '';
                }
                if (lugarExteriorInput) {
                    lugarExteriorInput.required = false;
                    if (selectedValue !== 'exterior') lugarExteriorInput.value = '';
                }
                
                // Mostrar y habilitar el campo correspondiente según la selección
                if (selectedValue === 'domicilio') {
                    direccionGroup.style.display = 'block';
                    if (lugarDomicilioInput) lugarDomicilioInput.required = true;
                } else if (selectedValue === 'exterior') {
                    ubicacionExteriorGroup.style.display = 'block';
                    if (lugarExteriorInput) lugarExteriorInput.required = true;
                }
            }

            if (lugarSelect) {
                // Manejar cambios en el select
                lugarSelect.addEventListener('change', actualizarCampos);
                
                // Ejecutar una vez al cargar para establecer el estado inicial
                actualizarCampos();

                // Mantener el valor seleccionado después de un cambio de foco
                if (lugarDomicilioInput) {
                    lugarDomicilioInput.addEventListener('blur', function() {
                        if (lugarSelect.value === 'domicilio') {
                            direccionGroup.style.display = 'block';
                        }
                    });
                }
            }
        });
    </script>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="P_Menu_principal.php">
                <i class="bi bi-camera" style="font-size:1.6em;color:#1976d2;"></i>
                <span style="font-weight:700;font-size:1.25em;">PhotoStudio</span>
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" href="P_Menu_principal.php">Inicio</a>
                    </li>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">

        <!-- Modern Stepper -->
        <div class="modern-stepper">
            <?php
            // Determinar el paso actual
            $paso = 1; // Por defecto, mostrar selección de tipo

            if (isset($_GET['tipo'])) {
                if (!isset($_GET['fecha'], $_GET['hora'])) {
                    $paso = 2; // Selección de fecha y hora
                } else {
                    if (isset($_GET['paso']) && $_GET['paso'] === 'pago' && isset($_SESSION['reserva_temp'])) {
                        $paso = 4; // Mostrar paso de pago solo si viene explícitamente del paso 3
                    } else {
                        $paso = 3; // Mostrar formulario de detalles
                    }
                }
            }

            // El paso 5 solo se alcanza al confirmar el pago
            if (isset($_POST['confirmar_pago'])) {
                $paso = 5;
            }
            $steps = [
                ["icon"=>"bi-camera","label"=>"Tipo de Sesión"],
                ["icon"=>"bi-calendar-event","label"=>"Fecha y Hora"],
                ["icon"=>"bi-person","label"=>"Detalles"],
                ["icon"=>"bi-credit-card","label"=>"Pago"],
                ["icon"=>"bi-check-circle","label"=>"Confirmación"],
            ];
            foreach ($steps as $i=>$step) {
                $state = $i+1 < $paso ? 'completed' : ($i+1==$paso ? 'active' : '');
                echo "<div class='modern-step $state'>";
                echo "  <div class='circle'><i class='bi {$step['icon']}'></i></div>";
                echo "  <div>{$step['label']}</div>";
                echo "</div>";
                if ($i < count($steps)-1) echo "<div class='modern-line'></div>";
            }
            ?>
        </div>

        <h1 class="text-center mb-2">Reserva tu Sesión Fotográfica</h1>
        <div class="text-center text-secondary mb-4">Sigue los pasos para completar tu reserva</div>

        <!-- Paso 1: Selección de tipo de sesión -->
        <?php if ($paso === 1): ?>
        <form method="get" action="">
            <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
                <?php
                $sessionManager = SessionManager::getInstance();
                $strategies = $sessionManager->getAllStrategies();
                $titles = [
                    'cobertura de evento' => 'Cobertura de Evento',
                    'temática' => 'Temática',
                    'estudio' => 'Estudio',
                    'exterior' => 'Exterior',
                    'corporativo' => 'Retrato Corporativo',
                    'familiar' => 'Familiar',
                    'retrato' => 'Retrato'
                ];
                
                foreach ($strategies as $key => $strategy):
                    $id = str_replace(' ', '_', $key);
                ?>
                <div class="col">
                    <div class="card card-type h-100 shadow-sm" onclick="selectCard(this,'<?php echo $id; ?>')">
                        <div class="card-body d-flex flex-column align-items-center text-center">
                            <div class="rounded-circle bg-primary bg-opacity-10 mb-3 p-3"
                                 style="width:60px;height:60px;display:flex;align-items:center;justify-content:center;">
                                <i class="<?php echo $strategy->getIcon(); ?> fs-2 text-primary"></i>
                            </div>
                            <input type="radio" id="<?php echo $id; ?>" name="tipo" value="<?php echo htmlspecialchars($key); ?>" required class="d-none">
                            <h5 class="card-title fw-bold mb-1"><?php echo htmlspecialchars($titles[$key]); ?></h5>
                            <div class="text-secondary small mb-2"><?php echo htmlspecialchars($strategy->getDescription()); ?></div>
                            <div class="d-flex justify-content-center align-items-center gap-2 mb-2">
                                <span class="badge bg-light text-dark border"><?php echo $strategy->getDuration(); ?></span>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary"><?php echo $strategy->getPrice(); ?></span>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-auto w-100"
                                    onclick="selectCard(this.closest('.card-type'),'<?php echo $id; ?>'); this.form.submit();">
                                Seleccionar
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <input type="submit" class="d-none">
        </form>
        <?php endif; ?>

        <!-- Paso 2: Selección de fecha y hora -->
        <?php if ($paso === 2): ?>
        <form method="get" action="" class="mx-auto" style="max-width:500px;">
            <input type="hidden" name="tipo" value="<?php echo htmlspecialchars($_GET['tipo']); ?>">
            <div class="card shadow-sm mb-4 border-primary-subtle">
                <div class="card-body p-4">
                    <h4 class="mb-4 text-primary"><i class="bi bi-calendar-event me-2"></i>Selecciona Fecha y Hora</h4>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label for="fecha" class="form-label fw-semibold">Fecha</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-calendar-event"></i></span>
                                <input type="date" name="fecha" id="fecha" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="hora" class="form-label fw-semibold">Hora</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-clock"></i></span>
                                <input type="time" name="hora" id="hora" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-4 d-flex justify-content-center gap-3">
                        <a href="P_Agendar_sesion.php" class="btn btn-outline-primary submit-btn">Cambiar tipo de sesión</a>
                        <button type="submit" class="btn btn-primary submit-btn px-5">Siguiente</button>
                    </div>
                </div>
            </div>
        </form>
        <?php endif; ?>

        <!-- Paso 3: Detalles de la sesión -->
        <?php if ($paso === 3): ?>
            <?php
            // Verificar que tenemos todos los parámetros necesarios
            if (!isset($_GET['tipo'], $_GET['fecha'], $_GET['hora'])) {
                header('Location: P_Agendar_sesion.php');
                exit;
            }
            
            $sessionType = $_GET['tipo'];
            $sessionManager = SessionManager::getInstance();
            $strategy = $sessionManager->getStrategy($sessionType);
            ?>
            <form method="post" action="" class="mx-auto" style="max-width:650px;">
            <input type="hidden" name="tipo"  value="<?php echo htmlspecialchars($_GET['tipo']); ?>">
            <input type="hidden" name="fecha" value="<?php echo htmlspecialchars($_GET['fecha']); ?>">
            <input type="hidden" name="hora"  value="<?php echo htmlspecialchars($_GET['hora']); ?>">
            <input type="hidden" name="duracion" value="<?php echo htmlspecialchars($strategy->getDuration()); ?>">
            <input type="hidden" name="precio"   value="<?php echo htmlspecialchars($strategy->getPrice()); ?>">


            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Resumen de tu Sesión</h5>
                    <?php 
                    $sessionType = $_GET['tipo'];
                    $strategy = getSessionStrategy($sessionType);
                    $basePrice = intval(str_replace(['$', ','], '', $strategy->getPrice()));
                    ?>
                    <p class="mb-0 text-secondary">
                        Tipo: <?php echo htmlspecialchars($_GET['tipo']); ?><br>
                        Fecha: <?php echo htmlspecialchars($_GET['fecha']); ?><br>
                        Hora: <?php echo htmlspecialchars($_GET['hora']); ?><br>
                        Precio base: <?php echo $strategy->getPrice(); ?>
                    </p>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Información Personal</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <input type="text" id="nombrePersonal" name="nombre" class="form-control" placeholder="Nombre Completo *" required>
                        </div>
                        <div class="col-md-6">
                            <input type="text" id="cedula" name="cedula" class="form-control" maxlength="14" placeholder="Cédula * (xx-xxxx-xxxxx)" required title="Ejemplo: 8-1011-2269">
                            <div id="cedulaError" class="invalid-feedback" style="display: none;">
                                <i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>
                                <span id="cedulaErrorText">Formato inválido</span>
                            </div>
                            <div id="cedulaSuccess" class="valid-feedback" style="display: none;">
                                <i class="bi bi-check-circle-fill text-success me-1"></i>
                                Cédula válida
                            </div>
                        </div>
                        <div class="col-md-6">
                            <input type="tel" id="telefono" name="telefono" class="form-control" maxlength="9" placeholder="Teléfono * (xxxx-xxxx)" required pattern="[0-9]{4}-[0-9]{4}" title="Ejemplo: xxxx-xxxx">
                        </div>
                        <div class="col-md-6">
                            <input type="email" name="email" class="form-control" placeholder="Email *" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Personalización de la Sesión</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <select id="lugar_sesion" name="lugar_sesion" class="form-select" required>
                                <option value="">Preferencia de Ubicación</option>
                                <option value="estudio">Estudio</option>
                                <option value="exterior">Exterior</option>
                                <option value="domicilio">A domicilio</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <select id="estilo_fotografia" name="estilo_fotografia" class="form-select" required>
                                <option value="">Estilo Fotográfico</option>
                                <option value="clásico">Clásico</option>
                                <option value="moderno">Moderno</option>
                                <option value="creativo">Creativo</option>
                            </select>
                        </div>
                        <div id="direccionGroup" class="col-12 mt-3" style="display: none;">
                            <label for="lugar_domicilio" class="form-label">Dirección</label>
                            <input type="text" id="lugar_domicilio" name="lugar_domicilio" class="form-control" placeholder="Ingresa tu dirección *">
                        </div>
                        <div id="ubicacionExteriorGroup" class="col-12 mt-3" style="display: none;">
                            <label for="lugar_exterior" class="form-label">Ubicación específica para sesión exterior</label>
                            <input type="text" id="lugar_exterior" name="lugar_exterior" class="form-control" placeholder="Ej: Parque Omar, Causeway, Casco Viejo, etc. *">
                        </div>
                    </div>
                    <hr>
                    <h6>Servicios Adicionales</h6>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="servicios_adicionales[]" value="maquillaje" id="maquillaje">
                        <label class="form-check-label" for="maquillaje">Maquillaje profesional (+$50)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="servicios_adicionales[]" value="vestuario" id="vestuario">
                        <label class="form-check-label" for="vestuario">Consultoría de vestuario (+$30)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="servicios_adicionales[]" value="edicion" id="edicion">
                        <label class="form-check-label" for="edicion">Edición extra (+$40)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="servicios_adicionales[]" value="impresiones" id="impresiones">
                        <label class="form-check-label" for="impresiones">Impresiones físicas (+$25)</label>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <label for="otros_datos" class="form-label">Solicitudes Especiales</label>
                        <textarea id="otros_datos" name="otros_datos" class="form-control" rows="3" placeholder="Describe cualquier solicitud..."></textarea>
                    </div>
                    <!-- Campos ocultos para mantener los datos de la URL -->
                    <input type="hidden" name="tipo" value="<?php echo htmlspecialchars($_GET['tipo']); ?>">
                    <input type="hidden" name="fecha" value="<?php echo htmlspecialchars($_GET['fecha']); ?>">
                    <input type="hidden" name="hora" value="<?php echo htmlspecialchars($_GET['hora']); ?>">
                    
                    <div class="d-flex justify-content-between gap-2">
                        <a href="P_Agendar_sesion.php?tipo=<?php echo urlencode($_GET['tipo']); ?>&fecha=<?php echo urlencode($_GET['fecha']); ?>&hora=<?php echo urlencode($_GET['hora']); ?>" class="btn btn-outline-primary submit-btn">Volver</a>
                        <button type="submit" class="btn btn-primary submit-btn">Continuar al Pago</button>
                    </div>
                </div>
            </div>
        </form>
        <?php endif; ?>

        <!-- Paso 4: Pago -->
        <?php if ($paso === 4 && isset($_SESSION['reserva_temp'])): 
            $data = $_SESSION['reserva_temp'];
            $tipo      = htmlspecialchars($data['tipo']);
            $fecha     = htmlspecialchars($data['fecha']);
            $hora      = htmlspecialchars($data['hora']);
            $nombre    = htmlspecialchars($data['nombre']);
            $email     = htmlspecialchars($data['email']);
            $telefono  = htmlspecialchars($data['telefono']);
            $duracion = $_POST['duracion'] ?? null;
            $servicios = $_POST['servicios_adicionales'] ?? [];
            
            // Obtener precio base de la estrategia
            $strategy = getSessionStrategy($tipo);
            $basePrice = intval(str_replace(['$', ','], '', $strategy->getPrice()));
            $total     = $basePrice;
            
            foreach ($servicios as $s) {
                if ($s=='maquillaje')  $total += 50;
                if ($s=='vestuario')   $total += 30;
                if ($s=='edicion')     $total += 40;
                if ($s=='impresiones') $total += 25;
            }
            
            $abono    = round($total * 0.3, 2);
            $restante = $total - $abono;
        ?>
        <div class="row justify-content-center g-4">
            <!-- Resumen del pedido -->
            <div class="col-12 col-lg-5">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Resumen del Pedido</h5>
                        <ul class="list-unstyled mb-3">
                            <li class="d-flex justify-content-between"><span>Sesión: <?php echo $tipo; ?></span><span><?php echo $strategy->getPrice(); ?></span></li>
                            <?php foreach ($servicios as $s): ?>
                                <?php if ($s=='maquillaje'): ?>
                                    <li class="d-flex justify-content-between"><span>Maquillaje profesional</span><span>+$50</span></li>
                                <?php elseif ($s=='vestuario'): ?>
                                    <li class="d-flex justify-content-between"><span>Consultoría de vestuario</span><span>+$30</span></li>
                                <?php elseif ($s=='edicion'): ?>
                                    <li class="d-flex justify-content-between"><span>Edición extra</span><span>+$40</span></li>
                                <?php elseif ($s=='impresiones'): ?>
                                    <li class="d-flex justify-content-between"><span>Impresiones físicas</span><span>+$25</span></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                        <hr>
                        <div class="d-flex justify-content-between"><strong>Total</strong><strong>$<?php echo number_format($total,2); ?></strong></div>
                        <div class="d-flex justify-content-between mt-2"><span class="text-secondary">Abono inicial (30%)</span><strong>$<?php echo number_format($abono,2); ?></strong></div>
                        <div class="d-flex justify-content-between"><span class="text-secondary">Saldo restante</span><span>$<?php echo number_format($restante,2); ?></span></div>
                        <hr>
                        <small>Fecha: <?php echo $fecha; ?></small><br>
                        <small>Hora: <?php echo $hora; ?></small><br>
                        <small>Cliente: <?php echo $nombre; ?></small><br>
                        <small>Email: <?php echo $email; ?></small>
                    </div>
                </div>
            </div>
            <!-- Formulario de pago -->
            <div class="col-12 col-lg-5">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Información de Pago</h5>
                        <form id="pagoFormulario" method="post" action="" novalidate>
                            <input type="hidden" name="paso" value="confirmacion">
                            <input type="hidden" name="total" value="<?php echo $total; ?>">
                            <!-- Selección de método de pago -->
                            <div class="mb-3">
                                <label class="form-label">Método de Pago</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="metodo_pago" id="pagoTarjeta" value="tarjeta" checked>
                                        <label class="form-check-label" for="pagoTarjeta"><i class="bi bi-credit-card"></i> Tarjeta de Crédito</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="metodo_pago" id="pagoYappy" value="yappy">
                                        <label class="form-check-label" for="pagoYappy"><img src="https://www.merkapp.com/_next/image?url=%2F_next%2Fstatic%2Fmedia%2Fyappy-logo.2ddc32f2.png&w=3840&q=75" alt="Yappy" style="height:1.2em;vertical-align:middle;"> Yappy</label>
                                    </div>
                                </div>
                            </div>
                            <!-- Campos de tarjeta de crédito -->
                            <div id="tarjetaFields">
                                <div class="mb-3">
                                    <label class="form-label">Nombre del Titular</label>
                                    <input type="text" id="nombreTitular" name="titular"
                                           class="form-control" placeholder="Nombre Completo *"
                                           pattern="[A-Za-z\u00C0-\u017F ]+"
                                           title="Solo letras y espacios">
                                    <div class="invalid-feedback">Formato inválido.</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Número de Tarjeta</label>
                                    <input type="text" id="numeroTarjeta" name="tarjeta"
                                           class="form-control" maxlength="19"
                                           placeholder="xxxx xxxx xxxx xxxx"
                                           pattern="^(?:\d{4} ){3}\d{4}$"
                                           title="xxxx xxxx xxxx xxxx">
                                    <div class="invalid-feedback">Formato inválido.</div>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col">
                                        <label class="form-label">Vto (MM/AA)</label>
                                        <input type="text" id="fechaVencimiento" name="vencimiento"
                                               class="form-control" maxlength="5"
                                               placeholder="MM/AA" pattern="(0[1-9]|1[0-2])\/\d{2}"
                                               title="MM/AA">
                                        <div class="invalid-feedback">Formato inválido.</div>
                                    </div>
                                    <div class="col">
                                        <label class="form-label">CVV</label>
                                        <input type="text" id="cvv" name="cvv"
                                               class="form-control" minlength="3" maxlength="4"
                                               pattern="\d{3,4}"
                                               title="3 o 4 dígitos">
                                        <div class="invalid-feedback">Formato inválido.</div>
                                    </div>
                                </div>
                            </div>
                            <div id="yappyFields" style="display:none;">
                                <div class="alert alert-info mb-3">
                                    Serás redirigido a Yappy para completar el pago.
                                </div>
                            </div>
                            <div class="alert alert-warning">
                                <strong>Nota:</strong> Pagarás ahora el abono de <strong>$<?php echo number_format($abono,2); ?></strong>.
                            </div>
                            <!-- Datos ocultos necesarios -->
                            <input type="hidden" name="tipo"     value="<?php echo $tipo; ?>">
                            <input type="hidden" name="fecha"    value="<?php echo $fecha; ?>">
                            <input type="hidden" name="hora"     value="<?php echo $hora; ?>">
                            <input type="hidden" name="cedula_cliente" value="<?php echo htmlspecialchars($_POST['cedula'] ?? ''); ?>">
                            <input type="hidden" name="duracion" value="<?php echo htmlspecialchars($strategy->getDuration()); ?>">
                            <input type="hidden" name="precio"   value="<?php echo htmlspecialchars($strategy->getPrice()); ?>">
                            <input type="hidden" name="nombre"   value="<?php echo $nombre; ?>">
                            <input type="hidden" name="email"    value="<?php echo $email; ?>">
                            <input type="hidden" name="telefono" value="<?php echo $telefono; ?>">
                            <input type="hidden" name="abono"    value="<?php echo number_format($abono,2); ?>">
                            <input type="hidden" name="restante" value="<?php echo number_format($restante,2); ?>">
                                <div class="d-flex justify-content-between">
                                    <!-- Botón para regresar al paso anterior -->
                                    <button 
                                    type="button" 
                                    class="btn btn-outline-secondary" 
                                    onclick="history.back()"
                                    >
                                        <i class="bi bi-arrow-left-circle"></i> Volver
                                    </button>

                                    <!-- Botón principal de pago -->
                                    <button 
                                    type="submit" 
                                    name="confirmar_pago" 
                                    class="btn btn-primary"
                                    >
                                        <i class="bi bi-credit-card"></i>
                                        Pagar $<?php echo number_format($abono,2); ?>
                                    </button>
                                </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mostrar/ocultar campos según método de pago
            function togglePagoFields() {
                const tarjetaFields = document.getElementById('tarjetaFields');
                const yappyFields = document.getElementById('yappyFields');
                const metodo = document.querySelector('input[name="metodo_pago"]:checked').value;
                if (metodo === 'tarjeta') {
                    tarjetaFields.style.display = '';
                    yappyFields.style.display = 'none';
                    // Hacer requeridos los campos de tarjeta
                    document.getElementById('nombreTitular').required = true;
                    document.getElementById('numeroTarjeta').required = true;
                    document.getElementById('fechaVencimiento').required = true;
                    document.getElementById('cvv').required = true;
                } else {
                    tarjetaFields.style.display = 'none';
                    yappyFields.style.display = '';
                    // Quitar requeridos
                    document.getElementById('nombreTitular').required = false;
                    document.getElementById('numeroTarjeta').required = false;
                    document.getElementById('fechaVencimiento').required = false;
                    document.getElementById('cvv').required = false;
                }
            }
            document.querySelectorAll('input[name="metodo_pago"]').forEach(radio => {
                radio.addEventListener('change', togglePagoFields);
            });
            togglePagoFields();
        });
        </script>
        <?php endif; ?>

        <!-- Paso 5: Inserción en BD y overlay -->
<?php
if (isset($_POST['paso'], $_POST['confirmar_pago']) && $_POST['paso'] === 'confirmacion') {
    // Instanciar cliente con el nuevo constructor
    $cliente = new Cliente(
        $_POST['cedula'],
        $_POST['nombre_completo'] ?? '',
        $_POST['telefono_celular'] ?? '',
        $_POST['correo'] ?? ''
    );

    $pdo->beginTransaction();
    try {
        // Registrar o leer cliente
        $idCliente = $cliente->obtenerORegistrarCliente();

        // Preparar datos para la sesión
        $datosSesion = [
            'tipo_sesion'        => $_POST['tipo_sesion'],
            'descripcion_sesion' => $_POST['descripcion_sesion'],
            'duracion_sesion'    => $_POST['duracion_sesion'],
            'lugar_sesion'       => $_POST['lugar_sesion'] ?? '',
            'direccion_sesion'   => $_POST['direccion_sesion'] ?? '',
            'estilo_fotografia'  => $_POST['estilo_fotografia'] ?? '',
            'servicios_adicionales' => isset($_POST['servicios_adicionales']) ? implode(',', $_POST['servicios_adicionales']) : '',
            'otros_datos'        => $_POST['otros_datos'] ?? '',
            'total_pagar'        => $_POST['total_pagar'],
            'abono_inicial'      => $_POST['abono_inicial'],
            'fecha_sesion'       => $_POST['fecha_sesion'],
            'hora_sesion'        => $_POST['hora_sesion']
        ];

    // Crear la sesión
    $idSesion = $cliente->reservarSesion($datosSesion, $idCliente);

    // Registrar el abono inicial con la nueva clase Pago
    $monto         = (float) $datosSesion['abono_inicial'];
    $metodoPago    = $_POST['metodo_pago'] ?? 'tarjeta';
    $pago          = Pago::seleccionarMetodoPago($metodoPago, $monto, $_POST);
    $numeroFactura = generarNumeroFactura();
    $pago->registrarPagoSesion($idSesion, $numeroFactura);

    // Confirmar transacción
    $pdo->commit();

    // Redirigir para disparar el overlay y pasar el número de factura
    header('Location: P_Agendar_sesion.php?success=1&factura=' . urlencode($numeroFactura));
    exit;

    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }

    // Redirigir para mostrar overlay de éxito y pasar el número de factura
    header('Location: P_Agendar_sesion.php?success=1&factura=' . urlencode($numeroFactura));
    exit;
}

if (isset($_GET['success'], $_GET['factura']) && $_GET['success'] === '1'): ?>
  <style>
    /* Overlay */
    #overlay {
      position: fixed; inset: 0;
      background: rgba(0,0,0,0.6);
      display: flex; align-items: center; justify-content: center;
      z-index: 1050;
    }
    /* Popup */
    #popup {
      position: relative;
      background: #fff; padding: 2rem;
      border-radius: .5rem;
      max-width: 400px; width: 90%;
      box-shadow: 0 4px 12px rgba(0,0,0,0.3);
      text-align: center;
    }
    /* Botón de cerrar */
    #closePopup {
      position: absolute; top: .5rem; right: .5rem;
      background: transparent; border: none; font-size: 1.5rem;
      cursor: pointer;
    }
    /* Contenedor botones */
    .btn-container {
      display: flex; flex-direction: column; gap: .75rem; margin-top: 1.5rem;
    }
    @media (min-width: 576px) {
      .btn-container { flex-direction: row; justify-content: center; }
      .btn-container .btn { min-width: 160px; }
    }
  </style>

  <div id="overlay">
    <div id="popup">
      <button id="closePopup" type="button">&times;</button>
      <h2 class="text-success mb-3">¡Reserva Confirmada!</h2>
      <h4 class="mb-2">Factura #<?= htmlentities($_GET['factura']) ?></h4>
      <p class="fs-5">Gracias por tu reserva.<br>Abono recibido correctamente.</p>

      <div class="btn-container">
        <a 
          href="descargar_factura.php?factura=<?= urlencode($_GET['factura']) ?>" 
          class="btn btn-outline-primary"
          target="_blank"
        >
          <i class="bi bi-file-earmark-pdf"></i>
          Descargar PDF
        </a>
        <a 
          href="P_Menu_principal.php" 
          class="btn btn-primary"
        >
          <i class="bi bi-house-door"></i>
          Menú Principal
        </a>
      </div>
    </div>
  </div>

  <script>
    document.getElementById('closePopup')
      .addEventListener('click', () => {
        document.getElementById('overlay').remove();
      });
  </script>
<?php endif; ?>


    </div><!-- .container -->

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Validar cédula: x-xxxx-xxxxx (formato panameño simplificado)
    const cedulaInput = document.getElementById('cedula');
    const cedulaError = document.getElementById('cedulaError');
    const cedulaErrorText = document.getElementById('cedulaErrorText');
    const cedulaSuccess = document.getElementById('cedulaSuccess');
    
    if (cedulaInput) {
      
      function validarCedula(cedula) {
        // Verificar formato con guiones
        if (!cedula.includes('-') || cedula.split('-').length !== 3) {
          return { valida: false, mensaje: 'Formato incorrecto. Use: xx-xxxx-xxxxx' };
        }
        
        // Dividir por guiones
        const partes = cedula.split('-');
        const primeraParte = partes[0];
        const segundaParte = partes[1];
        const terceraParte = partes[2];
        
        // Verificar que solo contengan números
        if (!/^\d+$/.test(primeraParte) || !/^\d+$/.test(segundaParte) || !/^\d+$/.test(terceraParte)) {
          return { valida: false, mensaje: 'Solo se permiten números en cada sección' };
        }
        
        // Verificar que haya 1 o 2 dígitos antes del primer guión
        if (primeraParte.length < 1 || primeraParte.length > 2) {
          return { valida: false, mensaje: 'Debe haber 1 o 2 dígitos antes del primer guión' };
        }
        
        // Verificar que el primer dígito esté entre 1-13
        const primerDigito = parseInt(primeraParte.charAt(0));
        if (primerDigito < 1 || primerDigito > 13) {
          return { valida: false, mensaje: 'El primer dígito debe ser del 1 al 13' };
        }
        
        // Si hay dos dígitos, verificar que no excedan 13
        if (primeraParte.length === 2) {
          const numeroCompleto = parseInt(primeraParte);
          if (numeroCompleto > 13) {
            return { valida: false, mensaje: 'Los dos primeros dígitos no pueden exceder 13' };
          }
        }
        
        // Verificar que haya entre 1-4 dígitos antes del segundo guión
        if (segundaParte.length < 1 || segundaParte.length > 4) {
          return { valida: false, mensaje: 'Debe haber entre 1 y 4 dígitos antes del segundo guión' };
        }
        
        // Verificar que haya entre 1-5 dígitos al final
        if (terceraParte.length < 1 || terceraParte.length > 6) {
          return { valida: false, mensaje: 'Debe haber entre 1 y 6 dígitos al final' };
        }
        
        return { valida: true, mensaje: 'Cédula válida' };
      }
      
      function mostrarValidacion(esValida, mensaje = '') {
        cedulaInput.classList.remove('is-valid', 'is-invalid');
        cedulaError.style.display = 'none';
        cedulaSuccess.style.display = 'none';
        
        if (cedulaInput.value.trim() === '') {
          return; // No mostrar validación si está vacío
        }
        
        if (esValida) {
          cedulaInput.classList.add('is-valid');
          cedulaSuccess.style.display = 'block';
        } else {
          cedulaInput.classList.add('is-invalid');
          cedulaError.style.display = 'block';
          cedulaErrorText.textContent = mensaje;
        }
      }
      
      // Solo permitir números y guiones
      cedulaInput.addEventListener('input', function(e) {
        // Permitir solo números y guiones
        this.value = this.value.replace(/[^\d-]/g, '');
        
        // Validar en tiempo real si tiene suficientes dígitos
        const numeros = this.value.replace(/[^\d]/g, '');
        if (numeros.length >= 10) {
          const validacion = validarCedula(this.value);
          mostrarValidacion(validacion.valida, validacion.mensaje);
        } else {
          mostrarValidacion(false, 'Formato incompleto');
        }
      });
      
      // Validar al perder el foco
      cedulaInput.addEventListener('blur', function() {
        const validacion = validarCedula(this.value);
        mostrarValidacion(validacion.valida, validacion.mensaje);
      });
      
      // Limpiar validación al enfocar
      cedulaInput.addEventListener('focus', function() {
        this.classList.remove('is-valid', 'is-invalid');
        cedulaError.style.display = 'none';
        cedulaSuccess.style.display = 'none';
      });
      
      // Validar antes de enviar el formulario
      cedulaInput.form && cedulaInput.form.addEventListener('submit', function(e) {
        const validacion = validarCedula(cedulaInput.value);
        if (!validacion.valida) {
          e.preventDefault();
          mostrarValidacion(false, validacion.mensaje);
          cedulaInput.focus();
          return false;
        }
      // Mantener formato al enviar
        cedulaInput.value = cedulaInput.value.replace(/[^\d-]/g, '');
      });
    }

    // Formatear teléfono: xxxx-xxxx
    const telefonoInput = document.getElementById('telefono');
    if (telefonoInput) {
      telefonoInput.addEventListener('input', function() {
        let v = this.value.replace(/[^\d]/g, '').slice(0,8);
        if (v.length > 4) v = v.slice(0,4) + '-' + v.slice(4);
        this.value = v;
      });
      // Mantener formato al enviar
      telefonoInput.form && telefonoInput.form.addEventListener('submit', function() {
        telefonoInput.value = telefonoInput.value.replace(/[^\d-]/g, '');
      });
    }
    document.addEventListener('DOMContentLoaded', () => {
      // Filtrar textarea detalles
      const detalles = document.getElementById('detalles');
      if (detalles) detalles.addEventListener('input', () => {
        detalles.value = detalles.value.replace(/[^A-Za-z0-9\u00C0-\u017F ]/g, '');
      });

      // Filtrar nombrePersonal
      const nombrePersonal = document.getElementById('nombrePersonal');
      if (nombrePersonal) nombrePersonal.addEventListener('input', () => {
        nombrePersonal.value = nombrePersonal.value.replace(/[^A-Za-z\u00C0-\u017F ]/g, '');
      });

      // Validación form de pago
      const pagoForm = document.getElementById('pagoFormulario');
      if (pagoForm) {
        pagoForm.addEventListener('submit', e => {
          if (!pagoForm.checkValidity()) {
            e.preventDefault();
            pagoForm.classList.add('was-validated');
          }
        });
      }

      // Formatear número de tarjeta
      const numeroTarjeta = document.getElementById('numeroTarjeta');
      if (numeroTarjeta) numeroTarjeta.addEventListener('input', function() {
        let digits = this.value.replace(/\D/g, '').slice(0,16);
        this.value = digits.replace(/(.{4})/g,'$1 ').trim();
      });

      // Formatear fecha de vencimiento
      const fechaV = document.getElementById('fechaVencimiento');
      if (fechaV) fechaV.addEventListener('input', () => {
        let v = fechaV.value.replace(/\D/g,'').slice(0,4);
        if (v.length>2) v = v.slice(0,2)+'/'+v.slice(2);
        fechaV.value = v;
      });

      // Limitar CVV
      const cvv = document.getElementById('cvv');
      if (cvv) cvv.addEventListener('input', () => {
        cvv.value = cvv.value.replace(/\D/g,'').slice(0,4);
      });

      // Overlay confirmación
      const overlay = document.getElementById('overlay');
      const closeBtn = document.getElementById('closePopup');
      if (overlay && closeBtn) {
        closeBtn.addEventListener('click', () => overlay.classList.add('hidden'));
        overlay.addEventListener('click', e => {
          if (e.target === overlay) overlay.classList.add('hidden');
        });
      }
    });

        // Mostrar/ocultar campo de dirección al seleccionar "A domicilio"
    const ubicacionSelect = document.getElementById('lugar');
    const direccionGroup  = document.getElementById('direccionGroup');
    const ubicacionExteriorGroup = document.getElementById('ubicacionExteriorGroup');
    ubicacionSelect.addEventListener('change', () => {
    if (ubicacionSelect.value === 'domicilio') {
        direccionGroup.style.display = 'block';
        direccionGroup.querySelector('input').required = true;
        ubicacionExteriorGroup.style.display = 'none';
        ubicacionExteriorGroup.querySelector('input').required = false;
    } else if (ubicacionSelect.value === 'exterior') {
        direccionGroup.style.display = 'none';
        direccionGroup.querySelector('input').required = false;
        ubicacionExteriorGroup.style.display = 'block';
        ubicacionExteriorGroup.querySelector('input').required = true;
    } else {
        direccionGroup.style.display = 'none';
        direccionGroup.querySelector('input').required = false;
        ubicacionExteriorGroup.style.display = 'none';
        ubicacionExteriorGroup.querySelector('input').required = false;
    }
    });
    // Inicializa correctamente si el usuario vuelve atrás
    ubicacionSelect.dispatchEvent(new Event('change'));
    </script>
</body>
</html>