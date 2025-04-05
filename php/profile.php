<?php
// Start session to access user data
session_start();
require_once '../connect.php';

// Redirect to login if not logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Get user information
$loggedIn = true;
$username = $_SESSION['username'];
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$userId = $_SESSION['user_id'];

// Get cart count if user is logged in
$cartCount = 0;
$countCart = $conn->prepare("SELECT SUM(quantity) as total FROM Cart WHERE user_id = ?");
$countCart->bind_param("i", $userId);
$countCart->execute();
$countResult = $countCart->get_result();
$count = $countResult->fetch_assoc();
$cartCount = $count['total'] ?? 0;
$countCart->close();

// Handle profile update
$updateSuccess = false;
$updateError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Get form data
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $zipCode = trim($_POST['zip_code'] ?? '');
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $updateError = 'Please enter a valid email address.';
    } else {
        // Check if email exists for another user
        $checkEmail = $conn->prepare("SELECT user_id FROM Users WHERE email = ? AND user_id != ?");
        $checkEmail->bind_param("si", $email, $userId);
        $checkEmail->execute();
        $emailResult = $checkEmail->get_result();
        
        if ($emailResult->num_rows > 0) {
            $updateError = 'Email address is already in use by another account.';
        } else {
            // Update user profile
            $updateProfile = $conn->prepare("UPDATE Users SET 
                full_name = ?, 
                email = ?, 
                phone = ?, 
                address = ?, 
                city = ?, 
                state = ?, 
                zip_code = ? 
                WHERE user_id = ?");
            
            $updateProfile->bind_param("sssssssi", 
                $fullName, 
                $email, 
                $phone, 
                $address, 
                $city, 
                $state, 
                $zipCode, 
                $userId
            );
            
            if ($updateProfile->execute()) {
                $updateSuccess = true;
                // Update session email if changed
                $_SESSION['email'] = $email;
            } else {
                $updateError = 'Error updating profile. Please try again.';
            }
            
            $updateProfile->close();
        }
        
        $checkEmail->close();
    }
}

// Password change handling
$passwordSuccess = false;
$passwordError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate password inputs
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $passwordError = 'All password fields are required.';
    } elseif ($newPassword !== $confirmPassword) {
        $passwordError = 'New password and confirmation do not match.';
    } elseif (strlen($newPassword) < 8) {
        $passwordError = 'Password must be at least 8 characters long.';
    } else {
        // Verify current password
        $checkPassword = $conn->prepare("SELECT password FROM Users WHERE user_id = ?");
        $checkPassword->bind_param("i", $userId);
        $checkPassword->execute();
        $passwordResult = $checkPassword->get_result();
        $user = $passwordResult->fetch_assoc();
        $checkPassword->close();
        
        if (password_verify($currentPassword, $user['password'])) {
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updatePassword = $conn->prepare("UPDATE Users SET password = ? WHERE user_id = ?");
            $updatePassword->bind_param("si", $hashedPassword, $userId);
            
            if ($updatePassword->execute()) {
                $passwordSuccess = true;
            } else {
                $passwordError = 'Error updating password. Please try again.';
            }
            
            $updatePassword->close();
        } else {
            $passwordError = 'Current password is incorrect.';
        }
    }
}

// Get user details
$userQuery = $conn->prepare("SELECT * FROM Users WHERE user_id = ?");
$userQuery->bind_param("i", $userId);
$userQuery->execute();
$userResult = $userQuery->get_result();
$user = $userResult->fetch_assoc();
$userQuery->close();

