<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireAuth();

if (!hasRole('administrador') && !hasRole('bibliotecario')) {
    header('Location: ../dashboard.php?error=no_permission');
    exit();
}

$errors = [];
$success = false;

if ($_POST) {
    $titulo = trim($_POST['titulo'] ?? '');
    $autor = trim($_POST['autor'] ?? '');
    $editorial = trim($_POST['editorial'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $categoria_id = intval($_POST['categoria_id'] ?? 0);
    $cantidad_total = intval($_POST['cantidad_total'] ?? 1);
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    
    // Validaciones
    if (empty($titulo)) {
        $errors[] = 'El título es obligatorio';
    }
    if (empty($autor)) {
        $errors[] = 'El autor es obligatorio';
    }
    if ($cantidad_total < 1) {
        $errors[] = 'La cantidad debe ser mayor a 0';
    }
    
    if (empty($errors)) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Verificar si el ISBN ya existe
            if (!empty($isbn)) {
                $stmt = $db->prepare("SELECT id FROM libros WHERE isbn = :isbn AND activo = 1");
                $stmt->bindParam(':isbn', $isbn);
                $stmt->execute();
                if ($stmt->rowCount() > 0) {
                    $errors[] = 'Ya existe un libro con este ISBN';
                }
            }
            
            if (empty($errors)) {
                $query = "INSERT INTO libros (titulo, autor, editorial, isbn, categoria_id, cantidad_total, cantidad_disponible, ubicacion, descripcion) 
                         VALUES (:titulo, :autor, :editorial, :isbn, :categoria_id, :cantidad_total, :cantidad_disponible, :ubicacion, :descripcion)";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':titulo', $titulo);
                $stmt->bindParam(':autor', $autor);
                $stmt->bindParam(':editorial', $editorial);
                $stmt->bindParam(':isbn', $isbn);
                $stmt->bindParam(':categoria_id', $categoria_id);
                $stmt->bindParam(':cantidad_total', $cantidad_total);
                $stmt->bindParam(':cantidad_disponible', $cantidad_total);
                $stmt->bindParam(':ubicacion', $ubicacion);
                $stmt->bindParam(':descripcion', $descripcion);
                
                if ($stmt->execute()) {
                    header('Location: index.php?success=created');
                    exit();
                } else {
                    $errors[] = 'Error al guardar el libro';
                }
            }
        } catch (Exception $e) {
            $errors[] = 'Error del sistema: ' . $e->getMessage();
        }
    }
}

// Obtener categorías
try {
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->query("SELECT * FROM categorias ORDER BY nombre");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $categorias = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Libro - Sistema Biblioteca</title>
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
                        <i class="fas fa-book-medical"></i> Agregar Nuevo Libro
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
                                    <i class="fas fa-info-circle"></i> Información del Libro
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="libro-form">
                                    <div class="row">
                                        <div class="col-md-8 mb-3">
                                            <label for="titulo" class="form-label">
                                                <i class="fas fa-heading"></i> Título *
                                            </label>
                                            <input type="text" class="form-control" id="titulo" name="titulo" 
                                                   value="<?php echo htmlspecialchars($titulo ?? ''); ?>" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="categoria_id" class="form-label">
                                                <i class="fas fa-tags"></i> Categoría
                                            </label>
                                            <select class="form-select" id="categoria_id" name="categoria_id">
                                                <option value="">Seleccionar categoría</option>
                                                <?php foreach ($categorias as $categoria): ?>
                                                    <option value="<?php echo $categoria['id']; ?>"
                                                            <?php echo (isset($categoria_id) && $categoria_id == $categoria['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="autor" class="form-label">
                                                <i class="fas fa-user-edit"></i> Autor *
                                            </label>
                                            <input type="text" class="form-control" id="autor" name="autor" 
                                                   value="<?php echo htmlspecialchars($autor ?? ''); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="editorial" class="form-label">
                                                <i class="fas fa-building"></i> Editorial
                                            </label>
                                            <input type="text" class="form-control" id="editorial" name="editorial" 
                                                   value="<?php echo htmlspecialchars($editorial ?? ''); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="isbn" class="form-label">
                                                <i class="fas fa-barcode"></i> ISBN
                                            </label>
                                            <input type="text" class="form-control" id="isbn" name="isbn" 
                                                   value="<?php echo htmlspecialchars($isbn ?? ''); ?>"
                                                   placeholder="978-3-16-148410-0">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="cantidad_total" class="form-label">
                                                <i class="fas fa-sort-numeric-up"></i> Cantidad Total *
                                            </label>
                                            <input type="number" class="form-control" id="cantidad_total" name="cantidad_total" 
                                                   value="<?php echo htmlspecialchars($cantidad_total ?? 1); ?>" min="1" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="ubicacion" class="form-label">
                                                <i class="fas fa-map-marker-alt"></i> Ubicación
                                            </label>
                                            <input type="text" class="form-control" id="ubicacion" name="ubicacion" 
                                                   value="<?php echo htmlspecialchars($ubicacion ?? ''); ?>"
                                                   placeholder="Ej: Estante A-1">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="descripcion" class="form-label">
                                            <i class="fas fa-align-left"></i> Descripción
                                        </label>
                                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"
                                                  placeholder="Descripción opcional del libro"><?php echo htmlspecialchars($descripcion ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="index.php" class="btn btn-secondary me-md-2">
                                            <i class="fas fa-times"></i> Cancelar
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Guardar Libro
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
                                        <small>El ISBN debe ser único en el sistema</small>
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success"></i>
                                        <small>La cantidad disponible será igual a la cantidad total inicialmente</small>
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success"></i>
                                        <small>Puedes agregar una categoría desde el menú de configuración</small>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-question-circle"></i> ¿Necesitas ayuda?
                                </h6>
                            </div>
                            <div class="card-body">
                                <p class="card-text small">
                                    Si tienes problemas para agregar un libro, contacta al administrador del sistema.
                                </p>
                                <a href="../ayuda.php" class="btn btn-sm btn-outline-info">
                                    <i class="fas fa-book-open"></i> Ver manual
                                </a>
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
        document.getElementById('libro-form').addEventListener('submit', function(e) {
            if (!validateForm('libro-form')) {
                e.preventDefault();
                showNotification('Por favor complete todos los campos obligatorios', 'warning');
            }
        });
        
        // Formatear ISBN mientras se escribe
        document.getElementById('isbn').addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^\d]/g, '');
            if (value.length >= 3) {
                value = value.substring(0,3) + '-' + value.substring(3);
            }
            if (value.length >= 5) {
                value = value.substring(0,5) + '-' + value.substring(5);
            }
            if (value.length >= 8) {
                value = value.substring(0,8) + '-' + value.substring(8);
            }
            if (value.length >= 15) {
                value = value.substring(0,15) + '-' + value.substring(15);
            }
            e.target.value = value;
        });
    </script>
</body>
</html>
