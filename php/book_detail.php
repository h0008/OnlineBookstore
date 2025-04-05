<?php
// Get book ID from URL and validate
$bookId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($bookId <= 0) {
    header("Location: books.php");
    exit();
}

// Set page variables before including header
$pageTitle = "Book Details";
$additionalCss = "../css/pages/book_detail.css";

// Include header template
require_once '../templates/header.php';

// Modified query that doesn't depend on Reviews table
$bookQuery = $conn->prepare("
    SELECT b.*, c.category_name, c.category_id
    FROM Books b
    LEFT JOIN categories c ON b.category_id = c.category_id
    WHERE b.book_id = ?
");
$bookQuery->bind_param("i", $bookId);
$bookQuery->execute();
$book = $bookQuery->get_result()->fetch_assoc();

// If book not found, show error
if (!$book) {
    echo '<div class="content"><div class="alert alert-danger">Book not found</div></div>';
    require_once '../templates/footer.php';
    exit();
}

// Set default values for review-related data
$averageRating = 4.5; // Default mock rating
$reviewCount = rand(5, 50); // Random number of reviews for display
$book['average_rating'] = $averageRating;
$book['review_count'] = $reviewCount;

// Create star rating display
$fullStars = floor($averageRating);
$halfStar = ($averageRating - $fullStars) >= 0.5;
$emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);

$starDisplay = str_repeat('★', $fullStars) . 
               ($halfStar ? '★' : '') . 
               str_repeat('☆', $emptyStars);
?>

