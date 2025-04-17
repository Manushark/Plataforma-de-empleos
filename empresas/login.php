<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// Verificar si ya está logueado
if (isLoggedIn()) {
    redirectByUserType();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger datos del formulario
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    // Validar campos requeridos
    if (empty($email)) {
        $errors[] = "El correo electrónico es obligatorio";
    }
    
    if (empty($password)) {
        $errors[] = "La contraseña es obligatoria";
    }
    
    // Si no hay errores, verificar credenciales
    if (empty($errors)) {
        $sql = "SELECT id, nombre, password FROM empresas WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            
            if (password_verify($password, $row['password'])) {
                // Credenciales válidas, iniciar sesión
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_type'] = 'empresa';
                $_SESSION['nombre'] = $row['nombre'];
                
                // Redireccionar al dashboard
                header('Location: dashboard.php');
                exit();
            } else {
                $errors[] = "Contraseña incorrecta";
            }
        } else {
            $errors[] = "Correo electrónico no registrado";
        }
    }
}

// Incluir el encabezado
include_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0">Iniciar Sesión como Empresa</h3>
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
                    
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-success">Iniciar Sesión</button>
                        
                        <div class="mt-3">
                            <p>¿No tienes una cuenta? <a href="registro.php">Registrarse</a></p>
                            <p>¿Buscas empleo? <a href="../candidatos/login.php">Iniciar sesión como candidato</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>