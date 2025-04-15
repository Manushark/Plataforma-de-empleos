<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Obtener ofertas recientes para mostrar en la página principal
$ofertas_recientes = getActiveOfertas($conn, 8);

// Incluir el encabezado
include_once 'includes/header.php';

// Si el usuario está logueado, obtener su tipo
$user_type = getUserType();
?>

<!-- Banner principal -->
<section class="bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold">Encuentra el trabajo de tus sueños</h1>
                <p class="lead mb-4">Conectamos talento con oportunidades. Miles de empresas buscan profesionales como tú.</p>
                
                <?php if (!isLoggedIn()): ?>
                    <div class="d-flex gap-3">
                        <a href="candidatos/registro.php" class="btn btn-light btn-lg">Registrarse</a>
                        <a href="candidatos/login.php" class="btn btn-outline-light btn-lg">Iniciar Sesión</a>
                    </div>
                <?php elseif ($user_type === 'candidato'): ?>
                    <a href="candidatos/buscar_ofertas.php" class="btn btn-light btn-lg">Explorar Ofertas</a>
                <?php elseif ($user_type === 'empresa'): ?>
                    <a href="empresas/publicar_oferta.php" class="btn btn-light btn-lg">Publicar Oferta</a>
                <?php endif; ?>
            </div>
            <div class="col-lg-6 d-none d-lg-block">
                <img src="assets/img/hero-image.svg" alt="Encuentra trabajo" class="img-fluid">
            </div>
        </div>
    </div>
</section>

<!-- Búsqueda rápida -->
<section class="py-4 bg-light">
    <div class="container">
        <form action="candidatos/buscar_ofertas.php" method="GET">
            <div class="row g-3">
                <div class="col-md-5">
                    <input type="text" class="form-control form-control-lg" name="q" placeholder="Puesto, empresa o palabra clave">
                </div>
                <div class="col-md-5">
                    <input type="text" class="form-control form-control-lg" name="ubicacion" placeholder="Ciudad o provincia">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-lg w-100">Buscar</button>
                </div>
            </div>
        </form>
    </div>
</section>

<!-- Ofertas destacadas -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-4">Ofertas de empleo destacadas</h2>
        
        <?php if (!empty($ofertas_recientes)): ?>
            <div class="row">
                <?php foreach ($ofertas_recientes as $oferta): ?>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $oferta['titulo']; ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted"><?php echo $oferta['empresa_nombre']; ?></h6>
                                <p class="card-text"><?php echo substr($oferta['descripcion'], 0, 100) . '...'; ?></p>
                            </div>
                            <div class="card-footer bg-white">
                                <a href="ofertas/detalle.php?id=<?php echo $oferta['id']; ?>" class="btn btn-sm btn-outline-primary">Ver Detalles</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-4">
                <a href="candidatos/buscar_ofertas.php" class="btn btn-primary">Ver Todas las Ofertas</a>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center">
                No hay ofertas disponibles en este momento.
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Para candidatos -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 order-lg-2">
                <img src="assets/img/candidates.svg" alt="Para candidatos" class="img-fluid">
            </div>
            <div class="col-lg-6 order-lg-1">
                <h2>Para candidatos</h2>
                <p class="lead">Encuentra las mejores oportunidades laborales y haz crecer tu carrera profesional.</p>
                
                <ul class="list-unstyled mt-4">
                    <li class="d-flex align-items-center mb-3">
                        <span class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                            <i class="fas fa-check"></i>
                        </span>
                        <span>Crea tu perfil profesional completo</span>
                    </li>
                    <li class="d-flex align-items-center mb-3">
                        <span class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                            <i class="fas fa-check"></i>
                        </span>
                        <span>Aplica a ofertas con un solo clic</span>
                    </li>
                    <li class="d-flex align-items-center mb-3">
                        <span class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                            <i class="fas fa-check"></i>
                        </span>
                        <span>Seguimiento de tus postulaciones</span>
                    </li>
                </ul>
                
                <?php if (!isLoggedIn()): ?>
                    <a href="candidatos/registro.php" class="btn btn-primary mt-3">Regístrate como Candidato</a>
                <?php elseif ($user_type === 'candidato'): ?>
                    <a href="candidatos/dashboard.php" class="btn btn-primary mt-3">Mi Dashboard</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Para empresas -->
