<?php
// ============================================================
// forgot_password.php - Forgot Password Page
// Step 1: User enters email → OTP sent to their email
// Step 2: User enters OTP → verified
// Step 3: User sets new password → updated in database
// ============================================================
session_start();
include 'db.php';

// If already logged in, redirect
if (isLoggedIn()) {
    redirect(isAdmin() ? 'admin/dashboard.php' : 'adopter/dashboard.php');
}

$error   = '';
$success = '';

// We use a 3-step process tracked by session variables:
// step 1 = enter email
// step 2 = enter OTP
// step 3 = enter new password

if (!isset($_SESSION['fp_step'])) {
    $_SESSION['fp_step'] = 1; // Start at step 1
}

// ============================================================
// STEP 1: Handle Email Submission → Generate & Send OTP
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_otp'])) {

    $email = sanitize($_POST['email']);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if email exists in database
        $stmt = mysqli_prepare($conn, "SELECT id, name FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user   = mysqli_fetch_assoc($result);

        if (!$user) {
            $error = "No account found with this email address.";
        } else {
            // Generate a 6-digit OTP
            $otp = rand(100000, 999999);

            // Store OTP, email and expiry time in session
            $_SESSION['fp_email']  = $email;
            $_SESSION['fp_otp']    = $otp;
            $_SESSION['fp_name']   = $user['name'];
            $_SESSION['fp_expiry'] = time() + (10 * 60); // OTP valid for 10 minutes
            $_SESSION['fp_step']   = 2;

            // Send OTP email
            include_once 'mailer.php';
            $subject = "Your Password Reset OTP - HopeNest";
            $body    = "
            <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px;'>
                <div style='background:linear-gradient(135deg,#7B5EA7,#9B72CF);padding:30px;border-radius:12px 12px 0 0;text-align:center;'>
                    <h1 style='color:#fff;margin:0;font-size:1.8rem;'>🏠 HopeNest</h1>
                    <p style='color:rgba(255,255,255,0.85);margin:8px 0 0;'>Password Reset Request</p>
                </div>
                <div style='background:#fff;padding:30px;border:1px solid #EEE6F8;border-top:none;border-radius:0 0 12px 12px;'>
                    <h2 style='color:#2D1B4E;'>Hello, " . htmlspecialchars($user['name']) . " 👋</h2>
                    <p style='color:#555;line-height:1.7;'>
                        We received a request to reset your password.
                        Use the OTP below to proceed. This OTP is valid for <strong>10 minutes</strong>.
                    </p>
                    <div style='text-align:center;margin:30px 0;'>
                        <div style='background:#F5F3FF;border:2px dashed #9B72CF;border-radius:12px;padding:25px;display:inline-block;min-width:200px;'>
                            <p style='color:#7B5EA7;font-size:0.85rem;margin:0 0 8px;font-weight:600;letter-spacing:2px;text-transform:uppercase;'>Your OTP</p>
                            <h1 style='color:#2D1B4E;font-size:3rem;margin:0;letter-spacing:10px;font-weight:800;'>" . $otp . "</h1>
                        </div>
                    </div>
                    <p style='color:#999;font-size:0.85rem;text-align:center;'>
                        ⚠️ Do not share this OTP with anyone.<br>
                        If you did not request this, please ignore this email.
                    </p>
                    <p style='color:#999;font-size:0.85rem;text-align:center;margin-top:20px;'>
                        © " . date('Y') . " HopeNest Orphanage
                    </p>
                </div>
            </div>";

            $sent = sendMail($email, $user['name'], $subject, $body);

            if ($sent) {
                $success = "OTP sent to <strong>" . htmlspecialchars($email) . "</strong>. Check your inbox.";
            } else {
                // Even if email fails, show OTP on screen for testing purposes
                $success = "OTP generated. (Mail not configured - use OTP: <strong>$otp</strong> for testing)";
            }
        }
    }
}

