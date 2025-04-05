<?php
session_start();
require_once '../connect.php';
require_once '../lib/phpqrcode/qrlib.php';

// Check if user is logged in
$loggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$username = $loggedIn ? $_SESSION['username'] : '';
$isAdmin = $loggedIn && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$userId = $loggedIn ? $_SESSION['user_id'] : 0;

// Initialize variables
$cartItems = [];
$cartTotal = 0;
$cartCount = 0;

// Process remove action
if ($loggedIn && isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $cartId = (int)$_GET['remove'];
    $deleteItem = $conn->prepare("DELETE FROM Cart WHERE cart_id = ? AND user_id = ?");
    $deleteItem->bind_param("ii", $cartId, $userId);
    $deleteItem->execute();
    $deleteItem->close();
    
    // Redirect to remove the query parameter
    header("Location: cart.php");
    exit;
}

// Process quantity update
if ($loggedIn && isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $cartId => $quantity) {
        if (is_numeric($cartId) && is_numeric($quantity) && $quantity > 0) {
            $updateQuantity = $conn->prepare("UPDATE Cart SET quantity = ? WHERE cart_id = ? AND user_id = ?");
            $updateQuantity->bind_param("iii", $quantity, $cartId, $userId);
            $updateQuantity->execute();
            $updateQuantity->close();
        }
    }
    
    // Redirect to prevent form resubmission
    header("Location: cart.php");
    exit;
}

