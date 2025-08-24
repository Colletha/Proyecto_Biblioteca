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

// No permitir eliminar el propio usuario
if ($id == $_SESSION['user_id']) {
    header('Location: index.php?error=cannot_delete_self');
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar si el usuario existe y obtener información
    $stmt = $db->prepare("SELECT u.*, r.nombre as rol_nombre, COUNT(p.id) as prestamos_activos 
                         FROM usuarios u 
                         JOIN roles r ON u.rol_id = r.id
                         LEFT JOIN prestamos p ON u.id = p.usuario_id AND p.estado = 'activo'
                         WHERE u.id = :id AND u.activo = 1 
                         GROUP BY u.id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        header('Location: index.php?error=not_found');
        exit();
    }
    
    // Verificar si tiene préstamos activos
    if ($usuario['prestamos_activos'] > 0) {
        header('Location: index.php?error=has_loans');
        exit();
    }
    
    if ($_POST && isset($_POST['confirmar'])) {
        // Eliminar lógicamente el usuario
        $stmt = $db->prepare("UPDATE usuarios SET activo = 0, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            header('Location: index.php?success=deleted');
            exit();
        } else {
            $error = 'Error al eliminar el usuario';
        }
    }
    
} catch (Exception $e) {
    header('Location: index.php?error=system');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar Usuario - Sistema Biblioteca</title>
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
                        <i class="fas fa-user-times"></i> Eliminar Usuario
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Volver a la lista
                        </a>
                    </div>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card border-danger">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-warning" role="alert">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>¡Atención!</strong> Esta acción no se puede deshacer. El usuario será eliminado permanentemente del sistema.
                                </div>
                                
                                <h6>¿Está seguro de que desea eliminar el siguiente usuario?</h6>
                                
                                <div class="card mt-3">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-md-2 text-center">
                                                <div class="avatar-circle-large">
                                                    <?php echo strtoupper(substr($usuario['nombre'], 0, 1) . substr($usuario['apellido'], 0, 1)); ?>
                                                </div>
                                            </div>
                                            <div class="col-md-10">
                                                <h5 class="card-title"><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></h5>
                                                <p class="card-text">
                                                    <strong>Email:</strong> <?php echo htmlspecialchars($usuario['email']); ?><br>
                                                    <strong>Carné:</strong> <?php echo htmlspecialchars($usuario['carne'] ?? 'N/A'); ?><br>
                                                    <strong>Rol:</strong> 
                                                    <span class="badge <?php 
                                                        echo $usuario['rol_nombre'] === 'administrador' ? 'bg-danger' : 
                                                            ($usuario['rol_nombre'] === 'bibliotecario' ? 'bg-warning' : 'bg-info'); 
                                                    ?>">
                                                        <?php echo ucfirst($usuario['rol_nombre']); ?>
                                                    </span><br>
                                                    <strong>Miembro desde:</strong> <?php echo date('d/m/Y', strtotime($usuario['created_at'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <form method="POST" class="mt-4">
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="index.php" class="btn btn-secondary me-md-2">
                                            <i class="fas fa-times"></i> Cancelar
                                        </a>
                                        <button type="submit" name="confirmar" value="1" class="btn btn-danger">
                                            <i class="fas fa-user-times"></i> Sí, Eliminar Usuario
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <style>
        .avatar-circle-large {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 24px;
            margin: 0 auto;
        }
    </style>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