// ============================================================
// STEP 2: Handle OTP Verification
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {

    $entered_otp = trim($_POST['otp']);

    if (empty($entered_otp)) {
        $error = "Please enter the OTP.";

    } elseif (time() > $_SESSION['fp_expiry']) {
        // OTP expired
        $error = "OTP has expired. Please start again.";
        $_SESSION['fp_step'] = 1;
        unset($_SESSION['fp_otp'], $_SESSION['fp_email'], $_SESSION['fp_expiry']);

    } elseif ($entered_otp != $_SESSION['fp_otp']) {
        $error = "Incorrect OTP. Please try again.";

    } else {
        // OTP is correct - move to step 3
        $_SESSION['fp_step']     = 3;
        $_SESSION['fp_verified'] = true;
        $success = "OTP verified! Now set your new password.";
    }
}

// ============================================================
// STEP 3: Handle New Password Submission
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {

    // Security check: OTP must have been verified
    if (!isset($_SESSION['fp_verified']) || !$_SESSION['fp_verified']) {
        redirect('forgot_password.php');
    }

    $new_pass  = $_POST['new_password'];
    $conf_pass = $_POST['confirm_password'];

    if (empty($new_pass) || empty($conf_pass)) {
        $error = "Both password fields are required.";

    } elseif (strlen($new_pass) < 6) {
        $error = "Password must be at least 6 characters.";

    } elseif ($new_pass !== $conf_pass) {
        $error = "Passwords do not match.";

    } else {
        // Hash and update password in database
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        $email  = $_SESSION['fp_email'];

        $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "ss", $hashed, $email);

        if (mysqli_stmt_execute($stmt)) {
            // Clear all forgot password session data
            unset(
                $_SESSION['fp_step'],
                $_SESSION['fp_otp'],
                $_SESSION['fp_email'],
                $_SESSION['fp_name'],
                $_SESSION['fp_expiry'],
                $_SESSION['fp_verified']
            );
            // Redirect to login with success message
            redirect('login.php?msg=password_reset');
        } else {
            $error = "Failed to update password. Please try again.";
        }
    }
}

// Reset link - start over
if (isset($_GET['reset'])) {
    unset(
        $_SESSION['fp_step'],
        $_SESSION['fp_otp'],
        $_SESSION['fp_email'],
        $_SESSION['fp_name'],
        $_SESSION['fp_expiry'],
        $_SESSION['fp_verified']
    );
    redirect('forgot_password.php');
}

$current_step = $_SESSION['fp_step'] ?? 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - HopeNest</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Step indicator */
        .steps {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            gap: 0;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.4rem;
        }

        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.95rem;
            border: 2px solid var(--border);
            background: #fff;
            color: var(--text-muted);
            transition: all 0.3s;
        }

        .step.active .step-circle {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }

        .step.done .step-circle {
            background: var(--success);
            border-color: var(--success);
            color: #fff;
        }

        .step-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            white-space: nowrap;
        }

        .step.active .step-label {
            color: var(--primary);
            font-weight: 600;
        }

        .step-line {
            flex: 1;
            height: 2px;
            background: var(--border);
            min-width: 50px;
            margin-bottom: 18px;
        }

        .step-line.done {
            background: var(--success);
        }

        /* OTP input boxes */
        .otp-inputs {
            display: flex;
            gap: 0.6rem;
            justify-content: center;
            margin: 1rem 0;
        }

        .otp-inputs input {
            width: 48px !important;
            height: 55px;
            text-align: center;
            font-size: 1.4rem;
            font-weight: 700;
            border: 2px solid var(--border);
            border-radius: 8px;
            padding: 0 !important;
            transition: border-color 0.2s;
        }

        .otp-inputs input:focus {
            border-color: var(--primary);
            outline: none;
        }

        /* Timer */
        .timer {
            text-align: center;
            font-size: 0.88rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }

        .timer span {
            font-weight: 700;
            color: var(--danger);
        }
    </style>
</head>
<body>

<!-- Navigation -->
<div class="navbar">
    <div class="logo">🏠 <span>Hope</span>Nest</div>
    <nav>
        <a href="index.php">Home</a>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
    </nav>
