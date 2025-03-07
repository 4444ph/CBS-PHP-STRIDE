<?php
session_start();
require_once 'config/database.php';
require_once 'config/functions.php';

// Redirect if not logged in
redirectIfNotLoggedIn();

$database = new Database();
$db = $database->getConnection();

// Get order ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$order_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Get order details
$query = "SELECT * FROM orders WHERE id = :order_id AND user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':order_id', $order_id);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: index.php");
    exit();
}

$order = $stmt->fetch(PDO::FETCH_ASSOC);

// Get order items
$query = "SELECT oi.*, p.name, p.image 
          FROM order_items oi 
          JOIN products p ON oi.product_id = p.id 
          WHERE oi.order_id = :order_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':order_id', $order_id);
$stmt->execute();
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$subtotal = 0;
$shipping = 10; // Fixed shipping cost
$tax_rate = 0.1; // 10% tax

foreach ($order_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$tax = $subtotal * $tax_rate;
$total = $subtotal + $tax + $shipping;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - E-Commerce Store</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="order-confirmation">
            <div class="confirmation-header">
                <h1>Order Confirmation</h1>
                <p>Thank you for your order!</p>
                <p>Your order has been received and is being processed.</p>
            </div>
            
            <div class="order-details">
                <div class="order-info">
                    <h2>Order Information</h2>
                    <p><strong>Order ID:</strong> #<?php echo $order_id; ?></p>
                    <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($order['created_at'])); ?></p>
                    <p><strong>Status:</strong> <?php echo ucfirst($order['status']); ?></p>
                    <p><strong>Payment Method:</strong> <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></p>
                </div>
                
                <div class="shipping-info">
                    <h2>Shipping Address</h2>
                    <p><?php echo nl2br($order['shipping_address']); ?></p>
                </div>
            </div>
            
            <div class="order-summary">
                <h2>Order Summary</h2>
                <table class="order-items-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                            <tr>
                                <td>
                                    <div class="product-info">
                                        <img src="assets/images/<?php echo $item['image'] ? $item['image'] : 'placeholder.jpg'; ?>" alt="<?php echo $item['name']; ?>">
                                        <span><?php echo $item['name']; ?></span>
                                    </div>
                                </td>
                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3">Subtotal</td>
                            <td>$<?php echo number_format($subtotal, 2); ?></td>
                        </tr>
                        <tr>
                            <td colspan="3">Tax (10%)</td>
                            <td>$<?php echo number_format($tax, 2); ?></td>
                        </tr>
                        <tr>
                            <td colspan="3">Shipping</td>
                            <td>$<?php echo number_format($shipping, 2); ?></td>
                        </tr>
                        <tr class="total">
                            <td colspan="3">Total</td>
                            <td>$<?php echo number_format($total, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="confirmation-actions">
                <a href="orders.php" class="btn">View All Orders</a>
                <a href="index.php" class="btn btn-primary">Continue Shopping</a>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>

