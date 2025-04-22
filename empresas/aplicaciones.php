<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// Verificar si está autenticado y es empresa
if (!estaAutenticado() || !esEmpresa()) {
    header('Location: ../login.php');
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$conn = conectarDB();

// Obtener información de la empresa
$sql = "SELECT * FROM empresas WHERE usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Si no existe la empresa, redirigir a completar perfil
    header('Location: perfil.php?nuevo=1');
    exit();
}

$empresa = $result->fetch_assoc();
$empresa_id = $empresa['id'];

// Filtrar por oferta específica si se proporciona un ID
$filtro_oferta = isset($_GET['oferta_id']) ? intval($_GET['oferta_id']) : 0;
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';

// Consulta base para obtener aplicaciones
// Incluye columna cv_pdf de candidatos en lugar de cv_archivo
$sql_base = "SELECT a.*, o.titulo as oferta_titulo, o.id as oferta_id, 
            c.nombre as candidato_nombre, c.apellidos as candidato_apellidos, 
            c.foto as candidato_foto, u.email as candidato_email, c.telefono as candidato_telefono, 
            c.usuario_id as candidato_usuario_id, c.cv_pdf as cv_pdf
            FROM aplicaciones a
            INNER JOIN ofertas o ON a.oferta_id = o.id
            INNER JOIN candidatos c ON a.candidato_id = c.id
            INNER JOIN usuarios u ON c.usuario_id = u.id
            WHERE o.empresa_id = ?";

$params = [$empresa_id];
$tipos = "i";

// Añadir filtros si existen
if ($filtro_oferta > 0) {
    $sql_base .= " AND o.id = ?";
    $params[] = $filtro_oferta;
    $tipos .= "i";
}

if (!empty($filtro_estado)) {
    $sql_base .= " AND a.estado = ?";
    $params[] = $filtro_estado;
    $tipos .= "s";
}

$sql_base .= " ORDER BY a.fecha_aplicacion DESC";

// Preparar y ejecutar la consulta
$stmt = $conn->prepare($sql_base);
$stmt->bind_param($tipos, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$aplicaciones = [];
while ($row = $result->fetch_assoc()) {
    $aplicaciones[] = $row;
}

// Obtener todas las ofertas de la empresa para el filtro
$sql = "SELECT id, titulo FROM ofertas WHERE empresa_id = ? ORDER BY fecha_publicacion DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$result = $stmt->get_result();

$ofertas = [];
while ($row = $result->fetch_assoc()) {
    $ofertas[] = $row;
}

// Cambiar el estado de una aplicación si se solicita
if (isset($_GET['accion']) && isset($_GET['aplicacion_id'])) {
    $accion = $_GET['accion'];
    $aplicacion_id = intval($_GET['aplicacion_id']);
    
    // Verificar que la aplicación pertenece a una oferta de esta empresa
    $sql = "SELECT a.id FROM aplicaciones a 
            INNER JOIN ofertas o ON a.oferta_id = o.id 
            WHERE a.id = ? AND o.empresa_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $aplicacion_id, $empresa_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        $nuevo_estado = '';
        
        switch ($accion) {
            case 'aceptar':
                $nuevo_estado = 'aceptada';
                break;
            case 'rechazar':
                $nuevo_estado = 'rechazada';
                break;
            case 'pendiente':
                $nuevo_estado = 'pendiente';
                break;
            case 'entrevista':
                $nuevo_estado = 'entrevista';
                break;
        }
        
        if (!empty($nuevo_estado)) {
            $sql = "UPDATE aplicaciones SET estado = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $nuevo_estado, $aplicacion_id);
            
            if ($stmt->execute()) {
                // Redirigir para evitar reenvío del formulario
                $redirect_url = 'aplicaciones.php';
                if ($filtro_oferta > 0) {
                    $redirect_url .= '?oferta_id=' . $filtro_oferta;
                    if (!empty($filtro_estado)) {
                        $redirect_url .= '&estado=' . $filtro_estado;
                    }
                } elseif (!empty($filtro_estado)) {
                    $redirect_url .= '?estado=' . $filtro_estado;
                }
                
                header('Location: ' . $redirect_url . '&msg=actualizado');
                exit();
            }
        }
    }
}

