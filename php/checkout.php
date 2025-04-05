<?php
// filepath: d:\XAMPP\htdocs\OnlineBookstore\php\checkout.php
session_start();

// Display errors for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../connect.php';

// Skip loading the QR library since it's causing errors
// require_once '../lib/phpqrcode/qrlib.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header('Location: login.php');
    exit;
}

// Get user info
$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Initialize variables
$cartItems = [];
$cartTotal = 0;
$cartCount = 0;
$shippingFee = 5.00; // Default shipping fee

// Error and success messages
$errorMsg = '';
$successMsg = '';

// Fetch user details for pre-filling form
$userQuery = $conn->prepare("SELECT * FROM Users WHERE user_id = ?");
$userQuery->bind_param("i", $userId);
$userQuery->execute();
$userDetails = $userQuery->get_result()->fetch_assoc();
$userQuery->close();

// Fetch cart items
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

// If cart is empty, redirect to cart page
if (empty($cartItems)) {
    header('Location: cart.php');
    exit;
}

// Process checkout form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    // Validate shipping info
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $zipCode = trim($_POST['zip_code']);
    $paymentMethod = $_POST['payment_method'];
    
    // Basic validation
    if (empty($fullName) || empty($email) || empty($address) || empty($city) || empty($state) || empty($zipCode)) {
        $errorMsg = "Please fill in all required fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = "Please enter a valid email address";
    } else {
        try {
            // Update user info with shipping details if they're different
            if ($fullName != $userDetails['username'] || $phone != $userDetails['phone'] || 
                $address != $userDetails['address'] || $city != $userDetails['city'] || 
                $state != $userDetails['state'] || $zipCode != $userDetails['zip_code']) {
                
                $updateUser = $conn->prepare("
                    UPDATE Users SET 
                    phone = ?, 
                    address = ?, 
                    city = ?, 
                    state = ?, 
                    zip_code = ? 
                    WHERE user_id = ?
                ");
                $updateUser->bind_param("sssssi", $phone, $address, $city, $state, $zipCode, $userId);
                $updateUser->execute();
                $updateUser->close();
            }
            
            // Calculate total amount with shipping
            $totalAmount = $cartTotal + $shippingFee;
            
            // Process order - begin transaction
            $conn->begin_transaction();
            
            // Create order record
            $insertOrder = $conn->prepare("
                INSERT INTO Orders (user_id, order_date, status, shipping_fee, total_amount, payment_method) 
                VALUES (?, NOW(), 'Processing', ?, ?, ?)
            ");
            $insertOrder->bind_param("idds", $userId, $shippingFee, $totalAmount, $paymentMethod);
            $insertOrder->execute();
            $orderId = $conn->insert_id;
            $insertOrder->close();
            
            // Insert order items
            $insertItem = $conn->prepare("
                INSERT INTO Order_Items (order_id, book_id, quantity, price) 
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($cartItems as $item) {
                $insertItem->bind_param("iiid", $orderId, $item['book_id'], $item['quantity'], $item['price']);
                $insertItem->execute();
            }
            $insertItem->close();
            
            // Create a unique reference code for the order
            $orderReference = 'HB-' . str_pad($orderId, 6, '0', STR_PAD_LEFT);
            
            // Update the order with reference code
            $updateRef = $conn->prepare("UPDATE Orders SET reference_code = ? WHERE order_id = ?");
            $updateRef->bind_param("si", $orderReference, $orderId);
            $updateRef->execute();
            $updateRef->close();
            
            // QR code generation - using Google Charts API (more reliable than phpqrcode)
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
            
            // Use Google Charts API to generate QR code
            $qrCodeUrl = 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . urlencode($qrContent);
            $qrFilename = $qrPath . "order_" . $orderId . ".png";
            
            // Try to fetch and save the QR code
            $qrImage = @file_get_contents($qrCodeUrl);
            if ($qrImage !== false) {
                file_put_contents($qrFilename, $qrImage);
                
                // Save QR code URL to database
                $qrUrl = "/images/qrcodes/order_" . $orderId . ".png";
                $updateQr = $conn->prepare("UPDATE Orders SET qr_code = ? WHERE order_id = ?");
                $updateQr->bind_param("si", $qrUrl, $orderId);
                $updateQr->execute();
                $updateQr->close();
            }
            
            // Clear user's cart
            $clearCart = $conn->prepare("DELETE FROM Cart WHERE user_id = ?");
            $clearCart->bind_param("i", $userId);
            $clearCart->execute();
            $clearCart->close();
            
            // Commit transaction
            $conn->commit();
            
            // Redirect to order confirmation page
            header("Location: order_confirmation.php?id=" . $orderId);
            exit;
            
        } catch (Exception $e) {
            // Rollback on error and show the error message
            $conn->rollback();
            $errorMsg = "Error processing your order: " . $e->getMessage();
        }
    }
}

// Calculate estimated tax (for display purposes)
$estimatedTax = $cartTotal * 0.08; // 8% tax rate example
$orderTotal = $cartTotal + $shippingFee + $estimatedTax;

// Get the base path for proper URLs
$currentPath = $_SERVER['PHP_SELF'];
$pathInfo = pathinfo($currentPath);
$basePath = '';
if (strpos($pathInfo['dirname'], '/php') !== false) {
    $basePath = '';
} else {
    $basePath = 'php/';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Haitchal Books</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Stick+No+Bills">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/home_styles.css">
    <style>
        .checkout-container {
            max-width: 1200px;
            margin: 20px auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        @media (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
            }
        }
        
        .checkout-summary, .checkout-form {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        .checkout-title {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            color: var(--primary-color);
        }
        
        .cart-items {
            margin-bottom: 20px;
        }
        
        .cart-item {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .cart-item img {
            width: 60px;
            height: 80px;
            object-fit: cover;
            margin-right: 15px;
        }
        
        .cart-item-details {
            flex: 1;
        }
        
        .cart-item-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .cart-item-author {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        
        .cart-item-price {
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .cart-item-quantity {
            margin-left: 10px;
            color: #666;
        }
        
        .order-summary {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .summary-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 1.1em;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid #eee;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .payment-method {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .payment-method:hover {
            border-color: var(--primary-color);
        }
        
        .payment-method.active {
            border-color: var(--primary-color);
            background-color: rgba(52, 152, 219, 0.1);
        }
        
        .payment-method i {
            font-size: 24px;
            margin-bottom: 10px;
            color: var(--primary-color);
        }
        
        .btn-place-order {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s;
        }
        
        .btn-place-order:hover {
            background-color: #2980b9;
        }
        
        .error-message {
            color: #e74c3c;
            padding: 10px;
            background-color: #fadbd8;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .secure-checkout {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 15px;
            color: #666;
            font-size: 0.9em;
        }
        
        .secure-checkout i {
            margin-right: 5px;
            color: #27ae60;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Include header -->
        <?php include_once '../templates/header.php'; ?>
        
        <!-- Main content -->
        <div class="checkout-container">
            <!-- Order summary -->
            <div class="checkout-summary">
                <h2 class="checkout-title">Order Summary</h2>
                
                <div class="cart-items">
                    <?php foreach ($cartItems as $item): ?>
                    <div class="cart-item">
                        <img src="<?php echo !empty($item['cover_image']) ? htmlspecialchars($item['cover_image']) : '../images/placeholder-book.png'; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                        <div class="cart-item-details">
                            <div class="cart-item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                            <div class="cart-item-author">by <?php echo htmlspecialchars($item['author']); ?></div>
                            <div>
                                <span class="cart-item-price">$<?php echo number_format($item['price'], 2); ?></span>
                                <span class="cart-item-quantity">× <?php echo $item['quantity']; ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="order-summary">
                    <div class="summary-row">
                        <div>Subtotal</div>
                        <div>$<?php echo number_format($cartTotal, 2); ?></div>
                    </div>
                    <div class="summary-row">
                        <div>Shipping</div>
                        <div>$<?php echo number_format($shippingFee, 2); ?></div>
                    </div>
                    <div class="summary-row">
                        <div>Estimated Tax</div>
                        <div>$<?php echo number_format($estimatedTax, 2); ?></div>
                    </div>
                    <div class="summary-row">
                        <div>Total</div>
                        <div>$<?php echo number_format($orderTotal, 2); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Checkout form -->
            <div class="checkout-form">
                <h2 class="checkout-title">Shipping & Payment</h2>
                
                <?php if (!empty($errorMsg)): ?>
                <div class="error-message">
                    <?php echo $errorMsg; ?>
                </div>
                <?php endif; ?>
                
                <form method="post" action="checkout.php">
                    <h3>Shipping Information</h3>
                    <div class="form-group">
                        <label for="full_name">Full Name*</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" required
                               value="<?php echo htmlspecialchars($userDetails['username']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address*</label>
                        <input type="email" id="email" name="email" class="form-control" required
                               value="<?php echo htmlspecialchars($userDetails['email']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control"
                               value="<?php echo htmlspecialchars($userDetails['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address*</label>
                        <input type="text" id="address" name="address" class="form-control" required
                               value="<?php echo htmlspecialchars($userDetails['address'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City*</label>
                            <input type="text" id="city" name="city" class="form-control" required
                                   value="<?php echo htmlspecialchars($userDetails['city'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="state">State*</label>
                            <input type="text" id="state" name="state" class="form-control" required
                                   value="<?php echo htmlspecialchars($userDetails['state'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="zip_code">ZIP Code*</label>
                        <input type="text" id="zip_code" name="zip_code" class="form-control" required
                               value="<?php echo htmlspecialchars($userDetails['zip_code'] ?? ''); ?>">
                    </div>
                    
                    <h3>Payment Method</h3>
                    <div class="payment-methods">
                        <div class="payment-method active" data-method="Credit Card">
                            <i class="fas fa-credit-card"></i>
                            <div>Credit Card</div>
                            <input type="radio" name="payment_method" value="Credit Card" checked style="display:none;">
                        </div>
                        <div class="payment-method" data-method="PayPal">
                            <i class="fab fa-paypal"></i>
                            <div>PayPal</div>
                            <input type="radio" name="payment_method" value="PayPal" style="display:none;">
                        </div>
                        <div class="payment-method" data-method="Bank Transfer">
                            <i class="fas fa-university"></i>
                            <div>Bank Transfer</div>
                            <input type="radio" name="payment_method" value="Bank Transfer" style="display:none;">
                        </div>
                    </div>
                    
                    <!-- Credit Card Details (shown/hidden based on selected payment method) -->
                    <div id="credit-card-form">
                        <div class="form-group">
                            <label for="card_number">Card Number*</label>
                            <input type="text" id="card_number" class="form-control" placeholder="1234 5678 9012 3456">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="expiry_date">Expiry Date*</label>
                                <input type="text" id="expiry_date" class="form-control" placeholder="MM/YY">
                            </div>
                            <div class="form-group">
                                <label for="cvv">CVV*</label>
                                <input type="text" id="cvv" class="form-control" placeholder="123">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" name="place_order" class="btn-place-order">
                        Place Order - $<?php echo number_format($orderTotal, 2); ?>
                    </button>
                    
                    <div class="secure-checkout">
                        <i class="fas fa-lock"></i> Secure Checkout - Your information is protected
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Include footer -->
        <?php include_once '../templates/footer.php'; ?>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Payment method selection
        const paymentMethods = document.querySelectorAll('.payment-method');
        const creditCardForm = document.getElementById('credit-card-form');
        
        paymentMethods.forEach(method => {
            method.addEventListener('click', function() {
                // Remove active class from all methods
                paymentMethods.forEach(m => m.classList.remove('active'));
                
                // Add active class to clicked method
                this.classList.add('active');
                
                // Update hidden radio input
                const methodValue = this.getAttribute('data-method');
                document.querySelector(`input[value="${methodValue}"]`).checked = true;
                
                // Show/hide credit card form
                if (methodValue === 'Credit Card') {
                    creditCardForm.style.display = 'block';
                } else {
                    creditCardForm.style.display = 'none';
                }
            });
        });
        
        // Simple card validation (demo only - would need more robust validation in production)
        const cardInput = document.getElementById('card_number');
        if (cardInput) {
            cardInput.addEventListener('input', function(e) {
                // Format card number with spaces every 4 digits
                let value = this.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                let formattedValue = '';
                
                for(let i = 0; i < value.length; i++) {
                    if(i > 0 && i % 4 === 0) {
                        formattedValue += ' ';
                    }
                    formattedValue += value[i];
                }
                
                this.value = formattedValue;
                
                // Limit to 19 characters (16 digits + 3 spaces)
                if (this.value.length > 19) {
                    this.value = this.value.slice(0, 19);
                }
            });
        }
        
        // Expiry date formatting
        const expiryInput = document.getElementById('expiry_date');
        if (expiryInput) {
            expiryInput.addEventListener('input', function(e) {
                let value = this.value.replace(/\D/g, '');
                
                if (value.length > 2) {
                    this.value = value.slice(0, 2) + '/' + value.slice(2, 4);
                } else {
                    this.value = value;
                }
                
                // Limit to 5 characters (MM/YY)
                if (this.value.length > 5) {
                    this.value = this.value.slice(0, 5);
                }
            });
        }
        
        // CVV validation - numbers only, max 4 digits
        const cvvInput = document.getElementById('cvv');
        if (cvvInput) {
            cvvInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/\D/g, '');
                
                if (this.value.length > 4) {
                    this.value = this.value.slice(0, 4);
                }
            });
        }
    });
    </script>
</body>
</html>