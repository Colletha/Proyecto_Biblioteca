<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireAuth();

if (!hasRole('administrador')) {
    header('Location: ../dashboard.php?error=no_permission');
    exit();
}

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header('Location: index.php');
    exit();
}

$errors = [];
$usuario = null;

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener usuario
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = :id AND activo = 1");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        header('Location: index.php?error=not_found');
        exit();
    }
    
} catch (Exception $e) {
    header('Location: index.php?error=system');
    exit();
}

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
    if (!empty($password)) {
        if (strlen($password) < 6) {
            $errors[] = 'La contraseña debe tener al menos 6 caracteres';
        }
        if ($password !== $confirm_password) {
            $errors[] = 'Las contraseñas no coinciden';
        }
    }
    if ($rol_id <= 0) {
        $errors[] = 'Debe seleccionar un rol';
    }
    
    if (empty($errors)) {
        try {
            // Verificar email duplicado (excluyendo el usuario actual)
            $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = :email AND id != :id AND activo = 1");
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $errors[] = 'Ya existe otro usuario con este email';
            }
            
            // Verificar carné duplicado (si se proporcionó)
            if (!empty($carne)) {
                $stmt = $db->prepare("SELECT id FROM usuarios WHERE carne = :carne AND id != :id AND activo = 1");
                $stmt->bindParam(':carne', $carne);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                if ($stmt->rowCount() > 0) {
                    $errors[] = 'Ya existe otro usuario con este carné';
                }
            }
            
            if (empty($errors)) {
                if (!empty($password)) {
                    // Actualizar con nueva contraseña
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $query = "UPDATE usuarios SET 
                             nombre = :nombre, 
                             apellido = :apellido, 
                             email = :email, 
                             carne = :carne, 
                             password = :password, 
                             rol_id = :rol_id,
                             updated_at = CURRENT_TIMESTAMP
                             WHERE id = :id";
                    
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':password', $hashed_password);
                } else {
                    // Actualizar sin cambiar contraseña
                    $query = "UPDATE usuarios SET 
                             nombre = :nombre, 
                             apellido = :apellido, 
                             email = :email, 
                             carne = :carne, 
                             rol_id = :rol_id,
                             updated_at = CURRENT_TIMESTAMP
                             WHERE id = :id";
                    
                    $stmt = $db->prepare($query);
                }
                
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':apellido', $apellido);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':carne', $carne);
                $stmt->bindParam(':rol_id', $rol_id);
                $stmt->bindParam(':id', $id);
                
                if ($stmt->execute()) {
                    header('Location: index.php?success=updated');
                    exit();
                } else {
                    $errors[] = 'Error al actualizar el usuario';
                }
            }
        } catch (Exception $e) {
            $errors[] = 'Error del sistema: ' . $e->getMessage();
        }
    }
} else {
    // Cargar datos del usuario en las variables
    $nombre = $usuario['nombre'];
    $apellido = $usuario['apellido'];
    $email = $usuario['email'];
    $carne = $usuario['carne'];
    $rol_id = $usuario['rol_id'];
}

// Obtener roles
try {
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
    <title>Editar Usuario - Sistema Biblioteca</title>
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
                        <i class="fas fa-user-edit"></i> Editar Usuario
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="ver.php?id=<?php echo $id; ?>" class="btn btn-outline-info me-2">
                            <i class="fas fa-eye"></i> Ver detalles
                        </a>
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
                                                   value="<?php echo htmlspecialchars($nombre); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="apellido" class="form-label">
                                                <i class="fas fa-user"></i> Apellido *
                                            </label>
                                            <input type="text" class="form-control" id="apellido" name="apellido" 
                                                   value="<?php echo htmlspecialchars($apellido); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-8 mb-3">
                                            <label for="email" class="form-label">
                                                <i class="fas fa-envelope"></i> Email *
                                            </label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($email); ?>" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="carne" class="form-label">
                                                <i class="fas fa-id-card"></i> Carné
                                            </label>
                                            <input type="text" class="form-control" id="carne" name="carne" 
                                                   value="<?php echo htmlspecialchars($carne); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="password" class="form-label">
                                                <i class="fas fa-lock"></i> Nueva Contraseña
                                            </label>
                                            <input type="password" class="form-control" id="password" name="password" 
                                                   minlength="6">
                                            <div class="form-text">Dejar en blanco para mantener la contraseña actual</div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="confirm_password" class="form-label">
                                                <i class="fas fa-lock"></i> Confirmar Nueva Contraseña
                                            </label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                                   minlength="6">
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
                                                        <?php echo $rol_id == $rol['id'] ? 'selected' : ''; ?>>
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
                                            <i class="fas fa-save"></i> Actualizar Usuario
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
                                    <i class="fas fa-info-circle"></i> Estado Actual
                                </h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <strong>Usuario ID:</strong> <?php echo $usuario['id']; ?>
                                    </li>
                                    <li class="mb-2">
                                        <strong>Estado:</strong> 
                                        <span class="badge <?php echo $usuario['activo'] ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo $usuario['activo'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </li>
                                    <li class="mb-2">
                                        <strong>Creado:</strong> <?php echo date('d/m/Y H:i', strtotime($usuario['created_at'])); ?>
                                    </li>
                                    <?php if ($usuario['updated_at'] != $usuario['created_at']): ?>
                                    <li class="mb-2">
                                        <strong>Actualizado:</strong> <?php echo date('d/m/Y H:i', strtotime($usuario['updated_at'])); ?>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                        
                        <?php if ($id == $_SESSION['user_id']): ?>
                        <div class="alert alert-info mt-3" role="alert">
                            <i class="fas fa-info-circle"></i>
                            <strong>Nota:</strong> Estás editando tu propio perfil. Los cambios se aplicarán inmediatamente.
                        </div>
                        <?php endif; ?>
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
            
            if (password && password !== confirmPassword) {
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
            
            if (password && confirmPassword && password !== confirmPassword) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    </script>
</body>
</html>
