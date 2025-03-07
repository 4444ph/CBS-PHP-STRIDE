<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

// Redirect if not admin
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

// Get order ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: orders.php");
    exit();
}

$order_id = $_GET['id'];

// Get order details
$query = "SELECT o.*, u.username, u.email FROM orders o 
          JOIN users u ON o.user_id = u.id 
          WHERE o.id = :order_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':order_id', $order_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: orders.php");
    exit();
}

$order = $stmt->fetch(PDO::FETCH_ASSOC);

// Get order items
$query = "SELECT oi.*, p.name as product_name FROM order_items oi 
          JOIN products p ON oi.product_id = p.id 
          WHERE oi.order_id = :order_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':order_id', $order_id);
$stmt->execute();
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle status update
if (isset($_POST['update_status'])) {
    $new_status = $_POST['new_status'];
    
    $query = "UPDATE orders SET status = :status WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $new_status);
    $stmt->bindParam(':id', $order_id);
    
    if ($stmt->execute()) {
        $success_message = "Order status updated successfully";
        $order['status'] = $new_status;
    } else {
        $error_message = "Failed to update order status";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - E-Commerce Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="admin-content">
            <div class="content-header">
                <h1>Order Details - #<?php echo $order_id; ?></h1>
                <a href="orders.php" class="btn">Back to Orders</a>
            </div>
            
            <?php if (isset($success_message)): ?>
                <div class="success-message">
                    <p><?php echo $success_message; ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <p><?php echo $error_message; ?></p>
                </div>
            <?php endif; ?>
            
            <div class="order-details">
                <div class="order-info">
                    <h2>Order Information</h2>
                    <p><strong>Order ID:</strong> #<?php echo $order['id']; ?></p>
                    <p><strong>Date:</strong> <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></p>
                    <p><strong>Status:</strong> 
                        <span class="status-badge status-<?php echo $order['status']; ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </p>
                    <p><strong>Total Amount:</strong> $<?php echo number_format($order['total_amount'], 2); ?></p>
                    <p><strong>Payment Method:</strong> <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></p>
                    
                    <h3>Update Status</h3>
                    <form action="order_details.php?id=<?php echo $order_id; ?>" method="post">
                        <select name="new_status">
                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                    </form>
                </div>
                
                <div class="customer-info">
                    <h2>Customer Information</h2>
                    <p><strong>Name:</strong> <?php echo $order['username']; ?></p>
                    <p><strong>Email:</strong> <?php echo $order['email']; ?></p>
                    <h3>Shipping Address</h3>
                    <p><?php echo nl2br($order['shipping_address']); ?></p>
                </div>
            </div>
            
            <div class="order-items">
                <h2>Order Items</h2>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                            <tr>
                                <td><?php echo $item['product_name']; ?></td>
                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-right"><strong>Total:</strong></td>
                            <td><strong>$<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

