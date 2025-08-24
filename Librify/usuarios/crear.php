<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireAuth();

if (!hasRole('administrador')) {
    header('Location: ../dashboard.php?error=no_permission');
    exit();
}

$errors = [];

if ($_POST) {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $carne = trim($_POST['carne'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $rol_id = intval($_POST['rol_id'] ?? 0);
    
    // Validaciones
    if (empty($nombre)) {
        $errors[] = 'El nombre es obligatorio';
    }
    if (empty($apellido)) {
        $errors[] = 'El apellido es obligatorio';
    }
    if (empty($email)) {
        $errors[] = 'El email es obligatorio';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El email no tiene un formato válido';
    }
    if (empty($password)) {
        $errors[] = 'La contraseña es obligatoria';
    } elseif (strlen($password) < 6) {
        $errors[] = 'La contraseña debe tener al menos 6 caracteres';
    }
    if ($password !== $confirm_password) {
        $errors[] = 'Las contraseñas no coinciden';
    }
    if ($rol_id <= 0) {
        $errors[] = 'Debe seleccionar un rol';
    }
    
    if (empty($errors)) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Verificar si el email ya existe
            $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = :email AND activo = 1");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $errors[] = 'Ya existe un usuario con este email';
            }
            
            // Verificar si el carné ya existe (si se proporcionó)
            if (!empty($carne)) {
                $stmt = $db->prepare("SELECT id FROM usuarios WHERE carne = :carne AND activo = 1");
                $stmt->bindParam(':carne', $carne);
                $stmt->execute();
                if ($stmt->rowCount() > 0) {
                    $errors[] = 'Ya existe un usuario con este carné';
                }
            }
            
            if (empty($errors)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $query = "INSERT INTO usuarios (nombre, apellido, email, carne, password, rol_id) 
                         VALUES (:nombre, :apellido, :email, :carne, :password, :rol_id)";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':apellido', $apellido);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':carne', $carne);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':rol_id', $rol_id);
                
                if ($stmt->execute()) {
                    header('Location: index.php?success=created');
                    exit();
                } else {
                    $errors[] = 'Error al guardar el usuario';
                }
            }
        } catch (Exception $e) {
            $errors[] = 'Error del sistema: ' . $e->getMessage();
        }
    }
}

// Obtener roles
try {
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->query("SELECT * FROM roles ORDER BY nombre");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $roles = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Usuario - Sistema Biblioteca</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-user-plus"></i> Agregar Nuevo Usuario
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Volver a la lista
                        </a>
                    </div>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <h6><i class="fas fa-exclamation-triangle"></i> Se encontraron errores:</h6>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-info-circle"></i> Información del Usuario
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="usuario-form">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="nombre" class="form-label">
                                                <i class="fas fa-user"></i> Nombre *
                                            </label>
                                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                                   value="<?php echo htmlspecialchars($nombre ?? ''); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="apellido" class="form-label">
                                                <i class="fas fa-user"></i> Apellido *
                                            </label>
                                            <input type="text" class="form-control" id="apellido" name="apellido" 
                                                   value="<?php echo htmlspecialchars($apellido ?? ''); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-8 mb-3">
                                            <label for="email" class="form-label">
                                                <i class="fas fa-envelope"></i> Email *
                                            </label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="carne" class="form-label">
                                                <i class="fas fa-id-card"></i> Carné
                                            </label>
                                            <input type="text" class="form-control" id="carne" name="carne" 
                                                   value="<?php echo htmlspecialchars($carne ?? ''); ?>"
                                                   placeholder="Ej: 2024001">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="password" class="form-label">
                                                <i class="fas fa-lock"></i> Contraseña *
                                            </label>
                                            <input type="password" class="form-control" id="password" name="password" 
                                                   minlength="6" required>
                                            <div class="form-text">Mínimo 6 caracteres</div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="confirm_password" class="form-label">
                                                <i class="fas fa-lock"></i> Confirmar Contraseña *
                                            </label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                                   minlength="6" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="rol_id" class="form-label">
                                            <i class="fas fa-user-tag"></i> Rol *
                                        </label>
                                        <select class="form-select" id="rol_id" name="rol_id" required>
                                            <option value="">Seleccionar rol</option>
                                            <?php foreach ($roles as $rol): ?>
                                                <option value="<?php echo $rol['id']; ?>"
                                                        <?php echo (isset($rol_id) && $rol_id == $rol['id']) ? 'selected' : ''; ?>>
                                                    <?php echo ucfirst($rol['nombre']); ?>
                                                    <?php if ($rol['descripcion']): ?>
                                                        - <?php echo $rol['descripcion']; ?>
                                                    <?php endif; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="index.php" class="btn btn-secondary me-md-2">
                                            <i class="fas fa-times"></i> Cancelar
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Guardar Usuario
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-info-circle"></i> Roles del Sistema
                                </h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <span class="badge bg-danger">Administrador</span>
                                        <small class="d-block text-muted">Acceso completo al sistema</small>
                                    </li>
                                    <li class="mb-2">
                                        <span class="badge bg-warning">Bibliotecario</span>
                                        <small class="d-block text-muted">Gestión de libros y préstamos</small>
                                    </li>
                                    <li class="mb-2">
                                        <span class="badge bg-info">Alumno</span>
                                        <small class="d-block text-muted">Consulta de libros y préstamos propios</small>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-lightbulb"></i> Consejos
                                </h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success"></i>
                                        <small>Los campos marcados con * son obligatorios</small>
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success"></i>
                                        <small>El email debe ser único en el sistema</small>
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success"></i>
                                        <small>El carné es opcional pero recomendado para estudiantes</small>
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success"></i>
                                        <small>La contraseña se encripta automáticamente</small>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script>
        // Validación del formulario
        document.getElementById('usuario-form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                showNotification('Las contraseñas no coinciden', 'warning');
                return;
            }
            
            if (!validateForm('usuario-form')) {
                e.preventDefault();
                showNotification('Por favor complete todos los campos obligatorios', 'warning');
            }
        });
        
        // Validación en tiempo real de contraseñas
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    </script>
</body>
</html>
