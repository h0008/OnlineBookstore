<?php
// filepath: d:\XAMPP\htdocs\OnlineBookstore\php\mobile_checkout.php
session_start();
require_once '../connect.php';

header('Content-Type: application/json');

if (!isset($_GET['token']) || empty($_GET['token'])) {
    echo json_encode(['success' => false, 'message' => 'Missing checkout token']);
    exit;
}

$token = $_GET['token'];

// Verify token and get checkout data
$checkQuery = $conn->prepare("SELECT * FROM Temp_Checkout WHERE token = ? AND expires_at > NOW() AND used = 0");
$checkQuery->bind_param("s", $token);
$checkQuery->execute();
$result = $checkQuery->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired checkout token']);
    exit;
}

$checkoutData = $result->fetch_assoc();
$userData = json_decode($checkoutData['data'], true);
$userId = $checkoutData['user_id'];

// Verify user is logged in and matches the token
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_id'] != $userId) {
    echo json_encode(['success' => false, 'message' => 'Authentication required', 'redirect' => 'login.php?redirect=mobile_checkout.php&token='.$token]);
    exit;
}

// Process the checkout
try {
    // Start transaction
    $conn->begin_transaction();
    
    // Create order record
    $shippingFee = 5.00; // Default shipping fee
    $totalAmount = $userData['total'] + $shippingFee;
    
    $insertOrder = $conn->prepare("INSERT INTO Orders (user_id, order_date, status, shipping_fee, total_amount, payment_method) VALUES (?, NOW(), 'Processing', ?, ?, 'Mobile QR Checkout')");
    $insertOrder->bind_param("idd", $userId, $shippingFee, $totalAmount);
    $insertOrder->execute();
    
    $orderId = $conn->insert_id;
    
    // Insert order items
    $insertItem = $conn->prepare("INSERT INTO Order_Items (order_id, book_id, quantity, price) VALUES (?, ?, ?, ?)");
    
    foreach ($userData['items'] as $item) {
        $insertItem->bind_param("iiid", $orderId, $item['book_id'], $item['quantity'], $item['price']);
        $insertItem->execute();
    }
    
    // Generate reference code
    $orderReference = 'HB-' . str_pad($orderId, 6, '0', STR_PAD_LEFT);
    
    // Update order with reference
    $updateRef = $conn->prepare("UPDATE Orders SET reference_code = ? WHERE order_id = ?");
    $updateRef->bind_param("si", $orderReference, $orderId);
    $updateRef->execute();
    
    // Create QR code
    $qrContent = json_encode([
        'order_id' => $orderId,
        'reference' => $orderReference,
        'amount' => $totalAmount,
        'date' => date('Y-m-d H:i:s')
    ]);
    
    $qrCodeUrl = 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . urlencode($qrContent);
    $qrPath = "../images/qrcodes/";
    
    if (!file_exists($qrPath)) {
        mkdir($qrPath, 0777, true);
    }
    
    $qrFilename = $qrPath . "order_" . $orderId . ".png";
    file_put_contents($qrFilename, file_get_contents($qrCodeUrl));
    
    $qrUrl = "/images/qrcodes/order_" . $orderId . ".png";
    $updateQr = $conn->prepare("UPDATE Orders SET qr_code = ? WHERE order_id = ?");
    $updateQr->bind_param("si", $qrUrl, $orderId);
    $updateQr->execute();
    
    // Mark checkout token as used
    $updateToken = $conn->prepare("UPDATE Temp_Checkout SET used = 1 WHERE token = ?");
    $updateToken->bind_param("s", $token);
    $updateToken->execute();
    
    // Clear user's cart
    $clearCart = $conn->prepare("DELETE FROM Cart WHERE user_id = ?");
    $clearCart->bind_param("i", $userId);
    $clearCart->execute();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'order_id' => $orderId,
        'message' => 'Order placed successfully',
        'redirect' => 'order_confirmation.php?id='.$orderId
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error processing order: ' . $e->getMessage()]);
}