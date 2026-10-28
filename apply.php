<?php
// ============================================================
// adopter/apply.php - Apply for Adoption
// Adopter submits their application with ID proof upload.
// Only 4 valid document types are accepted:
//   Aadhaar Card, PAN Card, Income Certificate, Voter ID
// ============================================================
session_start();
include '../db.php';

if (!isLoggedIn() || !isAdopter()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];
$error   = '';

// Get child ID from URL
$child_id = isset($_GET['child_id']) ? (int)$_GET['child_id'] : 0;
if ($child_id <= 0) redirect('children.php');

// ---- Load child information ----
$stmt = mysqli_prepare($conn, "SELECT * FROM children WHERE id=? AND status='available'");
mysqli_stmt_bind_param($stmt, "i", $child_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$child  = mysqli_fetch_assoc($result);
if (!$child) redirect('children.php');

// ---- Check if already applied ----
$check = mysqli_query($conn, "SELECT id FROM adoption_requests WHERE user_id=$user_id AND child_id=$child_id");
if (mysqli_num_rows($check) > 0) redirect('children.php?msg=already_applied');

// ============================================================
// ALLOWED DOCUMENT TYPES
// Only these 4 are accepted — anything else is rejected
// ============================================================
$allowed_doc_types = [
    'aadhaar'  => 'Aadhaar Card',
    'pan'      => 'PAN Card',
    'income'   => 'Income Certificate',
    'voter'    => 'Voter ID',
];

// ============================================================
// HANDLE FORM SUBMISSION
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $message_text  = sanitize($_POST['message']);
    $doc_type      = isset($_POST['doc_type']) ? $_POST['doc_type'] : '';
    $id_proof_name = null;

    // ---- Step 1: Validate document type selection ----
    if (!array_key_exists($doc_type, $allowed_doc_types)) {
        $error = "❌ Invalid document type selected. Please choose from: Aadhaar Card, PAN Card, Income Certificate, or Voter ID only.";
    }

    // ---- Step 2: Validate uploaded file ----
    if (empty($error)) {
        if (isset($_FILES['id_proof']) && $_FILES['id_proof']['error'] === 0) {
            $file = $_FILES['id_proof'];

            // Allowed MIME types (PDF documents only)
            $allowed_mime  = ['application/pdf'];
            $max_size      = 3 * 1024 * 1024; // 3MB

            // -- Check MIME type --
            if (!in_array($file['type'], $allowed_mime)) {
                $error = "❌ Invalid file format. Only PDF documents are accepted for ID proof.";
            }
            // -- Check file size --
            elseif ($file['size'] > $max_size) {
                $error = "❌ File size too large. ID proof must be less than 3MB.";
            }
            // -- Additional: check real MIME using finfo (prevents spoofing) --
            else {
                $finfo     = finfo_open(FILEINFO_MIME_TYPE);
                $real_mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                if (!in_array($real_mime, $allowed_mime)) {
                    $error = "❌ This file is not a valid document. Only PDF documents are accepted.";
                }
            }

            // -- Upload if all checks passed --
            if (empty($error)) {
                $ext           = pathinfo($file['name'], PATHINFO_EXTENSION);
                $id_proof_name = $doc_type . '_' . $user_id . '_' . time() . '.' . $ext;
                $upload_path   = '../uploads/id_proofs/' . $id_proof_name;

                if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $error = "❌ Failed to upload ID proof. Please check folder permissions.";
                    $id_proof_name = null;
                }
            }
        } else {
            $error = "❌ Please upload your ID proof document.";
        }
    }

    // ---- Step 3: Insert into database if no errors ----
    if (empty($error)) {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO adoption_requests (user_id, child_id, id_proof, message) VALUES (?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "iiss", $user_id, $child_id, $id_proof_name, $message_text);

        if (mysqli_stmt_execute($stmt)) {
            redirect('children.php?msg=applied');
        } else {
            $error = "❌ Failed to submit request. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Adoption - AdoptHope</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* ---- Document type cards ---- */
        .doc-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-top: 0.5rem;
        }
        .doc-option {
            position: relative;
        }
        .doc-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
        }
        .doc-option label {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.75rem 1rem;
            border: 2px solid var(--border);
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s;
            background: #fff;
            user-select: none;
        }
        .doc-option input[type="radio"]:checked + label {
            border-color: var(--primary);
            background: var(--primary-light, #eef4ff);
            color: var(--primary-dark);
        }
        .doc-option label:hover {
            border-color: var(--primary);
            background: var(--primary-light, #eef4ff);
        }
        .doc-icon { font-size: 1.4rem; }

        /* ---- Error box ---- */
        .alert-error {
            background: #fff0f0;
            border: 1.5px solid #f5a0a0;
            color: #c0392b;
            padding: 0.85rem 1.1rem;
            border-radius: 10px;
            margin-bottom: 1.2rem;
            font-weight: 500;
        }

        /* ---- File upload hint ---- */
        .upload-hint {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 0.35rem;
        }
        .upload-hint ul {
            margin: 0.3rem 0 0 1.2rem;
            padding: 0;
        }
    </style>
</head>
<body>

<div class="navbar">
    <div class="logo">🏠 <span>Adopt</span>Hope</div>
    <nav><a href="../logout.php">Logout</a></nav>
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
        <h1>📝 Apply for Adoption</h1>

        <!-- Child Info Preview -->
        <div class="card" style="max-width:600px; display:flex; gap:1.5rem; align-items:center; margin-bottom:1.5rem">
            <img src="../uploads/children/<?= sanitize($child['image']) ?>"
                 style="width:90px; height:90px; border-radius:12px; object-fit:cover; flex-shrink:0"
                 onerror="this.style.display='none'">
            <div>
                <h3 style="margin-bottom:0.3rem; color:var(--primary-dark)"><?= sanitize($child['name']) ?></h3>
                <p style="color:var(--text-muted); font-size:0.9rem">
                    Age: <?= $child['age'] ?> years &nbsp;|&nbsp; Gender: <?= $child['gender'] ?>
                </p>
                <?php if ($child['description']): ?>
                    <p style="font-size:0.85rem; margin-top:0.5rem"><?= sanitize($child['description']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Error Message -->
        <?php if ($error): ?>
            <div class="alert-error" style="max-width:600px"><?= $error ?></div>
        <?php endif; ?>

        <!-- Application Form -->
        <div class="card" style="max-width:600px">
            <p class="section-title">Your Application</p>
            <form method="POST" enctype="multipart/form-data">

                <!-- Adopter name (read-only) -->
                <div class="form-group">
                    <label>Your Name</label>
                    <input type="text" value="<?= sanitize($_SESSION['user_name']) ?>" disabled
                           style="background:#f8f9fa; color:var(--text-muted)">
                </div>

                <!-- ============================================================
                     DOCUMENT TYPE SELECTION
                     Only 4 valid types — user must pick one
                ============================================================ -->
                <div class="form-group">
                    <label>
                        Select ID Proof Type *
                        <small style="color:var(--text-muted); font-weight:400">
                            &nbsp;(Only the following 4 documents are accepted)
                        </small>
                    </label>

                    <div class="doc-options">

                        <div class="doc-option">
                            <input type="radio" name="doc_type" id="doc_aadhaar" value="aadhaar"
                                   <?= (isset($_POST['doc_type']) && $_POST['doc_type'] === 'aadhaar') ? 'checked' : '' ?> required>
                            <label for="doc_aadhaar">
                                <span class="doc-icon">🪪</span> Aadhaar Card
                            </label>
                        </div>

                        <div class="doc-option">
                            <input type="radio" name="doc_type" id="doc_pan" value="pan"
                                   <?= (isset($_POST['doc_type']) && $_POST['doc_type'] === 'pan') ? 'checked' : '' ?>>
                            <label for="doc_pan">
                                <span class="doc-icon">💳</span> PAN Card
                            </label>
                        </div>

                        <div class="doc-option">
                            <input type="radio" name="doc_type" id="doc_income" value="income"
                                   <?= (isset($_POST['doc_type']) && $_POST['doc_type'] === 'income') ? 'checked' : '' ?>>
                            <label for="doc_income">
                                <span class="doc-icon">📜</span> Income Certificate
                            </label>
                        </div>

                        <div class="doc-option">
                            <input type="radio" name="doc_type" id="doc_voter" value="voter"
                                   <?= (isset($_POST['doc_type']) && $_POST['doc_type'] === 'voter') ? 'checked' : '' ?>>
                            <label for="doc_voter">
                                <span class="doc-icon">🗳️</span> Voter ID
                            </label>
                        </div>

                    </div>
                </div>

                <!-- File Upload -->
                <div class="form-group">
                    <label>Upload Document *</label>
                    <input type="file" name="id_proof" accept=".pdf" required>
                    <div class="upload-hint">
                        Accepted format: PDF only &nbsp;|&nbsp; Max size: 3MB
                        <ul>
                            <li>Images of persons, passports, and driving licences are <strong>not accepted</strong>.</li>
                            <li>Make sure the document is clearly visible and not blurry.</li>
                        </ul>
                    </div>
                </div>

                <!-- Message / Reason -->
                <div class="form-group">
                    <label>Why do you want to adopt <?= sanitize($child['name']) ?>?</label>
                    <textarea name="message"
                              placeholder="Explain your reasons, living conditions, family background..."
                              rows="5"><?= isset($_POST['message']) ? sanitize($_POST['message']) : '' ?></textarea>
                </div>

                <!-- Submit / Cancel -->
                <div class="form-group" style="display:flex; gap:1rem">
                    <button type="submit" class="btn btn-accent">Submit Application</button>
                    <a href="children.php" class="btn" style="border:2px solid var(--border); color:var(--text)">Cancel</a>
                </div>

            </form>
        </div>

    </div>
</div>

</body>
</html>
