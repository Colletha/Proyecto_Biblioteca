<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <style>
        body { 
            font-family: Arial; 
            background: #f4f4f4; 
        }
        .login { 
            width: 300px; 
            margin: 100px auto; 
            background: white;
            padding: 20px; 
            border-radius: 5px; 
        }
        input { 
            width: 100%; 
            padding: 8px; 
            margin: 5px 0; 
        }
        button { 
            width: 100%; 
            padding: 8px; 
            background: #28a745; 
            color: white; 
            border: none; 
        }
        .error { 
            color: red; 
            font-size: 14px; 
        }
    </style>
</head>
<body>
    <div class="login">
        <h2>Iniciar Sesión</h2>
        
        <?php if(session()->getFlashdata('error')): ?>
            <p class="error"><?= session()->getFlashdata('error') ?></p>
        <?php endif; ?>
        
        <form method="post" action="<?= base_url('login/autenticar') ?>">
            <input type="text" name="usuario" placeholder="Usuario" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit">Ingresar</button>
        </form>
    </div>
</body>
</html>