<?php
session_start();
require_once 'config/database.php';
require_once 'config/functions.php';

$database = new Database();
$db = $database->getConnection();

// Get product ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$product_id = $_GET['id'];

// Get product details
$query = "SELECT p.*, c.name as category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $product_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: index.php");
    exit();
}

$product = $stmt->fetch(PDO::FETCH_ASSOC);

// Get product reviews
$query = "SELECT r.*, u.username FROM reviews r 
          JOIN users u ON r.user_id = u.id 
          WHERE r.product_id = :product_id 
          ORDER BY r.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':product_id', $product_id);
$stmt->execute();
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle review submission
$review_errors = [];
$review_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isLoggedIn()) {
        $review_errors[] = "You must be logged in to submit a review";
    } else {
        $rating = (int)$_POST['rating'];
        $comment = sanitize($_POST['comment']);
        
        if ($rating < 1 || $rating > 5) {
            $review_errors[] = "Rating must be between 1 and 5";
        }
        
        if (empty($comment)) {
            $review_errors[] = "Comment is required";
        }
        
        if (empty($review_errors)) {
            $user_id = $_SESSION['user_id'];
            
            // Check if user already reviewed this product
            $query = "SELECT id FROM reviews WHERE product_id = :product_id AND user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':product_id', $product_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                // Update existing review
                $query = "UPDATE reviews SET rating = :rating, comment = :comment 
                          WHERE product_id = :product_id AND user_id = :user_id";
            } else {
                // Insert new review
                $query = "INSERT INTO reviews (product_id, user_id, rating, comment) 
                          VALUES (:product_id, :user_id, :rating, :comment)";
            }
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':product_id', $product_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':rating', $rating);
            $stmt->bindParam(':comment', $comment);
            
            if ($stmt->execute()) {
                $review_success = true;
                
                // Refresh the page to show the new review
                header("Location: product.php?id=$product_id&review_success=1");
                exit();
            } else {
                $review_errors[] = "Failed to submit review. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product['name']; ?> - E-Commerce Store</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="product-details">
            <div class="product-image">
                <img src="assets/images/<?php echo $product['image'] ? $product['image'] : 'placeholder.jpg'; ?>" alt="<?php echo $product['name']; ?>">
            </div>
            
            <div class="product-info">
                <h1><?php echo $product['name']; ?></h1>
                <p class="category"><?php echo $product['category_name']; ?></p>
                <div class="rating">
                    <?php echo displayStars(getAverageRating($product_id)); ?>
                    <span>(<?php echo count($reviews); ?> reviews)</span>
                </div>
                <p class="price">$<?php echo $product['price']; ?></p>
                <p class="stock">In Stock: <?php echo $product['stock']; ?></p>
                <p class="description"><?php echo $product['description']; ?></p>
                
                <?php if (isLoggedIn()): ?>
                <div class="add-to-cart">
                    <form action="cart_actions.php" method="post">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                        <div class="quantity">
                            <label for="quantity">Quantity:</label>
                            <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Add to Cart</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="reviews-section">
            <h2>Customer Reviews</h2>
            
            <?php if (isset($_GET['review_success'])): ?>
                <div class="success-message">
                    <p>Your review has been submitted successfully!</p>
                </div>
            <?php endif; ?>
            
            <?php if (isLoggedIn()): ?>
                <div class="review-form">
                    <h3>Write a Review</h3>
                    
                    <?php if (!empty($review_errors)): ?>
                        <div class="error-messages">
                            <?php foreach ($review_errors as $error): ?>
                                <p><?php echo $error; ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="product.php?id=<?php echo $product_id; ?>" method="post">
                        <div class="form-group">
                            <label for="rating">Rating</label>
                            <select id="rating" name="rating" required>
                                <option value="5">5 - Excellent</option>
                                <option value="4">4 - Very Good</option>
                                <option value="3">3 - Good</option>
                                <option value="2">2 - Fair</option>
                                <option value="1">1 - Poor</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="comment">Comment</label>
                            <textarea id="comment" name="comment" required></textarea>
                        </div>
                        
                        <button type="submit" name="submit_review" class="btn">Submit Review</button>
                    </form>
                </div>
            <?php else: ?>
                <p><a href="login.php">Log in</a> to write a review.</p>
            <?php endif; ?>
            
            <div class="reviews-list">
                <?php if (count($reviews) > 0): ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review">
                            <div class="review-header">
                                <span class="reviewer"><?php echo $review['username']; ?></span>
                                <span class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                            </div>
                            <div class="review-rating">
                                <?php echo displayStars($review['rating']); ?>
                            </div>
                            <div class="review-comment">
                                <p><?php echo $review['comment']; ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No reviews yet. Be the first to review this product!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>

