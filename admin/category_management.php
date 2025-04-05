<?php
// filepath: d:\XAMPP\htdocs\OnlineBookstore\admin\category_management.php
session_start();
require_once '../connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../php/login.php");
    exit();
}

// Message variables
$successMessage = '';
$errorMessage = '';

// Get category stats
$totalCategories = $conn->query("SELECT COUNT(*) as count FROM Categories")->fetch_assoc()['count'];
$categoriesWithBooks = $conn->query("SELECT COUNT(DISTINCT category_id) as count FROM Books")->fetch_assoc()['count'];

// Process category deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $categoryId = (int)$_GET['delete'];
    
    // Check if the category has books
    $checkCategory = $conn->prepare("SELECT COUNT(*) as book_count FROM Books WHERE category_id = ?");
    $checkCategory->bind_param("i", $categoryId);
    $checkCategory->execute();
    $result = $checkCategory->get_result()->fetch_assoc();
    $bookCount = $result['book_count'];
    $checkCategory->close();
    
    if ($bookCount > 0) {
        $errorMessage = "Cannot delete category. It contains {$bookCount} books. Please reassign books first.";
    } else {
        // Delete the category
        $deleteCategory = $conn->prepare("DELETE FROM Categories WHERE category_id = ?");
        $deleteCategory->bind_param("i", $categoryId);
        
        if ($deleteCategory->execute()) {
            $successMessage = "Category deleted successfully.";
        } else {
            $errorMessage = "Error deleting category: " . $conn->error;
        }
        $deleteCategory->close();
    }
}

// Process category addition/update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_category'])) {
    // Check if this is an update or a new category
    $isUpdate = isset($_POST['category_id']) && !empty($_POST['category_id']);
    $categoryId = $isUpdate ? (int)$_POST['category_id'] : 0;
    
    // Get form data
    $categoryName = trim($_POST['category_name']);
    $description = trim($_POST['description']);
    
    // Validation
    $valid = true;
    
    if (empty($categoryName)) {
        $errorMessage = "Category name is required.";
        $valid = false;
    }
    
    if ($valid) {
        if ($isUpdate) {
            // Update existing category
            $updateCategory = $conn->prepare("
                UPDATE Categories 
                SET category_name = ?, 
                    description = ? 
                WHERE category_id = ?
            ");
            $updateCategory->bind_param("ssi", $categoryName, $description, $categoryId);
            
            if ($updateCategory->execute()) {
                $successMessage = "Category updated successfully.";
            } else {
                $errorMessage = "Error updating category: " . $conn->error;
            }
            $updateCategory->close();
        } else {
            // Add new category
            $addCategory = $conn->prepare("
                INSERT INTO Categories (category_name, description) 
                VALUES (?, ?)
            ");
            $addCategory->bind_param("ss", $categoryName, $description);
            
            if ($addCategory->execute()) {
                $successMessage = "Category added successfully.";
            } else {
                $errorMessage = "Error adding category: " . $conn->error;
            }
            $addCategory->close();
        }
    }
}

// Get category for editing
$editCategory = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $categoryId = (int)$_GET['edit'];
    $categoryQuery = $conn->prepare("SELECT * FROM Categories WHERE category_id = ?");
    $categoryQuery->bind_param("i", $categoryId);
    $categoryQuery->execute();
    $editCategory = $categoryQuery->get_result()->fetch_assoc();
    $categoryQuery->close();
    
    if (!$editCategory) {
        $errorMessage = "Category not found.";
    }
}

// Handle search
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get all categories with book counts
$categorySql = "SELECT c.*, COUNT(b.book_id) as book_count 
                FROM Categories c 
                LEFT JOIN Books b ON c.category_id = b.category_id";

if (!empty($searchTerm)) {
    $categorySql .= " WHERE c.category_name LIKE ?";
    $searchParam = "%$searchTerm%";
}

$categorySql .= " GROUP BY c.category_id ORDER BY c.category_name";