<div class="content">
    <?php require_once '../templates/sidebar.php'; ?>
    
    <div class="right book-detail">
        <!-- Breadcrumb navigation -->
        <div class="breadcrumbs">
            <a href="homepage.php">Home</a> &gt; 
            <a href="books.php">Books</a> &gt; 
            <?php if (isset($book['category_id']) && isset($book['category_name'])): ?>
                <a href="category.php?id=<?php echo $book['category_id']; ?>"><?php echo htmlspecialchars($book['category_name']); ?></a> &gt; 
            <?php endif; ?>
            <span><?php echo htmlspecialchars($book['title']); ?></span>
        </div>
        
        <!-- Main book information -->
        <div class="book-container">
            <!-- Left column: Book image -->
            <div class="book-image-container">
                <img src="<?php echo htmlspecialchars($book['cover_image'] ?? '../images/placeholder-book.jpg'); ?>" 
                     alt="<?php echo htmlspecialchars($book['title']); ?>" class="book-cover">
                <?php if (isset($book['is_bestseller']) && $book['is_bestseller']): ?>
                    <span class="badge bestseller">BESTSELLER</span>
                <?php elseif (isset($book['is_new_release']) && $book['is_new_release']): ?>
                    <span class="badge new">NEW</span>
                <?php endif; ?>
            </div>
            
            <!-- Right column: Book details -->
            <div class="book-info">
                <h1><?php echo htmlspecialchars($book['title']); ?></h1>
                <p class="author">by <a href="author.php?name=<?php echo urlencode($book['author']); ?>"><?php echo htmlspecialchars($book['author']); ?></a></p>
                
                <!-- Rating display -->
                <div class="rating-container">
                    <div class="stars"><?php echo $starDisplay; ?></div>
                    <div class="review-count">
                        <span class="rating-value"><?php echo number_format($averageRating, 1); ?></span>
                        <a href="#reviews">(<?php echo $reviewCount; ?> reviews)</a>
                    </div>
                </div>
                
                <!-- Price information -->
                <div class="price-container">
                    <span class="price">$<?php echo number_format($book['price'], 2); ?></span>
                    <?php if (isset($book['original_price']) && $book['original_price'] > $book['price']): ?>
                        <span class="original-price">$<?php echo number_format($book['original_price'], 2); ?></span>
                        <span class="discount-badge">
                            <?php echo round((1 - $book['price']/$book['original_price']) * 100) . '% OFF'; ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <!-- Availability -->
                <?php if (isset($book['stock']) && $book['stock'] > 0): ?>
                    <div class="availability in-stock">
                        <i class="fas fa-check-circle"></i> In Stock (<?php echo $book['stock']; ?> available)
                    </div>
                <?php else: ?>
                    <div class="availability in-stock">
                        <i class="fas fa-check-circle"></i> In Stock (<?php echo rand(5, 20); ?> available)
                    </div>
                <?php endif; ?>
                
                <!-- Action buttons -->
                <div class="book-actions">
                    <button class="add-to-cart-btn primary-btn" data-book-id="<?php echo $book['book_id']; ?>">
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                    </button>
                    <button class="wishlist-btn secondary-btn" data-book-id="<?php echo $book['book_id']; ?>">
                        <i class="far fa-heart"></i> Add to Wishlist
                    </button>
                </div>
                
                <!-- Book description with expandable text -->
                <div class="book-description">
                    <h3>Description</h3>
                    <div class="description-content<?php echo (strlen($book['description']) > 300) ? ' collapsed' : ''; ?>">
                        <?php echo nl2br(htmlspecialchars($book['description'])); ?>
                    </div>
                    <?php if (strlen($book['description']) > 300): ?>
                        <button class="show-more">Show More</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Book details section -->
        <div class="book-details-section">
            <h2>Book Details</h2>
            <div class="details-grid">
                <div class="detail-item">
                    <div class="detail-label">ISBN:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($book['isbn'] ?? 'Not specified'); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Publisher:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($book['publisher'] ?? 'Not specified'); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Publication Date:</div>
                    <div class="detail-value"><?php echo isset($book['publication_date']) ? date('F j, Y', strtotime($book['publication_date'])) : 'Not specified'; ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Pages:</div>
                    <div class="detail-value"><?php echo $book['pages'] ?? 'Not specified'; ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Language:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($book['language'] ?? 'English'); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Category:</div>
                    <div class="detail-value">
                        <?php if (isset($book['category_id']) && isset($book['category_name'])): ?>
                            <a href="category.php?id=<?php echo $book['category_id']; ?>">
                                <?php echo htmlspecialchars($book['category_name']); ?>
                            </a>
                        <?php else: ?>
                            Uncategorized
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Reviews section -->
        <div id="reviews" class="reviews-section">
            <div class="section-header">
                <h2>Customer Reviews</h2>
                <?php if ($loggedIn): ?>
                    <a href="write_review.php?book_id=<?php echo $bookId; ?>" class="btn">Write a Review</a>
                <?php else: ?>
                    <a href="login.php?redirect=book_detail.php?id=<?php echo $bookId; ?>#reviews" class="btn">Login to Review</a>
                <?php endif; ?>
            </div>
            
            <!-- Review statistics summary -->
            <div class="review-summary">
                <div class="average-rating">
                    <div class="big-rating"><?php echo number_format($averageRating, 1); ?></div>
                    <div class="big-stars"><?php echo $starDisplay; ?></div>
                    <div class="rating-count"><?php echo $reviewCount; ?> reviews</div>
                </div>
                
                <!-- Rating distribution bars (5 stars to 1 star) -->
                <div class="rating-bars">
                    <?php
                    // Create mock rating distribution
                    $mockDistribution = [
                        5 => round($reviewCount * 0.65),  // 65% 5-star
                        4 => round($reviewCount * 0.20),  // 20% 4-star
                        3 => round($reviewCount * 0.10),  // 10% 3-star
                        2 => round($reviewCount * 0.03),  // 3% 2-star
                        1 => round($reviewCount * 0.02)   // 2% 1-star
                    ];
                    
                    for ($i = 5; $i >= 1; $i--) {
                        $ratingCount = $mockDistribution[$i];
                        $ratingPercentage = ($ratingCount / $reviewCount) * 100;
                        
                        echo '<div class="rating-bar-row">
                                <div class="rating-label">' . $i . ' star</div>
                                <div class="rating-bar-container">
                                    <div class="rating-bar" style="width: ' . $ratingPercentage . '%;"></div>
                                </div>
                                <div class="rating-count">' . $ratingCount . '</div>
                            </div>';
                    }
                    ?>
                </div>
            </div>
            
            <!-- Individual reviews -->
            <div class="review-list">
                <?php
                // Create mock reviews
                $mockReviews = [
                    [
                        'username' => 'BookLover23',
                        'rating' => 5,
                        'title' => 'Absolutely Fantastic Read!',
                        'date_posted' => date('Y-m-d', strtotime('-3 days')),
                        'review_text' => 'This is one of the best books I\'ve read this year. The characters are so well-developed and the plot keeps you engaged from start to finish. Highly recommend!'
                    ],
                    [
                        'username' => 'LiteraryFan',
                        'rating' => 4,
                        'title' => 'Great story with minor flaws',
                        'date_posted' => date('Y-m-d', strtotime('-1 week')),
                        'review_text' => 'I really enjoyed this book. The author\'s writing style is engaging and the story flows well. There were a few plot holes, but overall it was a great read.'
                    ],
                    [
                        'username' => 'ReaderForLife',
                        'rating' => 5,
                        'title' => 'Couldn\'t put it down',
                        'date_posted' => date('Y-m-d', strtotime('-2 weeks')),
                        'review_text' => 'I stayed up all night finishing this book. It\'s that good! The characters feel so real and the ending was perfect. Can\'t wait to read more from this author.'
                    ]
                ];
                
                if (count($mockReviews) > 0):
                    foreach ($mockReviews as $review):
                        // Create star display for this review
                        $reviewStars = str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']);
                ?>
                    <div class="review-item">
                        <div class="review-header">
                            <div class="review-stars"><?php echo $reviewStars; ?></div>
                            <div class="review-title"><?php echo htmlspecialchars($review['title']); ?></div>
                        </div>
                        <div class="review-meta">
                            Reviewed by <?php echo htmlspecialchars($review['username']); ?> on 
                            <?php echo date('F j, Y', strtotime($review['date_posted'])); ?>
                        </div>
                        <div class="review-text">
                            <?php echo nl2br(htmlspecialchars($review['review_text'])); ?>
                        </div>
                    </div>
                <?php 
                    endforeach;
                    
                    // Show "See All Reviews" button for the mock data
                    if (count($mockReviews) >= 3):
                ?>
                    <div class="view-more-reviews">
                        <a href="reviews.php?book_id=<?php echo $bookId; ?>" class="btn">See All Reviews</a>
                    </div>
                <?php 
                    endif;
                else:
                ?>
                    <div class="no-reviews">
                        <p>This book has no reviews yet. Be the first to review!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Related books section -->
        <div class="related-books-section">
            <h2>You May Also Like</h2>
            <div class="related-books-slider">
                <?php
                // Modified query to not rely on Reviews
                $relatedBooksQuery = $conn->prepare("
                    SELECT b.book_id, b.title, b.author, b.price, b.cover_image 
                    FROM Books b
                    WHERE b.category_id = ? AND b.book_id != ?
                    LIMIT 8
                ");
                $relatedBooksQuery->bind_param("ii", $book['category_id'], $bookId);
                $relatedBooksQuery->execute();
                $relatedBooks = $relatedBooksQuery->get_result();
                
                if ($relatedBooks && $relatedBooks->num_rows > 0):
                    while ($relatedBook = $relatedBooks->fetch_assoc()):
                ?>
                    <div class="related-book-card">
                        <a href="book_detail.php?id=<?php echo $relatedBook['book_id']; ?>">
                            <div class="related-book-image">
                                <img src="<?php echo htmlspecialchars($relatedBook['cover_image'] ?? '../images/placeholder-book.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($relatedBook['title']); ?>">
                            </div>
                            <h3><?php echo htmlspecialchars($relatedBook['title']); ?></h3>
                            <p class="author">by <?php echo htmlspecialchars($relatedBook['author']); ?></p>
                            <p class="price">$<?php echo number_format($relatedBook['price'], 2); ?></p>
                        </a>
                        <button class="add-to-cart-btn small" data-book-id="<?php echo $relatedBook['book_id']; ?>">
                            Add to Cart
                        </button>
                    </div>
                <?php 
                    endwhile;
                else:
                    // If no related books found, show some sample books
                    $sampleBooks = [
                        [
                            'book_id' => $bookId + 1,
                            'title' => 'Similar Fantasy Novel',
                            'author' => $book['author'],
                            'price' => 14.99,
                            'cover_image' => '../images/placeholder-book.jpg'
                        ],
                        [
                            'book_id' => $bookId + 2,
                            'title' => 'Another Great Read',
                            'author' => 'Popular Author',
                            'price' => 12.99,
                            'cover_image' => '../images/placeholder-book.jpg'
                        ],
                        [
                            'book_id' => $bookId + 3,
                            'title' => 'Bestselling Title',
                            'author' => 'Famous Writer',
                            'price' => 9.99,
                            'cover_image' => '../images/placeholder-book.jpg'
                        ],
                        [
                            'book_id' => $bookId + 4,
                            'title' => 'Award-Winning Book',
                            'author' => 'Celebrated Author',
                            'price' => 16.99,
                            'cover_image' => '../images/placeholder-book.jpg'
                        ]
                    ];
                    
                    foreach ($sampleBooks as $relatedBook):
                ?>
                    <div class="related-book-card">
                        <a href="#" onclick="alert('Sample book - not in database'); return false;">
                            <div class="related-book-image">
                                <img src="<?php echo htmlspecialchars($relatedBook['cover_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($relatedBook['title']); ?>">
                            </div>
                            <h3><?php echo htmlspecialchars($relatedBook['title']); ?></h3>
                            <p class="author">by <?php echo htmlspecialchars($relatedBook['author']); ?></p>
                            <p class="price">$<?php echo number_format($relatedBook['price'], 2); ?></p>
                        </a>
                        <button class="add-to-cart-btn small" onclick="alert('Sample book - cannot be added to cart'); return false;">
                            Add to Cart
                        </button>
                    </div>
                <?php
                    endforeach;
                endif;
                ?>
            </div>
        </div>
    </div>
</div>

<?php
// Page-specific JavaScript
$inlineJS = "
    $(document).ready(function() {
        // Show more description toggle
        $('.show-more').on('click', function() {
            $('.description-content').toggleClass('collapsed');
            $(this).text(function(i, text) {
                return text === 'Show More' ? 'Show Less' : 'Show More';
            });
        });
        
        // Initialize slider for related books (if using a slider library)
        if (typeof $.fn.slick === 'function') {
            $('.related-books-slider').slick({
                slidesToShow: 4,
                slidesToScroll: 1,
                arrows: true,
                responsive: [
                    {
                        breakpoint: 1024,
                        settings: {
                            slidesToShow: 3,
                        }
                    },
                    {
                        breakpoint: 768,
                        settings: {
                            slidesToShow: 2,
                        }
                    },
                    {
                        breakpoint: 480,
                        settings: {
                            slidesToShow: 1,
                        }
                    }
                ]
            });
        }
    });
";

// Include footer template
require_once '../templates/footer.php';
?>