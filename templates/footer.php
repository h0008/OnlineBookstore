        <!-- Newsletter subscription -->
        <div class="newsletter">
            <div class="container">
                <h3>Subscribe to our Newsletter</h3>
                <p>Get updates on new releases, exclusive deals, and reading recommendations.</p>
                <form class="newsletter-form" action="subscribe.php" method="post">
                    <input type="email" name="email" placeholder="Your email address" required>
                    <button type="submit">Subscribe</button>
                </form>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-links">
                <div class="footer-column">
                    <h4>Customer Service</h4>
                    <ul>
                        <li><a href="help.php">Help Center</a></li>
                        <li><a href="order_status.php">Order Status</a></li>
                        <li><a href="returns.php">Returns & Refunds</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h4>About Us</h4>
                    <ul>
                        <li><a href="about.php">Our Story</a></li>
                        <li><a href="careers.php">Careers</a></li>
                        <li><a href="press.php">Press</a></li>
                        <li><a href="blog.php">Blog</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h4>Connect With Us</h4>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-pinterest"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Haitchal Books. All rights reserved.</p>
                <div class="footer-legal">
                    <a href="privacy.php">Privacy Policy</a>
                    <a href="terms.php">Terms of Use</a>
                    <a href="accessibility.php">Accessibility</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Cart functionality script -->
    <script>
        $(document).ready(function() {
            // Add to cart functionality
            $('.add-to-cart-btn').click(function(e) {
                e.preventDefault();
                
                var bookId = $(this).data('book-id');
                var button = $(this);
                
                // Disable button temporarily
                button.prop('disabled', true);
                button.text('Adding...');
                
                // Add to cart via AJAX
                $.ajax({
                    url: 'add_to_cart.php',
                    type: 'GET',
                    data: { book_id: bookId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Update cart count
                            $('#cart-count').text(response.cart_count);
                            
                            // Show success feedback
                            button.text('Added!');
                            setTimeout(function() {
                                button.text('Add to Cart');
                                button.prop('disabled', false);
                            }, 2000);
                        } else {
                            // If not logged in, redirect to login
                            if (response.message.includes('log in')) {
                                window.location.href = 'login.php';
                            } else {
                                // Show error
                                alert(response.message);
                                button.text('Add to Cart');
                                button.prop('disabled', false);
                            }
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                        button.text('Add to Cart');
                        button.prop('disabled', false);
                    }
                });
            });
        });
    </script>
    <?php if (isset($additionalJS)): ?>
    <script src="<?php echo $additionalJS; ?>"></script>
    <?php endif; ?>
    <?php if (isset($inlineJS)): ?>
    <script>
        <?php echo $inlineJS; ?>
    </script>
    <?php endif; ?>
</body>
</html>