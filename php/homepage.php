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

// Set page title variable before including header
$pageTitle = "Home";

// Include header template
require_once '../templates/header.php';

// Get hero banner slides from database
$heroSlides = $conn->query("SELECT * FROM Hero_Banners WHERE is_active = 1 ORDER BY display_order ASC");
$slides = [];
if ($heroSlides && $heroSlides->num_rows > 0) {
    while ($slide = $heroSlides->fetch_assoc()) {
        $slides[] = $slide;
    }
}
?>

<!-- Hero banner section -->
<div class="hero-banner">
    <div class="slideshow-container">
        <?php if (!empty($slides)): ?>
            <?php foreach ($slides as $index => $slide): ?>
                <div class="slide fade" style="background-image: url('<?php echo htmlspecialchars($slide['image_url']); ?>')">
                    <div class="hero-content">
                        <h2><?php echo htmlspecialchars($slide['title']); ?></h2>
                        <p><?php echo htmlspecialchars($slide['subtitle']); ?></p>
                        <a href="<?php echo htmlspecialchars($slide['button_link']); ?>" class="btn"><?php echo htmlspecialchars($slide['button_text']); ?></a>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- Navigation dots -->
            <div class="dots-container">
                <?php foreach ($slides as $index => $slide): ?>
                    <span class="dot" onclick="currentSlide(<?php echo $index + 1; ?>)"></span>
                <?php endforeach; ?>
            </div>
            
            <!-- Previous and Next buttons -->
            <a class="prev" onclick="plusSlides(-1)">&#10094;</a>
            <a class="next" onclick="plusSlides(1)">&#10095;</a>
        <?php else: ?>
    <!-- Fallback colorful slides when no images are found -->
    <div class="slide color-slide color-slide-1">
        <div class="hero-content">
            <h2>Spring Reading Sale</h2>
            <p>Discover your next favorite book with 30% off selected titles</p>
            <a href="special_offers.php" class="btn">Shop Now</a>
        </div>
    </div>
    <div class="slide color-slide color-slide-2" style="display: none;">
        <div class="hero-content">
            <h2>New Fiction Releases</h2>
            <p>Be the first to read this month's most anticipated books</p>
            <a href="new_releases.php" class="btn">Explore Now</a>
        </div>
    </div>
    
    <!-- Navigation dots for color slides -->
    <div class="dots-container">
        <span class="dot active-dot" onclick="currentSlide(1)"></span>
        <span class="dot" onclick="currentSlide(2)"></span>
        <span class="dot" onclick="currentSlide(3)"></span>
    </div>
    
    <!-- Previous and Next buttons -->
    <a class="prev" onclick="plusSlides(-1)">&#10094;</a>
    <a class="next" onclick="plusSlides(1)">&#10095;</a>
<?php endif; ?>
    </div>
</div>

<!-- Main content area -->
<div class="content">
    <?php 
    // Include sidebar template
    require_once '../templates/sidebar.php'; 
    ?>

    <!-- Main book display area -->
    <div class="right">
        <div class="section-header">
            <h2>Featured Books</h2>
            <a href="books.php" class="view-all">View All</a>
        </div>

        <div class="book-grid">
            <?php
            // Get books from database (without 'is_featured' filter)
            $featuredBooks = $conn->query("SELECT * FROM Books LIMIT 12");
            
            // If there are books in the database, display them
            if ($featuredBooks && $featuredBooks->num_rows > 0) {
                while ($book = $featuredBooks->fetch_assoc()) {
                    // Generate book card from database
            ?>
                <div class="book-card">
                    <div class="book-image">
                        <a href="book_detail.php?id=<?php echo $book['book_id']; ?>">
                            <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                            <?php if (isset($book['is_bestseller']) && $book['is_bestseller']): ?>
                                <span class="badge bestseller">BESTSELLER</span>
                            <?php elseif (isset($book['is_new_release']) && $book['is_new_release']): ?>
                                <span class="badge new">NEW</span>
                            <?php endif; ?>
                        </a>
                    </div>
                    <h3><a href="book_detail.php?id=<?php echo $book['book_id']; ?>" class="book-title-link"><?php echo htmlspecialchars($book['title']); ?></a></h3>
                    <p class="author">by <?php echo htmlspecialchars($book['author']); ?></p>
                    <p class="price">$<?php echo number_format($book['price'], 2); ?></p>
                    <div class="rating">
                        <span class="stars">★★★★☆</span>
                        <span class="reviews">(243)</span>
                    </div>
                    <button class="add-to-cart-btn" data-book-id="<?php echo $book['book_id']; ?>">Add to Cart</button>
                </div>
            <?php
                }
            } else {
                // Fallback: Display static books if database query fails
            ?>
                <!-- Fallback static book card -->
                <div class="book-card">
                    <div class="book-image">
                        <a href="book_detail.php?id=1">
                            <img src="https://m.media-amazon.com/images/I/418jD+Rsd5L._SL500_.jpg" alt="The Hobbit">
                        </a>
                    </div>
                    <h3><a href="book_detail.php?id=1" class="book-title-link">The Hobbit</a></h3>
                    <p class="author">by J.R.R. Tolkien</p>
                    <p class="price">$12.99</p>
                    <div class="rating">
                        <span class="stars">★★★★★</span>
                        <span class="reviews">(243)</span>
                    </div>
                    <button class="add-to-cart-btn" data-book-id="1">Add to Cart</button>
                </div>
            <?php
            }
            ?>
        </div>
    </div>
