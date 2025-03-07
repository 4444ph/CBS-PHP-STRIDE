<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

// Redirect if not admin
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

// Handle user deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = $_GET['delete'];
    
    // Prevent admin from deleting themselves
    if ($user_id != $_SESSION['user_id']) {
        $query = "DELETE FROM users WHERE id = :id AND is_admin = 0";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $user_id);
        
        if ($stmt->execute()) {
            header("Location: users.php?success=User deleted successfully");
            exit();
        } else {
            header("Location: users.php?error=Failed to delete user");
            exit();
        }
    } else {
        header("Location: users.php?error=You cannot delete your own account");
        exit();
    }
}

// Handle user suspension/activation
if (isset($_GET['toggle_suspension']) && is_numeric($_GET['toggle_suspension'])) {
    $user_id = $_GET['toggle_suspension'];
    
    // Prevent admin from suspending themselves
    if ($user_id != $_SESSION['user_id']) {
        $query = "UPDATE users SET is_suspended = NOT COALESCE(is_suspended, 0) WHERE id = :id AND is_admin = 0";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $user_id);
        
        if ($stmt->execute()) {
            header("Location: users.php?success=User status updated successfully");
            exit();
        } else {
            header("Location: users.php?error=Failed to update user status");
            exit();
        }
    } else {
        header("Location: users.php?error=You cannot suspend your own account");
        exit();
    }
}

// Get all users
$query = "SELECT *, COALESCE(is_suspended, 0) as is_suspended FROM users ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - E-Commerce Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="admin-content">
            <div class="content-header">
                <h1>Manage Users</h1>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="success-message">
                    <p><?php echo $_GET['success']; ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="error-message">
                    <p><?php echo $_GET['error']; ?></p>
                </div>
            <?php endif; ?>
            
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo $user['username']; ?></td>
                                <td><?php echo $user['email']; ?></td>
                                <td><?php echo $user['is_admin'] ? 'Admin' : 'User'; ?></td>
                                <td>
                                    <?php if ($user['is_admin']): ?>
                                        <span class="status-badge status-active">Active</span>
                                    <?php else: ?>
                                        <span class="status-badge status-<?php echo $user['is_suspended'] ? 'suspended' : 'active'; ?>">
                                            <?php echo $user['is_suspended'] ? 'Suspended' : 'Active'; ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php if (!$user['is_admin']): ?>
                                        <a href="users.php?toggle_suspension=<?php echo $user['id']; ?>" 
                                           class="btn btn-small <?php echo $user['is_suspended'] ? 'btn-primary' : 'btn-warning'; ?>"
                                           onclick="return confirm('Are you sure you want to <?php echo $user['is_suspended'] ? 'activate' : 'suspend'; ?> this user?')">
                                            <?php echo $user['is_suspended'] ? 'Activate' : 'Suspend'; ?>
                                        </a>
                                        <a href="users.php?delete=<?php echo $user['id']; ?>" 
                                           class="btn btn-small btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

