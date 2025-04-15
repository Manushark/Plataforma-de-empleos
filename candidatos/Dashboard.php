<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// Verificar si el usuario está logueado y es candidato
if (!isLoggedIn() || getUserType() !== 'candidato') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$candidato = getCandidatoInfo($conn, $user_id);
$aplicaciones = getAplicacionesByCandidato($conn, $user_id);
$ofertas_recientes = getActiveOfertas($conn, 5);

$cv_completo = !empty($candidato['formacion_academica']) && 
               !empty($candidato['experiencia_laboral']) && 
               !empty($candidato['habilidades']);

// Incluir el encabezado
include_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <?php if (!empty($candidato['foto'])): ?>
                        <img src="../uploads/fotos/<?php echo $candidato['foto']; ?>" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                    <?php else: ?>
                        <img src="../assets/img/default-user.png" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                    <?php endif; ?>
                    
                    <h4><?php echo $candidato['nombre'] . ' ' . $candidato['apellidos']; ?></h4>
                    <p class="text-muted"><?php echo $candidato['email']; ?></p>
                    
                    <div class="d-grid gap-2">
                        <a href="perfil.php" class="btn btn-primary">Editar Perfil</a>
                    </div>
                </div>
            </div>
            
            <div class="list-group mb-4">
                <a href="dashboard.php" class="list-group-item list-group-item-action active">Dashboard</a>
                <a href="perfil.php" class="list-group-item list-group-item-action">Mi Perfil</a>
                <a href="cv.php" class="list-group-item list-group-item-action">Mi CV</a>
                <a href="mis_aplicaciones.php" class="list-group-item list-group-item-action">Mis Aplicaciones</a>
                <a href="buscar_ofertas.php" class="list-group-item list-group-item-action">Buscar Ofertas</a>
                <a href="../logout.php" class="list-group-item list-group-item-action text-danger">Cerrar Sesión</a>
            </div>
        </div>
        
        <div class="col-md-9">
            <?php if (!$cv_completo): ?>
                <div class="alert alert-warning">
                    <h5><i class="fas fa-exclamation-triangle"></i> Completa tu CV</h5>
                    <p>Para aumentar tus posibilidades de conseguir empleo, completa tu currículum con toda tu información profesional.</p>
                    <a href="cv.php" class="btn btn-warning">Completar CV</a>
                </div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Dashboard</h4>
                </div>
                <div class="card-body">
                    <h5>Bienvenido, <?php echo $candidato['nombre']; ?>!</h5>
                    <p>Desde tu panel de control puedes gestionar tu perfil, actualizar tu CV y ver las ofertas de empleo disponibles.</p>
                    
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="card text-center mb-3">
                                <div class="card-body">
                                    <h1 class="display-4"><?php echo count($aplicaciones); ?></h1>
                                    <p class="text-muted">Aplicaciones</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center mb-3">
                                <div class="card-body">
                                    <h1 class="display-4"><?php echo $cv_completo ? '100%' : '0%'; ?></h1>
                                    <p class="text-muted">Perfil Completo</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center mb-3">
                                <div class="card-body">
                                    <h1 class="display-4"><?php echo count($ofertas_recientes); ?></h1>
                                    <p class="text-muted">Ofertas Recientes</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0">Mis Últimas Aplicaciones</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($aplicaciones)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Empresa</th>
                                        <th>Puesto</th>
                                        <th>Fecha de Aplicación</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($aplicaciones, 0, 5) as $aplicacion): ?>
                                        <tr>
                                            <td><?php echo $aplicacion['empresa_nombre']; ?></td>
                                            <td><?php echo $aplicacion['titulo']; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($aplicacion['fecha_aplicacion'])); ?></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $aplicacion['estado']; ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (count($aplicaciones) > 5): ?>
                            <div class="text-center mt-3">
                                <a href="mis_aplicaciones.php" class="btn btn-outline-primary">Ver todas mis aplicaciones</a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-center">Aún no has aplicado a ninguna oferta de empleo.</p>
                        <div class="text-center">
                            <a href="buscar_ofertas.php" class="btn btn-primary">Explorar Ofertas</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Ofertas Recientes</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($ofertas_recientes)): ?>
                        <div class="row">
                            <?php foreach ($ofertas_recientes as $oferta): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo $oferta['titulo']; ?></h5>
                                            <h6 class="card-subtitle mb-2 text-muted"><?php echo $oferta['empresa_nombre']; ?></h6>
                                            <p class="card-text"><?php echo substr($oferta['descripcion'], 0, 100) . '...'; ?></p>
                                        </div>
                                        <div class="card-footer bg-white">
                                            <a href="../ofertas/detalle.php?id=<?php echo $oferta['id']; ?>" class="btn btn-sm btn-outline-primary">Ver Detalles</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="buscar_ofertas.php" class="btn btn-success">Ver todas las ofertas</a>
                        </div>
                    <?php else: ?>
                        <p class="text-center">No hay ofertas disponibles en este momento.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>