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

$errors = [];
$libro = null;

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener libro
    $stmt = $db->prepare("SELECT * FROM libros WHERE id = :id AND activo = 1");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $libro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$libro) {
        header('Location: index.php?error=not_found');
        exit();
    }
    
} catch (Exception $e) {
    header('Location: index.php?error=system');
    exit();
}

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
    
    // Verificar que la cantidad total no sea menor a los libros prestados
    $prestados = $libro['cantidad_total'] - $libro['cantidad_disponible'];
    if ($cantidad_total < $prestados) {
        $errors[] = "No puedes reducir la cantidad total por debajo de $prestados (libros actualmente prestados)";
    }
    
    if (empty($errors)) {
        try {
            // Verificar ISBN duplicado (excluyendo el libro actual)
            if (!empty($isbn)) {
                $stmt = $db->prepare("SELECT id FROM libros WHERE isbn = :isbn AND id != :id AND activo = 1");
                $stmt->bindParam(':isbn', $isbn);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                if ($stmt->rowCount() > 0) {
                    $errors[] = 'Ya existe otro libro con este ISBN';
                }
            }
            
            if (empty($errors)) {
                // Calcular nueva cantidad disponible
                $nueva_cantidad_disponible = $libro['cantidad_disponible'] + ($cantidad_total - $libro['cantidad_total']);
                
                $query = "UPDATE libros SET 
                         titulo = :titulo, 
                         autor = :autor, 
                         editorial = :editorial, 
                         isbn = :isbn, 
                         categoria_id = :categoria_id, 
                         cantidad_total = :cantidad_total, 
                         cantidad_disponible = :cantidad_disponible, 
                         ubicacion = :ubicacion, 
                         descripcion = :descripcion,
                         updated_at = CURRENT_TIMESTAMP
                         WHERE id = :id";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':titulo', $titulo);
                $stmt->bindParam(':autor', $autor);
                $stmt->bindParam(':editorial', $editorial);
                $stmt->bindParam(':isbn', $isbn);
                $stmt->bindParam(':categoria_id', $categoria_id);
                $stmt->bindParam(':cantidad_total', $cantidad_total);
                $stmt->bindParam(':cantidad_disponible', $nueva_cantidad_disponible);
                $stmt->bindParam(':ubicacion', $ubicacion);
                $stmt->bindParam(':descripcion', $descripcion);
                $stmt->bindParam(':id', $id);
                
                if ($stmt->execute()) {
                    header('Location: index.php?success=updated');
                    exit();
                } else {
                    $errors[] = 'Error al actualizar el libro';
                }
            }
        } catch (Exception $e) {
            $errors[] = 'Error del sistema: ' . $e->getMessage();
        }
    }
} else {
    // Cargar datos del libro en las variables
    $titulo = $libro['titulo'];
    $autor = $libro['autor'];
    $editorial = $libro['editorial'];
    $isbn = $libro['isbn'];
    $categoria_id = $libro['categoria_id'];
    $cantidad_total = $libro['cantidad_total'];
    $ubicacion = $libro['ubicacion'];
    $descripcion = $libro['descripcion'];
}

// Obtener categorías
try {
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
    <title>Editar Libro - Sistema Biblioteca</title>
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
                        <i class="fas fa-edit"></i> Editar Libro
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
                                    <i class="fas fa-info-circle"></i> Información del Libro
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="libro-form">
                                    <!-- El formulario es idéntico al de crear.php pero con los valores precargados -->
                                    <div class="row">
                                        <div class="col-md-8 mb-3">
                                            <label for="titulo" class="form-label">
                                                <i class="fas fa-heading"></i> Título *
                                            </label>
                                            <input type="text" class="form-control" id="titulo" name="titulo" 
                                                   value="<?php echo htmlspecialchars($titulo); ?>" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="categoria_id" class="form-label">
                                                <i class="fas fa-tags"></i> Categoría
                                            </label>
                                            <select class="form-select" id="categoria_id" name="categoria_id">
                                                <option value="">Seleccionar categoría</option>
                                                <?php foreach ($categorias as $categoria): ?>
                                                    <option value="<?php echo $categoria['id']; ?>"
                                                            <?php echo $categoria_id == $categoria['id'] ? 'selected' : ''; ?>>
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
                                                   value="<?php echo htmlspecialchars($autor); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="editorial" class="form-label">
                                                <i class="fas fa-building"></i> Editorial
                                            </label>
                                            <input type="text" class="form-control" id="editorial" name="editorial" 
                                                   value="<?php echo htmlspecialchars($editorial); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="isbn" class="form-label">
                                                <i class="fas fa-barcode"></i> ISBN
                                            </label>
                                            <input type="text" class="form-control" id="isbn" name="isbn" 
                                                   value="<?php echo htmlspecialchars($isbn); ?>">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="cantidad_total" class="form-label">
                                                <i class="fas fa-sort-numeric-up"></i> Cantidad Total *
                                            </label>
                                            <input type="number" class="form-control" id="cantidad_total" name="cantidad_total" 
                                                   value="<?php echo $cantidad_total; ?>" min="<?php echo $libro['cantidad_total'] - $libro['cantidad_disponible']; ?>" required>
                                            <div class="form-text">
                                                Mínimo: <?php echo $libro['cantidad_total'] - $libro['cantidad_disponible']; ?> (libros prestados actualmente)
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="ubicacion" class="form-label">
                                                <i class="fas fa-map-marker-alt"></i> Ubicación
                                            </label>
                                            <input type="text" class="form-control" id="ubicacion" name="ubicacion" 
                                                   value="<?php echo htmlspecialchars($ubicacion); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="descripcion" class="form-label">
                                            <i class="fas fa-align-left"></i> Descripción
                                        </label>
                                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($descripcion); ?></textarea>
                                    </div>
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="index.php" class="btn btn-secondary me-md-2">
                                            <i class="fas fa-times"></i> Cancelar
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Actualizar Libro
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
                                        <strong>Cantidad Total:</strong> <?php echo $libro['cantidad_total']; ?>
                                    </li>
                                    <li class="mb-2">
                                        <strong>Disponibles:</strong> 
                                        <span class="badge <?php echo $libro['cantidad_disponible'] > 0 ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo $libro['cantidad_disponible']; ?>
                                        </span>
                                    </li>
                                    <li class="mb-2">
                                        <strong>Prestados:</strong> 
                                        <span class="badge bg-info">
                                            <?php echo $libro['cantidad_total'] - $libro['cantidad_disponible']; ?>
                                        </span>
                                    </li>
                                    <li class="mb-2">
                                        <strong>Creado:</strong> <?php echo date('d/m/Y', strtotime($libro['created_at'])); ?>
                                    </li>
                                    <?php if ($libro['updated_at'] != $libro['created_at']): ?>
                                    <li class="mb-2">
                                        <strong>Actualizado:</strong> <?php echo date('d/m/Y', strtotime($libro['updated_at'])); ?>
                                    </li>
                                    <?php endif; ?>
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
</body>
</html>