// Fetch cart items if logged in
if ($loggedIn) {
    $cartQuery = $conn->prepare("
        SELECT c.cart_id, c.book_id, c.quantity, b.title, b.author, b.cover_image, b.price
        FROM Cart c
        JOIN Books b ON c.book_id = b.book_id
        WHERE c.user_id = ?
        ORDER BY c.date_added DESC
    ");
    $cartQuery->bind_param("i", $userId);
    $cartQuery->execute();
    $result = $cartQuery->get_result();
    
    while ($item = $result->fetch_assoc()) {
        $cartItems[] = $item;
        $cartTotal += $item['price'] * $item['quantity'];
        $cartCount += $item['quantity'];
    }
    
    $cartQuery->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Shopping Cart - Haitchal Books</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Stick+No+Bills">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/home_styles.css">
    <style>
        .cart-container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .cart-header {
            display: grid;
            grid-template-columns: 3fr 1fr 1fr 1fr auto;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            font-weight: bold;
        }
        .cart-item {
            display: grid;
            grid-template-columns: 3fr 1fr 1fr 1fr auto;
            padding: 20px 0;
            border-bottom: 1px solid #eee;
            align-items: center;
        }
        .cart-item-details {
            display: flex;
            align-items: center;
        }
        .cart-item img {
            width: 60px;
            height: auto;
            margin-right: 15px;
        }
        .cart-quantity input {
            width: 50px;
            padding: 5px;
            text-align: center;
        }
        .cart-remove {
            color: #e74c3c;
            cursor: pointer;
        }
        .cart-total {
            text-align: right;
            padding: 20px 0;
            font-size: 1.2em;
        }
        .cart-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #2980b9;
        }
        .btn-update {
            background-color: #2ecc71;
        }
        .btn-update:hover {
            background-color: #27ae60;
        }
        .btn-checkout {
            background-color: #e74c3c;
        }
        .btn-checkout:hover {
            background-color: #c0392b;
        }
        .cart-empty {
            text-align: center;
            padding: 40px 0;
        }
        .cart-empty i {
            font-size: 4em;
            color: #ddd;
            margin-bottom: 20px;
        }
        .mobile-checkout {
            margin-top: 30px;
            text-align: center;
            padding: 20px;
            border-top: 1px solid #eee;
        }

        .qr-checkout img {
            max-width: 200px;
            display: block;
            margin: 15px auto;
        }

        .qr-checkout p {
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Top navigation bar with account links -->
        <div class="top-nav">
            <div class="container">
                <div class="user-links">
                    <?php if ($loggedIn): ?>
                        <span>Welcome, <?php echo htmlspecialchars($username); ?></span>
                        <span class="separator">|</span>
                        <?php if ($isAdmin): ?>
                            <a href="admin/dashboard.php">Admin Dashboard</a>
                            <span class="separator">|</span>
                        <?php else: ?>
                            <a href="profile.php">My Profile</a>
                            <span class="separator">|</span>
                        <?php endif; ?>
                        <a href="logout.php">Logout</a>
                        <span class="separator">|</span>
                        <a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart (<?php echo $cartCount; ?>)</a>
                    <?php else: ?>
                        <a href="login.php">Sign In</a>
                        <span class="separator">|</span>
                        <a href="register.php">Create Account</a>
                        <span class="separator">|</span>
                        <a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart (0)</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Main header with logo and search -->
        <div class="header">
            <div class="logo">
                <img id="logo" src="../images/Haitchal_Books.png" alt="Bookstore Logo" width="100" height="100">
                <h1>Haitchal Books</h1>
            </div>
            <div class="form-search">
                <form action="search.php" method="get">
                    <input type="text" name="search" id="search" placeholder="Search for books, authors, or genres...">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
        </div>

        <!-- Main navigation menu -->
        <div class="menu">
            <ul>
                <li><a href="homepage.php">Home</a></li>
                <li><a href="books.php">Books</a></li>
                <li><a href="new_releases.php">New Releases</a></li>
                <li><a href="bestsellers.php">Bestsellers</a></li>
                <li><a href="special_offers.php">Special Offers</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>
        </div>

        <!-- Cart Content -->
        <div class="cart-container">
            <h2>Your Shopping Cart</h2>
            
            <?php if (!$loggedIn): ?>
                <div class="cart-empty">
                    <i class="fas fa-sign-in-alt"></i>
                    <h3>Please log in to view your cart</h3>
                    <p>You need to be logged in to add and view items in your shopping cart.</p>
                    <a href="login.php?redirect=cart.php" class="btn">Sign In</a>
                </div>
            <?php elseif (empty($cartItems)): ?>
                <div class="cart-empty">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Your cart is empty</h3>
                    <p>Looks like you haven't added any books to your cart yet.</p>
                    <a href="homepage.php" class="btn">Continue Shopping</a>
                </div>
            <?php else: ?>
                <form action="cart.php" method="post">
                    <div class="cart-header">
                        <div>Product</div>
                        <div>Price</div>
                        <div>Quantity</div>
                        <div>Total</div>
                        <div></div>
                    </div>
                    
                    <?php foreach ($cartItems as $item): ?>
                        <div class="cart-item">
                            <div class="cart-item-details">
                                <img src="<?php echo !empty($item['cover_image']) ? htmlspecialchars($item['cover_image']) : 'https://via.placeholder.com/60x90?text=No+Image'; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                <div>
                                    <h4><?php echo htmlspecialchars($item['title']); ?></h4>
                                    <p>by <?php echo htmlspecialchars($item['author']); ?></p>
                                </div>
                            </div>
                            <div class="cart-price">$<?php echo number_format($item['price'], 2); ?></div>
                            <div class="cart-quantity">
                                <input type="number" name="quantity[<?php echo $item['cart_id']; ?>]" value="<?php echo $item['quantity']; ?>" min="1" max="99">
                            </div>
                            <div class="cart-item-total">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                            <div class="cart-remove">
                                <a href="cart.php?remove=<?php echo $item['cart_id']; ?>" title="Remove item"><i class="fas fa-trash"></i></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="cart-total">
                        <strong>Subtotal: $<?php echo number_format($cartTotal, 2); ?></strong>
                    </div>
                    
                    <div class="cart-actions">
                        <a href="homepage.php" class="btn">Continue Shopping</a>
                        <div>
                            <button type="submit" name="update_cart" class="btn btn-update">Update Cart</button>
                            <a href="checkout.php" class="btn btn-checkout">Proceed to Checkout</a>
                        </div>
                    </div>
                    <?php
                    // Add this right before the closing </div> in the cart-actions section (around line 290)
                    if (!empty($cartItems)) {
                        echo '<div class="mobile-checkout">';
                        echo '<h4>Quick Mobile Checkout</h4>';
                        
                        // Create temporary checkout data
                        $checkoutData = [
                            'user_id' => $userId,
                            'items' => array_map(function($item) {
                                return [
                                    'book_id' => $item['book_id'],
                                    'title' => $item['title'],
                                    'quantity' => $item['quantity'],
                                    'price' => $item['price']
                                ];
                            }, $cartItems),
                            'total' => $cartTotal,
                            'timestamp' => time()
                        ];
                        
                        $checkoutToken = md5(json_encode($checkoutData) . time());
                        $_SESSION['checkout_token'] = $checkoutToken;
                        
                        // Store checkout token temporarily
                        $tempCheckout = $conn->prepare("INSERT INTO Temp_Checkout (user_id, token, data, created_at, expires_at) VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 MINUTE))");
                        $jsonData = json_encode($checkoutData);
                        $tempCheckout->bind_param("iss", $userId, $checkoutToken, $jsonData);
                        $tempCheckout->execute();
                        $tempCheckout->close();
                        
                        // Generate QR code URL
                        $qrUrl = 'https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=' . urlencode(json_encode([
                            'action' => 'checkout',
                            'token' => $checkoutToken
                        ]));
                        
                        echo '<div class="qr-checkout">';
                        echo '<img src="' . $qrUrl . '" alt="Mobile Checkout QR">';
                        echo '<p>Scan with your mobile device to checkout</p>';
                        echo '</div>';
                        echo '</div>';
                    }
                    ?>
                </form>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-links">
                <div class="footer-column">
                    <h4>Customer Service</h4>
                    <ul>
                        <li><a href="help.php">Help Center</a></li>
                        <li><a href="order_status.php">Order Status</a></li>
                        <li><a href="returns.php">Returns & Refunds</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h4>About Us</h4>
                    <ul>
                        <li><a href="about.php">Our Story</a></li>
                        <li><a href="careers.php">Careers</a></li>
                        <li><a href="press.php">Press</a></li>
                        <li><a href="blog.php">Blog</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h4>Connect With Us</h4>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-pinterest"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Haitchal Books. All rights reserved.</p>
                <div class="footer-legal">
                    <a href="privacy.php">Privacy Policy</a>
                    <a href="terms.php">Terms of Use</a>
                    <a href="accessibility.php">Accessibility</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php
// Add after order is successfully created
if (isset($orderId) && $orderId > 0) {
    // Create a unique reference code for the order
    $orderReference = 'HB-' . str_pad($orderId, 6, '0', STR_PAD_LEFT);
    
    // Update the order with reference code
    $updateRef = $conn->prepare("UPDATE Orders SET reference_code = ? WHERE order_id = ?");
    $updateRef->bind_param("si", $orderReference, $orderId);
    $updateRef->execute();
    $updateRef->close();
    
    // Create QR code content
    $qrContent = json_encode([
        'order_id' => $orderId,
        'reference' => $orderReference,
        'amount' => $totalAmount,
        'date' => date('Y-m-d H:i:s')
    ]);
    
    // QR code image path
    $qrPath = "../images/qrcodes/";
    if (!file_exists($qrPath)) {
        mkdir($qrPath, 0777, true);
    }
    
    $qrFilename = $qrPath . "order_" . $orderId . ".png";
    
    // Generate QR code - Method 1: Using phpqrcode library
    if (function_exists('QRcode::png')) {
        QRcode::png($qrContent, $qrFilename, 'M', 10, 2);
    } else {
        // Method 2: Using Google Charts API if library not available
        $qrCodeUrl = 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . urlencode($qrContent);
        file_put_contents($qrFilename, file_get_contents($qrCodeUrl));
    }
    
    // Save QR code URL to database
    $qrUrl = str_replace("..", "", $qrFilename);
    $updateQr = $conn->prepare("UPDATE Orders SET qr_code = ? WHERE order_id = ?");
    $updateQr->bind_param("si", $qrUrl, $orderId);
    $updateQr->execute();
    $updateQr->close();
}
?>