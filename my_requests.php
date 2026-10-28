<?php
// ============================================================
// adopter/my_requests.php - View My Adoption Requests
// Shows all requests made by the logged-in adopter.
// ============================================================
session_start();
include '../db.php';

if (!isLoggedIn() || !isAdopter()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];

// Fetch all requests by this adopter with child details
$requests = mysqli_query($conn, "
    SELECT ar.id, ar.status, ar.applied_at, ar.message, ar.id_proof,
           c.name AS child_name, c.age, c.gender, c.image
    FROM adoption_requests ar
    JOIN children c ON ar.child_id = c.id
    WHERE ar.user_id = $user_id
    ORDER BY ar.applied_at DESC
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Requests - AdoptHope</title>
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
        <a href="children.php"><span class="icon">👶</span> View Children</a>
        <a href="my_requests.php" class="active"><span class="icon">📋</span> My Requests</a>
                <a href="../logout.php"><span class="icon">🚪</span> Logout</a>
    </div>

    <div class="dashboard-content">
        <h1>📋 My Adoption Requests</h1>

        <?php if (mysqli_num_rows($requests) > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Child</th>
                            <th>Details</th>
                            <th>My Message</th>
                            <th>Status</th>
                            <th>Status</th>
<th>Rejection Reason</th>
<th>Applied On</th>
                            
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($req = mysqli_fetch_assoc($requests)): ?>
                        <tr>
                            <td>
                                <img src="../uploads/children/<?= sanitize($req['image']) ?>"
                                     style="width:55px; height:55px; object-fit:cover; border-radius:8px"
                                     onerror="this.style.display='none'">
                            </td>
                            <td><?= sanitize($req['child_name']) ?></td>
                            <td style="font-size:0.85rem; color:var(--text-muted)">
                                <?= $req['age'] ?> yrs, <?= $req['gender'] ?>
                            </td>
                            <td style="max-width:200px; font-size:0.85rem">
                                <?= $req['message'] ? sanitize(substr($req['message'], 0, 100)) . '...' : '-' ?>
                            </td>
                            <td>
                                <span class="badge badge-<?= $req['status'] ?>">
                                    <?= ucfirst($req['status']) ?>
                                </span>
                                <!-- Show a helpful message based on status -->
                                <br>
                                <small style="color:var(--text-muted); font-size:0.75rem">
                                    <?php
                                    if ($req['status'] === 'pending')  echo 'Under Review';
                                    if ($req['status'] === 'approved') echo '🎉 Congratulations!';
                                    if ($req['status'] === 'rejected') echo 'Not this time';
                                    ?>
                                </small>
                            </td>
                            <td>
    <?php
    if ($req['status'] === 'rejected') {
        echo sanitize($req['rejection_reason']);
    } else {
        echo '-';
    }
    ?>
</td>
                            <td style="font-size:0.85rem"><?= date('d M Y', strtotime($req['applied_at'])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info" style="text-align:center; padding:3rem">
                <div style="font-size:3rem; margin-bottom:1rem">📋</div>
                You haven't applied for any adoption yet.<br><br>
                <a href="children.php" class="btn btn-accent">Browse Available Children</a>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