// Get order history
$ordersQuery = $conn->prepare("SELECT o.*, 
    COUNT(oi.order_item_id) as item_count,
    SUM(oi.quantity * oi.price) as total_amount
    FROM Orders o
    JOIN Order_Items oi ON o.order_id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.order_id
    ORDER BY o.order_date DESC
    LIMIT 10");
$ordersQuery->bind_param("i", $userId);
$ordersQuery->execute();
$ordersResult = $ordersQuery->get_result();
$ordersQuery->close();

// Set page title variable before including header
$pageTitle = "My Profile";
$additionalCss = "../css/pages/profile.css";

// Include header template
require_once '../templates/header.php';
?>

<!-- Page title section -->
<div class="page-title">
    <div class="container">
        <h1>My Profile</h1>
    </div>
</div>

<!-- Main content area -->
<div class="content">
    <div class="container">
        <?php if ($updateSuccess): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <p>Your profile has been updated successfully.</p>
            </div>
        <?php endif; ?>
        
        <?php if ($updateError): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <p><?php echo htmlspecialchars($updateError); ?></p>
            </div>
        <?php endif; ?>
        
        <?php if ($passwordSuccess): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <p>Your password has been changed successfully.</p>
            </div>
        <?php endif; ?>
        
        <?php if ($passwordError): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <p><?php echo htmlspecialchars($passwordError); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="grid">
            <!-- Profile sidebar -->
            <div class="grid-col grid-col-12 grid-col-md-4 grid-col-lg-3">
                <div class="profile-sidebar">
                    <div class="profile-avatar">
                        <img src="../images/user-avatar.png" alt="User Avatar">
                        <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
                        <p>Member since: <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                    </div>
                    
                    <ul class="profile-menu">
                        <li><a href="#profile-info" class="active" data-toggle="tab"><i class="fas fa-user"></i> Profile Information</a></li>
                        <li><a href="#order-history" data-toggle="tab"><i class="fas fa-shopping-bag"></i> Order History</a></li>
                        <li><a href="#change-password" data-toggle="tab"><i class="fas fa-lock"></i> Change Password</a></li>
                        <li><a href="#wishlist" data-toggle="tab"><i class="fas fa-heart"></i> Wishlist</a></li>
                        <?php if ($isAdmin): ?>
                            <li><a href="admin/dashboard.php"><i class="fas fa-cog"></i> Admin Dashboard</a></li>
                        <?php endif; ?>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
            
            <!-- Profile content -->
            <div class="grid-col grid-col-12 grid-col-md-8 grid-col-lg-9">
                <div class="tab-content">
                    <!-- Profile Information Tab -->
                    <div id="profile-info" class="tab-pane active">
                        <div class="card">
                            <div class="card-header">
                                <h2>Profile Information</h2>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                    <div class="grid">
                                        <div class="grid-col grid-col-12">
                                            <div class="form-group">
                                                <label for="full_name" class="form-label">Full Name</label>
                                                <input type="text" id="full_name" name="full_name" class="form-control" 
                                                       value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="grid-col grid-col-12 grid-col-md-6">
                                            <div class="form-group">
                                                <label for="email" class="form-label">Email Address</label>
                                                <input type="email" id="email" name="email" class="form-control" 
                                                       value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="grid-col grid-col-12 grid-col-md-6">
                                            <div class="form-group">
                                                <label for="phone" class="form-label">Phone Number</label>
                                                <input type="tel" id="phone" name="phone" class="form-control" 
                                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="grid-col grid-col-12">
                                            <div class="form-group">
                                                <label for="address" class="form-label">Address</label>
                                                <input type="text" id="address" name="address" class="form-control" 
                                                       value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="grid-col grid-col-12 grid-col-md-6">
                                            <div class="form-group">
                                                <label for="city" class="form-label">City</label>
                                                <input type="text" id="city" name="city" class="form-control" 
                                                       value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="grid-col grid-col-12 grid-col-md-3">
                                            <div class="form-group">
                                                <label for="state" class="form-label">State</label>
                                                <input type="text" id="state" name="state" class="form-control" 
                                                       value="<?php echo htmlspecialchars($user['state'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="grid-col grid-col-12 grid-col-md-3">
                                            <div class="form-group">
                                                <label for="zip_code" class="form-label">ZIP Code</label>
                                                <input type="text" id="zip_code" name="zip_code" class="form-control" 
                                                       value="<?php echo htmlspecialchars($user['zip_code'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="grid-col grid-col-12">
                                            <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order History Tab -->
                    <div id="order-history" class="tab-pane">
                        <div class="card">
                            <div class="card-header">
                                <h2>Order History</h2>
                            </div>
                            <div class="card-body">
                                <?php if ($ordersResult->num_rows > 0): ?>
                                    <div class="order-list">
                                        <?php while ($order = $ordersResult->fetch_assoc()): ?>
                                            <div class="order-item">
                                                <div class="order-header">
                                                    <div class="order-number">
                                                        <h3>Order #<?php echo $order['order_id']; ?></h3>
                                                        <span class="order-date"><?php echo date('F j, Y', strtotime($order['order_date'])); ?></span>
                                                    </div>
                                                    <div class="order-status <?php echo strtolower($order['status']); ?>">
                                                        <?php echo $order['status']; ?>
                                                    </div>
                                                </div>
                                                
                                                <div class="order-details">
                                                    <div class="detail-item">
                                                        <span class="label">Items:</span>
                                                        <span class="value"><?php echo $order['item_count']; ?></span>
                                                    </div>
                                                    <div class="detail-item">
                                                        <span class="label">Total:</span>
                                                        <span class="value price">$<?php echo number_format($order['total_amount'], 2); ?></span>
                                                    </div>
                                                    <div class="detail-item">
                                                        <span class="label">Payment:</span>
                                                        <span class="value"><?php echo $order['payment_method']; ?></span>
                                                    </div>
                                                </div>
                                                
                                                <div class="order-actions">
                                                    <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                                                    
                                                    <?php if ($order['status'] === 'Delivered'): ?>
                                                        <a href="write_review.php?order=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-outline-secondary">Write Review</a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                    
                                    <a href="orders.php" class="view-all">View All Orders</a>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-shopping-bag fa-3x"></i>
                                        <h3>No Orders Yet</h3>
                                        <p>You haven't placed any orders yet. Start shopping to see your order history here.</p>
                                        <a href="books.php" class="btn btn-primary">Browse Books</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Change Password Tab -->
                    <div id="change-password" class="tab-pane">
                        <div class="card">
                            <div class="card-header">
                                <h2>Change Password</h2>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                    <div class="form-group">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" id="new_password" name="new_password" class="form-control" required>
                                        <div class="password-requirements">
                                            <p>Password must be at least 8 characters long and contain:</p>
                                            <ul>
                                                <li>At least one uppercase letter</li>
                                                <li>At least one lowercase letter</li>
                                                <li>At least one number</li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                                    </div>
                                    
                                    <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Wishlist Tab -->
                    <div id="wishlist" class="tab-pane">
                        <div class="card">
                            <div class="card-header">
                                <h2>My Wishlist</h2>
                            </div>
                            <div class="card-body">
                                <?php
                                // Get wishlist items
                                $wishlistQuery = $conn->prepare("SELECT w.*, b.title, b.author, b.price, b.cover_image 
                                                              FROM Wishlist w
                                                              JOIN Books b ON w.book_id = b.book_id
                                                              WHERE w.user_id = ?
                                                              ORDER BY w.added_date DESC");
                                $wishlistQuery->bind_param("i", $userId);
                                $wishlistQuery->execute();
                                $wishlistResult = $wishlistQuery->get_result();
                                $wishlistQuery->close();
                                
                                if ($wishlistResult->num_rows > 0):
                                ?>
                                    <div class="wishlist-items">
                                        <?php while ($item = $wishlistResult->fetch_assoc()): ?>
                                            <div class="wishlist-item">
                                                <div class="wishlist-item-image">
                                                    <img src="<?php echo htmlspecialchars($item['cover_image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                                </div>
                                                <div class="wishlist-item-info">
                                                    <h3><a href="book_detail.php?id=<?php echo $item['book_id']; ?>"><?php echo htmlspecialchars($item['title']); ?></a></h3>
                                                    <p class="author">by <?php echo htmlspecialchars($item['author']); ?></p>
                                                    <p class="price">$<?php echo number_format($item['price'], 2); ?></p>
                                                    <p class="added-date">Added on <?php echo date('F j, Y', strtotime($item['added_date'])); ?></p>
                                                </div>
                                                <div class="wishlist-item-actions">
                                                    <button class="btn btn-sm btn-primary add-to-cart-btn" data-book-id="<?php echo $item['book_id']; ?>">Add to Cart</button>
                                                    <a href="remove_wishlist.php?id=<?php echo $item['wishlist_id']; ?>" class="btn btn-sm btn-outline-danger">Remove</a>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-heart fa-3x"></i>
                                        <h3>Your Wishlist is Empty</h3>
                                        <p>Save items you're interested in by clicking the heart icon on product pages.</p>
                                        <a href="books.php" class="btn btn-primary">Browse Books</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Profile page CSS -->
<style>
/* Profile page specific styles */
.page-title {
    background-color: var(--secondary-light);
    color: white;
    padding: var(--space-lg) 0;
    margin-bottom: var(--space-xl);
}

.page-title h1 {
    margin: 0;
    color: white;
}

.profile-sidebar {
    background-color: white;
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-sm);
    margin-bottom: var(--space-lg);
    overflow: hidden;
}

.profile-avatar {
    background-color: var(--primary-light);
    padding: var(--space-lg);
    text-align: center;
}

.profile-avatar img {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    margin-bottom: var(--space-sm);
    border: 3px solid white;
}

.profile-avatar h3 {
    margin-bottom: var(--space-xs);
    color: white;
}

.profile-avatar p {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
    margin: 0;
}

.profile-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.profile-menu li a {
    display: block;
    padding: var(--space-md);
    color: var(--secondary-color);
    text-decoration: none;
    border-bottom: 1px solid var(--light);
    transition: background-color 0.2s;
}

.profile-menu li a:hover {
    background-color: var(--light);
}

.profile-menu li a.active {
    background-color: var(--primary-color);
    color: white;
}

.profile-menu li a i {
    margin-right: var(--space-sm);
    width: 20px;
    text-align: center;
}

.card {
    margin-bottom: var(--space-lg);
}

.card-header {
    background-color: var(--light);
    padding: var(--space-md) var(--space-lg);
    border-bottom: 1px solid #e5e5e5;
}

.card-header h2 {
    margin: 0;
    font-size: 1.25rem;
}

.tab-content {
    display: block;
}

.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
}

