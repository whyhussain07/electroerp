<?php
session_start();
if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ElectroERP | Login</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #0f172a;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-box {
            background: #1e293b;
            padding: 40px;
            border-radius: 12px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
        }
        .login-box h1 {
            color: #38bdf8;
            font-size: 24px;
            margin-bottom: 6px;
        }
        .login-box p {
            color: #94a3b8;
            font-size: 14px;
            margin-bottom: 30px;
        }
        label {
            display: block;
            color: #cbd5e1;
            font-size: 13px;
            margin-bottom: 6px;
        }
        input {
            width: 100%;
            padding: 10px 14px;
            border-radius: 8px;
            border: 1px solid #334155;
            background: #0f172a;
            color: #f1f5f9;
            font-size: 14px;
            margin-bottom: 20px;
        }
        input:focus {
            outline: none;
            border-color: #38bdf8;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #38bdf8;
            color: #0f172a;
            font-weight: 700;
            font-size: 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        button:hover { background: #0ea5e9; }
        .error {
            background: #fee2e2;
            color: #dc2626;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
<div class="login-box">
    <h1>⚡ ElectroERP</h1>
    <p>Sign in to your account</p>

    <?php if(isset($_GET['error'])): ?>
        <div class="error">Invalid email or password.</div>
    <?php endif; ?>

    <form method="POST" action="auth.php">
        <label>Email Address</label>
        <input type="email" name="email" placeholder="admin@electroerp.com" required>

        <label>Password</label>
        <input type="password" name="password" placeholder="••••••••" required>

        <button type="submit">Sign In</button>
    </form>
</div>
</body>
</html>