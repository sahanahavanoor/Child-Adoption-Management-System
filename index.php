<?php
session_start();
include 'db.php';

$count_result   = mysqli_query($conn, "SELECT COUNT(*) as total FROM children WHERE status='available'");
$total_children = mysqli_fetch_assoc($count_result)['total'];

$adopted_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM children WHERE status='adopted'");
$total_adopted  = mysqli_fetch_assoc($adopted_result)['total'];

$adopter_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='adopter'");
$total_adopters = mysqli_fetch_assoc($adopter_result)['total'];

$admin_result = mysqli_query($conn, "SELECT name, email FROM users WHERE role='admin' LIMIT 1");
$admin        = mysqli_fetch_assoc($admin_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HopeNest - Child Adoption System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        html { scroll-behavior: smooth; }

        .about-section {
            padding: 5rem 2rem;
            background: #fff;
        }
        .section-heading { text-align:center; margin-bottom:3rem; }
        .section-heading h2 { font-size:2rem; color:var(--primary-dark); margin-bottom:0.5rem; }
        .section-heading p  { color:var(--text-muted); max-width:550px; margin:0 auto; }

        .about-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            max-width: 1000px;
            margin: 0 auto;
        }
        @media(max-width:768px){ .about-grid{ grid-template-columns:1fr; } }

        .about-card {
            border-radius: var(--radius);
            border: 2px solid var(--border);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .about-card:hover { transform:translateY(-4px); box-shadow:0 10px 30px rgba(0,0,0,0.12); }

        .about-card-header {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            cursor: pointer;
            user-select: none;
        }
        .about-card-header .icon-wrap {
            width:50px; height:50px;
            background:rgba(255,255,255,0.15);
            border-radius:10px;
            display:flex; align-items:center; justify-content:center;
            font-size:1.5rem; flex-shrink:0;
        }
        .about-card-header h3 { color:#fff; font-size:1.1rem; margin:0; flex:1; }
        .about-card-header p  { color:rgba(255,255,255,0.75); font-size:0.82rem; margin:3px 0 0; }
        .toggle-icon { color:#fff; font-size:1.2rem; transition:transform 0.3s; }
        .toggle-icon.open { transform:rotate(180deg); }

        .about-card-body {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease, padding 0.3s ease;
            background: #fff;
            padding: 0 1.5rem;
        }
        .about-card-body.open { max-height:700px; padding:1.5rem; }

        .info-row {
            display:flex; align-items:flex-start; gap:0.8rem;
            padding:0.7rem 0; border-bottom:1px solid var(--border); font-size:0.92rem;
        }
        .info-row:last-child { border-bottom:none; }
        .info-icon  { font-size:1.1rem; width:24px; flex-shrink:0; margin-top:2px; }
        .info-label { font-weight:600; color:var(--text-muted); width:130px; flex-shrink:0; }
        .info-value { color:var(--text); flex:1; }

        .admin-avatar {
            width:70px; height:70px; border-radius:50%;
            background:linear-gradient(135deg,var(--primary-dark),var(--primary));
            display:flex; align-items:center; justify-content:center;
            font-size:2rem; color:#fff;
            margin:0 auto 1rem;
            box-shadow:0 4px 12px rgba(0,0,0,0.15);
        }

        .stats-bar { background:var(--primary-dark); padding:2.5rem 2rem; }
        .stats-bar-inner {
            max-width:800px; margin:0 auto;
            display:grid; grid-template-columns:repeat(3,1fr);
            gap:1rem; text-align:center;
        }
        .stats-bar-item .num { font-size:2.5rem; font-weight:800; color:var(--accent); display:block; }
        .stats-bar-item .lbl { color:rgba(255,255,255,0.75); font-size:0.88rem; margin-top:0.3rem; }

        .note-box {
            background:#FEF3C7; border-left:4px solid #D97706;
            border-radius:0 8px 8px 0; padding:0.8rem 1rem;
            font-size:0.85rem; color:#92400E; margin-top:1rem;
        }
        .email-btn {
            display:block; text-align:center; background:var(--primary);
            color:#fff; padding:0.7rem 1rem; border-radius:8px;
            font-weight:600; margin-top:1rem; transition:background 0.2s;
        }
        .email-btn:hover { background:var(--primary-dark); color:#fff; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<div class="navbar">
    <div class="logo">🏠 <span>Hope</span>Nest</div>
    <nav>
        <a href="index.php" class="active">Home</a>
        <a href="#about">About Us</a>
        <?php if (isLoggedIn()): ?>
            <?php if (isAdmin()): ?>
                <a href="admin/dashboard.php">Admin Panel</a>
            <?php else: ?>
                <a href="adopter/dashboard.php">Dashboard</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </nav>
</div>

<!-- HERO -->
<div class="hero">
    <h1>Give a Child a <span>Forever Home</span></h1>
    <p>Every child deserves love, care, and a family. Join our adoption program and make a difference in a child's life today.</p>
    <div class="hero-buttons">
        <?php if (!isLoggedIn()): ?>
            <a href="register.php" class="btn btn-accent">Start Adoption Process</a>
            <a href="#about" class="btn btn-outline">Learn About Us</a>
        <?php else: ?>
            <?php if (isAdopter()): ?>
                <a href="adopter/children.php" class="btn btn-accent">View Children</a>
            <?php else: ?>
                <a href="admin/dashboard.php" class="btn btn-accent">Go to Dashboard</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- LIVE STATS BAR -->
<div class="stats-bar">
    <div class="stats-bar-inner">
        <div class="stats-bar-item">
            <span class="num"><?= $total_children ?></span>
            <div class="lbl">👶 Children Available</div>
        </div>
        <div class="stats-bar-item">
            <span class="num"><?= $total_adopted ?></span>
            <div class="lbl">✅ Successfully Adopted</div>
        </div>
        <div class="stats-bar-item">
            <span class="num"><?= $total_adopters ?></span>
            <div class="lbl">👨‍👩‍👧 Registered Families</div>
        </div>
    </div>
</div>

<!-- HOW IT WORKS -->
<div class="info-section">
    <div style="text-align:center; margin-bottom:2.5rem;">
        <h2 style="color:var(--primary-dark);font-size:1.8rem;margin-bottom:0.5rem;">How It Works</h2>
        <p style="color:var(--text-muted)">Simple steps to start your adoption journey</p>
    </div>
    <div class="info-grid">
        <div class="info-card"><span class="icon-big">1️⃣</span><h3>Register</h3><p>Create your free adopter account with your basic details.</p></div>
        <div class="info-card"><span class="icon-big">2️⃣</span><h3>Browse Children</h3><p>View profiles of children who need a loving family.</p></div>
        <div class="info-card"><span class="icon-big">3️⃣</span><h3>Apply</h3><p>Submit your adoption request and upload your ID proof.</p></div>
        <div class="info-card"><span class="icon-big">4️⃣</span><h3>Get Approved</h3><p>Our admin reviews your request and notifies you of the decision.</p></div>
    </div>
</div>

<!-- ABOUT US SECTION -->
<div class="about-section" id="about">
    <div class="section-heading">
        <h2>🏡 About Us</h2>
        <p>Click on a card below to learn about our orphanage and contact our admin team.</p>
    </div>

    <div class="about-grid">

        <!-- Orphanage Card -->
        <div class="about-card">
            <div class="about-card-header" onclick="toggleCard('orphanage')">
                <div class="icon-wrap">🏠</div>
                <div>
                    <h3>HopeNest Orphanage</h3>
                    <p>Click to view orphanage details</p>
                </div>
                <div class="toggle-icon" id="icon-orphanage">▼</div>
            </div>
            <div class="about-card-body" id="body-orphanage">
                <div style="text-align:center;margin-bottom:1.2rem;">
                    <div style="font-size:3.5rem;">🏡</div>
                    <h3 style="color:var(--primary-dark);margin:0.3rem 0;">HopeNest Orphanage</h3>
                    <p style="color:var(--text-muted);font-size:0.85rem;">Government Registered | Est. 2010</p>
                </div>
                <div class="info-row"><span class="info-icon">📍</span><span class="info-label">Address</span><span class="info-value">123, Hope Street, Bengaluru, Karnataka - 560001</span></div>
                <div class="info-row"><span class="info-icon">📞</span><span class="info-label">Phone</span><span class="info-value">+91 98765 43210</span></div>
                <div class="info-row"><span class="info-icon">📧</span><span class="info-label">Email</span><span class="info-value"><a href="mailto:info@hopenest.org" style="color:var(--primary)">info@hopenest.org</a></span></div>
                <div class="info-row"><span class="info-icon">🌐</span><span class="info-label">Website</span><span class="info-value"><a href="#" style="color:var(--primary)">www.hopenest.org</a></span></div>
                <div class="info-row"><span class="info-icon">🕐</span><span class="info-label">Working Hours</span><span class="info-value">Mon – Sat: 9:00 AM to 5:00 PM</span></div>

                <div style="margin-top:1rem;padding:1rem;background:var(--bg);border-radius:8px;font-size:0.88rem;color:var(--text-muted);line-height:1.8;">
                    HopeNest is a government-registered orphanage dedicated to providing a safe, loving,
                    and nurturing environment for children in need. We work tirelessly towards finding
                    permanent, caring families for every child.
                </div>
            </div>
        </div>

        <!-- Admin Card -->
        <div class="about-card">
            <div class="about-card-header" onclick="toggleCard('admin')">
                <div class="icon-wrap">👨‍💼</div>
                <div>
                    <h3>Admin Contact Info</h3>
                    <p>Click to contact our administrator</p>
                </div>
                <div class="toggle-icon" id="icon-admin">▼</div>
            </div>
            <div class="about-card-body" id="body-admin">
                <div style="text-align:center;margin-bottom:1.2rem;">
                    <div class="admin-avatar">👤</div>
                    <h3 style="color:var(--primary-dark);margin:0;"><?= sanitize($admin['name']) ?></h3>
                    <p style="color:var(--text-muted);font-size:0.85rem;margin:3px 0 0;">Orphanage Administrator</p>
                </div>
                <div class="info-row"><span class="info-icon">📧</span><span class="info-label">Email</span><span class="info-value"><a href="mailto:<?= sanitize($admin['email']) ?>" style="color:var(--primary)"><?= sanitize($admin['email']) ?></a></span></div>
                <div class="info-row"><span class="info-icon">📞</span><span class="info-label">Direct Line</span><span class="info-value">+91 98765 43210</span></div>
                <div class="info-row"><span class="info-icon">🕐</span><span class="info-label">Available</span><span class="info-value">Mon – Sat, 10:00 AM to 4:00 PM</span></div>
                <div class="info-row"><span class="info-icon">📍</span><span class="info-label">Office</span><span class="info-value">Room 101, HopeNest Building</span></div>
                
            </div>
        </div>

    </div>
</div>

<!-- FOOTER -->
<footer>
    <p>© <?= date('Y') ?> <span>HopeNest Orphanage</span> — Child Adoption System </p>
    <p style="margin-top:0.4rem;font-size:0.8rem;opacity:0.7;">
    
    </p>
</footer>

<script>
function toggleCard(name) {
    const body = document.getElementById('body-' + name);
    const icon = document.getElementById('icon-' + name);
    body.classList.toggle('open');
    icon.classList.toggle('open');
}

// Auto open cards if navigated via About Us link
if (window.location.hash === '#about') {
    setTimeout(function() {
        toggleCard('orphanage');
        toggleCard('admin');
    }, 400);
}
</script>

</body>
</html>