</div>

<div class="form-container" style="max-width:460px">
    <h2>🔐 Forgot Password</h2>
    <p class="subtitle">Reset your password in 3 simple steps</p>

    <!-- Step Indicator -->
    <div class="steps">
        <div class="step <?= $current_step >= 1 ? ($current_step > 1 ? 'done' : 'active') : '' ?>">
            <div class="step-circle"><?= $current_step > 1 ? '✓' : '1' ?></div>
            <div class="step-label">Email</div>
        </div>
        <div class="step-line <?= $current_step > 1 ? 'done' : '' ?>"></div>
        <div class="step <?= $current_step >= 2 ? ($current_step > 2 ? 'done' : 'active') : '' ?>">
            <div class="step-circle"><?= $current_step > 2 ? '✓' : '2' ?></div>
            <div class="step-label">OTP</div>
        </div>
        <div class="step-line <?= $current_step > 2 ? 'done' : '' ?>"></div>
        <div class="step <?= $current_step >= 3 ? 'active' : '' ?>">
            <div class="step-circle">3</div>
            <div class="step-label">New Password</div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <!-- ========== STEP 1: Enter Email ========== -->
    <?php if ($current_step == 1): ?>
        <form method="POST">
            <div class="form-group">
                <label>📧 Enter Your Registered Email</label>
                <input type="email" name="email"
                       placeholder="Enter your email address"
                       value="<?= isset($_POST['email']) ? sanitize($_POST['email']) : '' ?>"
                       required autofocus>
                <small style="color:var(--text-muted)">
                    We will send a 6-digit OTP to this email.
                </small>
            </div>
            <div class="form-group">
                <button type="submit" name="send_otp" class="btn btn-primary" style="width:100%;padding:0.8rem">
                    Send OTP →
                </button>
            </div>
        </form>

    <!-- ========== STEP 2: Enter OTP ========== -->
    <?php elseif ($current_step == 2): ?>

        <p style="text-align:center; color:var(--text-muted); font-size:0.9rem; margin-bottom:1rem;">
            OTP sent to <strong><?= sanitize($_SESSION['fp_email']) ?></strong>
        </p>

        <!-- Countdown Timer -->
        <div class="timer">
            OTP expires in: <span id="countdown">10:00</span>
        </div>

        <form method="POST" id="otpForm">
            <div class="form-group">
                <label style="text-align:center;display:block">Enter 6-Digit OTP</label>

                <!-- Individual OTP boxes for better UX -->
                <div class="otp-inputs">
                    <input type="text" maxlength="1" class="otp-box" id="otp1" autofocus>
                    <input type="text" maxlength="1" class="otp-box" id="otp2">
                    <input type="text" maxlength="1" class="otp-box" id="otp3">
                    <input type="text" maxlength="1" class="otp-box" id="otp4">
                    <input type="text" maxlength="1" class="otp-box" id="otp5">
                    <input type="text" maxlength="1" class="otp-box" id="otp6">
                </div>

                <!-- Hidden input that holds the combined OTP value -->
                <input type="hidden" name="otp" id="otp_combined">
            </div>

            <div class="form-group">
                <button type="submit" name="verify_otp" class="btn btn-primary" style="width:100%;padding:0.8rem">
                    Verify OTP →
                </button>
            </div>
        </form>

        <div style="text-align:center; margin-top:0.5rem;">
            <a href="forgot_password.php?reset=1" style="font-size:0.88rem; color:var(--text-muted)">
                ← Use different email
            </a>
        </div>

    <!-- ========== STEP 3: New Password ========== -->
    <?php elseif ($current_step == 3): ?>

        <p style="text-align:center; color:var(--success); font-weight:600; margin-bottom:1rem;">
            ✅ OTP Verified! Set your new password below.
        </p>

        <form method="POST" id="resetForm">
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" id="new_pass"
                       placeholder="Minimum 6 characters"
                       required autofocus>
            </div>

            <div class="form-group">
                <label> Confirm New Password</label>
                <input type="password" name="confirm_password" id="conf_pass"
                       placeholder="Re-enter new password"
                       required>
                <!-- Live match indicator -->
                <div id="match_msg" style="font-size:0.83rem; margin-top:5px;"></div>
            </div>

            <div class="form-group">
                <button type="submit" name="reset_password" class="btn btn-primary" style="width:100%;padding:0.8rem">
                     Reset Password
                </button>
            </div>
        </form>
    <?php endif; ?>

    <div class="form-footer">
        Remember your password? <a href="login.php">Login here</a>
    </div>
