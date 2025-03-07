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
$query = "SELECT c.id, c.quantity, p.id as product_id, p.name, p.price, p.image, p.stock 
          FROM cart c 
          JOIN products p ON c.product_id = p.id 
          WHERE c.user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$subtotal = 0;
$shipping = 10; // Fixed shipping cost
$tax_rate = 0.1; // 10% tax

foreach ($cart_items as $item) {
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
    <title>Shopping Cart - E-Commerce Store</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h1>Shopping Cart</h1>
        
        <?php if (count($cart_items) > 0): ?>
            <div class="cart-container">
                <div class="cart-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <div class="cart-item-image">
                                <img src="assets/images/<?php echo $item['image'] ? $item['image'] : 'placeholder.jpg'; ?>" alt="<?php echo $item['name']; ?>">
                            </div>
                            <div class="cart-item-details">
                                <h3><a href="product.php?id=<?php echo $item['product_id']; ?>"><?php echo $item['name']; ?></a></h3>
                                <p class="price">$<?php echo $item['price']; ?></p>
                            </div>
                            <div class="cart-item-quantity">
                                <form action="cart_actions.php" method="post">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>" onchange="this.form.submit()">
                                </form>
                            </div>
                            <div class="cart-item-total">
                                $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                            </div>
                            <div class="cart-item-remove">
                                <form action="cart_actions.php" method="post">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn btn-small">Remove</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="cart-summary">
                    <h3>Order Summary</h3>
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
                    <div class="cart-actions">
                        <a href="checkout.php" class="btn btn-primary">Proceed to Checkout</a>
                        <form action="cart_actions.php" method="post">
                            <input type="hidden" name="action" value="clear">
                            <button type="submit" class="btn">Clear Cart</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-cart">
                <p>Your cart is empty.</p>
                <a href="index.php" class="btn">Continue Shopping</a>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>

