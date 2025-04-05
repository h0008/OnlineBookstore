<?php
// filepath: d:\XAMPP\htdocs\OnlineBookstore\php\new_releases.php
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

// Set page title variable before including header
$pageTitle = "New Releases";

// Include header template
require_once '../templates/header.php';

// Get new releases (books published in the last 3 months)
$threeMonthsAgo = date('Y-m-d', strtotime('-3 months'));
$newReleases = $conn->prepare("
    SELECT b.*, c.category_name 
    FROM Books b 
    LEFT JOIN Categories c ON b.category_id = c.category_id 
    WHERE b.publication_date >= ? 
    ORDER BY b.publication_date DESC
    LIMIT 12
");
$newReleases->bind_param("s", $threeMonthsAgo);
$newReleases->execute();
$newReleasesResult = $newReleases->get_result();

// Get upcoming books (books with future publication dates)
$today = date('Y-m-d');
$upcomingBooks = $conn->prepare("
    SELECT b.*, c.category_name 
    FROM Books b 
    LEFT JOIN Categories c ON b.category_id = c.category_id 
    WHERE b.publication_date > ? 
    ORDER BY b.publication_date ASC
    LIMIT 8
");
$upcomingBooks->bind_param("s", $today);
$upcomingBooks->execute();
$upcomingBooksResult = $upcomingBooks->get_result();

// Get featured new release (random selection from new releases)
$featuredRelease = $conn->prepare("
    SELECT b.*, c.category_name 
    FROM Books b 
    LEFT JOIN Categories c ON b.category_id = c.category_id 
    WHERE b.publication_date >= ? 
    ORDER BY RAND()
    LIMIT 1
");
$featuredRelease->bind_param("s", $threeMonthsAgo);
$featuredRelease->execute();
$featuredReleaseResult = $featuredRelease->get_result()->fetch_assoc();
?>

<div class="content">
    <div class="container">
        <div class="page-header">
            <h1>New Releases</h1>
            <p>Discover the latest books to hit our shelves</p>
        </div>
        
        <?php if ($featuredReleaseResult): ?>
        <!-- Featured new release -->
        <div class="featured-release">
            <div class="featured-content">
                <div class="featured-image">
                    <img src="<?php echo htmlspecialchars($featuredReleaseResult['cover_image']); ?>" alt="<?php echo htmlspecialchars($featuredReleaseResult['title']); ?>">
                    <span class="new-badge">NEW</span>
                </div>
                <div class="featured-info">
                    <h2><?php echo htmlspecialchars($featuredReleaseResult['title']); ?></h2>
                    <p class="author">by <?php echo htmlspecialchars($featuredReleaseResult['author']); ?></p>
                    <p class="category"><?php echo htmlspecialchars($featuredReleaseResult['category_name']); ?></p>
                    <p class="release-date">Released: <?php echo date('F j, Y', strtotime($featuredReleaseResult['publication_date'])); ?></p>
                    <p class="description"><?php echo substr(htmlspecialchars($featuredReleaseResult['description']), 0, 300) . '...'; ?></p>
                    <div class="price">$<?php echo number_format($featuredReleaseResult['price'], 2); ?></div>
                    <div class="actions">
                        <a href="book_detail.php?id=<?php echo $featuredReleaseResult['book_id']; ?>" class="btn btn-secondary">View Details</a>
                        <button class="add-to-cart-btn" data-book-id="<?php echo $featuredReleaseResult['book_id']; ?>">Add to Cart</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- New releases grid -->
        <div class="new-releases-section">
            <h2>Just Released</h2>
            
            <?php if ($newReleasesResult->num_rows > 0): ?>
            <div class="book-grid">
                <?php while ($book = $newReleasesResult->fetch_assoc()): ?>
                <div class="book-card">
                    <div class="book-image">
                        <a href="book_detail.php?id=<?php echo $book['book_id']; ?>">
                            <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                            <?php 
                            $daysAgo = floor((time() - strtotime($book['publication_date'])) / (60 * 60 * 24));
                            if ($daysAgo < 14): ?>
                                <span class="new-badge">NEW</span>
                            <?php endif; ?>
                        </a>
                    </div>
                    <div class="book-info">
                        <h3><a href="book_detail.php?id=<?php echo $book['book_id']; ?>"><?php echo htmlspecialchars($book['title']); ?></a></h3>
                        <p class="author">by <?php echo htmlspecialchars($book['author']); ?></p>
                        <p class="release-date">Released: <?php echo date('M j, Y', strtotime($book['publication_date'])); ?></p>
                        <p class="price">$<?php echo number_format($book['price'], 2); ?></p>
                        <button class="add-to-cart-btn" data-book-id="<?php echo $book['book_id']; ?>">Add to Cart</button>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            
            <div class="view-more">
                <a href="books.php?sort=newest" class="btn">View More New Releases</a>
            </div>
            <?php else: ?>
            <div class="no-results">
                <p>No new releases available at this time. Please check back soon!</p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Coming soon section -->
        <div class="upcoming-section">
            <h2>Coming Soon</h2>
            <p class="section-description">Pre-order these upcoming releases and be among the first to read them</p>
            
            <?php if ($upcomingBooksResult->num_rows > 0): ?>
            <div class="upcoming-grid">
                <?php while ($book = $upcomingBooksResult->fetch_assoc()): 
                    $daysUntil = floor((strtotime($book['publication_date']) - time()) / (60 * 60 * 24));
                ?>
                <div class="upcoming-card">
                    <div class="upcoming-image">
                        <a href="book_detail.php?id=<?php echo $book['book_id']; ?>">
                            <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                            <span class="upcoming-badge">
                                <?php if ($daysUntil <= 7): ?>
                                    Coming in <?php echo $daysUntil; ?> days
                                <?php else: ?>
                                    Coming Soon
                                <?php endif; ?>
                            </span>
                        </a>
                    </div>
                    <div class="upcoming-info">
                        <h3><a href="book_detail.php?id=<?php echo $book['book_id']; ?>"><?php echo htmlspecialchars($book['title']); ?></a></h3>
                        <p class="author">by <?php echo htmlspecialchars($book['author']); ?></p>
                        <p class="release-date">Expected: <?php echo date('F j, Y', strtotime($book['publication_date'])); ?></p>
                        <p class="price">$<?php echo number_format($book['price'], 2); ?></p>
                        <button class="preorder-btn" data-book-id="<?php echo $book['book_id']; ?>">Pre-order Now</button>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="no-results">
                <p>No upcoming releases available at this time. Please check back soon!</p>
            </div>
            <?php endif; ?>
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
    
    .featured-release {
        margin-bottom: 50px;
        background-color: #f9f9f9;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .featured-content {
        display: grid;
        grid-template-columns: 300px 1fr;
    }
    
    @media (max-width: 768px) {
        .featured-content {
            grid-template-columns: 1fr;
        }
    }
    
    .featured-image {
        position: relative;
        overflow: hidden;
    }
    
    .featured-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    
    .new-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background-color: var(--accent-color);
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        font-weight: bold;
        font-size: 0.8rem;
    }
    
    .featured-info {
        padding: 30px;
    }
    
    .featured-info h2 {
        color: var(--primary-color);
        margin-bottom: 10px;
        font-size: 1.8rem;
    }
    
    .featured-info .author {
        color: #666;
        margin-bottom: 5px;
        font-style: italic;
    }
    
    .featured-info .category {
        color: var(--primary-color);
        margin-bottom: 10px;
        font-weight: bold;
    }
    
    .featured-info .release-date {
        margin-bottom: 15px;
        color: #666;
    }
    
    .featured-info .description {
        margin-bottom: 20px;
        line-height: 1.6;
    }
    
    .featured-info .price {
        font-size: 1.4rem;
        font-weight: bold;
        color: var(--accent-color);
        margin-bottom: 20px;
    }
    
    .featured-info .actions {
        display: flex;
        gap: 15px;
    }
    
    .new-releases-section,
    .upcoming-section {
        margin-bottom: 50px;
    }
    
    .new-releases-section h2,
    .upcoming-section h2 {
        color: var(--primary-color);
        margin-bottom: 20px;
        font-size: 1.8rem;
        position: relative;
        padding-bottom: 10px;
    }
    
    .new-releases-section h2:after,
    .upcoming-section h2:after {
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
        margin-bottom: 30px;
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
    
    .book-info .release-date {
        color: #888;
        font-size: 0.8rem;
        margin-bottom: 8px;
    }
    
    .book-info .price {
        font-weight: bold;
        color: var(--accent-color);
        margin-bottom: 10px;
    }
    
    .add-to-cart-btn,
    .preorder-btn {
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
    
    .preorder-btn {
        background-color: var(--accent-color);
    }
    
    .preorder-btn:hover {
        background-color: #e74c3c;
    }
    
    .view-more {
        text-align: center;
        margin-top: 20px;
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
    
    .btn-secondary {
        background-color: #6c757d;
    }
    
    .btn-secondary:hover {
        background-color: #5a6268;
    }
    
    .upcoming-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 30px;
    }
    
    .upcoming-card {
        background-color: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    
    .upcoming-card:hover {
        transform: translateY(-5px);
    }
    
    .upcoming-image {
        position: relative;
        height: 300px;
        overflow: hidden;
    }
    
    .upcoming-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .upcoming-badge {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background-color: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 8px;
        text-align: center;
        font-weight: bold;
    }
    
    .upcoming-info {
        padding: 15px;
    }
    
    .upcoming-info h3 {
        margin-bottom: 5px;
    }
    
    .upcoming-info h3 a {
        color: var(--primary-color);
        text-decoration: none;
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
// Close statement
$newReleases->close();
$upcomingBooks->close();
$featuredRelease->close();

// Include footer template
require_once '../templates/footer.php';
?>