</div>

<?php
// Include footer template
require_once '../templates/footer.php';
?>

<script>
// Slideshow functionality
let slideIndex = 1;
let slideInterval;

// Initialize the slideshow
document.addEventListener('DOMContentLoaded', function() {
    // Count the actual slides present on the page
    let slidesCount = document.querySelectorAll('.slide').length;
    
    if (slidesCount > 0) {
        showSlides(slideIndex);
        
        // Auto-advance slides every no second
        startSlideshow();
        
        // Pause slideshow when user hovers over it
        let slideshowContainer = document.querySelector('.slideshow-container');
        if (slideshowContainer) {
            slideshowContainer.addEventListener('mouseenter', function() {
                clearInterval(slideInterval);
            });
            
            // Resume slideshow when user stops hovering
            slideshowContainer.addEventListener('mouseleave', function() {
                startSlideshow();
            });
        }
    }
});

function startSlideshow() {
    clearInterval(slideInterval);
    slideInterval = setInterval(function() {
        plusSlides(1);
    }, 500000);
}

// Next/previous controls
function plusSlides(n) {
    showSlides(slideIndex += n);
}

// Thumbnail image controls
function currentSlide(n) {
    showSlides(slideIndex = n);
}

function showSlides(n) {
    let slides = document.getElementsByClassName("slide");
    let dots = document.getElementsByClassName("dot");
    
    if (slides.length === 0) return;
    
    if (n > slides.length) {slideIndex = 1}
    if (n < 1) {slideIndex = slides.length}
    
    // Hide all slides
    for (let i = 0; i < slides.length; i++) {
        slides[i].style.display = "none";
    }
    
    // Remove active class from all dots
    for (let i = 0; i < dots.length; i++) {
        dots[i].className = dots[i].className.replace(" active-dot", "");
    }
    
    // Show current slide and highlight current dot
    slides[slideIndex-1].style.display = "block";
    
    // Check if dots exist before adding active class
    if (dots.length > 0 && dots[slideIndex-1]) {
        dots[slideIndex-1].className += " active-dot";
    }
}
</script>

<style>
/* Add this to your CSS */
.color-slide-1 {
    background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%), 
                url('https://images.unsplash.com/photo-1507842217343-583bb7270b66?ixlib=rb-1.2.1&auto=format&fit=crop&w=1200&q=80');
    background-blend-mode: overlay;
    background-size: cover;
}

.color-slide-2 {
    background: linear-gradient(135deg, #f83600 0%, #f9d423 100%),
                url('https://images.unsplash.com/photo-1495446815901-a7297e633e8d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1200&q=80');
    background-blend-mode: overlay;
    background-size: cover;
}

.color-slide-3 {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%),
                url('https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?ixlib=rb-1.2.1&auto=format&fit=crop&w=1200&q=80');
    background-blend-mode: overlay;
    background-size: cover;
}
</style>