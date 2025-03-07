<?php
session_start();
require_once 'config/database.php';
require_once 'config/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    // Validate input
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    // Authenticate user
    if (empty($errors)) {
        $query = "SELECT id, username, email, password, is_admin, is_suspended, mfa_enabled FROM users WHERE username = :username OR email = :username";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user['is_suspended']) {
                $errors[] = "Your account has been suspended. Please contact the administrator.";
            } elseif (password_verify($password, $user['password'])) {
                if ($user['mfa_enabled']) {
                    // Generate and store MFA code
                    $mfa_code = generateMFACode();
                    $mfa_code_expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                    
                    $update_query = "UPDATE users SET mfa_code = :mfa_code, mfa_code_expiry = :mfa_code_expiry WHERE id = :id";
                    $update_stmt = $db->prepare($update_query);
                    $update_stmt->bindParam(':mfa_code', $mfa_code);
                    $update_stmt->bindParam(':mfa_code_expiry', $mfa_code_expiry);
                    $update_stmt->bindParam(':id', $user['id']);
                    $update_stmt->execute();
                    
                    // Send MFA code via email
                    if (sendMFACode($user['email'], $mfa_code)) {
                        // Redirect to MFA verification page
                        $_SESSION['mfa_user_id'] = $user['id'];
                        header("Location: mfa_verify.php");
                        exit();
                    } else {
                        $errors[] = "Failed to send MFA code. Please try again.";
                    }
                } else {
                    // Log in the user
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['is_admin'] = $user['is_admin'];
                    
                    header("Location: index.php");
                    exit();
                }
            } else {
                $errors[] = "Invalid username or password";
            }
        } else {
            $errors[] = "Invalid username or password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - E-Commerce Store</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="form-container">
            <h2>Login to Your Account</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="error-messages">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form action="login.php" method="post">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" value="<?php echo isset($username) ? $username : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
            
            <div class="form-footer">
                <p>Don't have an account? <a href="register.php">Register</a></p>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>

