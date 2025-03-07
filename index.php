<?php
session_start();
require_once 'config/database.php';
require_once 'config/functions.php';

$database = new Database();
$db = $database->getConnection();

// Get all products
$query = "SELECT p.*, c.name as category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          ORDER BY p.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all categories
$query = "SELECT * FROM categories";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Commerce Store</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="hero">
            <h1>Welcome to Our Store</h1>
            <p>Find the best products at the best prices</p>
        </div>
        
        <div class="filters">
            <h3>Categories</h3>
            <ul>
                <li><a href="index.php">All Products</a></li>
                <?php foreach ($categories as $category): ?>
                <li><a href="index.php?category=<?php echo $category['id']; ?>"><?php echo $category['name']; ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="products">
            <?php foreach ($products as $product): ?>
            <div class="product-card">
                <div class="product-image">
                    <img src="assets/images/<?php echo $product['image'] ? $product['image'] : 'placeholder.jpg'; ?>" alt="<?php echo $product['name']; ?>">
                </div>
                <div class="product-info">
                    <h3><?php echo $product['name']; ?></h3>
                    <p class="category"><?php echo $product['category_name']; ?></p>
                    <p class="price">$<?php echo $product['price']; ?></p>
                    <div class="rating">
                        <?php echo displayStars(getAverageRating($product['id'])); ?>
                    </div>
                    <div class="actions">
                        <a href="product.php?id=<?php echo $product['id']; ?>" class="btn">View Details</a>
                        <?php if (isLoggedIn()): ?>
                        <form action="cart_actions.php" method="post">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn btn-primary">Add to Cart</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>