if (!empty($searchTerm)) {
    $stmt = $conn->prepare($categorySql);
    $stmt->bind_param("s", $searchParam);
    $stmt->execute();
    $categoryResults = $stmt->get_result();
} else {
    $categoryResults = $conn->query($categorySql);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management - Haitchal Books Admin</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Stick+No+Bills">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin_styles.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --light-color: #ecf0f1;
            --dark-color: #34495e;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background-color: var(--secondary-color);
            color: white;
            padding: 20px 0;
        }

        .logo {
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
            font-family: 'Stick No Bills', sans-serif;
        }

        .logo h1 {
            font-size: 24px;
        }

        .logo span {
            display: block;
            font-size: 14px;
            opacity: 0.7;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 5px;
        }

        .nav-link {
            display: block;
            padding: 10px 20px;
            color: #ecf0f1;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .nav-link:hover, .nav-link.active {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .main-content {
            flex: 1;
            padding: 20px;
            background-color: #f5f5f5;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }

        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: white;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
        }

        .stat-card i {
            font-size: 30px;
            margin-right: 20px;
            width: 50px;
            height: 50px;
            background-color: var(--light-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn {
            display: inline-block;
            padding: 8px 15px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 3px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #2980b9;
        }

        .btn-success {
            background-color: var(--success-color);
        }

        .btn-success:hover {
            background-color: #27ae60;
        }

        .btn-danger {
            background-color: var(--danger-color);
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 3px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .panel {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .panel-header {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .panel-body {
            padding: 15px;
        }

        .search-bar {
            display: flex;
            margin-bottom: 20px;
            gap: 10px;
        }

        .search-bar input, 
        .search-bar select,
        .search-bar button {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }

        .search-bar input {
            flex: 1;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-size: 14px;
        }

        textarea.form-control {
            min-height: 100px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .table th {
            background-color: #f9f9f9;
            font-weight: 600;
        }

        .table tbody tr:hover {
            background-color: #f9f9f9;
        }

        .book-count {
            display: inline-block;
            padding: 3px 8px;
            background-color: var(--light-color);
            color: var(--dark-color);
            border-radius: 10px;
            font-size: 12px;
            font-weight: bold;
        }

        .book-count.zero {
            background-color: #f8d7da;
            color: #721c24;
        }

        .actions {
            display: flex;
            gap: 5px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <div class="logo">
                <h1>Haitchal Books</h1>
                <span>Admin Panel</span>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="user_management.php" class="nav-link">
                        <i class="fas fa-users"></i> User Management
                    </a>
                </li>
                <li class="nav-item">
                    <a href="product_management.php" class="nav-link">
                        <i class="fas fa-book"></i> Product Management
                    </a>
                </li>
                <li class="nav-item">
                    <a href="category_management.php" class="nav-link active">
                        <i class="fas fa-tags"></i> Categories
                    </a>
                </li>
                <li class="nav-item">
                    <a href="order_management.php" class="nav-link">
                        <i class="fas fa-shopping-cart"></i> Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../php/homepage.php" class="nav-link">
                        <i class="fas fa-home"></i> Visit Store
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../php/logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1><?php echo isset($editCategory) ? 'Edit Category' : 'Category Management'; ?></h1>
                <?php if (!isset($editCategory)): ?>
                <a href="?add=new" class="btn btn-success">
                    <i class="fas fa-plus"></i> Add New Category
                </a>
                <?php endif; ?>
            </div>

            <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success">
                <?php echo $successMessage; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger">
                <?php echo $errorMessage; ?>
            </div>
            <?php endif; ?>

            <?php if (isset($editCategory) || isset($_GET['add'])): ?>
            <!-- Category Form -->
            <div class="panel">
                <div class="panel-header">
                    <h2><?php echo isset($editCategory) ? 'Edit Category: ' . htmlspecialchars($editCategory['category_name']) : 'Add New Category'; ?></h2>
                </div>
                <div class="panel-body">
                    <form method="post" action="category_management.php">
                        <?php if (isset($editCategory)): ?>
                        <input type="hidden" name="category_id" value="<?php echo $editCategory['category_id']; ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="category_name">Category Name*</label>
                            <input type="text" id="category_name" name="category_name" class="form-control" required 
                                value="<?php echo isset($editCategory) ? htmlspecialchars($editCategory['category_name']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" class="form-control"><?php echo isset($editCategory) ? htmlspecialchars($editCategory['description']) : ''; ?></textarea>
                        </div>

                        <div class="form-group">
                            <button type="submit" name="save_category" class="btn btn-success">
                                <i class="fas fa-save"></i> <?php echo isset($editCategory) ? 'Update Category' : 'Add Category'; ?>
                            </button>
                            <a href="category_management.php" class="btn">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <!-- Dashboard Stats -->
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="icon books">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="info">
                        <h3><?php echo $totalCategories; ?></h3>
                        <p>Total Categories</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="icon categories">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="info">
                        <h3><?php echo $categoriesWithBooks; ?></h3>
                        <p>Categories With Books</p>
                    </div>
                </div>
            </div>
                
            <!-- Categories List -->
            <div class="panel">
                <div class="panel-header">
                    <h2>Categories List</h2>
                </div>
                <div class="panel-body">
                    <form method="get" action="category_management.php" class="search-bar">
                        <input type="text" name="search" placeholder="Search by category name..." 
                               value="<?php echo htmlspecialchars($searchTerm); ?>">
                        <button type="submit" class="btn">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <?php if (!empty($searchTerm)): ?>
                            <a href="category_management.php" class="btn">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        <?php endif; ?>
                    </form>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Category Name</th>
                                <th>Description</th>
                                <th>Books</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($categoryResults && $categoryResults->num_rows > 0): ?>
                                <?php while ($category = $categoryResults->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $category['category_id']; ?></td>
                                        <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                        <td><?php echo !empty($category['description']) ? htmlspecialchars(substr($category['description'], 0, 100)) . (strlen($category['description']) > 100 ? '...' : '') : 'No description'; ?></td>
                                        <td>
                                            <span class="book-count <?php echo $category['book_count'] == 0 ? 'zero' : ''; ?>">
                                                <?php echo $category['book_count']; ?> books
                                            </span>
                                        </td>
                                        <td class="actions">
                                            <a href="?edit=<?php echo $category['category_id']; ?>" class="btn" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($category['book_count'] == 0): ?>
                                                <a href="?delete=<?php echo $category['category_id']; ?>" class="btn btn-danger" title="Delete" 
                                                   onclick="return confirm('Are you sure you want to delete this category?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-danger" disabled title="Cannot delete categories with books">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">No categories found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>