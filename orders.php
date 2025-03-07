<?php
session_start();
require_once 'config/database.php';
require_once 'config/functions.php';

// Redirect if not logged in
redirectIfNotLoggedIn();

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];

// Get user orders
$query = "SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - E-Commerce Store</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h1>My Orders</h1>
        
        <?php if (count($orders) > 0): ?>
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-id">
                                <h3>Order #<?php echo $order['id']; ?></h3>
                                <p>Placed on <?php echo date('M d, Y', strtotime($order['created_at'])); ?></p>
                            </div>
                            <div class="order-status">
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="order-details">
                            <?php
                            // Get order items
                            $query = "SELECT oi.*, p.name, p.image 
                                      FROM order_items oi 
                                      JOIN products p ON oi.product_id = p.id 
                                      WHERE oi.order_id = :order_id";
                            $stmt = $db->prepare($query);
                            $stmt->bindParam(':order_id', $order['id']);
                            $stmt->execute();
                            $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            // Show first 2 items
                            $items_to_show = array_slice($order_items, 0, 2);
                            $remaining_items = count($order_items) - count($items_to_show);
                            ?>
                            
                            <div class="order-items">
                                <?php foreach ($items_to_show as $item): ?>
                                    <div class="order-item">
                                        <img src="assets/images/<?php echo $item['image'] ? $item['image'] : 'placeholder.jpg'; ?>" alt="<?php echo $item['name']; ?>">
                                        <div class="item-details">
                                            <p class="item-name"><?php echo $item['name']; ?></p>
                                            <p class="item-quantity">Qty: <?php echo $item['quantity']; ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <?php if ($remaining_items > 0): ?>
                                    <div class="more-items">
                                        <p>+<?php echo $remaining_items; ?> more item(s)</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="order-footer">
                            <div class="order-total">
                                <p>Total: $<?php echo number_format($order['total_amount'], 2); ?></p>
                            </div>
                            <div class="order-actions">
                                <a href="order_confirmation.php?id=<?php echo $order['id']; ?>" class="btn">View Order</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-orders">
                <p>You haven't placed any orders yet.</p>
                <a href="index.php" class="btn">Start Shopping</a>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>

