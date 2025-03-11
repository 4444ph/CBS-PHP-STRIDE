<?php
session_start();
require_once 'config/database.php';
require_once 'config/functions.php';

// Log the logout if user is logged in
if (isLoggedIn()) {
    $database = new Database();
    $db = $database->getConnection();
    
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['username'];
    
    // Log the logout action
    $query = "INSERT INTO audit_logs (user_id, username, action, status, ip_address) 
              VALUES (:user_id, :username, 'logout', 'success', :ip_address)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR']);
    $stmt->execute();
}

// Destroy the session
session_destroy();
header("Location: login.php");
exit();
?>

