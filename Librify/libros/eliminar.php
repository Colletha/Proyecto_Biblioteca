<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireAuth();

if (!hasRole('administrador') && !hasRole('bibliotecario')) {
    header('Location: ../dashboard.php?error=no_permission');
    exit();
}

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header('Location: index.php');
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar si el libro existe y obtener información
    $stmt = $db->prepare("SELECT l.*, COUNT(p.id) as prestamos_activos 
                         FROM libros l 
                         LEFT JOIN prestamos p ON l.id = p.libro_id AND p.estado = 'activo'
                         WHERE l.id = :id AND l.activo = 1 
                         GROUP BY l.id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $libro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$libro) {
        header('Location: index.php?error=not_found');
        exit();
    }
    
    // Verificar si tiene préstamos activos
    if ($libro['prestamos_activos'] > 0) {
        header('Location: index.php?error=has_loans');
        exit();
    }
    
    if ($_POST && isset($_POST['confirmar'])) {
        // Eliminar lógicamente el libro
        $stmt = $db->prepare("UPDATE libros SET activo = 0, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            header('Location: index.php?success=deleted');
            exit();
        } else {
            $error = 'Error al eliminar el libro';
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
    <title>Eliminar Libro - Sistema Biblioteca</title>
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
                        <i class="fas fa-trash"></i> Eliminar Libro
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
                                    <strong>¡Atención!</strong> Esta acción no se puede deshacer. El libro será eliminado permanentemente del sistema.
                                </div>
                                
                                <h6>¿Está seguro de que desea eliminar el siguiente libro?</h6>
                                
                                <div class="card mt-3">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <h5 class="card-title"><?php echo htmlspecialchars($libro['titulo']); ?></h5>
                                                <p class="card-text">
                                                    <strong>Autor:</strong> <?php echo htmlspecialchars($libro['autor']); ?><br>
                                                    <strong>Editorial:</strong> <?php echo htmlspecialchars($libro['editorial'] ?? 'N/A'); ?><br>
                                                    <strong>ISBN:</strong> <?php echo htmlspecialchars($libro['isbn'] ?? 'N/A'); ?>
                                                </p>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <div class="mb-2">
                                                    <span class="badge bg-primary">Total: <?php echo $libro['cantidad_total']; ?></span>
                                                </div>
                                                <div class="mb-2">
                                                    <span class="badge bg-success">Disponibles: <?php echo $libro['cantidad_disponible']; ?></span>
                                                </div>
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
                                            <i class="fas fa-trash"></i> Sí, Eliminar Libro
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
