<?php
// auth/login.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/farmacia.php';

if (isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Por favor, ingrese su correo y contraseña.';
    } else {
        try {
            $pdo = conectar();
            $stmt = $pdo->prepare("SELECT u.id, u.nombre, u.password_hash, u.rol_id, u.activo, r.nombre as rol_nombre 
                                   FROM usuarios u 
                                   JOIN roles r ON u.rol_id = r.id 
                                   WHERE u.email = ?");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();

            if ($usuario && password_verify($password, $usuario['password_hash'])) {
                if ($usuario['activo'] == 1) {
                    $_SESSION['usuario_id'] = $usuario['id'];
                    $_SESSION['usuario_nombre'] = $usuario['nombre'];
                    $_SESSION['rol_id'] = $usuario['rol_id'];
                    $_SESSION['usuario_rol'] = $usuario['rol_nombre'];
                    header("Location: ../index.php");
                    exit;
                } else {
                    $error = 'Su cuenta está inactiva. Contacte al administrador.';
                }
            } else {
                $error = 'Correo o contraseña incorrectos.';
            }
        } catch (PDOException $e) {
            $error = 'Error de sistema. Intente más tarde.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — <?= htmlspecialchars(FARMACIA_NOMBRE) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css?v=2.0">
    <link rel="stylesheet" href="../assets/css/components.css?v=2.0">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #f1f5f9;
            min-height: 100dvh;
            display: flex;
            align-items: stretch;
        }

        /* ── PANEL IZQUIERDO ── */
        .login-panel-left {
            display: none;
            flex: 1;
            background: linear-gradient(150deg, #064e3b 0%, #0f172a 60%, #065f46 100%);
            position: relative;
            overflow: hidden;
            padding: 60px 56px;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Círculos decorativos */
        .login-panel-left::before {
            content: '';
            position: absolute;
            top: -120px; right: -120px;
            width: 420px; height: 420px;
            border-radius: 50%;
            background: rgba(16, 185, 129, 0.12);
            pointer-events: none;
        }
        .login-panel-left::after {
            content: '';
            position: absolute;
            bottom: -80px; left: -80px;
            width: 300px; height: 300px;
            border-radius: 50%;
            background: rgba(16, 185, 129, 0.08);
            pointer-events: none;
        }

        .left-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            z-index: 1;
        }
        .left-brand-icon {
            width: 48px; height: 48px;
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.35);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #34d399;
        }
        .left-brand-icon svg { width: 24px; height: 24px; }
        .left-brand-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.02em;
        }
        .left-brand-sub {
            font-size: 0.75rem;
            color: #6ee7b7;
            font-weight: 500;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .left-center { z-index: 1; }
        .left-headline {
            font-size: 2.6rem;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.03em;
            line-height: 1.15;
            margin-bottom: 20px;
        }
        .left-headline span { color: #34d399; }
        .left-desc {
            font-size: 1rem;
            color: #94a3b8;
            line-height: 1.6;
            max-width: 380px;
        }

        .left-stats {
            display: flex;
            gap: 32px;
            z-index: 1;
        }
        .stat-item { }
        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #34d399;
            letter-spacing: -0.03em;
        }
        .stat-label {
            font-size: 0.78rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 500;
        }

        /* ── PANEL DERECHO ── */
        .login-panel-right {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 24px;
            background: #ffffff;
        }

        .login-form-wrap {
            width: 100%;
            max-width: 420px;
        }

        /* Mobile brand (visible solo en móvil) */
        .login-mobile-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 40px;
        }
        .login-mobile-icon {
            width: 44px; height: 44px;
            background: #ecfdf5;
            border: 1px solid #d1fae5;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            color: #059669;
        }
        .login-mobile-icon svg { width: 22px; height: 22px; }
        .login-mobile-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f172a;
        }
        .login-mobile-sub {
            font-size: 0.72rem;
            color: #64748b;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .login-heading {
            margin-bottom: 8px;
            font-size: 1.75rem;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.025em;
        }
        .login-subheading {
            font-size: 0.925rem;
            color: #64748b;
            margin-bottom: 36px;
        }

        /* Form fields */
        .field-group { margin-bottom: 20px; }
        .field-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
        }
        .field-input-wrap { position: relative; }
        .field-input-wrap svg:not(.toggle-pw svg) {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px; height: 18px;
            color: #94a3b8;
            pointer-events: none;
        }
        /* Selector específico para el ícono del campo (no el toggle) */
        .field-input-wrap > svg {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px; height: 18px;
            color: #94a3b8;
            pointer-events: none;
        }
        .field-input {
            width: 100%;
            height: 48px;
            padding: 0 48px 0 44px; /* izq: icono de campo, der: espacio para toggle */
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95rem;
            color: #0f172a;
            background: #f8fafc;
            font-family: inherit;
            transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
            outline: none;
        }
        .field-input:focus {
            border-color: #059669;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12);
        }
        .field-input::placeholder { color: #cbd5e1; }

        /* Toggle password */
        .toggle-pw {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #94a3b8;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4px;
            z-index: 2;
            transition: color 0.15s;
        }
        .toggle-pw:hover { color: #059669; }
        .toggle-pw svg {
            width: 18px; height: 18px;
            display: block;
            pointer-events: none;
        }

        /* Error banner */
        .login-error {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 12px 16px;
            color: #dc2626;
            font-size: 0.875rem;
            margin-bottom: 24px;
            animation: shake 0.4s ease;
        }
        .login-error svg { width: 18px; height: 18px; flex-shrink: 0; }

        @keyframes shake {
            0%,100% { transform: translateX(0); }
            20%,60%  { transform: translateX(-5px); }
            40%,80%  { transform: translateX(5px); }
        }

        /* Submit button */
        .btn-login {
            width: 100%;
            height: 50px;
            background: #059669;
            color: #ffffff;
            font-size: 0.95rem;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-family: inherit;
            letter-spacing: 0.01em;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
            box-shadow: 0 4px 14px rgba(5, 150, 105, 0.3);
            margin-top: 28px;
        }
        .btn-login:hover {
            background: #047857;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(5, 150, 105, 0.4);
        }
        .btn-login:active { transform: translateY(0); }
        .btn-login svg { width: 18px; height: 18px; }

        .login-footer {
            text-align: center;
            margin-top: 32px;
            font-size: 0.8rem;
            color: #94a3b8;
        }

        /* ── RESPONSIVE ── */
        @media (min-width: 900px) {
            .login-panel-left { display: flex; }
            .login-panel-right { width: 480px; flex-shrink: 0; }
            .login-mobile-brand { display: none; }
        }
    </style>
</head>
<body>

    <!-- Panel Izquierdo: Branding -->
    <div class="login-panel-left">
        <div class="left-brand">
            <div class="left-brand-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
            </div>
            <div>
                <div class="left-brand-name"><?= htmlspecialchars(FARMACIA_NOMBRE) ?></div>
                <div class="left-brand-sub">Sistema de Gestión</div>
            </div>
        </div>

        <div class="left-center">
            <h1 class="left-headline">Gestión farmacéutica<br><span>inteligente y segura.</span></h1>
            <p class="left-desc">Control total de inventario, ventas, vencimientos y reportes en un solo lugar. Accede de forma rápida y segura.</p>
        </div>

        <div class="left-stats">
            <div class="stat-item">
                <div class="stat-value">100%</div>
                <div class="stat-label">Seguro</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">Real-time</div>
                <div class="stat-label">Inventario</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">24/7</div>
                <div class="stat-label">Disponible</div>
            </div>
        </div>
    </div>

    <!-- Panel Derecho: Formulario -->
    <div class="login-panel-right">
        <div class="login-form-wrap">

            <!-- Brand solo mobile -->
            <div class="login-mobile-brand">
                <div class="login-mobile-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
                <div>
                    <div class="login-mobile-name"><?= htmlspecialchars(FARMACIA_NOMBRE) ?></div>
                    <div class="login-mobile-sub">Sistema de Gestión</div>
                </div>
            </div>

            <h1 class="login-heading">Bienvenido de nuevo</h1>
            <p class="login-subheading">Ingresa tus credenciales para continuar</p>

            <?php if ($error): ?>
                <div class="login-error">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" novalidate>
                <div class="field-group">
                    <label class="field-label" for="email">Correo Electrónico</label>
                    <div class="field-input-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                        </svg>
                        <input type="email" name="email" id="email" class="field-input"
                               placeholder="correo@farmacia.com" required autofocus
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label" for="password">Contraseña</label>
                    <div class="field-input-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                        <input type="password" name="password" id="password" class="field-input"
                               placeholder="••••••••" required>
                        <button type="button" class="toggle-pw" id="toggle-pw" aria-label="Mostrar contraseña">
                            <svg id="icon-eye" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg id="icon-eye-off" style="display:none;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                    </svg>
                    Iniciar Sesión
                </button>
            </form>

            <p class="login-footer">© <?= date('Y') ?> <?= htmlspecialchars(FARMACIA_NOMBRE) ?> · Sistema de Gestión Farmacéutica</p>
        </div>
    </div>

    <script>
    // Toggle password visibility
    const toggleBtn = document.getElementById('toggle-pw');
    const pwInput   = document.getElementById('password');
    const iconEye   = document.getElementById('icon-eye');
    const iconOff   = document.getElementById('icon-eye-off');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            const isHidden = pwInput.type === 'password';
            pwInput.type = isHidden ? 'text' : 'password';
            iconEye.style.display = isHidden ? 'none' : 'block';
            iconOff.style.display = isHidden ? 'block' : 'none';
        });
    }
    </script>

</body>
</html>
