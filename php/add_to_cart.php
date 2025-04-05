<?php
session_start();
require_once '../connect.php';

// Default response
$response = [
    'success' => false,
    'message' => 'An error occurred',
    'cart_count' => 0
];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Please log in to add items to your cart';
    
    // If it's an AJAX request
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode($response);
        exit;
    } else {
        // Redirect to login if it's a direct access
        $_SESSION['redirect_after_login'] = 'cart.php';
        header('Location: login.php');
        exit;
    }
}

// Process book addition to cart
if (isset($_GET['book_id'])) {
    $bookId = (int)$_GET['book_id'];
    $userId = $_SESSION['user_id'];
    $quantity = 1; // Default quantity
    
    // Check if the book exists
    $checkBook = $conn->prepare("SELECT book_id, price, title FROM Books WHERE book_id = ?");
    $checkBook->bind_param("i", $bookId);
    $checkBook->execute();
    $bookResult = $checkBook->get_result();
    
    if ($bookResult->num_rows === 0) {
        $response['message'] = 'Book not found';
    } else {
        $book = $bookResult->fetch_assoc();
        
        // Check if book is already in cart
        $checkCart = $conn->prepare("SELECT cart_id, quantity FROM Cart WHERE user_id = ? AND book_id = ?");
        $checkCart->bind_param("ii", $userId, $bookId);
        $checkCart->execute();
        $cartResult = $checkCart->get_result();
        
        if ($cartResult->num_rows > 0) {
            // Update quantity if already in cart
            $cartItem = $cartResult->fetch_assoc();
            $newQuantity = $cartItem['quantity'] + 1;
            
            $updateCart = $conn->prepare("UPDATE Cart SET quantity = ? WHERE cart_id = ?");
            $updateCart->bind_param("ii", $newQuantity, $cartItem['cart_id']);
            
            if ($updateCart->execute()) {
                $response['success'] = true;
                $response['message'] = 'Quantity updated in cart';
            } else {
                $response['message'] = 'Failed to update cart';
            }
            $updateCart->close();
        } else {
            // Add new item to cart
            $addToCart = $conn->prepare("INSERT INTO Cart (user_id, book_id, quantity) VALUES (?, ?, ?)");
            $addToCart->bind_param("iii", $userId, $bookId, $quantity);
            
            if ($addToCart->execute()) {
                $response['success'] = true;
                $response['message'] = 'Added to cart';
            } else {
                $response['message'] = 'Failed to add to cart';
            }
            $addToCart->close();
        }
        
        // Get updated cart count
        $countCart = $conn->prepare("SELECT SUM(quantity) as total FROM Cart WHERE user_id = ?");
        $countCart->bind_param("i", $userId);
        $countCart->execute();
        $countResult = $countCart->get_result();
        $count = $countResult->fetch_assoc();
        $response['cart_count'] = $count['total'] ?? 0;
        $countCart->close();
    }
    
    $checkBook->close();
}

// If it's an AJAX request
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    echo json_encode($response);
    exit;
} else {
    // Redirect to cart page if it's a direct access
    header('Location: cart.php');
    exit;
}
?>