<?php
session_start();
require_once '../connect.php';

// Initialize message variables
$successMessage = '';
$errorMessage = '';

// Check for success message in URL
if (isset($_GET['success'])) {
    $successMessage = $_GET['success'];
}

// Check if user is logged in and is an admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../php/login.php");
    exit();
}
$bookCount = $conn->query("SELECT COUNT(*) as count FROM Books")->fetch_assoc()['count'];
$categoryCount = $conn->query("SELECT COUNT(*) as count FROM Categories")->fetch_assoc()['count'];
// Message variables
$successMessage = '';
$errorMessage = '';

// Get categories for dropdowns
$categories = $conn->query("SELECT category_id, category_name FROM Categories ORDER BY category_name");
$categoryOptions = [];
while ($category = $categories->fetch_assoc()) {
    $categoryOptions[$category['category_id']] = $category['category_name'];
}

// Process book deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $bookId = (int)$_GET['delete'];
    
    // Check if the book exists
    $checkBook = $conn->prepare("SELECT title FROM Books WHERE book_id = ?");
    $checkBook->bind_param("i", $bookId);
    $checkBook->execute();
    $checkBook->store_result();
    
    if ($checkBook->num_rows > 0) {
        $deleteBook = $conn->prepare("DELETE FROM Books WHERE book_id = ?");
        $deleteBook->bind_param("i", $bookId);
        
        if ($deleteBook->execute()) {
            $successMessage = "Book deleted successfully.";
            header("Location: product_management.php?success=" . urlencode($successMessage));
            exit();
        } else {
            $errorMessage = "Error deleting book: " . $conn->error;
        }
        $deleteBook->close();
    } else {
        $errorMessage = "Book not found.";
    }
    $checkBook->close();
}

// Handle search
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Prepare the book query with optional search/filter
$bookSql = "SELECT b.*, c.category_name 
            FROM Books b 
            LEFT JOIN Categories c ON b.category_id = c.category_id
            WHERE 1=1";

$params = [];
$paramTypes = "";

if (!empty($searchTerm)) {
    $bookSql .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params[] = &$searchParam;
    $params[] = &$searchParam;
    $params[] = &$searchParam;
    $paramTypes .= "sss";
}

if ($categoryFilter > 0) {
    $bookSql .= " AND b.category_id = ?";
    $params[] = &$categoryFilter;
    $paramTypes .= "i";
}

$bookSql .= " ORDER BY b.book_id DESC";

// Prepare and execute query
$stmt = $conn->prepare($bookSql);
if (!empty($paramTypes)) {
    $stmt->bind_param($paramTypes, ...$params);
}
$stmt->execute();
$bookResults = $stmt->get_result();

