<?php
// Start session to access user data
session_start();
require_once '../connect.php';

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

// Set page variables before including header
$pageTitle = "All Books";
$additionalCss = "../css/pages/books.css"; // Optional additional CSS for this page

// Include header template
require_once '../templates/header.php';

// Get filter parameters
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$author = isset($_GET['author']) ? $_GET['author'] : '';
$minPrice = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 1000;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'title_asc';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$booksPerPage = 12;

// Build SQL query
$sql = "SELECT b.*, c.category_name 
        FROM Books b 
        LEFT JOIN Categories c ON b.category_id = c.category_id
        WHERE 1=1";
$countSql = "SELECT COUNT(*) as total FROM Books b WHERE 1=1";
$params = [];
$types = "";

// Add filters
if ($category > 0) {
    $sql .= " AND b.category_id = ?";
    $countSql .= " AND b.category_id = ?";
    $params[] = $category;
    $types .= "i";
}

if (!empty($author)) {
    $sql .= " AND b.author LIKE ?";
    $countSql .= " AND b.author LIKE ?";
    $params[] = "%$author%";
    $types .= "s";
}

if ($minPrice > 0) {
    $sql .= " AND b.price >= ?";
    $countSql .= " AND b.price >= ?";
    $params[] = $minPrice;
    $types .= "d";
}

if ($maxPrice < 1000) {
    $sql .= " AND b.price <= ?";
    $countSql .= " AND b.price <= ?";
    $params[] = $maxPrice;
    $types .= "d";
}

if (!empty($search)) {
    $sql .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.description LIKE ?)";
    $countSql .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "sss";
}

// Add sorting
switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY b.price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY b.price DESC";
        break;
    case 'title_asc':
        $sql .= " ORDER BY b.title ASC";
        break;
    case 'title_desc':
        $sql .= " ORDER BY b.title DESC";
        break;
    case 'newest':
        $sql .= " ORDER BY b.publication_date DESC";
        break;
    default:
        $sql .= " ORDER BY b.title ASC";
}

