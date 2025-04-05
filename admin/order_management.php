<?php
// filepath: d:\XAMPP\htdocs\OnlineBookstore\admin\order_management.php
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

// Get order stats
$totalOrders = $conn->query("SELECT COUNT(*) as count FROM Orders")->fetch_assoc()['count'];
$pendingOrders = $conn->query("SELECT COUNT(*) as count FROM Orders WHERE status = 'Processing'")->fetch_assoc()['count'];
$completedOrders = $conn->query("SELECT COUNT(*) as count FROM Orders WHERE status = 'Delivered'")->fetch_assoc()['count'];
$totalRevenue = $conn->query("SELECT SUM(total_amount) as total FROM Orders WHERE status != 'Cancelled'")->fetch_assoc()['total'] ?: 0;

// Process order status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $orderId = (int)$_POST['order_id'];
    $status = $_POST['status'];
    
    $updateOrder = $conn->prepare("UPDATE Orders SET status = ? WHERE order_id = ?");
    $updateOrder->bind_param("si", $status, $orderId);
    
    if ($updateOrder->execute()) {
        $successMessage = "Order status updated successfully.";
    } else {
        $errorMessage = "Error updating order status: " . $conn->error;
    }
    $updateOrder->close();
}

// Handle search and filtering
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Prepare the SQL query for orders
$orderSql = "SELECT o.*, u.username, u.email 
             FROM Orders o 
             JOIN Users u ON o.user_id = u.user_id 
             WHERE 1=1";

$params = [];
$paramTypes = "";

if (!empty($searchTerm)) {
    // Search by order ID or customer info
    if (is_numeric($searchTerm)) {
        $orderSql .= " AND o.order_id = ?";
        $params[] = $searchTerm;
        $paramTypes .= "i";
    } else {
        $orderSql .= " AND (u.username LIKE ? OR u.email LIKE ?)";
        $searchParam = "%$searchTerm%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $paramTypes .= "ss";
    }
}

if (!empty($statusFilter)) {
    $orderSql .= " AND o.status = ?";
    $params[] = $statusFilter;
    $paramTypes .= "s";
}

if (!empty($dateFrom)) {
    $orderSql .= " AND o.order_date >= ?";
    $params[] = $dateFrom . " 00:00:00";
    $paramTypes .= "s";
}

if (!empty($dateTo)) {
    $orderSql .= " AND o.order_date <= ?";
    $params[] = $dateTo . " 23:59:59";
    $paramTypes .= "s";
}

$orderSql .= " ORDER BY o.order_date DESC";

// Execute the query
if (!empty($params)) {
    $stmt = $conn->prepare($orderSql);
    $stmt->bind_param($paramTypes, ...$params);
    $stmt->execute();
    $orderResults = $stmt->get_result();
} else {
    $orderResults = $conn->query($orderSql);
}

