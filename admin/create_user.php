<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}
include "../config/database.php";

$pdo = (new Database())->connect();

$msg = "";
$editUser = null;

// Helper Function for File Upload
function uploadFile($file, $dir, $prefix) {
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $prefix . '_' . time() . '_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], $dir . $filename)) {
            return $filename;
        }
    }
    return null;
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
 if ($_GET['action'] === 'promote') {
        try {
            $pdo->beginTransaction();
            // Demote all existing managers to user
            $pdo->exec("UPDATE users SET role = 'user' WHERE role = 'manager'");
            // Promote the selected user
            $stmt = $pdo->prepare("UPDATE users SET role = 'manager' WHERE id = ?");
            $stmt->execute([$id]);
             $pdo->commit();
            $msg = "<div class='alert alert-success alert-dismissible fade show'>Manager updated successfully! Only 1 manager is allowed.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = "<div class='alert alert-danger'>Error updating manager.</div>";
        }
    }

       // 2. Toggle Status (Active/Inactive)
    if ($_GET['action'] === 'status') {
        $stmt = $pdo->prepare("UPDATE users SET status = NOT status WHERE id = ?");
        $stmt->execute([$id]);
        $msg = "<div class='alert alert-success alert-dismissible fade show'>User status updated!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }

    // 3. Delete User
    if ($_GET['action'] === 'delete' && $id != $_SESSION['user_id']) {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$id])) {
                $msg = "<div class='alert alert-success alert-dismissible fade show'>User deleted successfully!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            }
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                $msg = "<div class='alert alert-warning alert-dismissible fade show'>Cannot delete user with existing records. Please make them <strong>Inactive</strong> instead.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            } else {
                $msg = "<div class='alert alert-danger'>Error deleting user.</div>";
            }
        }
    }

    // 4. Edit User (Fetch Data)
    if ($_GET['action'] === 'edit') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $editUser = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

    /* UPDATE USER */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $id       = $_POST['user_id'];
    $name     = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    
if (empty($name) || empty($username) || empty($email) || empty($phone)) {
        $msg = "<div class='alert alert-danger'>All text fields are required.</div>";
    } else {
        // Check username uniqueness (excluding current user)
        $check = $pdo->prepare("SELECT id FROM users WHERE username=? AND id != ?");
        $check->execute([$username, $id]);

    if ($check->rowCount() > 0) {
            $msg = "<div class='alert alert-danger'>Username already exists</div>";
        } else {
            $sql = "UPDATE users SET full_name=?, username=?, email=?, phone=?";
            $params = [$name, $username, $email, $phone];

            if (!empty($_POST['password'])) {
                $sql .= ", password=?";
                $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }

            $uploadDir = "../uploads/";
            if (!empty($_FILES['photo']['name']) && $path = uploadFile($_FILES['photo'], $uploadDir, 'avatar')) {
                $sql .= ", photo=?"; $params[] = $path;
            }
            if (!empty($_FILES['nid_photo']['name']) && $path = uploadFile($_FILES['nid_photo'], $uploadDir, 'nid')) {
                $sql .= ", nid_photo=?"; $params[] = $path;
            }

            $sql .= " WHERE id=?";
            $params[] = $id;

            if ($pdo->prepare($sql)->execute($params)) {
                $msg = "<div class='alert alert-success'>User updated successfully!</div>";
                echo "<script>setTimeout(()=>window.location.href='create_user.php', 1000)</script>";
            } else {
                $msg = "<div class='alert alert-danger'>Update failed.</div>";
            }
        }
    }
}

    /* CREATE USER */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $name     = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    // Default role is always 'user' first
    $role = 'user';

    // Validation: Ensure all text fields are filled
    if (empty($name) || empty($username) || empty($_POST['password']) || empty($email) || empty($phone)) {
        $msg = "<div class='alert alert-danger'>All text fields are required.</div>";
    } elseif (empty($_FILES['nid_photo']['name'])) {
        $msg = "<div class='alert alert-danger'>NID Photo is required.</div>";
    } else 
    {// File Upload Logic
        $uploadDir = "../uploads/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $photoPath = uploadFile($_FILES['photo'], $uploadDir, 'avatar');
    $nidPath   = uploadFile($_FILES['nid_photo'], $uploadDir, 'nid');

    $check = $pdo->prepare("SELECT id FROM users WHERE username=?");
    $check->execute([$username]);

     if ($check->rowCount() > 0) {
        $msg = "<div class='alert alert-danger'>Username already exists</div>";
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO users (full_name, username, password, role, email, phone, photo, nid_photo, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)"
        );
          if ($stmt->execute([$name, $username, $password, $role, $email, $phone, $photoPath, $nidPath])) {
            $msg = "<div class='alert alert-success alert-dismissible fade show'>User created successfully! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        } else {
            $msg = "<div class='alert alert-danger'>Database error.</div>";
        }
    }
    }
}
    // --- FETCH USERS FOR LIST ---
