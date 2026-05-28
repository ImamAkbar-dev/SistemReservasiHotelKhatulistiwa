<?php
session_start();
if(isset($_SESSION['user'])) {
    // Redirect berdasarkan role
    if($_SESSION['user']['role'] == 'admin') {
        header("Location: admin/laporan.php");
    } else {
        header("Location: Resepsionis/index.php");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Hotel Khatulistiwa</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            /* Gradasi miring: biru tua -> biru muda -> putih -> biru muda -> biru tua */
            background: linear-gradient(135deg, 
                #1E3A8A 0%,      /* biru tua */
                #60A5FA 25%,     /* biru muda */
                #FFFFFF 50%,     /* putih */
                #60A5FA 75%,     /* biru muda */
                #1E3A8A 100%);   /* biru tua */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .login-container {
            max-width: 420px;
            width: 100%;
        }
        .login-card {
            background: white;
            border-radius: 32px;
            padding: 40px 32px;
            box-shadow: 0 25px 45px -12px rgba(0,0,0,0.25);
            border-top: 6px solid #2563EB;
            transition: transform 0.2s;
            backdrop-filter: blur(0px);
        }
        .login-card:hover {
            transform: translateY(-2px);
        }
        .logo {
            text-align: center;
            margin-bottom: 24px;
        }
        .logo h1 {
            font-size: 1.8rem;
            color: #1E3A8A;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .logo p {
            color: #475569;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        .login-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: #1E3A8A;
            margin-bottom: 28px;
            text-align: center;
        }
        .input-group {
            margin-bottom: 20px;
        }
        .input-group label {
            display: block;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
            font-size: 0.85rem;
        }
        .input-group input {
            width: 100%;
            padding: 14px 16px;
            font-size: 1rem;
            border: 1.5px solid #E2E8F0;
            border-radius: 20px;
            transition: all 0.2s;
            background: #F8FAFC;
            font-family: inherit;
        }
        .input-group input:focus {
            outline: none;
            border-color: #2563EB;
            background: white;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }
        .btn-login {
            width: 100%;
            background: #2563EB;
            color: white;
            border: none;
            padding: 14px;
            font-size: 1rem;
            font-weight: 700;
            border-radius: 40px;
            cursor: pointer;
            transition: 0.2s;
            margin-top: 8px;
            font-family: inherit;
        }
        .btn-login:hover {
            background: #1E40AF;
            transform: scale(0.98);
        }
        .error-message {
            background: #FEE2E2;
            color: #B91C1C;
            padding: 12px 16px;
            border-radius: 40px;
            font-size: 0.85rem;
            text-align: center;
            margin-bottom: 24px;
            border-left: 4px solid #EF4444;
        }
        .demo-info {
            margin-top: 24px;
            text-align: center;
            font-size: 0.75rem;
            color: #64748B;
            background: #F1F5F9;
            padding: 12px;
            border-radius: 20px;
        }
        .demo-info span {
            font-weight: bold;
            color: #2563EB;
        }
        hr {
            margin: 20px 0;
            border: none;
            border-top: 1px solid #E2E8F0;
        }
        @media (max-width: 480px) {
            .login-card { padding: 30px 24px; }
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-card">
        <div class="logo">
            <h1>Hotel Khatulistiwa</h1>
            <p>Sistem Reservasi Kamar</p>
        </div>
        <div class="login-title">Sign-In</div>

        <?php if(isset($_GET['error'])): ?>
            <div class="error-message">
                ⚠️ Username atau password salah!
            </div>
        <?php endif; ?>

        <form action="../process/login_process.php" method="POST">
            <div class="input-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Contoh: username1" required autocomplete="off">
            </div>
            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Masukkan password" required>
            </div>
            <button type="submit" class="btn-login">LOGIN</button>
        </form>

        <hr>
        
    </div>
</div>
</body>
</html>