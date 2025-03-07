<?php
session_start();
require_once 'config/database.php';
require_once 'config/functions.php';

// Redirect if not logged in
redirectIfNotLoggedIn();

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action === 'add') {
    $product_id = $_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    
    if ($quantity < 1) $quantity = 1;
    
    // Check if product exists and has enough stock
    $query = "SELECT stock FROM products WHERE id = :product_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($quantity > $product['stock']) {
            $quantity = $product['stock'];
        }
        
        // Check if product is already in cart
        $query = "SELECT quantity FROM cart WHERE user_id = :user_id AND product_id = :product_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // Update quantity
            $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);
            $new_quantity = $cart_item['quantity'] + $quantity;
            
            if ($new_quantity > $product['stock']) {
                $new_quantity = $product['stock'];
            }
            
            $query = "UPDATE cart SET quantity = :quantity WHERE user_id = :user_id AND product_id = :product_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':quantity', $new_quantity);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':product_id', $product_id);
        } else {
            // Add new item to cart
            $query = "INSERT INTO cart (user_id, product_id, quantity) VALUES (:user_id, :product_id, :quantity)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':product_id', $product_id);
            $stmt->bindParam(':quantity', $quantity);
        }
        
        $stmt->execute();
    }
    
    // Redirect back to previous page or cart
    $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'cart.php';
    header("Location: $redirect");
    exit();
} elseif ($action === 'update') {
    $cart_id = $_POST['cart_id'];
    $quantity = (int)$_POST['quantity'];
    
    if ($quantity < 1) {
        // Remove item if quantity is less than 1
        $query = "DELETE FROM cart WHERE id = :cart_id AND user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':cart_id', $cart_id);
        $stmt->bindParam(':user_id', $user_id);
    } else {
        // Update quantity
        $query = "UPDATE cart SET quantity = :quantity WHERE id = :cart_id AND user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':cart_id', $cart_id);
        $stmt->bindParam(':user_id', $user_id);
    }
    
    $stmt->execute();
    
    header("Location: cart.php");
    exit();
} elseif ($action === 'remove') {
    $cart_id = $_POST['cart_id'];
    
    $query = "DELETE FROM cart WHERE id = :cart_id AND user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':cart_id', $cart_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    header("Location: cart.php");
    exit();
} elseif ($action === 'clear') {
    $query = "DELETE FROM cart WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    header("Location: cart.php");
    exit();
} else {
    header("Location: index.php");
    exit();
}
?>

