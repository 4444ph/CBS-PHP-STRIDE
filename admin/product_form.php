<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

// Redirect if not admin
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

// Get all categories
$query = "SELECT * FROM categories ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize variables
$product = [
    'id' => '',
    'name' => '',
    'description' => '',
    'price' => '',
    'stock' => '',
    'category_id' => '',
    'image' => ''
];
$is_edit = false;
$errors = [];

// Check if editing existing product
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $product_id = $_GET['id'];
    $is_edit = true;
    
    $query = "SELECT * FROM products WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $product_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        header("Location: products.php?error=Product not found");
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $category_id = (int)$_POST['category_id'];
    
    // Validate input
    if (empty($name)) {
        $errors[] = "Product name is required";
    }
    
    if ($price <= 0) {
        $errors[] = "Price must be greater than zero";
    }
    
    if ($stock < 0) {
        $errors[] = "Stock cannot be negative";
    }
    
    // Handle image upload
    $image = $product['image']; // Keep existing image by default
    
    if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
        $target_dir = "../assets/images/";
        $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Check file type
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($file_extension, $allowed_types)) {
            $errors[] = "Only JPG, JPEG, PNG & GIF files are allowed";
        }
        
        // Upload file if no errors
        if (empty($errors)) {
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image = $new_filename;
            } else {
                $errors[] = "Failed to upload image";
            }
        }
    }
    
    // Save product if no errors
    if (empty($errors)) {
        if ($is_edit) {
            // Update existing product
            $query = "UPDATE products SET name = :name, description = :description, price = :price, 
                      stock = :stock, category_id = :category_id, image = :image 
                      WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $product['id']);
        } else {
            // Insert new product
            $query = "INSERT INTO products (name, description, price, stock, category_id, image) 
                      VALUES (:name, :description, :price, :stock, :category_id, :image)";
            $stmt = $db->prepare($query);
        }
        
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':stock', $stock);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->bindParam(':image', $image);
        
        if ($stmt->execute()) {
            $success_message = $is_edit ? "Product updated successfully" : "Product added successfully";
            header("Location: products.php?success=$success_message");
            exit();
        } else {
            $errors[] = "Failed to save product";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit' : 'Add'; ?> Product - E-Commerce Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="admin-content">
            <div class="content-header">
                <h1><?php echo $is_edit ? 'Edit' : 'Add'; ?> Product</h1>
                <a href="products.php" class="btn">Back to Products</a>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="error-messages">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="admin-form">
                <form action="<?php echo $is_edit ? "product_form.php?id={$product['id']}" : 'product_form.php'; ?>" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name">Product Name</label>
                        <input type="text" id="name" name="name" value="<?php echo $product['name']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $category['id'] == $product['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo $category['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="5"><?php echo $product['description']; ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Price</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo $product['price']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="stock">Stock</label>
                            <input type="number" id="stock" name="stock" min="0" value="<?php echo $product['stock']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Product Image</label>
                        <?php if ($product['image']): ?>
                            <div class="current-image">
                                <img src="../assets/images/<?php echo $product['image']; ?>" alt="Current Image">
                                <p>Current Image: <?php echo $product['image']; ?></p>
                            </div>
                        <?php endif; ?>
                        <input type="file" id="image" name="image" accept="image/*">
                        <p class="form-hint">Leave empty to keep current image</p>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><?php echo $is_edit ? 'Update' : 'Add'; ?> Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