// Get order details if viewing a specific order
$orderDetails = null;
$orderItems = null;
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $orderId = (int)$_GET['view'];
    
    // Get order information
    $orderQuery = $conn->prepare("
        SELECT o.*, u.username, u.email, u.phone, u.address, u.city, u.state, u.zip_code
        FROM Orders o
        JOIN Users u ON o.user_id = u.user_id
        WHERE o.order_id = ?
    ");
    $orderQuery->bind_param("i", $orderId);
    $orderQuery->execute();
    $orderDetails = $orderQuery->get_result()->fetch_assoc();
    $orderQuery->close();
    
    // Get order items
    if ($orderDetails) {
        $itemsQuery = $conn->prepare("
            SELECT oi.*, b.title, b.author, b.cover_image
            FROM Order_Items oi
            JOIN Books b ON oi.book_id = b.book_id
            WHERE oi.order_id = ?
        ");
        $itemsQuery->bind_param("i", $orderId);
        $itemsQuery->execute();
        $orderItems = $itemsQuery->get_result();
        $itemsQuery->close();
    } else {
        $errorMessage = "Order not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Haitchal Books Admin</title>
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
            flex-wrap: wrap;
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
            min-width: 200px;
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

        .book-image {
            width: 50px;
            height: 70px;
            object-fit: cover;
        }

        .actions {
            display: flex;
            gap: 5px;
        }

        .status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-processing {
            background-color: #fff8e1;
            color: #ff9800;
        }

        .status-shipped {
            background-color: #e3f2fd;
            color: #2196f3;
        }

        .status-delivered {
            background-color: #e8f5e9;
            color: #4caf50;
        }

        .status-cancelled {
            background-color: #ffebee;
            color: #f44336;
        }

        .order-detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .order-info h3 {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .info-group {
            margin-bottom: 10px;
        }

        .info-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 3px;
        }

        .order-total {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #eee;
            font-size: 18px;
            font-weight: 600;
            text-align: right;
        }

        .order-items {
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .order-detail-grid {
                grid-template-columns: 1fr;
            }
        }
        
        #qr-reader {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }
        .scan-container {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            display: none;
        }
        .btn-scan {
            background-color: var(--warning-color);
        }
        .btn-scan:hover {
            background-color: #e67e22;
        }
    </style>
    <script src="https://unpkg.com/html5-qrcode"></script>
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
                    <a href="category_management.php" class="nav-link">
                        <i class="fas fa-tags"></i> Categories
                    </a>
                </li>
                <li class="nav-item">
                    <a href="order_management.php" class="nav-link active">
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
                <h1><?php echo isset($orderDetails) ? 'Order Details #' . $orderDetails['order_id'] : 'Order Management'; ?></h1>
                <?php if (isset($orderDetails)): ?>
                <a href="order_management.php" class="btn">
                    <i class="fas fa-arrow-left"></i> Back to Orders
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

            <?php if (isset($orderDetails)): ?>
            <!-- Order Details View -->
            <div class="panel">
                <div class="panel-header">
                    <h2>Order #<?php echo $orderDetails['order_id']; ?> Details</h2>
                    <span class="status status-<?php echo strtolower($orderDetails['status']); ?>">
                        <?php echo $orderDetails['status']; ?>
                    </span>
                </div>
                <div class="panel-body">
                    <div class="order-detail-grid">
                        <div class="order-info">
                            <h3>Order Information</h3>
                            <div class="info-group">
                                <label>Order Date:</label>
                                <div><?php echo date('F j, Y, g:i a', strtotime($orderDetails['order_date'])); ?></div>
                            </div>
                            <div class="info-group">
                                <label>Payment Method:</label>
                                <div><?php echo htmlspecialchars($orderDetails['payment_method']); ?></div>
                            </div>
                            <div class="info-group">
                                <label>Status:</label>
                                <div>
                                    <form method="post" action="order_management.php" style="display: flex; gap: 10px; align-items: center;">
                                        <input type="hidden" name="order_id" value="<?php echo $orderDetails['order_id']; ?>">
                                        <select name="status" class="form-control" style="width: auto;">
                                            <option value="Processing" <?php echo $orderDetails['status'] == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="Shipped" <?php echo $orderDetails['status'] == 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                                            <option value="Delivered" <?php echo $orderDetails['status'] == 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                            <option value="Cancelled" <?php echo $orderDetails['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn btn-success">Update Status</button>
                                    </form>
                                </div>
                            </div>
                            <div class="info-group">
                                <label>Shipping Fee:</label>
                                <div>$<?php echo number_format($orderDetails['shipping_fee'], 2); ?></div>
                            </div>
                            <div class="info-group">
                                <label>Total Amount:</label>
                                <div>$<?php echo number_format($orderDetails['total_amount'], 2); ?></div>
                            </div>
                            <div class="info-group">
                                <label>Reference Code:</label>
                                <div><?php echo htmlspecialchars($orderDetails['reference_code'] ?? 'N/A'); ?></div>
                            </div>
                            <?php if (!empty($orderDetails['qr_code'])): ?>
                            <div class="info-group">
                                <label>QR Code:</label>
                                <div>
                                    <img src="<?php echo "../" . htmlspecialchars($orderDetails['qr_code']); ?>" 
                                         alt="Order QR Code" style="width: 150px; height: 150px;">
                                    <p><small>Scan to verify order</small></p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="customer-info">
                            <h3>Customer Information</h3>
                            <div class="info-group">
                                <label>Name:</label>
                                <div><?php echo htmlspecialchars($orderDetails['username']); ?></div>
                            </div>
                            <div class="info-group">
                                <label>Email:</label>
                                <div><?php echo htmlspecialchars($orderDetails['email']); ?></div>
                            </div>
                            <div class="info-group">
                                <label>Phone:</label>
                                <div><?php echo htmlspecialchars($orderDetails['phone'] ?? 'Not provided'); ?></div>
                            </div>
                            <div class="info-group">
                                <label>Shipping Address:</label>
                                <div>
                                    <?php 
                                    $address = [];
                                    if (!empty($orderDetails['address'])) $address[] = htmlspecialchars($orderDetails['address']);
                                    if (!empty($orderDetails['city'])) $address[] = htmlspecialchars($orderDetails['city']);
                                    if (!empty($orderDetails['state'])) $address[] = htmlspecialchars($orderDetails['state']);
                                    if (!empty($orderDetails['zip_code'])) $address[] = htmlspecialchars($orderDetails['zip_code']);
                                    
                                    echo !empty($address) ? implode(', ', $address) : 'Not provided';
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="order-items">
                        <h3>Order Items</h3>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $subtotal = 0;
                                while ($item = $orderItems->fetch_assoc()): 
                                    $itemTotal = $item['price'] * $item['quantity'];
                                    $subtotal += $itemTotal;
                                ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($item['cover_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($item['cover_image']); ?>" alt="Cover" class="book-image">
                                        <?php else: ?>
                                            <div class="book-image" style="background-color: #eee; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-book"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><strong><?php echo htmlspecialchars($item['title']); ?></strong></div>
                                        <div>by <?php echo htmlspecialchars($item['author']); ?></div>
                                    </td>
                                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>$<?php echo number_format($itemTotal, 2); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        
                        <div class="order-total">
                            <div>Subtotal: $<?php echo number_format($subtotal, 2); ?></div>
                            <div>Shipping: $<?php echo number_format($orderDetails['shipping_fee'], 2); ?></div>
                            <div>Total: $<?php echo number_format($orderDetails['total_amount'], 2); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- Dashboard Stats -->
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="icon orders">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="info">
                        <h3><?php echo $totalOrders; ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="icon pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="info">
                        <h3><?php echo $pendingOrders; ?></h3>
                        <p>Pending Orders</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="icon completed">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="info">
                        <h3><?php echo $completedOrders; ?></h3>
                        <p>Completed Orders</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="icon revenue">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="info">
                        <h3>$<?php echo number_format($totalRevenue, 2); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
            </div>
                
            <!-- Orders List -->
            <div class="panel">
                <div class="panel-header">
                    <h2>Orders List</h2>
                </div>
                <div class="panel-body">
                    <form method="get" action="order_management.php" class="search-bar">
                        <input type="text" name="search" placeholder="Search by order ID or customer..." 
                               value="<?php echo htmlspecialchars($searchTerm); ?>">
                        <select name="status">
                            <option value="">All Statuses</option>
                            <option value="Processing" <?php echo $statusFilter == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="Shipped" <?php echo $statusFilter == 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="Delivered" <?php echo $statusFilter == 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="Cancelled" <?php echo $statusFilter == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        <input type="date" name="date_from" placeholder="Date From" value="<?php echo $dateFrom; ?>">
                        <input type="date" name="date_to" placeholder="Date To" value="<?php echo $dateTo; ?>">
                        <button type="submit" class="btn">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <?php if (!empty($searchTerm) || !empty($statusFilter) || !empty($dateFrom) || !empty($dateTo)): ?>
                            <a href="order_management.php" class="btn">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        <?php endif; ?>
                        <button type="button" id="scanQrBtn" class="btn btn-scan">
                            <i class="fas fa-qrcode"></i> Scan QR Code
                        </button>
                    </form>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Total</th>
                                <th>Payment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($orderResults && $orderResults->num_rows > 0): ?>
                                <?php while ($order = $orderResults->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $order['order_id']; ?></td>
                                        <td>
                                            <div><?php echo htmlspecialchars($order['username']); ?></div>
                                            <small><?php echo htmlspecialchars($order['email']); ?></small>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                        <td>
                                            <span class="status status-<?php echo strtolower($order['status']); ?>">
                                                <?php echo $order['status']; ?>
                                            </span>
                                        </td>
                                        <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                                        <td class="actions">
                                            <a href="?view=<?php echo $order['order_id']; ?>" class="btn" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center;">No orders found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div id="qrScanModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Scan Order QR Code</h2>
            <div id="qr-reader"></div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Modal control
        const modal = document.getElementById('qrScanModal');
        const scanBtn = document.getElementById('scanQrBtn');
        const closeSpan = document.getElementsByClassName('close')[0];
        
        if (scanBtn) {
            scanBtn.onclick = function() {
                modal.style.display = 'block';
                startQrScanner();
            }
        }
        
        if (closeSpan) {
            closeSpan.onclick = function() {
                modal.style.display = 'none';
                stopQrScanner();
            }
        }
        
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
                stopQrScanner();
            }
        }
        
        // QR Scanner
        let html5QrCode;
        
        function startQrScanner() {
            html5QrCode = new Html5Qrcode("qr-reader");
            html5QrCode.start(
                { facingMode: "environment" }, // Use rear camera
                {
                    fps: 10,
                    qrbox: 250
                },
                onScanSuccess,
                onScanFailure
            );
        }
        
        function stopQrScanner() {
            if (html5QrCode && html5QrCode.getState() === 2) {
                html5QrCode.stop().catch(error => {
                    console.error("Error stopping scanner:", error);
                });
            }
        }
        
        function onScanSuccess(decodedText) {
            try {
                const orderData = JSON.parse(decodedText);
                if (orderData.order_id) {
                    window.location.href = 'order_management.php?view=' + orderData.order_id;
                } else {
                    alert("Invalid QR code format");
                }
            } catch (e) {
                alert("Error processing QR code: " + e.message);
            }
            
            stopQrScanner();
            modal.style.display = 'none';
        }
        
        function onScanFailure(error) {
            // Silent failure - no need to show errors while scanning
        }
    });
    </script>
</body>
</html>