<section class="py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <img src="assets/img/companies.svg" alt="Para empresas" class="img-fluid">
            </div>
            <div class="col-lg-6">
                <h2>Para empresas</h2>
                <p class="lead">Encuentra el mejor talento para tu empresa de forma rápida y eficiente.</p>
                
                <ul class="list-unstyled mt-4">
                    <li class="d-flex align-items-center mb-3">
                        <span class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                            <i class="fas fa-check"></i>
                        </span>
                        <span>Publica ofertas de trabajo fácilmente</span>
                    </li>
                    <li class="d-flex align-items-center mb-3">
                        <span class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                            <i class="fas fa-check"></i>
                        </span>
                        <span>Accede a una amplia base de candidatos cualificados</span>
                    </li>
                    <li class="d-flex align-items-center mb-3">
                        <span class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                            <i class="fas fa-check"></i>
                        </span>
                        <span>Gestiona fácilmente los procesos de selección</span>
                    </li>
                </ul>
                
                <?php if (!isLoggedIn()): ?>
                    <a href="empresas/registro.php" class="btn btn-success mt-3">Regístrate como Empresa</a>
                <?php elseif ($user_type === 'empresa'): ?>
                    <a href="empresas/dashboard.php" class="btn btn-success mt-3">Mi Dashboard</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Testimonios -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Lo que dicen nuestros usuarios</h2>
        
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <img src="assets/img/testimonial-1.jpg" alt="Usuario" class="rounded-circle me-3" style="width: 60px; height: 60px; object-fit: cover;">
                            <div>
                                <h5 class="mb-0">Laura Fernández</h5>
                                <p class="text-muted mb-0">Desarrolladora Web</p>
                            </div>
                        </div>
                        <p class="card-text">"Gracias a esta plataforma encontré mi trabajo ideal en menos de un mes. El proceso fue muy sencillo y la interfaz super intuitiva."</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <img src="assets/img/testimonial-2.jpg" alt="Usuario" class="rounded-circle me-3" style="width: 60px; height: 60px; object-fit: cover;">
                            <div>
                                <h5 class="mb-0">Carlos Sánchez</h5>
                                <p class="text-muted mb-0">Director RRHH</p>
                            </div>
                        </div>
                        <p class="card-text">"Como empresa, hemos mejorado significativamente nuestros procesos de reclutamiento. Encontramos candidatos de calidad en tiempo récord."</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <img src="assets/img/testimonial-3.jpg" alt="Usuario" class="rounded-circle me-3" style="width: 60px; height: 60px; object-fit: cover;">
                            <div>
                                <h5 class="mb-0">Ana Martínez</h5>
                                <p class="text-muted mb-0">Diseñadora Gráfica</p>
                            </div>
                        </div>
                        <p class="card-text">"La posibilidad de tener un CV digital completo y poder adjuntar mi portafolio ha hecho que consiga entrevistas en empresas de primer nivel."</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-5 bg-primary text-white text-center">
    <div class="container">
        <h2>¿Listo para dar el siguiente paso en tu carrera?</h2>
        <p class="lead mb-4">Únete a nuestra plataforma y comienza a descubrir oportunidades.</p>
        
        <?php if (!isLoggedIn()): ?>
            <div class="d-flex justify-content-center gap-3">
                <a href="candidatos/registro.php" class="btn btn-light btn-lg">Busco Empleo</a>
                <a href="empresas/registro.php" class="btn btn-outline-light btn-lg">Busco Profesionales</a>
            </div>
        <?php elseif ($user_type === 'candidato'): ?>
            <a href="candidatos/buscar_ofertas.php" class="btn btn-light btn-lg">Explorar Ofertas</a>
        <?php elseif ($user_type === 'empresa'): ?>
            <a href="empresas/publicar_oferta.php" class="btn btn-light btn-lg">Publicar Oferta</a>
        <?php endif; ?>
    </div>
</section>

<?php include_once 'includes/footer.php'; ?>