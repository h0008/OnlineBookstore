<?php
session_start();
require_once '../connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../php/login.php");
    exit();
}

// Get counts for dashboard statistics
$userCount = $conn->query("SELECT COUNT(*) as count FROM Users")->fetch_assoc()['count'];
$bookCount = $conn->query("SELECT COUNT(*) as count FROM Books")->fetch_assoc()['count'];
$categoryCount = $conn->query("SELECT COUNT(*) as count FROM Categories")->fetch_assoc()['count'];

// Get recent users
$recentUsers = $conn->query("SELECT user_id, username, email, role, registration_date FROM Users ORDER BY registration_date DESC LIMIT 5");

// Get recent books
$recentBooks = $conn->query("SELECT b.book_id, b.title, b.author, b.price, c.category_name 
                             FROM Books b 
                             JOIN Categories c ON b.category_id = c.category_id 
                             ORDER BY b.book_id DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Haitchal Books</title>
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

        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            margin: 0;
            padding: 0;
        }

        /* Update container class */
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar styles */
        .sidebar {
            width: 250px;
            background-color: var(--secondary-color);
            color: white;
            padding: 20px 0;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            font-family: 'Stick No Bills', sans-serif;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar li {
            margin-bottom: 5px;
        }

        .sidebar a {
            display: block;
            padding: 10px 20px;
            color: #ecf0f1;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .sidebar a:hover, .sidebar a.active {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Content area */
        .content {
            flex: 1;
            padding: 20px;
            background-color: #f5f5f5;
        }

        /* Header styles */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 15px 0;
            border-bottom: 1px solid #ddd;
        }

        /* Button styles */
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

        /* Alert styles */
        .message, .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 3px;
        }

        .message.success, .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .message.error, .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
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

        .recent-section, .panel {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .recent-section h2, .panel-header {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 0;
        }

        .recent-section h2 {
            font-size: 18px;
        }

        .panel-body, .recent-section > *:not(h2) {
            padding: 15px;
        }

        /* Table styles */
        .data-table, .table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th, .data-table td,
        .table th, .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .data-table th, .table th {
            background-color: #f9f9f9;
            font-weight: 600;
        }

        .data-table tr:hover, .table tr:hover {
            background-color: #f9f9f9;
        }

        /* Form styles */
        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .form-group input, .form-group select, .form-group textarea,
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 3px;
            box-sizing: border-box;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            border-radius: 5px;
            width: 50%;
            max-width: 700px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        /* Sidebar specific styles */
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
            margin: 0;
        }

        .logo span {
            display: block;
            font-size: 14px;
            opacity: 0.7;
        }

        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
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

        /* Consistent card styling */
        .panel, .recent-section {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .panel-header, .recent-section h2 {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 0;
            font-size: 18px;
        }

        .panel-body {
            padding: 15px;
        }

        /* Make message styling match alert */
        .message, .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 3px;
        }

        .message.success, .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .message.error, .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
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

        /* Fix for the container class */
        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Update main content styling */
        .main-content {
            flex: 1;
            padding: 20px;
            background-color: #f5f5f5;
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
                    <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="user_management.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'user_management.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> User Management
                    </a>
                </li>
                <li class="nav-item">
                    <a href="product_management.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'product_management.php' ? 'active' : ''; ?>">
                        <i class="fas fa-book"></i> Product Management
                    </a>
                </li>
                <li class="nav-item">
                    <a href="category_management.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'category_management.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tags"></i> Categories
                    </a>
                </li>
                <li class="nav-item">
                    <a href="order_management.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'order_management.php' ? 'active' : ''; ?>">
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
            <!-- Header -->
            <div class="header">
                <h1><?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'Dashboard' : 'User Management'; ?></h1>
                <?php if (basename($_SERVER['PHP_SELF']) == 'user_management.php'): ?>
                <a href="#" class="btn btn-success" onclick="openAddModal(); return false;">
                    <i class="fas fa-plus"></i> Add New User
                </a>
                <?php endif; ?>
            </div>

            <!-- Stats Cards -->
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="icon users">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="info">
                        <h3><?php echo $userCount; ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>
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

            <!-- Recent Users -->
            <div class="recent-section">
                <h2>Recently Registered Users</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Registration Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $recentUsers->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $user['user_id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($user['registration_date'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <a href="user_management.php" class="btn view-all">View All Users</a>
            </div>

            <!-- Recent Books -->
            <div class="recent-section">
                <h2>Recently Added Books</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Price</th>
                            <th>Category</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($book = $recentBooks->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $book['book_id']; ?></td>
                            <td><?php echo htmlspecialchars($book['title']); ?></td>
                            <td><?php echo htmlspecialchars($book['author']); ?></td>
                            <td>$<?php echo number_format($book['price'], 2); ?></td>
                            <td><?php echo htmlspecialchars($book['category_name']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <a href="product_management.php" class="btn view-all">View All Books</a>
            </div>
        </div>
    </div>
</body>
</html>