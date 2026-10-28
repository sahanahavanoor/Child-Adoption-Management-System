<?php
// ============================================================
// register.php - Register Page
// Features: Password visibility toggle + strength requirements
// ============================================================
session_start();
include 'db.php';

if (isLoggedIn()) {
    redirect(isAdmin() ? 'admin/dashboard.php' : 'adopter/dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $pass  = $_POST['password'];
    $cpass = $_POST['confirm_password'];

    // Validate all password requirements server-side too
    $has_min     = strlen($pass) >= 8;
    $has_upper   = preg_match('/[A-Z]/', $pass);
    $has_lower   = preg_match('/[a-z]/', $pass);
    $has_number  = preg_match('/[0-9]/', $pass);
    $has_special = preg_match('/[\W_]/', $pass);

    if (empty($name) || empty($email) || empty($pass) || empty($cpass)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (!$has_min || !$has_upper || !$has_lower || !$has_number || !$has_special) {
        $error = "Password does not meet all requirements.";
    } elseif ($pass !== $cpass) {
        $error = "Passwords do not match.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $error = "This email is already registered. Please login.";
        } else {
            $hashed_password = password_hash($pass, PASSWORD_DEFAULT);
            $insert = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'adopter')");
            mysqli_stmt_bind_param($insert, "sss", $name, $email, $hashed_password);
            if (mysqli_stmt_execute($insert)) {
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - HopeNest</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Password field wrapper */
        .pass-wrap {
            position: relative;
        }
        .pass-wrap input {
            padding-right: 3rem !important;
        }
        /* Eye toggle button */
        .eye-btn {
            position: absolute;
            right: 0.8rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            color: var(--text-muted);
            padding: 0;
            line-height: 1;
            transition: color 0.2s;
        }
        .eye-btn:hover { color: var(--primary); }

        /* Password requirements box */
        .pw-requirements {
            background: #F9F5FF;
            border: 1px solid #EEE6F8;
            border-radius: 8px;
            padding: 0.9rem 1rem;
            margin-top: 0.5rem;
            font-size: 0.83rem;
        }
        .pw-requirements p {
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .req-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.2rem 0;
            color: var(--text-muted);
            transition: color 0.2s;
        }
        .req-item .dot {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 2px solid #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.65rem;
            flex-shrink: 0;
            transition: all 0.2s;
        }
        .req-item.valid { color: var(--success); }
        .req-item.valid .dot {
            background: var(--success);
            border-color: var(--success);
            color: #fff;
        }
        .req-item.invalid .dot {
            background: #FEE2E2;
            border-color: var(--danger);
            color: var(--danger);
        }

        /* Strength bar */
        .strength-bar-wrap {
            margin-top: 0.6rem;
            display: flex;
            gap: 4px;
        }
        .strength-segment {
            height: 4px;
            flex: 1;
            border-radius: 2px;
            background: var(--border);
            transition: background 0.3s;
        }
        .strength-label {
            font-size: 0.78rem;
            margin-top: 4px;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="navbar">
    <div class="logo">🏠 <span>Hope</span>Nest</div>
    <nav>
        <a href="index.php">Home</a>
        <a href="login.php">Login</a>
        <a href="register.php" class="active">Register</a>
    </nav>
</div>

<div class="form-container" style="max-width:500px">
    <h2>👤 Create Account</h2>
    <p class="subtitle">Register to start your adoption journey</p>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= $success ?> <a href="login.php">Login here</a>
        </div>
    <?php endif; ?>

    <form method="POST" id="registerForm">

        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name"
                   placeholder="Enter your full name"
                   value="<?= isset($_POST['name']) ? sanitize($_POST['name']) : '' ?>"
                   required>
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email"
                   placeholder="Enter your email"
                   value="<?= isset($_POST['email']) ? sanitize($_POST['email']) : '' ?>"
                   required>
        </div>

        <!-- Password with eye toggle -->
        <div class="form-group">
            <label for="password">Password</label>
            <div class="pass-wrap">
                <input type="password" id="password" name="password"
                       placeholder="Create a strong password"
                       oninput="checkStrength(this.value)"
                       required>
                <button type="button" class="eye-btn" onclick="toggleEye('password','eye1')">
                    <span id="eye1">👁️</span>
                </button>
            </div>

            <!-- Strength bar -->
            <div class="strength-bar-wrap" id="strengthBar">
                <div class="strength-segment" id="seg1"></div>
                <div class="strength-segment" id="seg2"></div>
                <div class="strength-segment" id="seg3"></div>
                <div class="strength-segment" id="seg4"></div>
            </div>
            <div class="strength-label" id="strengthLabel" style="color:var(--text-muted)"></div>

            

        <!-- Confirm password with eye toggle -->
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <div class="pass-wrap">
                <input type="password" id="confirm_password" name="confirm_password"
                       placeholder="Re-enter your password"
                       oninput="checkMatch()"
                       required>
                <button type="button" class="eye-btn" onclick="toggleEye('confirm_password','eye2')">
                    <span id="eye2">👁️</span>
                </button>
            </div>
            <div id="matchMsg" style="font-size:0.83rem; margin-top:5px;"></div>
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-primary" id="submitBtn">
                Create Account
            </button>
        </div>
    </form>

    <div class="form-footer">
        Already have an account? <a href="login.php">Login here</a>
    </div>
</div>

<script>
// ============================================================
// Toggle password visibility
// ============================================================
function toggleEye(inputId, eyeId) {
    const input = document.getElementById(inputId);
    const eye   = document.getElementById(eyeId);
    if (input.type === 'password') {
        input.type  = 'text';
        eye.textContent = '🙈';
    } else {
        input.type  = 'password';
        eye.textContent = '👁️';
    }
}

// ============================================================
// Check each password requirement in real time
// ============================================================
function checkStrength(val) {
    const rules = {
        'req-min':     val.length >= 8,
        'req-upper':   /[A-Z]/.test(val),
        'req-lower':   /[a-z]/.test(val),
        'req-number':  /[0-9]/.test(val),
        'req-special': /[\W_]/.test(val),
    };

    let passed = 0;

    // Update each requirement item
    for (const [id, ok] of Object.entries(rules)) {
        const el  = document.getElementById(id);
        const dot = el.querySelector('.dot');
        if (ok) {
            el.classList.add('valid');
            el.classList.remove('invalid');
            dot.textContent = '✓';
            passed++;
        } else {
            el.classList.add('invalid');
            el.classList.remove('valid');
            dot.textContent = '✕';
        }
    }

    // Update strength bar
    const colors  = ['#DC2626','#F97316','#EAB308','#16A34A'];
    const labels  = ['Weak','Fair','Good','Strong'];
    const segs    = [1,2,3,4];

    segs.forEach(i => {
        const seg = document.getElementById('seg' + i);
        seg.style.background = i <= passed ? colors[Math.min(passed-1,3)] : 'var(--border)';
    });

    const lbl = document.getElementById('strengthLabel');
    if (val.length === 0) {
        lbl.textContent = '';
    } else {
        lbl.textContent = passed <= 1 ? '🔴 Weak' : passed <= 2 ? '🟠 Fair' : passed <= 3 ? '🟡 Good' : '🟢 Strong';
        lbl.style.color = colors[Math.min(passed-1,3)];
    }
}

// ============================================================
// Check if passwords match
// ============================================================
function checkMatch() {
    const pass  = document.getElementById('password').value;
    const cpass = document.getElementById('confirm_password').value;
    const msg   = document.getElementById('matchMsg');
    if (cpass.length === 0) { msg.textContent = ''; return; }
    if (pass === cpass) {
        msg.textContent = '✅ Passwords match';
        msg.style.color = 'var(--success)';
    } else {
        msg.textContent = '❌ Passwords do not match';
        msg.style.color = 'var(--danger)';
    }
}

// ============================================================
// Final validation before form submit
// ============================================================
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const pass    = document.getElementById('password').value;
    const cpass   = document.getElementById('confirm_password').value;
    const valid   = pass.length>=8 && /[A-Z]/.test(pass) && /[a-z]/.test(pass)
                    && /[0-9]/.test(pass) && /[\W_]/.test(pass);
    if (!valid) {
        alert('Please make sure your password meets all requirements.');
        e.preventDefault(); return;
    }
    if (pass !== cpass) {
        alert('Passwords do not match!');
        e.preventDefault(); return;
    }
});
</script>
</body>
</html>
