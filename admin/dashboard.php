<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

// Redirect if not admin
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

// Get counts for dashboard
$query = "SELECT COUNT(*) as count FROM products";
$stmt = $db->prepare($query);
$stmt->execute();
$product_count = $stmt->fetch(PDO::FETCH_ASSOC);

$query = "SELECT COUNT(*) as count FROM users WHERE is_admin = 0";
$stmt = $db->prepare($query);
$stmt->execute();
$user_count = $stmt->fetch(PDO::FETCH_ASSOC);

$query = "SELECT COUNT(*) as count FROM orders";
$stmt = $db->prepare($query);
$stmt->execute();
$order_count = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent orders
$query = "SELECT o.*, u.username FROM orders o 
          JOIN users u ON o.user_id = u.id 
          ORDER BY o.created_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - E-Commerce Store</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="admin-content">
            <h1>Dashboard</h1>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $product_count['count']; ?></div>
                    <div class="stat-label">Products</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $user_count['count']; ?></div>
                    <div class="stat-label">Customers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $order_count['count']; ?></div>
                    <div class="stat-label">Orders</div>
                </div>
            </div>
            
            <div class="recent-orders">
                <h2>Recent Orders</h2>
                
                <?php if (count($recent_orders) > 0): ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo $order['username']; ?></td>
                                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-small">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No orders yet.</p>
                <?php endif; ?>
                
                <div class="view-all">
                    <a href="orders.php" class="btn">View All Orders</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

