<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

iniciarSesion();

// Redireccionar si ya está autenticado
if (estaAutenticado()) {
    header('Location: index.php');
    exit;
}

$errores = [];
$email = '';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conexion = conectarDB();
    
    $email = limpiarDatos($_POST['email']);
    $password = $_POST['password'];
    
    // Validaciones básicas
    if (empty($email)) {
        $errores[] = "El correo electrónico es obligatorio";
    }
    
    if (empty($password)) {
        $errores[] = "La contraseña es obligatoria";
    }
    
    // Si no hay errores, intentar autenticar
    if (empty($errores)) {
        $query = "SELECT id, email, password, tipo FROM usuarios WHERE email = ?";
        $stmt = $conexion->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows === 1) {
            $usuario = $resultado->fetch_assoc();
            
            // Verificar contraseña
            if (password_verify($password, $usuario['password'])) {
                // Iniciar sesión
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['email'] = $usuario['email'];
                $_SESSION['tipo_usuario'] = $usuario['tipo'];
                
                // Mensaje de éxito y redirección
                mostrarNotificacion("Inicio de sesión exitoso", "success");
                
                // Redireccionar según el tipo de usuario
                if ($usuario['tipo'] === 'candidato') {
                    header('Location: candidatos/index.php');
                } else {
                    header('Location: empresas/index.php');
                }
                exit;
            } else {
                $errores[] = "Contraseña incorrecta";
            }
        } else {
            $errores[] = "Este correo no está registrado";
        }
    }
    
    $conexion->close();
}

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="card-title mb-0">Iniciar Sesión</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($errores)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errores as $error): ?>
                                <li><?= $error ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="login.php">
                    <div class="mb-3">
                        <label for="email" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= sanitizarHTML($email) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="recordarme" name="recordarme">
                        <label class="form-check-label" for="recordarme">
                            Recordarme
                        </label>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
                    </div>
                </form>
                
                <div class="mt-3 text-center">
                    ¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a>
                </div>
                <div class="mt-2 text-center">
                    <a href="recuperar_password.php">¿Olvidaste tu contraseña?</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>