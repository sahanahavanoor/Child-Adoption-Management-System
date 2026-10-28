<?php
session_start();
include 'db.php';
if (isLoggedIn()) {
    redirect(isAdmin() ? 'admin/dashboard.php' : 'adopter/dashboard.php');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $pass  = $_POST['password'];
    if (empty($email) || empty($pass)) {
        $error = "Please enter both email and password.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, name, email, password, role FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            if (password_verify($pass, $row['password'])) {
                $_SESSION['user_id']   = $row['id'];
                $_SESSION['user_name'] = $row['name'];
                $_SESSION['email']     = $row['email'];
                $_SESSION['role']      = $row['role'];
                redirect($row['role'] === 'admin' ? 'admin/dashboard.php' : 'adopter/dashboard.php');
            } else { $error = "Incorrect password. Please try again."; }
        } else { $error = "No account found with this email address."; }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HopeNest</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .pass-wrap { position: relative; }
        .pass-wrap input { padding-right: 3rem !important; }
        .eye-btn {
            position: absolute; right: 0.8rem; top: 50%;
            transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            font-size: 1.1rem; color: var(--text-muted); padding: 0;
            transition: color 0.2s;
        }
        .eye-btn:hover { color: var(--primary); }
        .pw-hint {
            background: #F9F5FF; border: 1px solid #EEE6F8;
            border-radius: 8px; padding: 0.8rem 1rem;
            font-size: 0.82rem; color: var(--text-muted);
            margin-top: 0.5rem; display: none;
        }
        .pw-hint.show { display: block; }
        .pw-hint ul { padding-left: 1.2rem; margin: 0.3rem 0 0; }
        .pw-hint li { padding: 0.1rem 0; }
    </style>
</head>
<body>

<div class="navbar">
    <div class="logo">🏠 <span>Hope</span>Nest</div>
    <nav>
        <a href="index.php">Home</a>
        <a href="login.php" class="active">Login</a>
        <a href="register.php">Register</a>
    </nav>
</div>

<div class="form-container">
    <h2>🔐 Login</h2>
    <p class="subtitle">Access your account to continue</p>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'password_reset'): ?>
        <div class="alert alert-success">✅ Password reset successfully! Login with your new password.</div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="Enter your email"
                   value="<?= isset($_POST['email']) ? sanitize($_POST['email']) : '' ?>" required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <div class="pass-wrap">
                <input type="password" id="loginPass" name="password"
                       placeholder="Enter your password"
                       onfocus="showHint()" onblur="hideHint()"
                       required>
                <button type="button" class="eye-btn" onclick="toggleEye()">
                    <span id="eyeIcon">👁️</span>
                </button>
            </div>
            
        
        <div style="text-align:right; margin-bottom:1rem;">
            <a href="forgot_password.php" style="font-size:0.88rem; color:var(--text-muted)">
                🔐 Forgot Password?
            </a>
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-primary" style="width:100%">Login</button>
        </div>
    </form>

    <div class="form-footer">
        Don't have an account? <a href="register.php">Register here</a>
    </div>


<script>
function toggleEye() {
    const input = document.getElementById('loginPass');
    const icon  = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = '🙈';
    } else {
        input.type = 'password';
        icon.textContent = '👁️';
    }
}
function showHint() { document.getElementById('pwHint').classList.add('show'); }
function hideHint() { document.getElementById('pwHint').classList.remove('show'); }
</script>
</body>
</html>
