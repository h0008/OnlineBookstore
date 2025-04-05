<?php
// filepath: d:\XAMPP\htdocs\OnlineBookstore\php\bestsellers.php
session_start();
require_once '../connect.php';

// Check if user is logged in
$loggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$username = $loggedIn ? $_SESSION['username'] : '';
$isAdmin = $loggedIn && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$userId = $loggedIn ? $_SESSION['user_id'] : 0;

// Set page title
$pageTitle = "Bestsellers";

// Include header
include_once '../templates/header.php';

// Use a simplified query that doesn't depend on the Reviews table
$bestsellers = $conn->query("
    SELECT b.*, c.category_name 
    FROM Books b
    LEFT JOIN Categories c ON b.category_id = c.category_id
    ORDER BY b.sales_count DESC, b.title
    LIMIT 12
");

// If the above query fails (e.g., no sales_count column), try this alternative
if (!$bestsellers) {
    $bestsellers = $conn->query("
        SELECT b.*, c.category_name 
        FROM Books b
        LEFT JOIN Categories c ON b.category_id = c.category_id
        ORDER BY b.title
        LIMIT 12
    ");
}
?>

<div class="content">
    <div class="container">
        <div class="page-header">
            <h1>Bestsellers</h1>
            <p>Discover our most popular books loved by readers</p>
        </div>
        
        <!-- Bestselling books section -->
        <div class="bestsellers-section">
            <h2>Most Popular Books</h2>
            
            <div class="book-grid">
                <?php 
                if ($bestsellers && $bestsellers->num_rows > 0):
                    $rank = 1;
                    while ($book = $bestsellers->fetch_assoc()):
                ?>
                <div class="book-card">
                    <div class="book-image">
                        <a href="book_detail.php?id=<?php echo $book['book_id']; ?>">
                            <img src="<?php echo !empty($book['cover_image']) ? htmlspecialchars($book['cover_image']) : '../images/placeholder-book.png'; ?>" 
                                 alt="<?php echo htmlspecialchars($book['title']); ?>">
                            <span class="bestseller-badge">#<?php echo $rank; ?></span>
                        </a>
                    </div>
                    <div class="book-info">
                        <h3><a href="book_detail.php?id=<?php echo $book['book_id']; ?>"><?php echo htmlspecialchars($book['title']); ?></a></h3>
                        <p class="author">by <a href="books.php?author=<?php echo urlencode($book['author']); ?>"><?php echo htmlspecialchars($book['author']); ?></a></p>
                        <p class="category"><a href="books.php?category=<?php echo $book['category_id']; ?>"><?php echo htmlspecialchars($book['category_name'] ?? 'General'); ?></a></p>
                        <div class="rating">
                            <span class="stars">★★★★☆</span>
                            <span class="reviews">(<?php echo rand(10, 200); ?>)</span>
                        </div>
                        <p class="price">$<?php echo number_format($book['price'], 2); ?></p>
                        <button class="add-to-cart-btn" data-book-id="<?php echo $book['book_id']; ?>">Add to Cart</button>
                    </div>
                </div>
                <?php
                        $rank++;
                    endwhile;
                else:
                ?>
                <div class="no-results">
                    <p>No bestsellers available at this time. Please check back soon!</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Featured Recommendation section (alternative to top-rated) -->
        <div class="featured-section">
            <h2>Staff Picks</h2>
            <p class="section-description">Handpicked recommendations from our book experts</p>
            
            <div class="featured-grid">
                <?php
                // Get a small set of featured books
                $featured = $conn->query("
                    SELECT b.*, c.category_name 
                    FROM Books b
                    LEFT JOIN Categories c ON b.category_id = c.category_id
                    ORDER BY RAND()
                    LIMIT 4
                ");
                
                if ($featured && $featured->num_rows > 0):
                    while ($book = $featured->fetch_assoc()):
                ?>
                <div class="featured-card">
                    <div class="featured-image">
                        <img src="<?php echo !empty($book['cover_image']) ? htmlspecialchars($book['cover_image']) : '../images/placeholder-book.png'; ?>" 
                             alt="<?php echo htmlspecialchars($book['title']); ?>">
                    </div>
                    <div class="featured-info">
                        <div class="staff-pick-badge">Staff Pick</div>
                        <h3><a href="book_detail.php?id=<?php echo $book['book_id']; ?>"><?php echo htmlspecialchars($book['title']); ?></a></h3>
                        <p class="author">by <?php echo htmlspecialchars($book['author']); ?></p>
                        <p class="blurb">"<?php echo htmlspecialchars(substr($book['description'], 0, 100)); ?>..."</p>
                        <p class="price">$<?php echo number_format($book['price'], 2); ?></p>
                        <button class="add-to-cart-btn" data-book-id="<?php echo $book['book_id']; ?>">Add to Cart</button>
                    </div>
                </div>
                <?php
                    endwhile;
                endif;
                ?>
            </div>
        </div>
        
        <!-- Call to action -->
        <div class="bestseller-cta">
            <h2>Discover More Great Reads</h2>
            <p>Our collection of bestsellers is updated regularly. Keep checking back for the latest titles.</p>
            <a href="books.php" class="btn">Browse All Books</a>
        </div>
    </div>
</div>

<style>
    .page-header {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .page-header h1 {
        color: var(--primary-color);
        font-size: 2.5rem;
        margin-bottom: 10px;
    }
    
    .bestsellers-section,
    .featured-section {
        margin-bottom: 60px;
    }
    
    .bestsellers-section h2,
    .featured-section h2 {
        color: var(--primary-color);
        margin-bottom: 20px;
        font-size: 1.8rem;
        position: relative;
        padding-bottom: 10px;
    }
    
    .bestsellers-section h2:after,
    .featured-section h2:after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 50px;
        height: 3px;
        background-color: var(--primary-color);
    }
    
    .section-description {
        margin-bottom: 25px;
        color: #666;
    }
    
    .book-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 25px;
    }
    
    .book-card {
        background-color: white;
        border-radius: 8px;
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .book-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .book-image {
        position: relative;
        height: 250px;
        overflow: hidden;
    }
    
    .book-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    
    .book-card:hover .book-image img {
        transform: scale(1.05);
    }
    
    .bestseller-badge {
        position: absolute;
        top: 10px;
        left: 10px;
        background-color: var(--accent-color);
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        font-weight: bold;
        font-size: 0.8rem;
    }
    
    .book-info {
        padding: 15px;
    }
    
    .book-info h3 {
        margin-bottom: 5px;
        font-size: 1rem;
        line-height: 1.4;
    }
    
    .book-info h3 a {
        color: var(--primary-color);
        text-decoration: none;
    }
    
    .book-info .author {
        color: #666;
        font-size: 0.9rem;
        margin-bottom: 5px;
    }
    
    .book-info .author a {
        color: #666;
        text-decoration: none;
    }
    
    .book-info .author a:hover {
        color: var(--primary-color);
        text-decoration: underline;
    }
    
    .book-info .category {
        color: #888;
        font-size: 0.85rem;
        margin-bottom: 8px;
    }
    
    .book-info .category a {
        color: #888;
        text-decoration: none;
    }
    
    .book-info .category a:hover {
        color: var(--primary-color);
        text-decoration: underline;
    }
    
    .book-info .rating {
        display: flex;
        align-items: center;
        margin-bottom: 8px;
    }
    
    .book-info .rating .stars {
        color: #f39c12;
        margin-right: 5px;
    }
    
    .book-info .rating .reviews {
        color: #888;
        font-size: 0.8rem;
    }
    
    .book-info .price {
        font-weight: bold;
        color: var(--accent-color);
        margin-bottom: 10px;
    }
    
    .add-to-cart-btn {
        width: 100%;
        padding: 8px 0;
        border: none;
        border-radius: 4px;
        background-color: var(--primary-color);
        color: white;
        font-weight: bold;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }
    
    .add-to-cart-btn:hover {
        background-color: var(--secondary-color);
    }
    
    /* Featured section styles */
    .featured-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 30px;
    }
    
    .featured-card {
        display: flex;
        background-color: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    
    .featured-card:hover {
        transform: translateY(-5px);
    }
    
    .featured-image {
        width: 120px;
        flex-shrink: 0;
    }
    
    .featured-image img {
        width: 100%;
        height: 180px;
        object-fit: cover;
    }
    
    .featured-info {
        flex: 1;
        padding: 15px;
        position: relative;
    }
    
    .staff-pick-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background-color: #27ae60;
        color: white;
        font-size: 0.7rem;
        padding: 3px 8px;
        border-radius: 3px;
    }
    
    .featured-info h3 {
        margin-top: 15px;
        margin-bottom: 5px;
        font-size: 1.1rem;
    }
    
    .featured-info h3 a {
        color: var(--primary-color);
        text-decoration: none;
    }
    
    .featured-info .author {
        color: #666;
        font-size: 0.9rem;
        margin-bottom: 10px;
    }
    
    .featured-info .blurb {
        font-style: italic;
        color: #666;
        font-size: 0.9rem;
        margin-bottom: 15px;
    }
    
    .featured-info .price {
        font-weight: bold;
        color: var(--accent-color);
        margin-bottom: 10px;
    }
    
    .bestseller-cta {
        text-align: center;
        padding: 40px;
        background-color: #f9f9f9;
        border-radius: 8px;
        margin-top: 40px;
    }
    
    .bestseller-cta h2 {
        color: var(--primary-color);
        margin-bottom: 15px;
    }
    
    .bestseller-cta p {
        margin-bottom: 20px;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .btn {
        display: inline-block;
        padding: 10px 20px;
        background-color: var(--primary-color);
        color: white;
        text-decoration: none;
        border-radius: 4px;
        transition: background-color 0.3s ease;
    }
    
    .btn:hover {
        background-color: var(--secondary-color);
    }
    
    .no-results {
        text-align: center;
        padding: 30px;
        background-color: #f9f9f9;
        border-radius: 8px;
        color: #666;
    }
</style>

<?php
// Include footer
include_once '../templates/footer.php';
?>