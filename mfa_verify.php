<?php
session_start();
require_once 'config/database.php';
require_once 'config/functions.php';

// Redirect if not in MFA process
if (!isset($_SESSION['mfa_user_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mfa_code = sanitize($_POST['mfa_code']);
    
    if (empty($mfa_code)) {
        $errors[] = "Verification code is required";
    } else {
        $query = "SELECT id, username, is_admin, mfa_code, mfa_code_expiry FROM users WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_SESSION['mfa_user_id']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($mfa_code === $user['mfa_code'] && strtotime($user['mfa_code_expiry']) > time()) {
                // MFA successful, log in the user
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = $user['is_admin'];
                
                // Clear MFA session and code
                unset($_SESSION['mfa_user_id']);
                $clear_query = "UPDATE users SET mfa_code = NULL, mfa_code_expiry = NULL WHERE id = :id";
                $clear_stmt = $db->prepare($clear_query);
                $clear_stmt->bindParam(':id', $user['id']);
                $clear_stmt->execute();
                
                header("Location: index.php");
                exit();
            } else {
                $errors[] = "Invalid or expired verification code";
            }
        } else {
            $errors[] = "User not found";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MFA Verification - E-Commerce Store</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="form-container">
            <h2>Enter Verification Code</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="error-messages">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form action="mfa_verify.php" method="post">
                <div class="form-group">
                    <label for="mfa_code">Verification Code</label>
                    <input type="text" id="mfa_code" name="mfa_code" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Verify</button>
            </form>
            
            <div class="form-footer">
                <p>Didn't receive the code? <a href="mfa_verify.php?resend=1">Resend</a></p>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>

