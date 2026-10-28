<?php
// ============================================================
// adopter/dashboard.php - Adopter Dashboard
// Shows summary + Orphanage Info + Admin Contact Info
// ============================================================
session_start();
include '../db.php';

if (!isLoggedIn() || !isAdopter()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];

$res1 = mysqli_query($conn, "SELECT COUNT(*) as total FROM adoption_requests WHERE user_id=$user_id");
$total_requests = mysqli_fetch_assoc($res1)['total'];

$res2 = mysqli_query($conn, "SELECT COUNT(*) as total FROM adoption_requests WHERE user_id=$user_id AND status='pending'");
$pending = mysqli_fetch_assoc($res2)['total'];

$res3 = mysqli_query($conn, "SELECT COUNT(*) as total FROM adoption_requests WHERE user_id=$user_id AND status='approved'");
$approved = mysqli_fetch_assoc($res3)['total'];

$admin_result = mysqli_query($conn, "SELECT name, email FROM users WHERE role='admin' LIMIT 1");
$admin = mysqli_fetch_assoc($admin_result);

$my_requests = mysqli_query($conn, "
    SELECT ar.id, c.name AS child_name, c.age, c.gender, c.image, ar.status, ar.applied_at
    FROM adoption_requests ar
    JOIN children c ON ar.child_id = c.id
    WHERE ar.user_id = $user_id
    ORDER BY ar.applied_at DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - HopeNest</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="navbar">
    <div class="logo">🏠 <span>Hope</span>Nest</div>
    <nav>
        <a href="../index.php">Home</a>
        
        <a href="../logout.php">Logout (<?= sanitize($_SESSION['user_name']) ?>)</a>
    </nav>
</div>

<div class="dashboard-layout">
    <div class="sidebar">
        <div class="sidebar-title">Adopter Menu</div>
        <a href="dashboard.php" class="active"><span class="icon">🏠</span> Dashboard</a>
        <a href="children.php"><span class="icon">👶</span> View Children</a>
        <a href="my_requests.php"><span class="icon">📋</span> My Requests</a>
                <a href="../logout.php"><span class="icon">🚪</span> Logout</a>
    </div>

    <div class="dashboard-content">
        <h1>Welcome, <?= sanitize($_SESSION['user_name']) ?> 👋</h1>

        <!-- STATS -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number"><?= $total_requests ?></span>
                <div class="stat-label">Total Requests</div>
            </div>
            <div class="stat-card">
                <span class="stat-number" style="color:var(--warning)"><?= $pending ?></span>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <span class="stat-number" style="color:var(--success)"><?= $approved ?></span>
                <div class="stat-label">Approved</div>
            </div>
        </div>

       

        <!-- RECENT REQUESTS -->
        <p class="section-title">My Recent Adoption Requests</p>
        <?php if (mysqli_num_rows($my_requests) > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Child Name</th>
                            <th>Age</th>
                            <th>Status</th>
                            <th>Applied On</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($req = mysqli_fetch_assoc($my_requests)): ?>
                        <tr>
                            <td>
                                <img src="../uploads/children/<?= sanitize($req['image']) ?>"
                                     style="width:45px;height:45px;object-fit:cover;border-radius:8px"
                                     onerror="this.style.display='none'">
                            </td>
                            <td><?= sanitize($req['child_name']) ?></td>
                            <td><?= $req['age'] ?> yrs</td>
                            <td><span class="badge badge-<?= $req['status'] ?>"><?= ucfirst($req['status']) ?></span></td>
                            <td><?= date('d M Y', strtotime($req['applied_at'])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                You haven't applied for any adoption yet.
                <a href="children.php">Browse children</a> to get started!
            </div>
        <?php endif; ?>

        <br>
        <a href="children.php" class="btn btn-accent">Browse Children →</a>
        &nbsp;
        <a href="my_requests.php" class="btn btn-primary">View All My Requests →</a>
    </div>
</div>

</body>
</html>
