<?php
session_start();
require_once 'config/database.php';
require_once 'config/functions.php';

// Redirect if not logged in
redirectIfNotLoggedIn();

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];

// Get cart items
$query = "SELECT c.quantity, p.id as product_id, p.name, p.price, p.stock 
          FROM cart c 
          JOIN products p ON c.product_id = p.id 
          WHERE c.user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Redirect if cart is empty
if (count($cart_items) == 0) {
    header("Location: cart.php");
    exit();
}

// Calculate totals
$subtotal = 0;
$shipping = 10; // Fixed shipping cost
$tax_rate = 0.1; // 10% tax

foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$tax = $subtotal * $tax_rate;
$total = $subtotal + $tax + $shipping;

// Get user information
$query = "SELECT * FROM users WHERE id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Process order
$errors = [];
$order_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = sanitize($_POST['address']);
    $payment_method = sanitize($_POST['payment_method']);
    
    // Validate input
    if (empty($shipping_address)) {
        $errors[] = "Shipping address is required";
    }
    
    if (empty($payment_method)) {
        $errors[] = "Payment method is required";
    }
    
    // Check if products are still in stock
    foreach ($cart_items as $item) {
        if ($item['quantity'] > $item['stock']) {
            $errors[] = "Sorry, {$item['name']} is out of stock. Please update your cart.";
        }
    }
    
    // Create order if no errors
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Create order
            $query = "INSERT INTO orders (user_id, total_amount, shipping_address, payment_method) 
                      VALUES (:user_id, :total_amount, :shipping_address, :payment_method)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':total_amount', $total);
            $stmt->bindParam(':shipping_address', $shipping_address);
            $stmt->bindParam(':payment_method', $payment_method);
            $stmt->execute();
            
            $order_id = $db->lastInsertId();
            
            // Add order items
            foreach ($cart_items as $item) {
                $query = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                          VALUES (:order_id, :product_id, :quantity, :price)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':order_id', $order_id);
                $stmt->bindParam(':product_id', $item['product_id']);
                $stmt->bindParam(':quantity', $item['quantity']);
                $stmt->bindParam(':price', $item['price']);
                $stmt->execute();
                
                // Update product stock
                $new_stock = $item['stock'] - $item['quantity'];
                $query = "UPDATE products SET stock = :stock WHERE id = :product_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':stock', $new_stock);
                $stmt->bindParam(':product_id', $item['product_id']);
                $stmt->execute();
            }
            
            // Clear cart
            $query = "DELETE FROM cart WHERE user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $db->commit();
            
            // Redirect to order confirmation
            header("Location: order_confirmation.php?id=$order_id");
            exit();
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = "Order processing failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - E-Commerce Store</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h1>Checkout</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="error-messages">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="checkout-container">
            <div class="checkout-form">
                <h2>Shipping Information</h2>
                <form action="checkout.php" method="post">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="<?php echo $user['username']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo $user['phone']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Shipping Address</label>
                        <textarea id="address" name="address" required><?php echo $user['address']; ?></textarea>
                    </div>
                    
                    <h2>Payment Method</h2>
                    <div class="form-group">
                        <label>
                            <input type="radio" name="payment_method" value="credit_card" checked> Credit Card
                        </label>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="radio" name="payment_method" value="paypal"> PayPal
                        </label>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="radio" name="payment_method" value="cash_on_delivery"> Cash on Delivery
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Place Order</button>
                </form>
            </div>
            
            <div class="order-summary">
                <h2>Order Summary</h2>
                <div class="order-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="order-item">
                            <span><?php echo $item['name']; ?> x <?php echo $item['quantity']; ?></span>
                            <span>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="order-totals">
                    <div class="summary-item">
                        <span>Subtotal</span>
                        <span>$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="summary-item">
                        <span>Tax (10%)</span>
                        <span>$<?php echo number_format($tax, 2); ?></span>
                    </div>
                    <div class="summary-item">
                        <span>Shipping</span>
                        <span>$<?php echo number_format($shipping, 2); ?></span>
                    </div>
                    <div class="summary-item total">
                        <span>Total</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>

