<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Parámetros de búsqueda y filtros
$busqueda = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';
$ubicacion = isset($_GET['ubicacion']) ? sanitizeInput($_GET['ubicacion']) : '';
$categoria = isset($_GET['categoria']) ? sanitizeInput($_GET['categoria']) : '';
$tipo_contrato = isset($_GET['tipo_contrato']) ? sanitizeInput($_GET['tipo_contrato']) : '';
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$ofertas_por_pagina = 10;

// Crear conexión
$conn = conectarDB();

// Obtener ofertas filtradas y paginadas
$ofertas = getOfertasFiltradas($conn, $busqueda, $ubicacion, $categoria, $tipo_contrato, $pagina, $ofertas_por_pagina);

// Obtener el total de ofertas para la paginación
$total_ofertas = getTotalOfertasFiltradas($conn, $busqueda, $ubicacion, $categoria, $tipo_contrato);
$total_paginas = ceil($total_ofertas / $ofertas_por_pagina);

// Obtener categorías para filtros
$categorias = getCategorias($conn);

// Incluir el encabezado
include_once 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <!-- Barra lateral con filtros -->
        <div class="col-lg-3">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Filtros de Búsqueda</h5>
                </div>
                <div class="card-body">
                    <form action="ofertas.php" method="GET">
                        <div class="mb-3">
                            <label for="q" class="form-label">Palabras Clave</label>
                            <input type="text" class="form-control" id="q" name="q" value="<?php echo $busqueda; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="ubicacion" class="form-label">Ubicación</label>
                            <input type="text" class="form-control" id="ubicacion" name="ubicacion" value="<?php echo $ubicacion; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="categoria" class="form-label">Categoría</label>
                            <select class="form-select" id="categoria" name="categoria">
                                <option value="">Todas las categorías</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo ($categoria == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo $cat['nombre']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tipo_contrato" class="form-label">Tipo de Contrato</label>
                            <select class="form-select" id="tipo_contrato" name="tipo_contrato">
                                <option value="">Todos</option>
                                <option value="Tiempo completo" <?php echo ($tipo_contrato == 'Tiempo completo') ? 'selected' : ''; ?>>Tiempo completo</option>
                                <option value="Medio tiempo" <?php echo ($tipo_contrato == 'Medio tiempo') ? 'selected' : ''; ?>>Medio tiempo</option>
                                <option value="Freelance" <?php echo ($tipo_contrato == 'Freelance') ? 'selected' : ''; ?>>Freelance</option>
                                <option value="Prácticas" <?php echo ($tipo_contrato == 'Prácticas') ? 'selected' : ''; ?>>Prácticas</option>
                                <option value="Temporal" <?php echo ($tipo_contrato == 'Temporal') ? 'selected' : ''; ?>>Temporal</option>
                            </select>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Filtrar</button>
                            <a href="ofertas.php" class="btn btn-outline-secondary mt-2">Limpiar Filtros</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Listado de ofertas -->
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Ofertas de Empleo</h4>
                    <span class="badge bg-light text-dark"><?php echo $total_ofertas; ?> ofertas encontradas</span>
                </div>
                <div class="card-body">
                    <?php if (count($ofertas) > 0): ?>
                        <?php foreach ($ofertas as $oferta): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <h5 class="card-title"><?php echo $oferta['titulo']; ?></h5>
                                            <h6 class="card-subtitle mb-2 text-muted"><?php echo $oferta['empresa_nombre']; ?></h6>
                                            <p class="mb-2">
                                                <i class="fas fa-map-marker-alt text-primary me-2"></i> <?php echo $oferta['ubicacion']; ?>
                                                <span class="ms-3"><i class="fas fa-briefcase text-primary me-2"></i> <?php echo $oferta['tipo_contrato']; ?></span>
                                            </p>
                                            <p class="card-text"><?php echo substr($oferta['descripcion'], 0, 150) . '...'; ?></p>
                                        </div>
                                        <div class="col-md-4 text-end d-flex flex-column justify-content-between">
                                            <div>
                                                <span class="badge bg-info mb-2"><?php echo $oferta['categoria_nombre']; ?></span>
                                                <?php if (!empty($oferta['salario'])): ?>
                                                    <p class="text-success fw-bold mb-2"><?php echo $oferta['salario']; ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <a href="/plataforma-de-empleos/detalle-oferta.php?id=<?php echo $oferta['id']; ?>" class="btn btn-primary">Ver Detalles</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer text-muted">
                                    <small>Publicada: <?php echo formatearFecha($oferta['fecha_publicacion'], 'd/m/Y'); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Paginación -->
                        <?php if ($total_paginas > 1): ?>
                            <nav aria-label="Paginación de ofertas">
                                <ul class="pagination justify-content-center">
                                    <?php if ($pagina > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?q=<?php echo $busqueda; ?>&ubicacion=<?php echo $ubicacion; ?>&categoria=<?php echo $categoria; ?>&tipo_contrato=<?php echo $tipo_contrato; ?>&pagina=<?php echo $pagina - 1; ?>">Anterior</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                        <li class="page-item <?php echo ($i == $pagina) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?q=<?php echo $busqueda; ?>&ubicacion=<?php echo $ubicacion; ?>&categoria=<?php echo $categoria; ?>&tipo_contrato=<?php echo $tipo_contrato; ?>&pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($pagina < $total_paginas): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?q=<?php echo $busqueda; ?>&ubicacion=<?php echo $ubicacion; ?>&categoria=<?php echo $categoria; ?>&tipo_contrato=<?php echo $tipo_contrato; ?>&pagina=<?php echo $pagina + 1; ?>">Siguiente</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="alert alert-info">
                            <h5 class="alert-heading">No se encontraron ofertas</h5>
                            <p>No se encontraron ofertas que coincidan con los criterios de búsqueda. Prueba ajustando los filtros o realiza una nueva búsqueda.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>