/* Order history styles */
.order-item {
    border: 1px solid #e5e5e5;
    border-radius: var(--radius-sm);
    margin-bottom: var(--space-md);
    overflow: hidden;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--space-sm) var(--space-md);
    background-color: #f9f9f9;
}

.order-number h3 {
    margin: 0;
    font-size: 1rem;
}

.order-date {
    color: var(--medium);
    font-size: 0.9rem;
}

.order-status {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: bold;
    text-transform: uppercase;
}

.order-status.processing {
    background-color: #fff8e1;
    color: #ff9800;
}

.order-status.shipped {
    background-color: #e3f2fd;
    color: #2196f3;
}

.order-status.delivered {
    background-color: #e8f5e9;
    color: #4caf50;
}

.order-status.cancelled {
    background-color: #ffebee;
    color: #f44336;
}

.order-details {
    display: flex;
    border-top: 1px solid #e5e5e5;
    border-bottom: 1px solid #e5e5e5;
}

.detail-item {
    flex: 1;
    padding: var(--space-sm) var(--space-md);
    text-align: center;
    border-right: 1px solid #e5e5e5;
}

.detail-item:last-child {
    border-right: none;
}

.detail-item .label {
    display: block;
    font-size: 0.8rem;
    color: var(--medium);
    margin-bottom: 2px;
}

