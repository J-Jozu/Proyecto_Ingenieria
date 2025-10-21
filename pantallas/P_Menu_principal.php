<!DOCTYPE html>
<html>
<head>
    <title>PhotoStudio - Menú Principal</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .hero {
            background: linear-gradient(90deg,#2563eb 0%,#7c3aed 100%);
            color: #fff;
            text-align: center;
            padding: 60px 20px 40px 20px;
        }
        .hero h1 { font-size: 2.5em; margin-bottom: 10px; }
        .hero p { font-size: 1.2em; margin-bottom: 30px; }
        .feature-icon { font-size: 2em; margin-bottom: 10px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
              <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="P_Menu_principal.php">
                    <i class="bi bi-camera" style="font-size:1.5em;color:#1976d2;vertical-align:middle;"></i>
                    <span style="font-weight:500;font-size:1.15em;color:#222;line-height:1;">PhotoStudio</span>
                </a>
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a href="login.php" class="btn btn-outline-primary ms-3 d-flex align-items-center">
                            <i class="bi bi-person me-2"></i>
                            <span>Iniciar Sesión</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="P_Sistema_impresiones.php" class="btn btn-outline-secondary ms-3 d-flex align-items-center">
                            <i class="bi bi-printer" style="font-size:1.2em;"></i>
                            <span class="ms-2">Sistema de Impresiones</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="hero">
        <h1>Captura Momentos Únicos</h1>
        <p>Sesiones fotográficas profesionales para eventos, retratos, familias y más. Reserva tu sesión y crea recuerdos que durarán para siempre.</p>
        <a href="P_Agendar_sesion.php" class="btn btn-primary btn-lg d-inline-flex align-items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24"><path fill="#fff" d="M12 7a2 2 0 1 1 0 4 2 2 0 0 1 0-4Zm0 10c-3.31 0-6-2.69-6-6 0-1.1.9-2 2-2h8c1.1 0 2 .9 2 2 0 3.31-2.69 6-6 6Zm0-16C6.48 1 2 5.48 2 11c0 5.52 4.48 10 10 10s10-4.48 10-10C22 5.48 17.52 1 12 1Z"/></svg>
            <span class="ms-2">Reservar Sesión Ahora</span>
        </a>
    </div>
    <div class="container py-5">
        <h2 class="text-center mb-5">¿Por qué elegirnos?</h2>
        <div class="row justify-content-center g-4">
            <div class="col-12 col-md-4">
                <div class="card h-100 text-center border-0 shadow-sm">
                    <div class="card-body">
                        <div class="feature-icon">📸</div>
                        <h5 class="card-title">Equipo Profesional</h5>
                        <p class="card-text">Utilizamos equipos de última generación para garantizar la mejor calidad</p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card h-100 text-center border-0 shadow-sm">
                    <div class="card-body">
                        <div class="feature-icon">🕒</div>
                        <h5 class="card-title">Horarios Flexibles</h5>
                        <p class="card-text">Adaptamos nuestros horarios a tu disponibilidad</p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card h-100 text-center border-0 shadow-sm">
                    <div class="card-body">
                        <div class="feature-icon">⭐</div>
                        <h5 class="card-title">Calidad Garantizada</h5>
                        <p class="card-text">Más de 500 clientes satisfechos nos respaldan</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Sección Nuestros Servicios -->
    <div class="container pb-5">
        <h2 class="text-center mb-5 mt-5" style="font-weight:700;">Nuestros Servicios</h2>
        <div class="row justify-content-center g-4">
            <div class="col-12 col-md-4 col-lg-4">
                <div class="border rounded-3 bg-white h-100 p-4 text-center" style="border:1.5px solid #e5e7eb;">
                    <div class="fw-bold mb-1" style="color:#111;font-size:1.1em;">Cobertura de Evento</div>
                    <div class="fw-bold mb-1" style="color:#1976d2;font-size:1.5em;">$500</div>
                    <div class="text-secondary" style="font-size:1em;">4 horas</div>
                </div>
            </div>
            <div class="col-12 col-md-4 col-lg-4">
                <div class="border rounded-3 bg-white h-100 p-4 text-center" style="border:1.5px solid #e5e7eb;">
                    <div class="fw-bold mb-1" style="color:#111;font-size:1.1em;">Sesión Temática</div>
                    <div class="fw-bold mb-1" style="color:#1976d2;font-size:1.5em;">$300</div>
                    <div class="text-secondary" style="font-size:1em;">2 horas</div>
                </div>
            </div>
            <div class="col-12 col-md-4 col-lg-4">
                <div class="border rounded-3 bg-white h-100 p-4 text-center" style="border:1.5px solid #e5e7eb;">
                    <div class="fw-bold mb-1" style="color:#111;font-size:1.1em;">Estudio</div>
                    <div class="fw-bold mb-1" style="color:#1976d2;font-size:1.5em;">$250</div>
                    <div class="text-secondary" style="font-size:1em;">1.5 horas</div>
                </div>
            </div>
            <div class="col-12 col-md-4 col-lg-4">
                <div class="border rounded-3 bg-white h-100 p-4 text-center" style="border:1.5px solid #e5e7eb;">
                    <div class="fw-bold mb-1" style="color:#111;font-size:1.1em;">Exterior</div>
                    <div class="fw-bold mb-1" style="color:#1976d2;font-size:1.5em;">$350</div>
                    <div class="text-secondary" style="font-size:1em;">2.5 horas</div>
                </div>
            </div>
            <div class="col-12 col-md-4 col-lg-4">
                <div class="border rounded-3 bg-white h-100 p-4 text-center" style="border:1.5px solid #e5e7eb;">
                    <div class="fw-bold mb-1" style="color:#111;font-size:1.1em;">Retrato Corporativo</div>
                    <div class="fw-bold mb-1" style="color:#1976d2;font-size:1.5em;">$200</div>
                    <div class="text-secondary" style="font-size:1em;">1 hora</div>
                </div>
            </div>
            <div class="col-12 col-md-4 col-lg-4">
                <div class="border rounded-3 bg-white h-100 p-4 text-center" style="border:1.5px solid #e5e7eb;">
                    <div class="fw-bold mb-1" style="color:#111;font-size:1.1em;">Familiar</div>
                    <div class="fw-bold mb-1" style="color:#1976d2;font-size:1.5em;">$280</div>
                    <div class="text-secondary" style="font-size:1em;">2 horas</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal para acceder a la galería privada -->
    <div class="modal fade" id="galeriaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-images text-success me-2"></i>Acceder a tu Galería Privada
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">
                        Ingresa el token que recibiste por correo electrónico para acceder a tu galería privada.
                    </p>
                    <form id="galeriaForm" method="get" action="galeria_privada.php">
                        <div class="mb-3">
                            <label for="token" class="form-label">Token de acceso</label>
                            <input type="text" class="form-control" id="token" name="token" 
                                   placeholder="Ej: a1b2c3d4e5f6" required 
                                   pattern="[a-f0-9]+" title="Ingresa el token de 12 caracteres">
                            <div class="form-text">
                                El token es una cadena de 12 caracteres que recibiste por correo.
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="galeriaForm" class="btn btn-success">
                        <i class="bi bi-images me-2"></i>Acceder a mi Galería
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>