<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            
            <?php if (hasRole('administrador') || hasRole('bibliotecario')): ?>
            <li class="nav-item">
                <a class="nav-link" href="libros/">
                    <i class="fas fa-book"></i> Gestión de Libros
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (hasRole('administrador')): ?>
            <li class="nav-item">
                <a class="nav-link" href="usuarios/">
                    <i class="fas fa-users"></i> Gestión de Usuarios
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (hasRole('administrador') || hasRole('bibliotecario')): ?>
            <li class="nav-item">
                <a class="nav-link" href="prestamos/">
                    <i class="fas fa-hand-holding"></i> Préstamos
                </a>
            </li>
            <?php endif; ?>
            
            <li class="nav-item">
                <a class="nav-link" href="catalogo.php">
                    <i class="fas fa-search"></i> Catálogo de Libros
                </a>
            </li>
            
            <?php if ($_SESSION['user_role'] === 'alumno'): ?>
            <li class="nav-item">
                <a class="nav-link" href="mis-prestamos.php">
                    <i class="fas fa-bookmark"></i> Mis Préstamos
                </a>
            </li>
            <?php endif; ?>
            
            <li class="nav-item">
                <a class="nav-link" href="reportes/">
                    <i class="fas fa-chart-bar"></i> Reportes
                </a>
            </li>
        </ul>
        
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Configuración</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <?php if (hasRole('administrador')): ?>
            <li class="nav-item">
                <a class="nav-link" href="configuracion.php">
                    <i class="fas fa-cog"></i> Configuración
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link" href="ayuda.php">
                    <i class="fas fa-question-circle"></i> Ayuda
                </a>
            </li>
        </ul>
    </div>
</nav>
