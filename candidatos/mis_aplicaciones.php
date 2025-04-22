<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

// Verificar que sea un candidato
requiereCandidato();

// Obtener datos del candidato
$usuario_id = $_SESSION['usuario_id'];
$candidato = obtenerDatosCandidato($usuario_id);
$candidato_id = $candidato['id'];

// Variables para filtrado y paginación
$estado_filtro = isset($_GET['estado']) ? $_GET['estado'] : '';
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 10;
$offset = ($pagina_actual - 1) * $por_pagina;

// Obtener aplicaciones con filtrado y paginación
$aplicaciones = obtenerAplicaciones($candidato_id, $estado_filtro, $por_pagina, $offset);
$total_aplicaciones = contarAplicaciones($candidato_id, $estado_filtro);
$total_paginas = ceil($total_aplicaciones / $por_pagina);

// Función para obtener aplicaciones con filtros
function obtenerAplicaciones($candidato_id, $estado_filtro = '', $limit = 10, $offset = 0) {
    $conexion = conectarDB();
    
    $sql = "SELECT a.*, o.titulo, o.descripcion, o.ubicacion, o.tipo_contrato, o.salario, 
                  e.nombre as empresa_nombre, e.logo as empresa_logo 
           FROM aplicaciones a
           JOIN ofertas o ON a.oferta_id = o.id
           JOIN empresas e ON o.empresa_id = e.id
           WHERE a.candidato_id = ?";
    
    $params = [$candidato_id];
    $types = "i";
    
    // Aplicamos filtro por estado si está establecido
    if (!empty($estado_filtro)) {
        $sql .= " AND a.estado = ?";
        $types .= "s";
        $params[] = $estado_filtro;
    }
    
    $sql .= " ORDER BY a.fecha_aplicacion DESC LIMIT ? OFFSET ?";
    $types .= "ii";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $aplicaciones = [];
    while ($aplicacion = $resultado->fetch_assoc()) {
        $aplicaciones[] = $aplicacion;
    }
    
    $stmt->close();
    $conexion->close();
    
    return $aplicaciones;
}

// Función para contar total de aplicaciones (para paginación)
function contarAplicaciones($candidato_id, $estado_filtro = '') {
    $conexion = conectarDB();
    
    $sql = "SELECT COUNT(*) as total FROM aplicaciones WHERE candidato_id = ?";
    $params = [$candidato_id];
    $types = "i";
    
    // Aplicamos filtro por estado si está establecido
    if (!empty($estado_filtro)) {
        $sql .= " AND estado = ?";
        $types .= "s";
        $params[] = $estado_filtro;
    }
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $fila = $resultado->fetch_assoc();
    
    $stmt->close();
    $conexion->close();
    
    return $fila['total'];
}

// Obtener datos del candidato (puedes reutilizar esta función desde el archivo original)
function obtenerDatosCandidato($usuario_id) {
    $conexion = conectarDB();
    $query = "SELECT * FROM candidatos WHERE usuario_id = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $candidato = $resultado->fetch_assoc();
    $stmt->close();
    $conexion->close();
    return $candidato;
}

