<?php
// ============================================================
// adopter/children.php - View Available Children
// Adopters can browse children and apply for adoption.
// ============================================================
session_start();
include '../db.php';

if (!isLoggedIn() || !isAdopter()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];

// Get IDs of children this adopter has already applied for
// So we can disable the "Apply" button for those
$applied_result = mysqli_query($conn, "SELECT child_id FROM adoption_requests WHERE user_id=$user_id");
$applied_ids = [];
while ($row = mysqli_fetch_assoc($applied_result)) {
    $applied_ids[] = $row['child_id']; // Store applied child IDs in array
}

// ---- Fetch all available children ----
$children = mysqli_query($conn, "SELECT * FROM children WHERE status='available' ORDER BY created_at DESC");

$message = isset($_GET['msg']) ? sanitize($_GET['msg']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Children - AdoptHope</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="navbar">
    <div class="logo">🏠 <span>Adopt</span>Hope</div>
    <nav>
        <a href="../index.php">Home</a>
        
        <a href="../logout.php">Logout</a>
    </nav>
</div>

<div class="dashboard-layout">
    <div class="sidebar">
        <div class="sidebar-title">Adopter Menu</div>
        <a href="dashboard.php"><span class="icon">🏠</span> Dashboard</a>
        <a href="children.php" class="active"><span class="icon">👶</span> View Children</a>
        <a href="my_requests.php"><span class="icon">📋</span> My Requests</a>
                <a href="../logout.php"><span class="icon">🚪</span> Logout</a>
    </div>

    <div class="dashboard-content">
        <h1>👶 Children Available for Adoption</h1>

        <?php if ($message === 'applied'): ?>
            <div class="alert alert-success">
                Your adoption request has been submitted successfully! We'll review it soon.
            </div>
        <?php endif; ?>

        <?php if (mysqli_num_rows($children) > 0): ?>
            <div class="children-grid">
            <?php while ($child = mysqli_fetch_assoc($children)): ?>
                <div class="child-card">
                    <!-- Child Photo -->
                    <img src="../uploads/children/<?= sanitize($child['image']) ?>"
                         alt="<?= sanitize($child['name']) ?>"
                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNTYiIGhlaWdodD0iMjAwIiBmaWxsPSIjZTJlOGYwIj48cmVjdCB3aWR0aD0iMjU2IiBoZWlnaHQ9IjIwMCIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LXNpemU9IjQ4IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+8J+RpTwvdGV4dD48L3N2Zz4='">

                    <div class="child-card-body">
                        <h3><?= sanitize($child['name']) ?></h3>
                        <p>🎂 Age: <?= $child['age'] ?> years</p>
                        <p>⚥ Gender: <?= $child['gender'] ?></p>
                        <?php if ($child['description']): ?>
                            <p style="margin-top:0.5rem; font-size:0.85rem; color:var(--text-muted)">
                                <?= sanitize(substr($child['description'], 0, 100)) ?>...
                            </p>
                        <?php endif; ?>

                        <!-- Show Apply or Already Applied -->
                        <?php if (in_array($child['id'], $applied_ids)): ?>
                            <div class="btn" style="width:100%; text-align:center; background:#f0fdf4; color:var(--success); border:2px solid var(--success); margin-top:0.8rem; cursor:default">
                                ✓ Request Submitted
                            </div>
                        <?php else: ?>
                            <a href="apply.php?child_id=<?= $child['id'] ?>" class="btn btn-accent">
                                Apply for Adoption
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info" style="text-align:center; padding:3rem">
                <div style="font-size:3rem; margin-bottom:1rem">👶</div>
                No children are currently available for adoption. Please check back later.
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