$users = $pdo->query("SELECT * FROM users ORDER BY FIELD(role, 'admin', 'manager', 'user'), id DESC")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "User Management | Admin";
ob_start();
?>
<style>
    .card { border: none; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    .form-control { border-radius: 10px; padding: 12px; border: 1px solid #e2e8f0; }
    .form-control:focus { box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); border-color: #3b82f6; }
    .btn-primary { border-radius: 10px; padding: 12px 24px; font-weight: 600; }


    
</style>
<?= $msg ?>

    <div class="row g-4">
        <!-- LEFT: CREATE USER FORM -->
        <div class="col-lg-4">
            <div class="card p-4 h-100">
                <h5 class="fw-bold mb-4 text-secondary">
                    <i class="bi bi-person-<?= $editUser ? 'gear' : 'plus' ?>-fill me-2"></i>
                    <?= $editUser ? 'Edit Member' : 'Add New Member' ?>
                </h5>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="<?= $editUser ? 'update_user' : 'create_user' ?>" value="1">
                    <?php if($editUser): ?><input type="hidden" name="user_id" value="<?= $editUser['id'] ?>"><?php endif; ?>
                    <!-- Photo Preview -->
                    <div class="text-center mb-3">
                        <div class="avatar-upload">
                        <?php 
                            $preview = "https://via.placeholder.com/100?text=Photo";
                            if($editUser && !empty($editUser['photo'])) $preview = "../uploads/" . $editUser['photo'];
                        ?>
    <img src="<?= $preview ?>" id="preview" class="avatar-preview">
                        </div>
                        <label class="btn btn-sm btn-outline-primary rounded-pill px-3">
                            <i class="bi bi-camera me-1"></i> Upload Photo
                            <input type="file" name="photo" class="d-none" accept="image/*" onchange="document.getElementById('preview').src = window.URL.createObjectURL(this.files[0])">
                        </label>
                    </div>
      <iv class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">FULL NAME</label>
                            <input type="text" name="full_name" class="form-control" placeholder="e.g. Towfiq omar" value="<?= $editUser['full_name'] ?? '' ?>" required>
                        </div>
        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">USERNAME</label>
                            <input type="text" name="username" class="form-control" placeholder="e.g. towfiq123" value="<?= $editUser['username'] ?? '' ?>" required>
                        </div>
<div class="col-12">
                            <label class="form-label small fw-bold text-muted">PASSWORD</label>
                            <input type="password" name="password" class="form-control" placeholder="<?= $editUser ? 'Leave blank to keep current' : '******' ?>" <?= $editUser ? '' : 'required' ?>>
                        </div>
          <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">PHONE</label>
                            <input type="text" name="phone" class="form-control" placeholder="017..." value="<?= $editUser['phone'] ?? '' ?>" required>
                        </div>
          <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">EMAIL</label>
                            <input type="email" name="email" class="form-control" placeholder="mail@example.com" value="<?= $editUser['email'] ?? '' ?>" required>
                        </div>
          <div class="col-12">
                            <label class="form-label small fw-bold text-muted">NID PHOTO</label>
                            <input type="file" name="nid_photo" class="form-control" accept="image/*" <?= $editUser ? '' : 'required' ?>>
                            <?php if($editUser && !empty($editUser['nid_photo'])): ?>
                                <small class="text-success"><i class="bi bi-check-circle"></i> Uploaded</small>
                            <?php endif; ?>
</div>
                        <div class="col-12 mt-4">
                            <button class="btn btn-primary w-100 shadow-sm mb-2">
                                <i class="bi bi-check-lg me-2"></i> <?= $editUser ? 'Update Member' : 'Create Member' ?>
                            </button>
                            <?php if($editUser): ?>
                                <a href="create_user.php" class="btn btn-light w-100 text-muted">Cancel Edit</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>

          <!-- RIGHT: USER LIST -->
        <div class="col-lg-8">
            <div class="card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold text-secondary mb-0"><i class="bi bi-people-fill me-2"></i>Member List</h5>
                    <span class="badge bg-light text-dark border"><?= count($users) ?> Members</span>
                </div>
        <div class="table-responsive">
                    <table class="table table-hover align-middle text-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Member</th>
                                <th>Contact</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $u): 
                                $photo = !empty($u['photo']) ? "../uploads/".$u['photo'] : "https://ui-avatars.com/api/?name=".urlencode($u['full_name'])."&background=random";
                            ?>
                                <tr>
                                <td class="ps-3">
                                    <div class="d-flex align-items-center">
                                        <img src="<?= $photo ?>" class="user-avatar-sm me-3">
                                        <div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($u['full_name']) ?></div>
                                            <div class="small text-muted">@<?= htmlspecialchars($u['username']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                    <td>
                                    <div class="small"><i class="bi bi-telephone me-1"></i> <?= htmlspecialchars($u['phone'] ?? '-') ?></div>
                                    <div class="small text-muted"><i class="bi bi-envelope me-1"></i> <?= htmlspecialchars($u['email'] ?? '-') ?></div>
                                </td>
                                    <td>
                                    <?php if($u['role'] === 'admin'): ?>
                                        <span class="role-badge role-admin">Admin</span>
                                    <?php elseif($u['role'] === 'manager'): ?>
                                        <span class="role-badge role-manager">Manager</span>
                                    <?php else: ?>
                                        <span class="role-badge role-user">User</span>
                                    <?php endif; ?>
                                </td>
                                    <td>
                                    <?php if($u['status']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                    
<?php
$content = ob_get_clean();
include "layout.php";
?>
