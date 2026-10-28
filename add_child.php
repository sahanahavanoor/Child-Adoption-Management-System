<?php
// ============================================================
// admin/add_child.php - Add New Child Record
// Admin fills in child details and uploads a photo.
// ============================================================
session_start();
include '../db.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$error = '';
$success = '';

// ============================================================
// HANDLE FORM SUBMISSION
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name        = sanitize($_POST['name']);
    $age         = (int)$_POST['age'];
    $gender      = sanitize($_POST['gender']);
    $description = sanitize($_POST['description']);
    $image_name  = 'default.png'; // Default if no image uploaded

    // Validate required fields
    if (empty($name) || $age <= 0 || empty($gender)) {
        $error = "Name, Age, and Gender are required.";

    } else {
        // ---- Handle Image Upload ----
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $file = $_FILES['image'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            $max_size = 2 * 1024 * 1024; // 2MB

            if (!in_array($file['type'], $allowed_types)) {
                $error = "Only JPG, PNG, GIF images are allowed.";
            } elseif ($file['size'] > $max_size) {
                $error = "Image must be less than 2MB.";
            } else {
                // Create a unique filename to avoid overwrites
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $image_name = 'child_' . time() . '_' . rand(100, 999) . '.' . $ext;
                $upload_path = '../uploads/children/' . $image_name;

                if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $error = "Failed to upload image. Check folder permissions.";
                    $image_name = 'default.png';
                }
            }
        }

        if (empty($error)) {
            // Insert child record into database
            $stmt = mysqli_prepare($conn,
                "INSERT INTO children (name, age, gender, description, image) VALUES (?, ?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt, "sisss", $name, $age, $gender, $description, $image_name);

            if (mysqli_stmt_execute($stmt)) {
                $success = "Child added successfully!";
            } else {
                $error = "Database error. Please try again.";
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
    <title>Add Child - Admin</title>
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
        <a href="children.php"><span class="icon">👶</span> Manage Children</a>
        <a href="add_child.php" class="active"><span class="icon">➕</span> Add Child</a>
        <a href="requests.php"><span class="icon">📋</span> Adoption Requests</a>
        <a href="../logout.php"><span class="icon">🚪</span> Logout</a>
    </div>

    <div class="dashboard-content">
        <h1>➕ Add New Child</h1>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= $success ?> <a href="children.php">View all children</a>
            </div>
        <?php endif; ?>

        <div class="card" style="max-width:600px">
            <!-- enctype needed for file upload -->
            <form method="POST" enctype="multipart/form-data">

                <div class="form-group">
                    <label>Child's Name *</label>
                    <input type="text" name="name" placeholder="Full name of the child"
                           value="<?= isset($_POST['name']) ? sanitize($_POST['name']) : '' ?>"
                           required>
                </div>

                <div class="form-group">
                    <label>Age (in years) *</label>
                    <input type="number" name="age" min="0" max="18"
                           placeholder="Age"
                           value="<?= isset($_POST['age']) ? (int)$_POST['age'] : '' ?>"
                           required>
                </div>

                <div class="form-group">
                    <label>Gender *</label>
                    <select name="gender" required>
                        <option value="">-- Select Gender --</option>
                        <option value="Male"   <?= (isset($_POST['gender']) && $_POST['gender'] === 'Male')   ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= (isset($_POST['gender']) && $_POST['gender'] === 'Female') ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" placeholder="Write a brief description about the child (personality, interests, health, etc.)"><?= isset($_POST['description']) ? sanitize($_POST['description']) : '' ?></textarea>
                </div>

                <div class="form-group">
                    <label>Child's Photo</label>
                    <input type="file" name="image" accept="image/*"
                           onchange="previewImage(this)">
                    <small style="color:var(--text-muted)">JPG/PNG/GIF only. Max 2MB.</small>
                    <!-- Preview box -->
                    <div id="preview" style="margin-top:10px; display:none;">
                        <img id="previewImg" src="" style="max-width:150px; border-radius:8px; border:2px solid var(--border);">
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Add Child</button>
                    &nbsp;
                    <a href="children.php" class="btn btn-outline" style="color:var(--text); border-color:var(--border)">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript: Image preview before upload -->
<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('preview').style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

</body>
</html>
