<?php
// ============================================================
// admin/requests.php - View & Manage Adoption Requests
// Admin can approve or reject each request here.
// ============================================================
session_start();
include '../db.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$message = '';

// ============================================================
// HANDLE APPROVE / REJECT ACTION
// ============================================================
if (isset($_GET['action']) && isset($_GET['id'])) {
    $req_id = (int)$_GET['id'];
    $action = $_GET['action'];

    // Only allow valid status values
    if (in_array($action, ['approved', 'rejected'])) {
        $reason = '';

if ($action === 'rejected' && isset($_GET['reason'])) {
    $reason = sanitize($_GET['reason']);
}

$upd = mysqli_prepare(
    $conn,
    "UPDATE adoption_requests SET status=?, rejection_reason=? WHERE id=?"
);

mysqli_stmt_bind_param($upd, "ssi", $action, $reason, $req_id);
        if (mysqli_stmt_execute($upd)) {
            // If approved, mark child as adopted
            if ($action === 'approved') {
                // Get child_id from the request
                $res = mysqli_query($conn, "SELECT child_id FROM adoption_requests WHERE id=$req_id");
                $req_row = mysqli_fetch_assoc($res);
                if ($req_row) {
                    mysqli_query($conn, "UPDATE children SET status='adopted' WHERE id=" . $req_row['child_id']);
                }
            }
            $message = "Request has been " . ucfirst($action) . " successfully.";
            
        }
    }
}

// ---- Filter by status if requested ----
$filter = isset($_GET['filter']) ? sanitize($_GET['filter']) : 'all';
$where = "";
if ($filter === 'pending')  $where = "WHERE ar.status='pending'";
if ($filter === 'approved') $where = "WHERE ar.status='approved'";
if ($filter === 'rejected') $where = "WHERE ar.status='rejected'";

// ---- Fetch all requests with user and child info ----
$requests = mysqli_query($conn, "
    SELECT ar.*, u.name AS user_name, u.email, c.name AS child_name, c.age, c.gender
    FROM adoption_requests ar
    JOIN users u ON ar.user_id = u.id
    JOIN children c ON ar.child_id = c.id
    $where
    ORDER BY ar.applied_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adoption Requests - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<script>
function rejectRequest(id, filter) {
    let reason = prompt("Enter reason for rejection:");

    if (reason && reason.trim() !== "") {
        window.location.href =
            "requests.php?action=rejected&id=" + id +
            "&reason=" + encodeURIComponent(reason) +
            "&filter=" + filter;
    }
}
</script>
<body>

<div class="navbar">
    <div class="logo">🏠 <span>Adopt</span>Hope - Admin</div>
    <nav><a href="../logout.php">Logout</a></nav>
</div>

<div class="dashboard-layout">
    <div class="sidebar">
        <div class="sidebar-title">Admin Menu</div>
        <a href="dashboard.php"><span class="icon">📊</span> Dashboard</a>
        <a href="children.php"><span class="icon">👶</span> Manage Children</a>
        <a href="add_child.php"><span class="icon">➕</span> Add Child</a>
        <a href="requests.php" class="active"><span class="icon">📋</span> Adoption Requests</a>
        <a href="../logout.php"><span class="icon">🚪</span> Logout</a>
    </div>

    <div class="dashboard-content">
        <h1>📋 Adoption Requests</h1>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>

        <!-- Filter buttons -->
        <div style="margin-bottom:1.5rem; display:flex; gap:0.6rem; flex-wrap:wrap;">
            <a href="requests.php?filter=all"
               class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : '' ?>"
               style="<?= $filter !== 'all' ? 'border:2px solid var(--border);color:var(--text)' : '' ?>">
               All
            </a>
            <a href="requests.php?filter=pending"
               class="btn btn-sm <?= $filter === 'pending' ? 'btn-primary' : '' ?>"
               style="<?= $filter !== 'pending' ? 'border:2px solid var(--border);color:var(--text)' : '' ?>">
               Pending
            </a>
            <a href="requests.php?filter=approved"
               class="btn btn-sm <?= $filter === 'approved' ? 'btn-primary' : '' ?>"
               style="<?= $filter !== 'approved' ? 'border:2px solid var(--border);color:var(--text)' : '' ?>">
               Approved
            </a>
            <a href="requests.php?filter=rejected"
               class="btn btn-sm <?= $filter === 'rejected' ? 'btn-primary' : '' ?>"
               style="<?= $filter !== 'rejected' ? 'border:2px solid var(--border);color:var(--text)' : '' ?>">
               Rejected
            </a>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Adopter</th>
                        <th>Email</th>
                        <th>Child</th>
                        <th>ID Proof</th>
                        <th>Message</th>
                
                        
                        <th>Status</th>
<th>Reason</th>
<th>Date</th>
                        
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($requests) > 0): ?>
                    <?php while ($req = mysqli_fetch_assoc($requests)): ?>
                    <tr>
                        <td><?= $req['id'] ?></td>
                        <td><?= sanitize($req['user_name']) ?></td>
                        <td style="font-size:0.82rem"><?= sanitize($req['email']) ?></td>
                        <td>
                            <?= sanitize($req['child_name']) ?>
                            <br><small style="color:var(--text-muted)"><?= $req['age'] ?> yrs, <?= $req['gender'] ?></small>
                        </td>
                        <td>
                            <?php if ($req['id_proof']): ?>
                                <!-- Link to view uploaded ID proof -->
                                <a href="../uploads/id_proofs/<?= sanitize($req['id_proof']) ?>"
                                   target="_blank"
                                   class="btn btn-sm"
                                   style="border:2px solid var(--border)">
                                   View Proof
                                </a>
                            <?php else: ?>
                                <span style="color:var(--text-muted)">None</span>
                            <?php endif; ?>
                        </td>
                        <td style="max-width:150px; font-size:0.82rem">
                            <?= $req['message'] ? sanitize(substr($req['message'], 0, 80)) . '...' : '-' ?>
                        </td>
                        <td>
                            <span class="badge badge-<?= $req['status'] ?>">
                                <?= ucfirst($req['status']) ?>
                            </span>
                        </td>
                        <td>
    <?= !empty($req['rejection_reason'])
        ? sanitize($req['rejection_reason'])
        : '-' ?>
</td>
                        <td style="font-size:0.82rem"><?= date('d M Y', strtotime($req['applied_at'])) ?></td>
                        <td>
                            <?php if ($req['status'] === 'pending'): ?>
                                <!-- Show Approve/Reject only for pending requests -->
                                <a href="requests.php?action=approved&id=<?= $req['id'] ?>&filter=<?= $filter ?>"
                                   class="btn btn-sm btn-success"
                                   onclick="return confirm('Approve this adoption request?')">
                                   ✓ Approve
                                </a>
                                <br><br>
                                <a href="#"
   class="btn btn-sm btn-danger"
   onclick="rejectRequest(<?= $req['id'] ?>,'<?= $filter ?>')">
   ✗ Reject
</a>
                                
                            <?php else: ?>
                                <span style="color:var(--text-muted); font-size:0.82rem">Decided</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align:center; padding:2rem; color:var(--text-muted)">
                            No adoption requests found.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