// Count total rows for pagination
$countStmt = $conn->prepare($countSql);
if (!empty($types)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$countResult = $countStmt->get_result()->fetch_assoc();
$totalBooks = $countResult['total'];
$totalPages = ceil($totalBooks / $booksPerPage);

// Ensure page is within valid range
if ($page < 1) $page = 1;
if ($page > $totalPages && $totalPages > 0) $page = $totalPages;

// Add pagination
$offset = ($page - 1) * $booksPerPage;
$sql .= " LIMIT $offset, $booksPerPage";

// Execute query
$stmt = $conn->prepare($sql);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$books = $stmt->get_result();
?>

<div class="content">
    <?php require_once '../templates/sidebar.php'; ?>
    
    <div class="right">
        <!-- Breadcrumb navigation -->
        <div class="breadcrumbs">
            <a href="homepage.php">Home</a> &gt; 
            <span>All Books</span>
        </div>
        
        <!-- Page title with book count -->
        <?php
        // Add this right before your page header section
        if ($totalBooks instanceof mysqli_result) {
            // If somehow $totalBooks became a result object, fix it
            $tempResult = $totalBooks->fetch_assoc();
            $totalBooks = $tempResult['total'] ?? 0;
        }
        ?>
        <div class="page-header">
            <h1>All Books</h1>
            <span class="book-count"><?php echo $totalBooks; ?> books found</span>
        </div>
        
        <!-- Filter and sort controls -->
        <div class="filter-container">
            <form id="filter-form" action="books.php" method="get" class="filters">
                <?php if (!empty($search)): ?>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                <?php endif; ?>
                
                <div class="filter-group">
                    <label for="category">Category:</label>
                    <select name="category" id="category" class="filter-select">
                        <option value="0">All Categories</option>
                        <?php
                        $categories = $conn->query("SELECT category_id, category_name FROM Categories ORDER BY category_name");
                        while ($cat = $categories->fetch_assoc()) {
                            $selected = ($cat['category_id'] == $category) ? 'selected' : '';
                            echo '<option value="' . $cat['category_id'] . '" ' . $selected . '>' . 
                                htmlspecialchars($cat['category_name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="author">Author:</label>
                    <input type="text" name="author" id="author" placeholder="Author name" 
                           value="<?php echo htmlspecialchars($author); ?>" class="filter-input">
                </div>
                
                <div class="filter-group price-range">
                    <label>Price:</label>
                    <div class="price-inputs">
                        <input type="number" name="min_price" min="0" max="1000" step="0.01" placeholder="Min" 
                               value="<?php echo $minPrice > 0 ? $minPrice : ''; ?>" class="filter-input">
                        <span class="price-separator">to</span>
                        <input type="number" name="max_price" min="0" max="1000" step="0.01" placeholder="Max" 
                               value="<?php echo $maxPrice < 1000 ? $maxPrice : ''; ?>" class="filter-input">
                    </div>
                </div>
                
                <div class="filter-group">
                    <label for="sort">Sort By:</label>
                    <select name="sort" id="sort" class="filter-select">
                        <option value="title_asc" <?php echo $sort === 'title_asc' ? 'selected' : ''; ?>>Title (A-Z)</option>
                        <option value="title_desc" <?php echo $sort === 'title_desc' ? 'selected' : ''; ?>>Title (Z-A)</option>
                        <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price (Low to High)</option>
                        <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price (High to Low)</option>
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                    </select>
                </div>
                
                <div class="filter-buttons">
                    <button type="submit" class="filter-btn">Apply Filters</button>
                    <a href="books.php" class="reset-btn">Reset</a>
                </div>
            </form>
            
            <?php if ($category || !empty($author) || $minPrice > 0 || $maxPrice < 1000 || !empty($search)): ?>
                <div class="active-filters">
                    <span>Active Filters:</span>
                    <?php if ($category): ?>
                        <?php 
                        $categoryQuery = $conn->prepare("SELECT category_name FROM Categories WHERE category_id = ?");
                        $categoryQuery->bind_param("i", $category);
                        $categoryQuery->execute();
                        $categoryResult = $categoryQuery->get_result();
                        
                        // Store the category name in a variable
                        $categoryName = "Unknown Category"; // Default value
                        if ($categoryResult && $categoryResult->num_rows > 0) {
                            $categoryData = $categoryResult->fetch_assoc();
                            $categoryName = $categoryData['category_name'];
                        }
                        $categoryQuery->close();
                        ?>
                        <span class="filter-tag">
                            Category: <?php echo htmlspecialchars($categoryName); ?>
                            <a href="<?php echo removeQueryParam('category'); ?>" class="remove-filter">&times;</a>
                        </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($author)): ?>
                        <span class="filter-tag">
                            Author: <?php echo htmlspecialchars($author); ?>
                            <a href="<?php echo removeQueryParam('author'); ?>" class="remove-filter">&times;</a>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($minPrice > 0 || $maxPrice < 1000): ?>
                        <span class="filter-tag">
                            Price: $<?php echo $minPrice; ?> - $<?php echo $maxPrice; ?>
                            <a href="<?php echo removeQueryParam(['min_price', 'max_price']); ?>" class="remove-filter">&times;</a>
                        </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($search)): ?>
                        <span class="filter-tag">
                            Search: "<?php echo htmlspecialchars($search); ?>"
                            <a href="<?php echo removeQueryParam('search'); ?>" class="remove-filter">&times;</a>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($books->num_rows > 0): ?>
            <!-- Book grid -->
            <div class="book-grid">
                <?php while ($book = $books->fetch_assoc()): ?>
                    <div class="book-card">
                        <div class="book-image">
                            <a href="book_detail.php?id=<?php echo $book['book_id']; ?>">
                                <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($book['title']); ?>">
                                <?php if (isset($book['is_bestseller']) && $book['is_bestseller']): ?>
                                    <span class="badge bestseller">BESTSELLER</span>
                                <?php elseif (isset($book['is_new_release']) && $book['is_new_release']): ?>
                                    <span class="badge new">NEW</span>
                                <?php endif; ?>
                            </a>
                        </div>
                        <h3><a href="book_detail.php?id=<?php echo $book['book_id']; ?>">
                            <?php echo htmlspecialchars($book['title']); ?>
                        </a></h3>
                        <p class="author">by <a href="books.php?author=<?php echo urlencode($book['author']); ?>">
                            <?php echo htmlspecialchars($book['author']); ?>
                        </a></p>
                        <p class="category">
                            <a href="books.php?category=<?php echo $book['category_id']; ?>">
                                <?php echo htmlspecialchars($book['category_name'] ?? 'Uncategorized'); ?>
                            </a>
                        </p>
                        <p class="price">$<?php echo number_format($book['price'], 2); ?></p>
                        <div class="rating">
                            <span class="stars">★★★★☆</span>
                            <span class="reviews">(243)</span>
                        </div>
                        <button class="add-to-cart-btn" data-book-id="<?php echo $book['book_id']; ?>">Add to Cart</button>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="<?php echo buildPaginationUrl($page - 1); ?>" class="page-link">&laquo; Previous</a>
                    <?php endif; ?>
                    
                    <?php
                    // Display page numbers with ellipsis for large page counts
                    $startPage = max(1, $page - 2);
                    $endPage = min($startPage + 4, $totalPages);
                    
                    if ($startPage > 1) {
                        echo '<a href="' . buildPaginationUrl(1) . '" class="page-link">1</a>';
                        if ($startPage > 2) {
                            echo '<span class="ellipsis">...</span>';
                        }
                    }
                    
                    for ($i = $startPage; $i <= $endPage; $i++) {
                        $activeClass = ($i === $page) ? 'active' : '';
                        echo '<a href="' . buildPaginationUrl($i) . '" class="page-link ' . $activeClass . '">' . $i . '</a>';
                    }
                    
                    if ($endPage < $totalPages) {
                        if ($endPage < $totalPages - 1) {
                            echo '<span class="ellipsis">...</span>';
                        }
                        echo '<a href="' . buildPaginationUrl($totalPages) . '" class="page-link">' . $totalPages . '</a>';
                    }
                    ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="<?php echo buildPaginationUrl($page + 1); ?>" class="page-link">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <!-- No results message -->
            <div class="no-results">
                <i class="fas fa-book-open fa-3x"></i>
                <h2>No books found</h2>
                <p>We couldn't find any books matching your criteria. Try adjusting your filters or search terms.</p>
                <a href="books.php" class="btn">View All Books</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Helper function to build pagination URL
function buildPaginationUrl($pageNum) {
    $params = $_GET;
    $params['page'] = $pageNum;
    return 'books.php?' . http_build_query($params);
}

// Helper function to remove query parameters
function removeQueryParam($param) {
    $params = $_GET;
    if (is_array($param)) {
        foreach ($param as $p) {
            unset($params[$p]);
        }
    } else {
        unset($params[$param]);
    }
    return 'books.php?' . http_build_query($params);
}

// Include footer template
require_once '../templates/footer.php';
?>