// Incluir la cabecera
include '../includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <!-- Menú lateral para candidatos -->
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Panel de Candidato</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="index.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="formulario_CV.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user me-2"></i> Mi Perfil / CV
                    </a>
                    <a href="mis_aplicaciones.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-briefcase me-2"></i> Mis Aplicaciones
                    </a>
                    <a href="../ofertas.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-search me-2"></i> Buscar Empleo
                    </a>
                    <a href="../logout.php" class="list-group-item list-group-item-action text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Contenido principal -->
        <div class="col-lg-9">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Mis Aplicaciones</h4>
                    <a href="../ofertas.php" class="btn btn-light btn-sm">
                        <i class="fas fa-search me-1"></i> Buscar nuevas ofertas
                    </a>
                </div>
                <div class="card-body">
                    <!-- Filtros -->
                    <div class="mb-4">
                        <div class="row">
                            <div class="col-md-8">
                                <h6>Filtrar por estado:</h6>
                                <div class="btn-group" role="group">
                                    <a href="mis_aplicaciones.php" class="btn btn-sm <?= empty($estado_filtro) ? 'btn-primary' : 'btn-outline-primary' ?>">Todas</a>
                                    <a href="mis_aplicaciones.php?estado=pendiente" class="btn btn-sm <?= $estado_filtro == 'pendiente' ? 'btn-warning' : 'btn-outline-warning' ?>">Pendientes</a>
                                    <a href="mis_aplicaciones.php?estado=revisado" class="btn btn-sm <?= $estado_filtro == 'revisado' ? 'btn-info' : 'btn-outline-info' ?>">Revisadas</a>
                                    <a href="mis_aplicaciones.php?estado=entrevista" class="btn btn-sm <?= $estado_filtro == 'entrevista' ? 'btn-primary' : 'btn-outline-primary' ?>">Entrevista</a>
                                    <a href="mis_aplicaciones.php?estado=seleccionado" class="btn btn-sm <?= $estado_filtro == 'seleccionado' ? 'btn-success' : 'btn-outline-success' ?>">Seleccionadas</a>
                                    <a href="mis_aplicaciones.php?estado=rechazado" class="btn btn-sm <?= $estado_filtro == 'rechazado' ? 'btn-danger' : 'btn-outline-danger' ?>">No seleccionadas</a>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <p class="text-muted mt-2">
                                    Total: <?= $total_aplicaciones ?> aplicaciones
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lista de aplicaciones -->
                    <?php if (empty($aplicaciones)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">No se encontraron aplicaciones<?= !empty($estado_filtro) ? ' con el estado seleccionado' : '' ?></h5>
                            <p>Explora nuevas ofertas y empieza a postularte</p>
                            <a href="../ofertas.php" class="btn btn-primary mt-2">Buscar ofertas</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Empresa</th>
                                        <th>Puesto</th>
                                        <th>Fecha</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($aplicaciones as $aplicacion): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($aplicacion['empresa_logo'])): ?>
                                                        <img src="/plataforma-de-empleos/uploads/logos/<?= $aplicacion['empresa_logo'] ?>" alt="Logo" class="me-2" style="width: 30px; height: 30px; object-fit: cover;">
                                                    <?php else: ?>
                                                        <i class="fas fa-building me-2 text-secondary"></i>
                                                    <?php endif; ?>
                                                    <?= sanitizarHTML($aplicacion['empresa_nombre']) ?>
                                                </div>
                                            </td>
                                            <td><?= sanitizarHTML($aplicacion['titulo']) ?></td>
                                            <td><?= formatearFecha($aplicacion['fecha_aplicacion'], 'd/m/Y') ?></td>
                                            <td>
                                                <?php
                                                switch ($aplicacion['estado']) {
                                                    case 'pendiente':
                                                        echo '<span class="badge bg-warning">Pendiente</span>';
                                                        break;
                                                    case 'revisado':
                                                        echo '<span class="badge bg-info">Revisado</span>';
                                                        break;
                                                    case 'entrevista':
                                                        echo '<span class="badge bg-primary">Entrevista</span>';
                                                        break;
                                                    case 'seleccionado':
                                                        echo '<span class="badge bg-success">Seleccionado</span>';
                                                        break;
                                                    case 'rechazado':
                                                        echo '<span class="badge bg-danger">No seleccionado</span>';
                                                        break;
                                                    default:
                                                        echo '<span class="badge bg-secondary">Sin estado</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="../ofertas/detalle.php?id=<?= $aplicacion['oferta_id'] ?>" class="btn btn-outline-primary" title="Ver oferta">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($aplicacion['estado'] == 'pendiente'): ?>
                                                    <button type="button" class="btn btn-outline-danger" title="Cancelar aplicación" 
                                                            onclick="confirmarCancelacion(<?= $aplicacion['id'] ?>)">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Paginación -->
                        <?php if ($total_paginas > 1): ?>
                            <nav aria-label="Navegación de páginas" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?= ($pagina_actual <= 1) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?pagina=<?= $pagina_actual - 1 ?><?= !empty($estado_filtro) ? '&estado=' . $estado_filtro : '' ?>">Anterior</a>
                                    </li>
                                    
                                    <?php for($i = 1; $i <= $total_paginas; $i++): ?>
                                        <li class="page-item <?= ($pagina_actual == $i) ? 'active' : '' ?>">
                                            <a class="page-link" href="?pagina=<?= $i ?><?= !empty($estado_filtro) ? '&estado=' . $estado_filtro : '' ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?= ($pagina_actual >= $total_paginas) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?pagina=<?= $pagina_actual + 1 ?><?= !empty($estado_filtro) ? '&estado=' . $estado_filtro : '' ?>">Siguiente</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Consejos para postulaciones -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-lightbulb me-2 text-warning"></i>Consejos para tus postulaciones</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Mejora tus oportunidades:</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check-circle text-success me-2"></i>Mantén tu CV actualizado</li>
                                <li><i class="fas fa-check-circle text-success me-2"></i>Personaliza tu carta de presentación</li>
                                <li><i class="fas fa-check-circle text-success me-2"></i>Destaca tus habilidades relevantes</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Preparación para entrevistas:</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check-circle text-success me-2"></i>Investiga sobre la empresa</li>
                                <li><i class="fas fa-check-circle text-success me-2"></i>Practica preguntas comunes</li>
                                <li><i class="fas fa-check-circle text-success me-2"></i>Prepara tus propias preguntas</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para confirmar cancelación -->
<div class="modal fade" id="cancelarAplicacionModal" tabindex="-1" aria-labelledby="cancelarAplicacionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelarAplicacionModalLabel">Confirmar cancelación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro que deseas cancelar esta aplicación?</p>
                <p class="text-muted small">Esta acción no se puede deshacer. Tendrás que volver a aplicar si cambias de opinión.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <form id="formCancelarAplicacion" action="cancelar_aplicacion.php" method="POST">
                    <input type="hidden" id="aplicacion_id" name="aplicacion_id" value="">
                    <button type="submit" class="btn btn-danger">Cancelar aplicación</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Función para confirmar cancelación de aplicación
function confirmarCancelacion(aplicacionId) {
    document.getElementById('aplicacion_id').value = aplicacionId;
    var modal = new bootstrap.Modal(document.getElementById('cancelarAplicacionModal'));
    modal.show();
}
</script>

<?php include '../includes/footer.php'; ?>