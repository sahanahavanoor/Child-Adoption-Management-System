<?php
// ============================================================
// admin/edit_child.php - Edit Existing Child Record
// Loads current data and allows admin to update it.
// ============================================================
session_start();
include '../db.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$error = '';
$success = '';

// Get child ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    redirect('children.php');
}

// ---- Load current child data ----
$stmt = mysqli_prepare($conn, "SELECT * FROM children WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$child = mysqli_fetch_assoc($result);

if (!$child) {
    redirect('children.php'); // Child not found
}

// ============================================================
// HANDLE FORM SUBMISSION (UPDATE)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name        = sanitize($_POST['name']);
    $age         = (int)$_POST['age'];
    $gender      = sanitize($_POST['gender']);
    $description = sanitize($_POST['description']);
    $status      = sanitize($_POST['status']);
    $image_name  = $child['image']; // Keep old image by default

    if (empty($name) || $age <= 0) {
        $error = "Name and Age are required.";
    } else {
        // ---- Handle new image upload if provided ----
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $file = $_FILES['image'];
            $allowed = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];

            if (in_array($file['type'], $allowed) && $file['size'] <= 2 * 1024 * 1024) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_name = 'child_' . time() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], '../uploads/children/' . $new_name)) {
                    // Delete old image
                    if ($child['image'] && $child['image'] !== 'default.png') {
                        @unlink('../uploads/children/' . $child['image']);
                    }
                    $image_name = $new_name;
                }
            }
        }

        // Update the database record
        $upd = mysqli_prepare($conn,
            "UPDATE children SET name=?, age=?, gender=?, description=?, image=?, status=? WHERE id=?"
        );
        mysqli_stmt_bind_param($upd, "sissssi1.7Future Enhancement and Limitations", $name, $age, $gender, $description, $image_name, $status, $id);

        if (mysqli_stmt_execute($upd)) {
            $success = "Child record updated successfully!";
            // Reload child data to show updated values
            $child['name'] = $name;
            $child['age'] = $age;
            $child['gender'] = $gender;
            $child['description'] = $description;
            $child['status'] = $status;
        } else {
            $error = "Update failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Child - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="navbar">
    <div class="logo">🏠 <span>Adopt</span>Hope - Admin</div>
    <nav><a href="../logout.php">Logout</a></nav>
</div>

<div class="dashboard-layout">
    <div class="sidebar">
        <div class="sidebar-title">Admin Menu</div>
        <a href="dashboard.php"><span class="icon">📊</span> Dashboard</a>
        <a href="children.php" class="active"><span class="icon">👶</span> Manage Children</a>
        <a href="add_child.php"><span class="icon">➕</span> Add Child</a>
        <a href="requests.php"><span class="icon">📋</span> Adoption Requests</a>
        <a href="../logout.php"><span class="icon">🚪</span> Logout</a>
    </div>

    <div class="dashboard-content">
        <h1>✏️ Edit Child: <?= sanitize($child['name']) ?></h1>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <div class="card" style="max-width:600px">
            <form method="POST" enctype="multipart/form-data">

                <div class="form-group">
                    <label>Child's Name *</label>
                    <input type="text" name="name" value="<?= sanitize($child['name']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Age *</label>
                    <input type="number" name="age" value="<?= $child['age'] ?>" min="0" max="18" required>
                </div>

                <div class="form-group">
                    <label>Gender *</label>
                    <select name="gender">
                        <option value="Male"   <?= $child['gender'] === 'Male'   ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= $child['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="available" <?= $child['status'] === 'available' ? 'selected' : '' ?>>Available</option>
                        <option value="adopted"   <?= $child['status'] === 'adopted'   ? 'selected' : '' ?>>Adopted</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description"><?= sanitize($child['description']) ?></textarea>
                </div>

                <div class="form-group">
                    <label>Current Photo</label><br>
                    <img src="../uploads/children/<?= sanitize($child['image']) ?>"
                         style="width:100px; border-radius:8px; border:2px solid var(--border); margin-bottom:10px"
                         onerror="this.src='../assets/img/default.png'">
                    <br>
                    <label>Replace Photo (optional)</label>
                    <input type="file" name="image" accept="image/*">
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Update Child</button>
                    &nbsp;
                    <a href="children.php" class="btn" style="border:2px solid var(--border); color:var(--text)">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