// Process book addition/update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_book'])) {
    // Check if this is an update or a new book
    $isUpdate = isset($_POST['book_id']) && !empty($_POST['book_id']);
    $bookId = $isUpdate ? (int)$_POST['book_id'] : 0;
    
    // Get form data
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $isbn = trim($_POST['isbn']);
    $price = (float)$_POST['price'];
    $description = trim($_POST['description']);
    $publisher = trim($_POST['publisher']);
    $coverImage = trim($_POST['cover_image']);
    $stockQuantity = (int)$_POST['stock_quantity'];
    $categoryId = (int)$_POST['category_id'];
    
    // Validation
    $valid = true;
    
    if (empty($title)) {
        $errorMessage = "Title is required.";
        $valid = false;
    }
    
    if (empty($author)) {
        $errorMessage = "Author is required.";
        $valid = false;
    }
    
    if ($price <= 0) {
        $errorMessage = "Price must be greater than zero.";
        $valid = false;
    }
    
    if ($valid) {
        if ($isUpdate) {
            // Update existing book
            $updateBook = $conn->prepare("
                UPDATE Books 
                SET title = ?, 
                    author = ?, 
                    isbn = ?, 
                    price = ?, 
                    description = ?, 
                    publisher = ?, 
                    cover_image = ?, 
                    stock_quantity = ?, 
                    category_id = ? 
                WHERE book_id = ?
            ");
            $updateBook->bind_param("sssdsssiis", $title, $author, $isbn, $price, $description, $publisher, $coverImage, $stockQuantity, $categoryId, $bookId);
            
            if ($updateBook->execute()) {
                $successMessage = "Book updated successfully.";
                header("Location: product_management.php?success=" . urlencode($successMessage));
                exit();
            } else {
                $errorMessage = "Error updating book: " . $conn->error;
            }
            $updateBook->close();
        } else {
            // Add new book
            $addBook = $conn->prepare("
                INSERT INTO Books (title, author, isbn, price, description, publisher, cover_image, stock_quantity, category_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $addBook->bind_param("sssdsssis", $title, $author, $isbn, $price, $description, $publisher, $coverImage, $stockQuantity, $categoryId);
            
            if ($addBook->execute()) {
                $successMessage = "Book added successfully.";
                header("Location: product_management.php?success=" . urlencode($successMessage));
                exit();
            } else {
                $errorMessage = "Error adding book: " . $conn->error;
            }
            $addBook->close();
        }
    }
}

// Get book for editing
$editBook = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $bookId = (int)$_GET['edit'];
    $bookQuery = $conn->prepare("SELECT * FROM Books WHERE book_id = ?");
    $bookQuery->bind_param("i", $bookId);
    $bookQuery->execute();
    $editBook = $bookQuery->get_result()->fetch_assoc();
    $bookQuery->close();
    
    if (!$editBook) {
        $errorMessage = "Book not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Haitchal Books Admin</title>
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
        /* Update stat cards */
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
                /* Card and section styles */
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

        .book-cover {
            width: 50px;
            height: 70px;
            object-fit: cover;
        }

        .actions {
            display: flex;
            gap: 5px;
        }

        .two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
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
                    <a href="product_management.php" class="nav-link active">
                        <i class="fas fa-book"></i> Product Management
                    </a>
                </li>
                <li class="nav-item">
                    <a href="category_management.php" class="nav-link">
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
                <h1><?php echo isset($editBook) ? 'Edit Book' : 'Product Management'; ?></h1>
                <?php if (!isset($editBook)): ?>
                <a href="?add=new" class="btn btn-success">
                    <i class="fas fa-plus"></i> Add New Book
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

            <?php if (isset($editBook) || isset($_GET['add'])): ?>
            <!-- Book Form -->
            <div class="panel">
                <div class="panel-header">
                    <h2><?php echo isset($editBook) ? 'Edit Book: ' . htmlspecialchars($editBook['title']) : 'Add New Book'; ?></h2>
                </div>
                <div class="panel-body">
                    <form method="post" action="product_management.php">
                        <?php if (isset($editBook)): ?>
                        <input type="hidden" name="book_id" value="<?php echo $editBook['book_id']; ?>">
                        <?php endif; ?>

                        <div class="two-columns">
                            <div>
                                <div class="form-group">
                                    <label for="title">Title*</label>
                                    <input type="text" id="title" name="title" class="form-control" required 
                                        value="<?php echo isset($editBook) ? htmlspecialchars($editBook['title']) : ''; ?>">
                                </div>

                                <div class="form-group">
                                    <label for="author">Author*</label>
                                    <input type="text" id="author" name="author" class="form-control" required
                                        value="<?php echo isset($editBook) ? htmlspecialchars($editBook['author']) : ''; ?>">
                                </div>

                                <div class="form-group">
                                    <label for="isbn">ISBN</label>
                                    <input type="text" id="isbn" name="isbn" class="form-control"
                                        value="<?php echo isset($editBook) ? htmlspecialchars($editBook['isbn']) : ''; ?>">
                                </div>

                                <div class="form-group">
                                    <label for="price">Price*</label>
                                    <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" required
                                        value="<?php echo isset($editBook) ? htmlspecialchars($editBook['price']) : ''; ?>">
                                </div>

                                <div class="form-group">
                                    <label for="category_id">Category</label>
                                    <select id="category_id" name="category_id" class="form-control">
                                        <option value="0">Select a category</option>
                                        <?php foreach ($categoryOptions as $id => $name): ?>
                                            <option value="<?php echo $id; ?>" <?php if (isset($editBook) && $editBook['category_id'] == $id) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <div class="form-group">
                                    <label for="cover_image">Cover Image URL</label>
                                    <input type="text" id="cover_image" name="cover_image" class="form-control"
                                        value="<?php echo isset($editBook) ? htmlspecialchars($editBook['cover_image']) : ''; ?>">
                                </div>

                                <div class="form-group">
                                    <label for="publisher">Publisher</label>
                                    <input type="text" id="publisher" name="publisher" class="form-control"
                                        value="<?php echo isset($editBook) ? htmlspecialchars($editBook['publisher']) : ''; ?>">
                                </div>

                                <div class="form-group">
                                    <label for="stock_quantity">Stock Quantity*</label>
                                    <input type="number" id="stock_quantity" name="stock_quantity" class="form-control" min="0" required
                                        value="<?php echo isset($editBook) ? htmlspecialchars($editBook['stock_quantity']) : '0'; ?>">
                                </div>

                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <textarea id="description" name="description" class="form-control"><?php echo isset($editBook) ? htmlspecialchars($editBook['description']) : ''; ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" name="save_book" class="btn btn-success">
                                <i class="fas fa-save"></i> <?php echo isset($editBook) ? 'Update Book' : 'Add Book'; ?>
                            </button>
                            <a href="product_management.php" class="btn">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
            <?php else: ?>
                <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="icon books">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="info">
                        <h3><?php echo $bookCount; ?></h3>
                        <p>Total Books</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="icon categories">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="info">
                        <h3><?php echo $categoryCount; ?></h3>
                        <p>Categories</p>
                    </div>
                </div>
            </div>
                <!-- Books List -->
            <div class="panel">
                <div class="panel-header">
                    <h2>Books List</h2>
                </div>
                <div class="panel-body">
                    <form method="get" action="product_management.php" class="search-bar">
                        <input type="text" name="search" placeholder="Search by title, author, or ISBN..." 
                               value="<?php echo htmlspecialchars($searchTerm); ?>">
                        <select name="category">
                            <option value="0">All Categories</option>
                            <?php foreach ($categoryOptions as $id => $name): ?>
                                <option value="<?php echo $id; ?>" <?php if ($categoryFilter == $id) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <?php if (!empty($searchTerm) || $categoryFilter > 0): ?>
                            <a href="product_management.php" class="btn">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        <?php endif; ?>
                    </form>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cover</th>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($bookResults->num_rows > 0): ?>
                                <?php while ($book = $bookResults->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $book['book_id']; ?></td>
                                        <td>
                                            <?php if (!empty($book['cover_image'])): ?>
                                                <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" alt="Cover" class="book-cover">
                                            <?php else: ?>
                                                <div class="book-cover" style="background-color: #eee; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-book"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                                        <td><?php echo htmlspecialchars($book['category_name'] ?? 'Uncategorized'); ?></td>
                                        <td>$<?php echo number_format($book['price'], 2); ?></td>
                                        <td><?php echo $book['stock_quantity']; ?></td>
                                        <td class="actions">
                                            <a href="?edit=<?php echo $book['book_id']; ?>" class="btn" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?delete=<?php echo $book['book_id']; ?>" class="btn btn-danger" title="Delete" 
                                               onclick="return confirm('Are you sure you want to delete this book?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center;">No books found</td>
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