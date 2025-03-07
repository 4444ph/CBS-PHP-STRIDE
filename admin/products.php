<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

// Redirect if not admin
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

// Handle product deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $product_id = $_GET['delete'];
    
    $query = "DELETE FROM products WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $product_id);
    
    if ($stmt->execute()) {
        header("Location: products.php?success=Product deleted successfully");
        exit();
    } else {
        header("Location: products.php?error=Failed to delete product");
        exit();
    }
}

// Get all products
$query = "SELECT p.*, c.name as category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          ORDER BY p.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - E-Commerce Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="admin-content">
            <div class="content-header">
                <h1>Manage Products</h1>
                <a href="product_form.php" class="btn btn-primary">Add New Product</a>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="success-message">
                    <p><?php echo $_GET['success']; ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="error-message">
                    <p><?php echo $_GET['error']; ?></p>
                </div>
            <?php endif; ?>
            
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo $product['id']; ?></td>
                                <td>
                                    <img src="../assets/images/<?php echo $product['image'] ? $product['image'] : 'placeholder.jpg'; ?>" alt="<?php echo $product['name']; ?>" class="product-thumbnail">
                                </td>
                                <td><?php echo $product['name']; ?></td>
                                <td><?php echo $product['category_name']; ?></td>
                                <td>$<?php echo number_format($product['price'], 2); ?></td>
                                <td><?php echo $product['stock']; ?></td>
                                <td>
                                    <a href="product_form.php?id=<?php echo $product['id']; ?>" class="btn btn-small">Edit</a>
                                    <a href="products.php?delete=<?php echo $product['id']; ?>" class="btn btn-small btn-danger" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