// Incluir el encabezado
include_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <!-- Menú lateral -->
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Panel de Empresa</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="index.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="perfil.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user me-2"></i> Mi Perfil
                    </a>
                    <a href="crear-oferta.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-plus-circle me-2"></i> Nueva Oferta
                    </a>
                    <a href="aplicaciones.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-users me-2"></i> Aplicaciones
                    </a>
                    <a href="../logout.php" class="list-group-item list-group-item-action text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="card mt-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Filtros</h5>
                </div>
                <div class="card-body">
                    <form action="aplicaciones.php" method="get">
                        <!-- Filtro por oferta -->
                        <div class="mb-3">
                            <label for="oferta_id" class="form-label">Oferta:</label>
                            <select name="oferta_id" id="oferta_id" class="form-select form-select-sm">
                                <option value="">Todas las ofertas</option>
                                <?php foreach ($ofertas as $oferta): ?>
                                <option value="<?php echo $oferta['id']; ?>" <?php echo ($filtro_oferta == $oferta['id']) ? 'selected' : ''; ?>>
                                    <?php echo $oferta['titulo']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Filtro por estado -->
                        <div class="mb-3">
                            <label for="estado" class="form-label">Estado:</label>
                            <select name="estado" id="estado" class="form-select form-select-sm">
                                <option value="">Todos los estados</option>
                                <option value="pendiente" <?php echo ($filtro_estado == 'pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                                <option value="entrevista" <?php echo ($filtro_estado == 'entrevista') ? 'selected' : ''; ?>>Entrevista</option>
                                <option value="aceptada" <?php echo ($filtro_estado == 'aceptada') ? 'selected' : ''; ?>>Aceptada</option>
                                <option value="rechazada" <?php echo ($filtro_estado == 'rechazada') ? 'selected' : ''; ?>>Rechazada</option>
                            </select>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-sm btn-primary">Filtrar</button>
                            <a href="aplicaciones.php" class="btn btn-sm btn-outline-secondary mt-2">Limpiar filtros</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Contenido principal -->
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <?php if ($filtro_oferta > 0): ?>
                            Aplicaciones para: <?php 
                            foreach ($ofertas as $oferta) {
                                if ($oferta['id'] == $filtro_oferta) {
                                    echo $oferta['titulo'];
                                    break;
                                }
                            }
                            ?>
                        <?php else: ?>
                            Todas las aplicaciones
                        <?php endif; ?>
                    </h5>
                    <span class="badge bg-light text-dark"><?php echo count($aplicaciones); ?> aplicaciones</span>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'actualizado'): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>¡Éxito!</strong> El estado de la aplicación ha sido actualizado.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (count($aplicaciones) > 0): ?>
                        <div class="row">
                            <?php foreach ($aplicaciones as $aplicacion): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header d-flex justify-content-between align-items-center 
                                            <?php 
                                            switch ($aplicacion['estado']) {
                                                case 'aceptada':
                                                    echo 'bg-success';
                                                    break;
                                                case 'rechazada':
                                                    echo 'bg-danger';
                                                    break;
                                                case 'entrevista':
                                                    echo 'bg-warning';
                                                    break;
                                                default:
                                                    echo 'bg-info';
                                            }
                                            ?> text-white">
                                            <span>
                                                <i class="fas fa-briefcase me-2"></i>
                                                <?php echo $aplicacion['oferta_titulo']; ?>
                                            </span>
                                            <small><?php echo formatearFecha($aplicacion['fecha_aplicacion'], 'd/m/Y'); ?></small>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex mb-3">
                                                <div class="me-3">
                                                    <?php if (!empty($aplicacion['candidato_foto'])): ?>
                                                        <img src="../uploads/candidatos/<?php echo $aplicacion['candidato_foto']; ?>" 
                                                             class="rounded-circle" width="60" height="60" alt="Foto del candidato">
                                                    <?php else: ?>
                                                        <div class="bg-light rounded-circle d-flex justify-content-center align-items-center" 
                                                             style="width: 60px; height: 60px;">
                                                            <i class="fas fa-user fa-2x text-secondary"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <h5 class="card-title mb-1">
                                                        <?php echo $aplicacion['candidato_nombre'] . ' ' . $aplicacion['candidato_apellidos']; ?>
                                                    </h5>
                                                    <p class="card-text text-muted mb-1">
                                                        <i class="fas fa-envelope me-1"></i> <?php echo $aplicacion['candidato_email']; ?>
                                                    </p>
                                                    <?php if (!empty($aplicacion['candidato_telefono'])): ?>
                                                    <p class="card-text text-muted mb-0">
                                                        <i class="fas fa-phone me-1"></i> <?php echo $aplicacion['candidato_telefono']; ?>
                                                    </p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <?php if (!empty($aplicacion['mensaje'])): ?>
                                            <div class="mb-3">
                                                <h6>Mensaje del candidato:</h6>
                                                <p class="card-text">
                                                    <?php echo nl2br(htmlspecialchars($aplicacion['mensaje'])); ?>
                                                </p>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="badge 
                                                    <?php 
                                                    switch ($aplicacion['estado']) {
                                                        case 'aceptada':
                                                            echo 'bg-success';
                                                            break;
                                                        case 'rechazada':
                                                            echo 'bg-danger';
                                                            break;
                                                        case 'entrevista':
                                                            echo 'bg-warning text-dark';
                                                            break;
                                                        default:
                                                            echo 'bg-info';
                                                    }
                                                    ?>">
                                                        <?php 
                                                        switch ($aplicacion['estado']) {
                                                            case 'aceptada':
                                                                echo 'Aceptada';
                                                                break;
                                                            case 'rechazada':
                                                                echo 'Rechazada';
                                                                break;
                                                            case 'entrevista':
                                                                echo 'En proceso de entrevista';
                                                                break;
                                                            default:
                                                                echo 'Pendiente';
                                                        }
                                                        ?>
                                                    </span>
                                                </div>
                                                <a href="../candidatos/perfil-publico.php?id=<?php echo $aplicacion['candidato_usuario_id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary" target="_blank">
                                                    Ver perfil completo
                                                </a>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-light d-flex justify-content-between">
                                            <?php if (!empty($aplicacion['cv_pdf'])): ?>
                                            <a href="../uploads/cv/<?php echo $aplicacion['cv_pdf']; ?>" 
                                               class="btn btn-sm btn-outline-primary" target="_blank">
                                                <i class="fas fa-file-pdf me-1"></i> Ver CV
                                            </a>
                                            <?php else: ?>
                                            <span class="text-muted small">Sin CV adjunto</span>
                                            <?php endif; ?>
                                            
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                                        id="dropdownEstado<?php echo $aplicacion['id']; ?>" 
                                                        data-bs-toggle="dropdown" aria-expanded="false">
                                                    Cambiar estado
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end" 
                                                    aria-labelledby="dropdownEstado<?php echo $aplicacion['id']; ?>">
                                                    <li>
                                                        <a class="dropdown-item <?php echo ($aplicacion['estado'] == 'pendiente') ? 'active' : ''; ?>" 
                                                           href="aplicaciones.php?accion=pendiente&aplicacion_id=<?php echo $aplicacion['id']; ?><?php 
                                                                echo ($filtro_oferta > 0) ? '&oferta_id=' . $filtro_oferta : ''; 
                                                                echo (!empty($filtro_estado)) ? '&estado=' . $filtro_estado : ''; 
                                                            ?>">
                                                            <i class="fas fa-clock me-1"></i> Pendiente
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item <?php echo ($aplicacion['estado'] == 'entrevista') ? 'active' : ''; ?>" 
                                                           href="aplicaciones.php?accion=entrevista&aplicacion_id=<?php echo $aplicacion['id']; ?><?php 
                                                                echo ($filtro_oferta > 0) ? '&oferta_id=' . $filtro_oferta : ''; 
                                                                echo (!empty($filtro_estado)) ? '&estado=' . $filtro_estado : ''; 
                                                            ?>">
                                                            <i class="fas fa-calendar-check me-1"></i> Entrevista
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item <?php echo ($aplicacion['estado'] == 'aceptada') ? 'active' : ''; ?>" 
                                                           href="aplicaciones.php?accion=aceptar&aplicacion_id=<?php echo $aplicacion['id']; ?><?php 
                                                                echo ($filtro_oferta > 0) ? '&oferta_id=' . $filtro_oferta : ''; 
                                                                echo (!empty($filtro_estado)) ? '&estado=' . $filtro_estado : ''; 
                                                            ?>">
                                                            <i class="fas fa-check-circle me-1 text-success"></i> Aceptar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item <?php echo ($aplicacion['estado'] == 'rechazada') ? 'active' : ''; ?>" 
                                                           href="aplicaciones.php?accion=rechazar&aplicacion_id=<?php echo $aplicacion['id']; ?><?php 
                                                                echo ($filtro_oferta > 0) ? '&oferta_id=' . $filtro_oferta : ''; 
                                                                echo (!empty($filtro_estado)) ? '&estado=' . $filtro_estado : ''; 
                                                            ?>">
                                                            <i class="fas fa-times-circle me-1 text-danger"></i> Rechazar
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <h5 class="alert-heading">No hay aplicaciones disponibles</h5>
                            <?php if ($filtro_oferta > 0 || !empty($filtro_estado)): ?>
                                <p>No se encontraron aplicaciones con los filtros seleccionados.</p>
                                <hr>
                                <div class="d-grid gap-2 col-6 mx-auto">
                                    <a href="aplicaciones.php" class="btn btn-primary">
                                        <i class="fas fa-filter me-2"></i> Ver todas las aplicaciones
                                    </a>
                                </div>
                            <?php else: ?>
                                <p>Todavía no has recibido aplicaciones a tus ofertas de empleo.</p>
                                <hr>
                                <div class="d-grid gap-2 col-6 mx-auto">
                                    <a href="crear-oferta.php" class="btn btn-primary">
                                        <i class="fas fa-plus-circle me-2"></i> Crear una nueva oferta
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>