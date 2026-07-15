<?php
/**
 * Login Page
 */
require_once dirname(__DIR__) . '/config/config.php';

if (Auth::check()) {
    redirect('views/dashboard.php');
}

$error = '';
$usernameOrEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $usernameOrEmail = trim($_POST['username_or_email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!Utility::validateCSRFToken($token)) {
        $error = 'Invalid request security token.';
    } else {
        $validator = new Validation();
        $validator->required(['username_or_email' => 'Username or Email', 'password' => 'Password'], $_POST);

        if (!$validator->passes()) {
            $errors = $validator->getErrors();
            $error  = reset($errors);
        } else {
            if (Auth::attempt($usernameOrEmail, $password)) {
                Utility::setFlash('success', 'Welcome back, ' . Auth::displayName() . '!');
                redirect('views/dashboard.php');
            } else {
                $error = 'Invalid username/email or password.';
            }
        }
    }
}

$csrfToken  = Utility::generateCSRFToken();
$schoolName = Setting::get('school_name', 'Talent Institute');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Dark Mode Loader (prevents flash) -->
    <script>
        (function() {
            var t = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', t);
            document.addEventListener('DOMContentLoaded', function() {
                document.body && document.body.setAttribute('data-theme', t);
            });
        })();
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — <?= e($schoolName) ?></title>
    <meta name="description" content="Sign in to your <?= e($schoolName) ?> portal.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        /* ── Design tokens ── */
        :root {
            --blue-900: #1e3a8a;
            --blue-700: #1d4ed8;
            --blue-600: #2563eb;
            --blue-500: #3b82f6;
            --blue-400: #60a5fa;
            --blue-100: #dbeafe;
            --white:    #ffffff;
            --gray-50:  #f1f5f9;
            --gray-100: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-500: #64748b;
            --gray-700: #334155;
            --gray-900: #0f172a;
        }
        [data-theme="dark"] {
            --white:    #0b0f19;
            --gray-50:  #0d1424;
            --gray-100: #152238;
            --gray-300: #334155;
            --gray-500: #94a3b8;
            --gray-700: #cbd5e1;
            --gray-900: #f1f5f9;
            --blue-100: #1e293b;
        }

        html, body {
            height: 100%;
            font-family: 'Inter', sans-serif;
            background: var(--gray-50);
            color: var(--gray-700);
            transition: background 0.3s, color 0.3s;
        }

        /* ── Full-page centering ── */
        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            background: #e8edf5;
            transition: background 0.3s;
        }
        [data-theme="dark"] .login-wrapper {
            background: #07090f;
        }

        /* ── Card ── */
        .login-card {
            width: 100%;
            max-width: 420px;
            background: var(--white);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 28px rgba(0,0,0,0.13);
            transition: background 0.3s, box-shadow 0.3s;
        }

        /* ── Card header band ── */
        .card-header-band {
            background: var(--blue-700);
            padding: 5px 0 0;
        }
        .card-header-inner {
            background: var(--white);
            border-radius: 6px 6px 0 0;
            padding: 28px 32px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            border-bottom: 1px solid var(--gray-100);
        }
        [data-theme="dark"] .card-header-inner {
            background: var(--white);
            border-bottom-color: var(--gray-300);
        }

        /* Logo box */
        .logo-box {
            width: 72px; height: 72px;
            background: linear-gradient(135deg, var(--blue-900), var(--blue-600));
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem; font-weight: 900;
            color: #fff; letter-spacing: -1px;
            margin-bottom: 14px;
            box-shadow: 0 4px 16px rgba(37,99,235,0.3);
        }
        .school-name-en {
            font-size: 1.1rem; font-weight: 800;
            color: var(--blue-900); line-height: 1.2;
            margin-bottom: 2px;
        }
        [data-theme="dark"] .school-name-en { color: var(--blue-400); }
        .school-name-ar {
            font-size: 0.88rem; font-weight: 600;
            color: var(--gray-500); margin-bottom: 0;
        }

        /* ── Card body ── */
        .card-body-area {
            padding: 28px 32px 32px;
        }

        .signin-tagline {
            text-align: center;
            font-size: 0.9rem;
            color: var(--gray-500);
            margin-bottom: 24px;
        }
        .signin-tagline span {
            color: var(--blue-600);
            font-weight: 600;
        }

        /* Error */
        .error-box {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            color: #dc2626;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 18px;
            font-size: 0.85rem;
            font-weight: 500;
            display: flex; align-items: center; gap: 8px;
        }
        [data-theme="dark"] .error-box {
            background: #2d0f0f; border-left-color: #b91c1c; color: #fca5a5;
        }

        /* ── Input fields ── */
        .input-group-custom {
            position: relative; margin-bottom: 16px;
        }
        .input-field {
            width: 100%;
            padding: 11px 44px 11px 14px;
            border: 1.5px solid var(--gray-300);
            border-radius: 6px;
            font-size: 0.9rem;
            font-family: 'Inter', sans-serif;
            color: var(--gray-900);
            background: var(--white);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .input-field:focus {
            border-color: var(--blue-500);
            box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
        }
        .input-field::placeholder { color: var(--gray-300); }
        [data-theme="dark"] .input-field {
            background: var(--gray-100);
            border-color: var(--gray-300);
            color: var(--gray-900);
        }

        .input-icon {
            position: absolute; right: 13px; top: 50%;
            transform: translateY(-50%);
            color: var(--gray-700); font-size: 0.9rem;
            pointer-events: none;
        }
        .input-icon.clickable { pointer-events: auto; cursor: pointer; transition: color 0.2s; color: var(--gray-700); }
        .input-icon.clickable:hover { color: var(--blue-600); }

        /* ── Login button ── */
        .btn-login {
            width: 100%;
            padding: 12px;
            background: var(--blue-600);
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            letter-spacing: 0.3px;
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
            margin-top: 4px;
        }
        .btn-login:hover {
            background: var(--blue-700);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(37,99,235,0.28);
        }
        .btn-login:active { transform: translateY(0); }

        /* ── Extras row ── */
        .extras-row {
            display: flex; justify-content: space-between; align-items: center;
            margin-top: 14px; font-size: 0.8rem;
        }
        .check-label {
            display: flex; align-items: center; gap: 6px;
            color: var(--gray-500); cursor: pointer;
        }
        .check-label input { accent-color: var(--blue-600); }
        .forgot-link {
            color: var(--blue-600); text-decoration: none; font-weight: 600;
            transition: color 0.2s;
        }
        .forgot-link:hover { color: var(--blue-900); }

        /* ── Bottom nav ── */
        .card-footer-area {
            background: var(--gray-50);
            border-top: 1px solid var(--gray-100);
            padding: 14px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        [data-theme="dark"] .card-footer-area {
            background: var(--gray-100);
            border-top-color: var(--gray-300);
        }
        .footer-link {
            font-size: 0.78rem; font-weight: 600;
            color: var(--gray-500); text-decoration: none;
            display: flex; align-items: center; gap: 5px;
            transition: color 0.2s;
        }
        .footer-link:hover { color: var(--blue-600); }

        /* ── Theme toggle (floating) ── */
        .theme-toggle {
            position: fixed; bottom: 24px; right: 24px;
            width: 42px; height: 42px; border-radius: 50%;
            border: 1.5px solid var(--gray-300);
            background: var(--white);
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
            transition: all 0.2s; z-index: 50;
        }
        .theme-toggle:hover { background: var(--blue-50, #eff6ff); border-color: var(--blue-400); }

        @media (max-width: 480px) {
            .card-header-inner, .card-body-area, .card-footer-area { padding-left: 20px; padding-right: 20px; }
            .card-footer-area { justify-content: center; }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">

        <!-- Header band + logo -->
        <div class="card-header-band">
            <div class="card-header-inner">
                <div class="logo-box">TI</div>
                <div class="school-name-en"><?= e($schoolName) ?></div>
                <div class="school-name-ar">معـهـد تـالـنـت · للغات والمواد العلمية</div>
            </div>
        </div>

        <!-- Form body -->
        <div class="card-body-area">

            <p class="signin-tagline">Sign in to <span>start your session</span></p>

            <?= Utility::displayFlash() ?>

            <?php if (!empty($error)): ?>
                <div class="error-box">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form action="<?= BASE_URL ?>views/login.php" method="POST" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <!-- Username / Email -->
                <div class="input-group-custom">
                    <input
                        type="text"
                        class="input-field"
                        id="username_or_email"
                        name="username_or_email"
                        value="<?= e($usernameOrEmail) ?>"
                        placeholder="Username or Email"
                        autocomplete="username"
                        required
                    >
                    <span class="input-icon"><i class="fas fa-envelope"></i></span>
                </div>

                <!-- Password -->
                <div class="input-group-custom">
                    <input
                        type="password"
                        class="input-field"
                        id="password"
                        name="password"
                        placeholder="Password"
                        autocomplete="current-password"
                        required
                    >
                    <span class="input-icon clickable" id="togglePwd" title="Show/hide password">
                        <i class="fas fa-lock" id="eyeIcon"></i>
                    </span>
                </div>

                <!-- Login button -->
                <button type="submit" class="btn-login" id="submitBtn">
                    Login
                </button>

                <!-- Extras -->
                <div class="extras-row">
                    <label class="check-label">
                        <input type="checkbox" name="remember" id="remember">
                        Remember Me
                    </label>
                    <a href="<?= BASE_URL ?>views/forgot_password.php" class="forgot-link">Forgot Password?</a>
                </div>
            </form>
        </div>

        <!-- Footer links -->
        <div class="card-footer-area">
            <a href="<?= BASE_URL ?>" class="footer-link">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
            <a href="<?= BASE_URL ?>views/login.php" class="footer-link">
                <i class="fas fa-shield-alt"></i> Admin Login
            </a>
        </div>

    </div>
</div>

<!-- Floating Theme Toggle -->
<button id="themeToggleBtn" class="theme-toggle" title="Toggle Dark/Light Mode" aria-label="Toggle Theme">
    <i class="fas fa-moon" id="themeIcon" style="color:#64748b;font-size:0.95rem;"></i>
</button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // ── Theme ──
    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        document.body.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        const icon = document.getElementById('themeIcon');
        if (icon) {
            icon.className = theme === 'dark' ? 'fas fa-sun'  : 'fas fa-moon';
            icon.style.color = theme === 'dark' ? '#fbbf24' : '#64748b';
        }
    }
    applyTheme(localStorage.getItem('theme') || 'light');
    document.getElementById('themeToggleBtn').addEventListener('click', () => {
        applyTheme(document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
    });

    // ── Password toggle ──
    document.getElementById('togglePwd').addEventListener('click', function() {
        const inp  = document.getElementById('password');
        const icon = document.getElementById('eyeIcon');
        if (inp.type === 'password') {
            inp.type = 'text';
            icon.className = 'fas fa-eye-slash';
        } else {
            inp.type = 'password';
            icon.className = 'fas fa-lock';
        }
    });

    // ── Loading state on submit ──
    document.getElementById('loginForm').addEventListener('submit', function() {
        const btn = document.getElementById('submitBtn');
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin me-2"></i>Signing in…';
        btn.disabled = true;
    });
</script>
</body>
</html>