</div>

<script>
// ============================================================
// OTP Input Auto-focus: moves to next box on each keypress
// ============================================================
const otpBoxes = document.querySelectorAll('.otp-box');

otpBoxes.forEach(function(box, index) {
    box.addEventListener('input', function() {
        // Only allow numbers
        this.value = this.value.replace(/[^0-9]/g, '');

        if (this.value.length === 1 && index < otpBoxes.length - 1) {
            otpBoxes[index + 1].focus(); // Move to next box
        }

        // Combine all OTP digits into hidden input
        let combined = '';
        otpBoxes.forEach(function(b) { combined += b.value; });
        const hiddenOtp = document.getElementById('otp_combined');
        if (hiddenOtp) hiddenOtp.value = combined;
    });

    // Handle backspace - go back to previous box
    box.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && this.value === '' && index > 0) {
            otpBoxes[index - 1].focus();
        }
    });

    // Allow paste into first box - auto-fill all
    box.addEventListener('paste', function(e) {
        e.preventDefault();
        const pasted = (e.clipboardData || window.clipboardData).getData('text');
        const digits = pasted.replace(/[^0-9]/g, '').split('');
        digits.forEach(function(digit, i) {
            if (otpBoxes[i]) otpBoxes[i].value = digit;
        });
        let combined = '';
        otpBoxes.forEach(function(b) { combined += b.value; });
        const hiddenOtp = document.getElementById('otp_combined');
        if (hiddenOtp) hiddenOtp.value = combined;
        const lastFilled = Math.min(digits.length, otpBoxes.length) - 1;
        otpBoxes[lastFilled].focus();
    });
});

// ============================================================
// Countdown Timer (10 minutes = 600 seconds)
// ============================================================
<?php if ($current_step == 2 && isset($_SESSION['fp_expiry'])): ?>
const expiryTime = <?= $_SESSION['fp_expiry'] ?>;

function updateTimer() {
    const remaining = expiryTime - Math.floor(Date.now() / 1000);
    const timerEl   = document.getElementById('countdown');

    if (!timerEl) return;

    if (remaining <= 0) {
        timerEl.textContent = 'EXPIRED';
        timerEl.style.color = '#DC2626';
        // Disable the verify button
        const btn = document.querySelector('button[name="verify_otp"]');
        if (btn) {
            btn.disabled  = true;
            btn.textContent = 'OTP Expired - Start Again';
            btn.style.opacity = '0.6';
        }
        return;
    }

    const mins = Math.floor(remaining / 60);
    const secs = remaining % 60;
    timerEl.textContent = mins + ':' + (secs < 10 ? '0' : '') + secs;

    // Turn red when under 2 minutes
    if (remaining < 120) {
        timerEl.style.color = '#DC2626';
    }
}

updateTimer();
setInterval(updateTimer, 1000);
<?php endif; ?>

// ============================================================
// Password match indicator on step 3
// ============================================================
const confPass = document.getElementById('conf_pass');
if (confPass) {
    confPass.addEventListener('input', function() {
        const newPass = document.getElementById('new_pass').value;
        const msgEl   = document.getElementById('match_msg');

        if (this.value.length === 0) {
            msgEl.textContent = '';
        } else if (newPass === this.value) {
            msgEl.textContent  = '✅ Passwords match';
            msgEl.style.color  = 'var(--success)';
        } else {
            msgEl.textContent  = '❌ Passwords do not match';
            msgEl.style.color  = 'var(--danger)';
        }
    });
}
</script>

</body>
</html>
