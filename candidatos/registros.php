<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// Verificar si ya está logueado
if (isLoggedIn()) {
    redirectByUserType();
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger datos del formulario
    $nombre = sanitizeInput($_POST['nombre']);
    $apellidos = sanitizeInput($_POST['apellidos']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validar campos requeridos
    if (empty($nombre)) {
        $errors[] = "El nombre es obligatorio";
    }
    
    if (empty($apellidos)) {
        $errors[] = "Los apellidos son obligatorios";
    }
    
    if (empty($email)) {
        $errors[] = "El correo electrónico es obligatorio";
    } elseif (!isValidEmail($email)) {
        $errors[] = "El formato del correo electrónico no es válido";
    }
    
    if (empty($password)) {
        $errors[] = "La contraseña es obligatoria";
    } elseif (strlen($password) < 6) {
        $errors[] = "La contraseña debe tener al menos 6 caracteres";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Las contraseñas no coinciden";
    }
    
    // Verificar si el correo ya está registrado
    if (empty($errors) && emailExists($conn, $email, 'candidatos')) {
        $errors[] = "Este correo electrónico ya está registrado";
    }
    
    // Si no hay errores, registrar al usuario
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO candidatos (nombre, apellidos, email, password) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $nombre, $apellidos, $email, $hashed_password);
        
        if ($stmt->execute()) {
            $success = true;
            
            // Iniciar sesión automáticamente
            $user_id = $stmt->insert_id;
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_type'] = 'candidato';
            $_SESSION['nombre'] = $nombre;
            
            // Redireccionar al dashboard
            header('Location: dashboard.php');
            exit();
        } else {
            $errors[] = "Error al registrar: " . $conn->error;
        }
    }
}

// Incluir el encabezado
include_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Registro de Candidato</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            ¡Registro exitoso! Serás redirigido a tu dashboard...
                        </div>
                    <?php else: ?>
                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nombre" class="form-label">Nombre</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo isset($_POST['nombre']) ? $_POST['nombre'] : ''; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="apellidos" class="form-label">Apellidos</label>
                                    <input type="text" class="form-control" id="apellidos" name="apellidos" value="<?php echo isset($_POST['apellidos']) ? $_POST['apellidos'] : ''; ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Contraseña</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <small class="text-muted">Mínimo 6 caracteres</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Registrarse</button>
                            
                            <div class="mt-3">
                                <p>¿Ya tienes una cuenta? <a href="login.php">Iniciar sesión</a></p>
                                <p>¿Eres una empresa? <a href="../empresas/registro.php">Regístrate como empresa</a></p>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>