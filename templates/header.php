<?php
// If session hasn't been started already, start it
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection if not already included
if (!isset($conn)) {
    require_once dirname(__FILE__) . '/../connect.php';
}

// Define base path for URLs if not already defined
if (!isset($basePath)) {
    // Get the relative path from document root to the PHP directory
    $basePath = '/OnlineBookstore/php/';
}

// Check if user is logged in
$loggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$username = $loggedIn ? $_SESSION['username'] : '';
$isAdmin = $loggedIn && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$userId = $loggedIn ? $_SESSION['user_id'] : 0;

// Get cart count if user is logged in
$cartCount = 0;
if ($loggedIn) {
    $countCart = $conn->prepare("SELECT SUM(quantity) as total FROM Cart WHERE user_id = ?");
    $countCart->bind_param("i", $userId);
    $countCart->execute();
    $countResult = $countCart->get_result();
    $count = $countResult->fetch_assoc();
    $cartCount = $count['total'] ?? 0;
    $countCart->close();
}

// Default page title if not set
$pageTitle = $pageTitle ?? 'Online Bookstore';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Haitchal Books</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Stick+No+Bills">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/home_styles.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css">
    <script src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>
    <?php if (isset($additionalCss)): ?>
    <link rel="stylesheet" href="<?php echo $additionalCss; ?>">
    <?php endif; ?>
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
                            <a href="../admin/dashboard.php">Admin Dashboard</a>
                            <span class="separator">|</span>
                        <?php else: ?>
                            <a href="profile.php">My Profile</a>
                            <span class="separator">|</span>
                        <?php endif; ?>
                        <a href="logout.php">Logout</a>
                        <span class="separator">|</span>
                        <a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart (<span id="cart-count"><?php echo $cartCount; ?></span>)</a>
                    <?php else: ?>
                        <a href="login.php">Sign In</a>
                        <span class="separator">|</span>
                        <a href="register.php">Create Account</a>
                        <span class="separator">|</span>
                        <a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart (<span id="cart-count">0</span>)</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Main header with logo and search -->
        <div class="header">
            <div class="logo">
            <h1><a href="<?php echo $basePath ?? ''; ?>homepage.php" class="logo-link">
                    <img id="logo" src="../images/Haitchal_Books.png" alt="Bookstore Logo" width="80" height="80"></h1>
                    <h1>Haitchal Books</h1>
                </a>
            </div>
            <div class="form-search">
                <form action="/OnlineBookstore/php/books.php" method="get" class="form-search">
                    <input type="text" name="search" placeholder="Search by title, author, or ISBN..." 
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
        </div>

        <!-- Main navigation menu -->
        <div class="menu">
            <ul>
                <li><a href="/OnlineBookstore/php/homepage.php" <?php echo basename($_SERVER['PHP_SELF']) == 'homepage.php' ? 'class="active"' : ''; ?>>Home</a></li>
                <li><a href="/OnlineBookstore/php/books.php" <?php echo basename($_SERVER['PHP_SELF']) == 'books.php' ? 'class="active"' : ''; ?>>Books</a></li>
                <li><a href="/OnlineBookstore/php/new_releases.php" <?php echo basename($_SERVER['PHP_SELF']) == 'new_releases.php' ? 'class="active"' : ''; ?>>New Releases</a></li>
                <li><a href="/OnlineBookstore/php/bestsellers.php" <?php echo basename($_SERVER['PHP_SELF']) == 'bestsellers.php' ? 'class="active"' : ''; ?>>Bestsellers</a></li>
                <li><a href="/OnlineBookstore/php/about.php" <?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'class="active"' : ''; ?>>About</a></li>
                <li><a href="/OnlineBookstore/php/contact.php" <?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'class="active"' : ''; ?>>Contact</a></li>
            </ul>
        </div>