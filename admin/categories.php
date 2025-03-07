<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

// Redirect if not admin
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

// Handle category deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $category_id = $_GET['delete'];
    
    $query = "DELETE FROM categories WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $category_id);
    
    if ($stmt->execute()) {
        header("Location: categories.php?success=Category deleted successfully");
        exit();
    } else {
        header("Location: categories.php?error=Failed to delete category");
        exit();
    }
}

// Handle category addition/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $category_id = isset($_POST['category_id']) ? $_POST['category_id'] : null;

    if (empty($name)) {
        $error = "Category name is required";
    } else {
        if ($category_id) {
            // Update existing category
            $query = "UPDATE categories SET name = :name, description = :description WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $category_id);
        } else {
            // Add new category
            $query = "INSERT INTO categories (name, description) VALUES (:name, :description)";
            $stmt = $db->prepare($query);
        }

        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);

        if ($stmt->execute()) {
            header("Location: categories.php?success=" . ($category_id ? "Category updated successfully" : "Category added successfully"));
            exit();
        } else {
            $error = "Failed to " . ($category_id ? "update" : "add") . " category";
        }
    }
}

// Get all categories
$query = "SELECT * FROM categories ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get category for editing
$category_to_edit = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $query = "SELECT * FROM categories WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $edit_id);
    $stmt->execute();
    $category_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - E-Commerce Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="admin-content">
            <div class="content-header">
                <h1>Manage Categories</h1>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="success-message">
                    <p><?php echo $_GET['success']; ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <p><?php echo $error; ?></p>
                </div>
            <?php endif; ?>
            
            <div class="admin-form">
                <h2><?php echo $category_to_edit ? 'Edit Category' : 'Add New Category'; ?></h2>
                <form action="categories.php" method="post">
                    <?php if ($category_to_edit): ?>
                        <input type="hidden" name="category_id" value="<?php echo $category_to_edit['id']; ?>">
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="name">Category Name</label>
                        <input type="text" id="name" name="name" value="<?php echo $category_to_edit ? $category_to_edit['name'] : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description"><?php echo $category_to_edit ? $category_to_edit['description'] : ''; ?></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><?php echo $category_to_edit ? 'Update Category' : 'Add Category'; ?></button>
                        <?php if ($category_to_edit): ?>
                            <a href="categories.php" class="btn">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo $category['id']; ?></td>
                                <td><?php echo $category['name']; ?></td>
                                <td><?php echo $category['description']; ?></td>
                                <td>
                                    <a href="categories.php?edit=<?php echo $category['id']; ?>" class="btn btn-small">Edit</a>
                                    <a href="categories.php?delete=<?php echo $category['id']; ?>" class="btn btn-small btn-danger" onclick="return confirm('Are you sure you want to delete this category?')">Delete</a>
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

