<?php
// Start session and connect to database
session_start();
require_once '../connect.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$wishlistId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($wishlistId > 0) {
    // Verify this wishlist item belongs to the user
    $checkStmt = $conn->prepare("SELECT * FROM Wishlist WHERE wishlist_id = ? AND user_id = ?");
    $checkStmt->bind_param("ii", $wishlistId, $userId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $checkStmt->close();
    
    if ($result->num_rows > 0) {
        // Delete the wishlist item
        $deleteStmt = $conn->prepare("DELETE FROM Wishlist WHERE wishlist_id = ?");
        $deleteStmt->bind_param("i", $wishlistId);
        $deleteStmt->execute();
        $deleteStmt->close();
    }
}

// Redirect back to profile page
header("Location: profile.php#wishlist");
exit;
?>