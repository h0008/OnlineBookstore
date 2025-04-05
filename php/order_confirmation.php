<?php
// filepath: d:\XAMPP\htdocs\OnlineBookstore\php\order_confirmation.php
session_start();
require_once '../connect.php';

// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Get user info
$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: homepage.php');
    exit;
}

$orderId = (int)$_GET['id'];

// Fetch order details
$orderQuery = $conn->prepare("
    SELECT o.*, u.username, u.email, u.phone, u.address, u.city, u.state, u.zip_code
    FROM Orders o
    JOIN Users u ON o.user_id = u.user_id
    WHERE o.order_id = ? AND o.user_id = ?
");
$orderQuery->bind_param("ii", $orderId, $userId);
$orderQuery->execute();
$order = $orderQuery->get_result()->fetch_assoc();
$orderQuery->close();

// If order not found or doesn't belong to user
if (!$order) {
    header('Location: homepage.php');
    exit;
}

// Fetch order items
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Haitchal Books</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Stick+No+Bills">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/home_styles.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .confirmation-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .confirmation-header i {
            display: block;
            font-size: 50px;
            color: #2ecc71;
            margin-bottom: 15px;
        }
        .order-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        .order-info h3, .shipping-info h3 {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .info-group {
            margin-bottom: 10px;
        }
        .info-group strong {
            display: block;
            font-weight: bold;
        }
        .order-items {
            margin-bottom: 30px;
        }
        .order-item {
            display: flex;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .order-item img {
            width: 60px;
            height: 80px;
            object-fit: cover;
            margin-right: 15px;
        }
        .order-item-details {
            flex: 1;
        }
        .order-item-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .order-item-author {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        .order-item-price {
            color: #666;
        }
        .order-total {
            text-align: right;
            font-size: 1.1em;
            margin-bottom: 30px;
        }
        .order-total div {
            margin-bottom: 10px;
        }
        .order-total strong {
            font-size: 1.2em;
            color: var(--primary-color);
        }
        .order-actions {
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #2980b9;
        }
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            text-transform: uppercase;
            font-size: 0.8em;
            font-weight: bold;
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
        .order-qr-code {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .order-qr-code img {
            max-width: 200px;
            margin: 10px auto;
            padding: 10px;
            border: 1px solid #eee;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Include header -->
        <?php include_once '../templates/header.php'; ?>
        
        <!-- Confirmation content -->
        <div class="confirmation-container">
            <div class="confirmation-header">
                <i class="fas fa-check-circle"></i>
                <h1>Order Confirmed!</h1>
                <p>Your order has been received and is now being processed.</p>
                <p>Order #<?php echo $order['order_id']; ?> - <span class="status status-<?php echo strtolower($order['status']); ?>"><?php echo $order['status']; ?></span></p>
                <p>Reference: <?php echo htmlspecialchars($order['reference_code'] ?? 'N/A'); ?></p>
                <p>Order Date: <?php echo date('F j, Y, g:i a', strtotime($order['order_date'])); ?></p>
            </div>
            
            <div class="order-details">
                <div class="order-info">
                    <h3>Order Information</h3>
                    <div class="info-group">
                        <strong>Payment Method:</strong>
                        <span><?php echo htmlspecialchars($order['payment_method']); ?></span>
                    </div>
                    <div class="info-group">
                        <strong>Order Status:</strong>
                        <span class="status status-<?php echo strtolower($order['status']); ?>"><?php echo $order['status']; ?></span>
                    </div>
                </div>
                
                <div class="shipping-info">
                    <h3>Shipping Information</h3>
                    <div class="info-group">
                        <strong>Name:</strong>
                        <span><?php echo htmlspecialchars($order['username']); ?></span>
                    </div>
                    <div class="info-group">
                        <strong>Address:</strong>
                        <span>
                            <?php echo htmlspecialchars($order['address']); ?><br>
                            <?php echo htmlspecialchars($order['city']); ?>, <?php echo htmlspecialchars($order['state']); ?> <?php echo htmlspecialchars($order['zip_code']); ?>
                        </span>
                    </div>
                    <div class="info-group">
                        <strong>Phone:</strong>
                        <span><?php echo htmlspecialchars($order['phone'] ?? 'Not provided'); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="order-items">
                <h3>Order Items</h3>
                <?php 
                $subtotal = 0;
                while ($item = $orderItems->fetch_assoc()):
                    $itemTotal = $item['price'] * $item['quantity'];
                    $subtotal += $itemTotal;
                ?>
                <div class="order-item">
                    <img src="<?php echo !empty($item['cover_image']) ? htmlspecialchars($item['cover_image']) : '../images/placeholder-book.png'; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                    <div class="order-item-details">
                        <div class="order-item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                        <div class="order-item-author">by <?php echo htmlspecialchars($item['author']); ?></div>
                        <div class="order-item-price">
                            $<?php echo number_format($item['price'], 2); ?> × <?php echo $item['quantity']; ?> = $<?php echo number_format($itemTotal, 2); ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            
            <div class="order-total">
                <div>Subtotal: $<?php echo number_format($subtotal, 2); ?></div>
                <div>Shipping: $<?php echo number_format($order['shipping_fee'], 2); ?></div>
                <div>Tax: $<?php echo number_format($order['total_amount'] - $subtotal - $order['shipping_fee'], 2); ?></div>
                <div><strong>Total: $<?php echo number_format($order['total_amount'], 2); ?></strong></div>
            </div>
            
            <div class="order-actions">
                <a href="homepage.php" class="btn">Continue Shopping</a>
                <?php if ($order['status'] === 'Processing' || $order['status'] === 'Shipped'): ?>
                <a href="#" class="btn">Track Order</a>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($order['qr_code'])): ?>
            <div class="order-qr-code">
                <h3>Order QR Code</h3>
                <p>Use this QR code for quick reference and order tracking</p>
                <img src="<?php echo "../" . htmlspecialchars($order['qr_code']); ?>" alt="Order QR Code">
                <p><small>Reference: <?php echo htmlspecialchars($order['reference_code']); ?></small></p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Include footer -->
        <?php include_once '../templates/footer.php'; ?>
    </div>
</body>
</html>