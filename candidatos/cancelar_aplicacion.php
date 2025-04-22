<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// Verificar que sea un candidato
requiereCandidato();

// Obtener ID de la aplicación a cancelar
$aplicacion_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$usuario_id = $_SESSION['usuario_id'];
$mensaje = '';
$error = '';

// Verificar si existe la aplicación y pertenece al candidato
$conexion = conectarDB();

// Primero obtener el ID del candidato
$query = "SELECT id FROM candidatos WHERE usuario_id = ?";
$stmt = $conexion->prepare($query);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows == 0) {
    $error = "No se encontró el candidato asociado a esta cuenta.";
} else {
    $candidato = $resultado->fetch_assoc();
    $candidato_id = $candidato['id'];
    
    // Verificar que la aplicación pertenezca al candidato
    $query = "SELECT a.*, o.titulo, e.nombre as empresa_nombre 
              FROM aplicaciones a
              JOIN ofertas o ON a.oferta_id = o.id
              JOIN empresas e ON o.empresa_id = e.id
              WHERE a.id = ? AND a.candidato_id = ?";
    
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("ii", $aplicacion_id, $candidato_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows == 0) {
        $error = "La aplicación no existe o no tienes permiso para cancelarla.";
    } else {
        $aplicacion = $resultado->fetch_assoc();
        
        // Solo permitir cancelar si está en estado 'pendiente' o 'revisado'
        if ($aplicacion['estado'] == 'pendiente' || $aplicacion['estado'] == 'revisado') {
            
            // Si se confirma la cancelación
            if (isset($_POST['confirmar_cancelacion']) && $_POST['confirmar_cancelacion'] == 'si') {
                // Actualizar el estado a 'cancelado'
                $query = "UPDATE aplicaciones SET estado = 'cancelado' WHERE id = ?";
                $stmt = $conexion->prepare($query);
                $stmt->bind_param("i", $aplicacion_id);
                
                if ($stmt->execute()) {
                    $mensaje = "La aplicación ha sido cancelada correctamente.";
                    
                    // Registrar la actividad
                    $actividad = "Cancelación de aplicación para la oferta: " . $aplicacion['titulo'];
                    registrarActividad($usuario_id, 'cancelacion_aplicacion', $actividad);
                    
                    // Redirigir después de 2 segundos
                    header("Refresh:2; URL=mis_aplicaciones.php");
                } else {
                    $error = "Error al cancelar la aplicación: " . $conexion->error;
                }
            }
        } else {
            $error = "No se puede cancelar esta aplicación porque ya está en proceso avanzado.";
        }
    }
}

// Incluir la cabecera
include '../includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Cancelar aplicación</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?= $error ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="mis_aplicaciones.php" class="btn btn-secondary">Volver a mis aplicaciones</a>
                        </div>
                    <?php elseif ($mensaje): ?>
                        <div class="alert alert-success">
                            <?= $mensaje ?>
                        </div>
                        <div class="text-center mt-3">
                            <p>Redirigiendo a tus aplicaciones...</p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <h5 class="alert-heading">¿Estás seguro de que deseas cancelar esta aplicación?</h5>
                            <p>Estás a punto de cancelar tu aplicación para el puesto:</p>
                            <p class="mb-0"><strong><?= sanitizarHTML($aplicacion['titulo']) ?></strong> en <strong><?= sanitizarHTML($aplicacion['empresa_nombre']) ?></strong></p>
                        </div>
                        
                        <p class="text-muted">Una vez cancelada, tu aplicación ya no será considerada para el proceso de selección. Esta acción no se puede deshacer.</p>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="confirmar_cancelacion" value="si">
                            <div class="d-flex justify-content-center gap-3 mt-4">
                                <a href="mis_aplicaciones.php" class="btn btn-secondary">No, volver a mis aplicaciones</a>
                                <button type="submit" class="btn btn-danger">Sí, cancelar aplicación</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$conexion->close();
include '../includes/footer.php'; 
?>