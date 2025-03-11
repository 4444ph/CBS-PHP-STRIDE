<?php

if (file_exists('vendor/autoload.php')) {
    require 'vendor/autoload.php';
} elseif (file_exists('../vendor/autoload.php')) {
    require '../vendor/autoload.php';
}

// Check if PHPMailer classes exist
$phpmailer_available = class_exists('PHPMailer\PHPMailer\PHPMailer');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function redirectIfNotAdmin() {
    if (!isAdmin()) {
        header("Location: index.php");
        exit();
    }
}

function getCartCount() {
    if (!isLoggedIn()) return 0;
    
    global $db;
    $user_id = $_SESSION['user_id'];
    
    $query = "SELECT SUM(quantity) as count FROM cart WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'] ? $result['count'] : 0;
}

function calculateCartTotal() {
    if (!isLoggedIn()) return 0;
    
    global $db;
    $user_id = $_SESSION['user_id'];
    
    $query = "SELECT SUM(c.quantity * p.price) as total 
              FROM cart c 
              JOIN products p ON c.product_id = p.id 
              WHERE c.user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ? $result['total'] : 0;
}

function getAverageRating($product_id) {
    global $db;
    
    $query = "SELECT AVG(rating) as avg_rating FROM reviews WHERE product_id = :product_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['avg_rating'] ? number_format($result['avg_rating'], 1) : 'No ratings';
}

function displayStars($rating) {
    if ($rating === 'No ratings') {
        return 'No ratings yet';
    }
    
    $rating = floatval($rating);
    $output = '';
    $fullStars = floor($rating);
    $halfStar = $rating - $fullStars >= 0.5;
    $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
    
    for ($i = 0; $i < $fullStars; $i++) {
        $output .= '★';
    }
    
    if ($halfStar) {
        $output .= '½';
    }
    
    for ($i = 0; $i < $emptyStars; $i++) {
        $output .= '☆';
    }
    
    return $output;
}

function generateMFACode() {
    return sprintf('%06d', mt_rand(0, 999999));
}

function sendMFACode($email, $code) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'jnavarez20@gmail.com'; // Replace with your Gmail address
        $mail->Password   = 'nqgw obkv lnyl cryd'; // Replace with your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('jnavarez20@gmail.com', 'CBS TESTING MFA');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your MFA Code for Login';
        $mail->Body    = "Your MFA code is: <b>$code</b>. This code will expire in 5 minutes.";
        $mail->AltBody = "Your MFA code is: $code. This code will expire in 5 minutes.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Log user login attempts
 * 
 * @param int|null $user_id User ID (null for failed logins)
 * @param string $username Username used for login attempt
 * @param string $status 'success' or 'failed'
 * @return bool Whether the log was successfully created
 */
function logLoginAttempt($user_id, $username, $status) {
    global $db;
    
    $query = "INSERT INTO audit_logs (user_id, username, action, status, ip_address) 
              VALUES (:user_id, :username, 'login', :status, :ip_address)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR']);
    
    return $stmt->execute();
}