.detail-item .value {
    font-weight: 600;
}

.detail-item .price {
    color: var(--accent-color);
}

.order-actions {
    padding: var(--space-sm) var(--space-md);
    display: flex;
    justify-content: flex-end;
    gap: var(--space-sm);
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: var(--space-xl) 0;
}

.empty-state i {
    color: #e0e0e0;
    margin-bottom: var(--space-md);
}

.empty-state h3 {
    margin-bottom: var(--space-sm);
}

.empty-state p {
    color: var(--medium);
    max-width: 400px;
    margin: 0 auto var(--space-lg) auto;
}

/* Wishlist styles */
.wishlist-item {
    display: flex;
    border-bottom: 1px solid #e5e5e5;
    padding: var(--space-md) 0;
}

.wishlist-item:last-child {
    border-bottom: none;
}

.wishlist-item-image {
    width: 80px;
    margin-right: var(--space-md);
}

.wishlist-item-image img {
    width: 100%;
    height: auto;
    border-radius: var(--radius-sm);
}

.wishlist-item-info {
    flex: 1;
}

.wishlist-item-info h3 {
    margin-top: 0;
    margin-bottom: var(--space-xs);
    font-size: 1rem;
}

.wishlist-item-info .author {
    color: var(--medium);
    margin-bottom: var(--space-xs);
    font-size: 0.9rem;
}

