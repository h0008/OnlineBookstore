<!-- Sidebar -->
<div class="left">
    <!-- Categories section -->
    <div class="category">
        <h2>Categories</h2>
        <ul>
            <?php
            // Get categories from database and only show those with books
            $categories = $conn->query("SELECT c.category_id, c.category_name, COUNT(b.book_id) as book_count 
                                       FROM Categories c
                                       LEFT JOIN Books b ON c.category_id = b.category_id
                                       GROUP BY c.category_id
                                       ORDER BY book_count DESC, c.category_name");
            
            // Get current category_id from URL if any
            $current_category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
            
            // Get total book count for "All Categories"
            $totalBooks = $conn->query("SELECT COUNT(*) as total FROM Books");
            $total = $totalBooks->fetch_assoc();
            $totalBookCount = $total['total'] ?? 0;
            
            if ($categories && $categories->num_rows > 0) {
                // Add "All Categories" option
                $activeClass = ($current_category == 0) ? 'class="active"' : '';
                echo '<li><a href="books.php" ' . $activeClass . '>' . 
                     '<span class="category-name">All Categories</span>' . 
                     '<span class="count">' . $totalBookCount . '</span></a></li>';
                
                // Display each category with book count (skip empty categories for main display)
                $emptyCategories = array();
                
                while ($category = $categories->fetch_assoc()) {
                    // If category has books, show it in the main list
                    if ($category['book_count'] > 0) {
                        $activeClass = ($current_category == $category['category_id']) ? 'class="active"' : '';
                        echo '<li><a href="books.php?category=' . $category['category_id'] . '" ' . $activeClass . '>' . 
                             '<span class="category-name">' . htmlspecialchars($category['category_name']) . '</span>' . 
                             '<span class="count">' . $category['book_count'] . '</span></a></li>';
                    } else {
                        // Store empty categories for later
                        $emptyCategories[] = $category;
                    }
                }
                
                // If we want to show empty categories, add a divider and list them
                if (!empty($emptyCategories)) {
                    echo '<li class="category-divider"><span>Empty Categories</span></li>';
                    
                    foreach ($emptyCategories as $category) {
                        echo '<li class="empty-category"><a href="books.php?category=' . $category['category_id'] . '">' . 
                             '<span class="category-name">' . htmlspecialchars($category['category_name']) . '</span>' . 
                             '<span class="count">0</span></a></li>';
                    }
                }
            } else {
                echo '<li>No categories found</li>';
            }
            ?>
        </ul>
    </div>
    
    <!-- Featured authors section -->
    <div class="brand">
        <h2>Popular Authors</h2>
        <ul>
            <?php
            // Get top authors from database (exclude suspicious entries with very short names)
            $authors = $conn->query("SELECT author, COUNT(*) as book_count 
                                    FROM Books 
                                    WHERE LENGTH(author) > 2
                                    GROUP BY author 
                                    ORDER BY book_count DESC, author 
                                    LIMIT 10");
            
            // Get current author from URL if any
            $current_author = isset($_GET['author']) ? $_GET['author'] : '';
            
            if ($authors && $authors->num_rows > 0) {
                while ($author = $authors->fetch_assoc()) {
                    $activeClass = ($current_author == $author['author']) ? 'class="active"' : '';
                    echo '<li><a href="books.php?author=' . urlencode($author['author']) . '" ' . $activeClass . '>' . 
                         htmlspecialchars($author['author']) . 
                         '<span class="count">' . $author['book_count'] . '</span></a></li>';
                }
            } else {
                echo '<li>No authors found</li>';
            }
            ?>
        </ul>
    </div>
    
    <!-- Price filter -->
    <div class="filter">
        <h2>Filter by Price</h2>
        <form action="books.php" method="get">
            <?php 
            // Preserve existing query parameters
            if (isset($_GET['category'])) {
                echo '<input type="hidden" name="category" value="' . htmlspecialchars($_GET['category']) . '">';
            }
            if (isset($_GET['author'])) {
                echo '<input type="hidden" name="author" value="' . htmlspecialchars($_GET['author']) . '">';
            }
            if (isset($_GET['search'])) {
                echo '<input type="hidden" name="search" value="' . htmlspecialchars($_GET['search']) . '">';
            }
            
            // Get current price range if set
            $min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
            $max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 100;
            ?>
            
            <div class="price-range">
                <div class="price-inputs">
                    <div class="form-group">
                        <label for="min_price">Min:</label>
                        <input type="number" id="min_price" name="min_price" min="0" max="1000" step="0.01" value="<?php echo $min_price; ?>">
                    </div>
                    <div class="form-group">
                        <label for="max_price">Max:</label>
                        <input type="number" id="max_price" name="max_price" min="0" max="1000" step="0.01" value="<?php echo $max_price; ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-sm btn-primary">Apply Filter</button>
            </div>
        </form>
    </div>
    
    <!-- Featured book -->
    <?php
    // Get a random featured book
    $featuredBook = $conn->query("SELECT * FROM Books WHERE cover_image IS NOT NULL ORDER BY RAND() LIMIT 1");
    if ($featuredBook && $featuredBook->num_rows > 0):
        $book = $featuredBook->fetch_assoc();
    ?>
    <div class="featured-sidebar">
        <h2>Featured Book</h2>
        <div class="featured-book">
            <a href="book_detail.php?id=<?php echo $book['book_id']; ?>">
                <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
            </a>
            <h3><a href="book_detail.php?id=<?php echo $book['book_id']; ?>"><?php echo htmlspecialchars($book['title']); ?></a></h3>
            <p class="author">by <?php echo htmlspecialchars($book['author']); ?></p>
            <p class="price">$<?php echo number_format($book['price'], 2); ?></p>
            <button class="add-to-cart-btn" data-book-id="<?php echo $book['book_id']; ?>">Add to Cart</button>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
/* Additional sidebar styling */
.category-divider {
    margin-top: 15px;
    padding-top: 10px;
    border-top: 1px dashed #e0e0e0;
}

.category-divider span {
    font-size: 0.8rem;
    color: #999;
    font-style: italic;
}

.empty-category a {
    color: #999 !important;
}

.empty-category:hover a {
    color: var(--primary-color) !important;
}

.filter .form-group {
    margin-bottom: 10px;
}

.price-inputs {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 15px;
}

price-inputs input {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.filter button {
    width: 100%;
}

.featured-sidebar {
    margin-top: 25px;
}

.featured-book {
    text-align: center;
}

.featured-book img {
    max-width: 100%;
    height: auto;
    border-radius: 4px;
    margin-bottom: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.featured-book h3 {
    font-size: 1rem;
    margin-bottom: 5px;
}

.featured-book .author {
    color: #777;
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.featured-book .price {
    color: var(--accent-color);
    font-weight: bold;
    margin-bottom: 10px;
}

.add-to-cart-btn {
    background-color: var(--primary-color);
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 4px;
    cursor: pointer;
    width: 100%;
}

.add-to-cart-btn:hover {
    background-color: var(--primary-dark);
}

/* Category item styling */
.category ul li a {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--space-xs) 0;
    color: var(--dark);
    text-decoration: none;
    transition: color 0.2s;
}

.category ul li a:hover {
    color: var(--primary-color);
}

/* Count badge styling with better spacing */
.count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background-color: var(--light);
    color: var(--primary-color);
    border-radius: 12px;
    padding: 2px 8px;
    font-size: 0.75rem;
    font-weight: bold;
    margin-left: 8px; /* Space between name and count */
    min-width: 24px;
}

/* Empty categories styling */
.empty-category a .count {
    background-color: #f0f0f0;
    color: #999;
}

/* Active category styling */
.category ul li a.active {
    color: var(--primary-color);
    font-weight: bold;
}

.category ul li a.active .count {
    background-color: var(--primary-color);
    color: white;
}

/* Category list styling */
.category ul li a {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--space-xs) 0;
    color: var(--dark);
    text-decoration: none;
    transition: color 0.2s;
}

.category ul li a:hover {
    color: var(--primary-color);
}

/* Count badge styling */
.count {
    background-color: var(--light);
    color: var(--primary-color);
    border-radius: 12px;
    padding: 2px 8px;
    font-size: 0.75rem;
    font-weight: bold;
    margin-left: 10px; /* Add space between category name and count */
    min-width: 24px;
    text-align: center;
}

/* Empty category styling */
.empty-category a .count {
    background-color: #f0f0f0;
    color: #999;
}

/* Active category styling */
.category ul li a.active {
    color: var(--primary-color);
    font-weight: bold;
}

.category ul li a.active .count {
    background-color: var(--primary-color);
    color: white;
}
</style>

<script>
// Add to cart functionality
document.addEventListener('DOMContentLoaded', function() {
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            const bookId = this.getAttribute('data-book-id');
            
            // AJAX request to add to cart
            fetch('../php/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'book_id=' + bookId + '&quantity=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update cart count in header
                    const cartCountElement = document.querySelector('.cart-count');
                    if (cartCountElement) {
                        cartCountElement.textContent = data.cartCount;
                    }
                    
                    alert('Book added to cart!');
                } else {
                    alert(data.message || 'Error adding book to cart');
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