.wishlist-item-info .price {
    color: var(--accent-color);
    font-weight: 600;
    margin-bottom: var(--space-xs);
}

.wishlist-item-info .added-date {
    color: var(--medium);
    font-size: 0.8rem;
}

.wishlist-item-actions {
    display: flex;
    flex-direction: column;
    gap: var(--space-xs);
    align-items: flex-end;
    justify-content: center;
}

.password-requirements {
    margin-top: var(--space-xs);
    font-size: 0.85rem;
    color: var(--medium);
}

.password-requirements ul {
    padding-left: var(--space-md);
    margin-top: var(--space-xs);
}

.alert {
    padding: var(--space-md);
    border-radius: var(--radius-md);
    margin-bottom: var(--space-lg);
    display: flex;
    align-items: center;
}

.alert i {
    font-size: 1.5rem;
    margin-right: var(--space-md);
}

.alert-success {
    background-color: rgba(46, 204, 113, 0.1);
    border: 1px solid var(--success);
    color: var(--success);
}

.alert-danger {
    background-color: rgba(231, 76, 60, 0.1);
    border: 1px solid var(--danger);
    color: var(--danger);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .order-details {
        flex-wrap: wrap;
    }
    
    .detail-item {
        flex: 0 0 50%;
        border-bottom: 1px solid #e5e5e5;
    }
    
    .detail-item:nth-child(even) {
        border-right: none;
    }
    
    .detail-item:nth-last-child(-n+2) {
        border-bottom: none;
    }
    
    .wishlist-item {
        flex-wrap: wrap;
    }
    
    .wishlist-item-actions {
        flex-direction: row;
        width: 100%;
        margin-top: var(--space-sm);
        justify-content: flex-start;
    }
}
</style>

<!-- Add JavaScript for tab switching -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle tab switching
    const tabLinks = document.querySelectorAll('[data-toggle="tab"]');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all tabs
            tabLinks.forEach(item => item.classList.remove('active'));
            tabPanes.forEach(pane => pane.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Show corresponding tab pane
            const targetId = this.getAttribute('href').substring(1);
            document.getElementById(targetId).classList.add('active');
        });
    });
    
    // Handle add to cart functionality
    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        button.addEventListener('click', function() {
            const bookId = this.getAttribute('data-book-id');
            
            // Send AJAX request to add book to cart
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'book_id=' + bookId + '&quantity=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update cart count in the header
                    const cartCountElement = document.querySelector('.cart-count');
                    if (cartCountElement) {
                        cartCountElement.textContent = data.cartCount;
                    }
                    
                    // Show success message
                    alert('Book added to cart successfully!');
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    });
});
</script>

<?php
// Include footer template
require_once '../templates/footer.php';
?>