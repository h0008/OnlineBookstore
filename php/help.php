<?php
// filepath: d:\XAMPP\htdocs\OnlineBookstore\php\help.php
session_start();
require_once '../connect.php';

// Set page title
$pageTitle = "Help Center";

// Include header
include_once '../templates/header.php';
?>

<div class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>Help Center</h1>
            <p>Find answers to your questions about orders, shipping, returns, and more.</p>
        </div>
        
        <div class="help-container">
            <div class="help-sidebar">
                <div class="help-search">
                    <input type="text" placeholder="Search help topics..." id="helpSearch">
                    <button><i class="fas fa-search"></i></button>
                </div>
                <div class="help-categories">
                    <h3>Help Topics</h3>
                    <ul>
                        <li><a href="#orders" class="active">Orders & Shipping</a></li>
                        <li><a href="#returns">Returns & Refunds</a></li>
                        <li><a href="#account">Account Management</a></li>
                        <li><a href="#payment">Payment Information</a></li>
                        <li><a href="#ebooks">E-Books & Downloads</a></li>
                        <li><a href="#contact">Contact Support</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="help-content">
                <section id="orders" class="help-section active">
                    <h2>Orders & Shipping</h2>
                    
                    <div class="faq-item">
                        <h3>How do I track my order?</h3>
                        <div class="faq-answer">
                            <p>Once your order has shipped, you will receive a shipping confirmation email with a tracking number. You can use this number to track your package's progress.</p>
                            <p>You can also check the status of your order by:</p>
                            <ol>
                                <li>Logging into your account</li>
                                <li>Going to "My Orders" in your account menu</li>
                                <li>Selecting the order you want to track</li>
                            </ol>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <h3>How long will it take for my order to arrive?</h3>
                        <div class="faq-answer">
                            <p>Delivery times depend on your location and the shipping method you selected at checkout:</p>
                            <ul>
                                <li><strong>Standard Shipping:</strong> 3-7 business days</li>
                                <li><strong>Express Shipping:</strong> 2-3 business days</li>
                                <li><strong>Priority Shipping:</strong> 1-2 business days</li>
                            </ul>
                            <p>Please note that these are estimated delivery times and do not include order processing time, which is typically 1-2 business days.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <h3>Can I change or cancel my order?</h3>
                        <div class="faq-answer">
                            <p>You can change or cancel your order within 1 hour of placing it. After this window, your order may have already entered the processing stage and cannot be modified.</p>
                            <p>To change or cancel an order:</p>
                            <ol>
                                <li>Log into your account</li>
                                <li>Go to "My Orders"</li>
                                <li>Select the order you wish to modify</li>
                                <li>Click on "Cancel Order" or "Modify Order" if the options are available</li>
                            </ol>
                            <p>If these options are not available, please contact customer support immediately for assistance.</p>
                        </div>
                    </div>
                </section>
                
                <section id="returns" class="help-section">
                    <h2>Returns & Refunds</h2>
                    
                    <div class="faq-item">
                        <h3>What is your return policy?</h3>
                        <div class="faq-answer">
                            <p>You may return items purchased from Haitchal Books within 30 days of receipt. Items must be in their original condition with all packaging intact.</p>
                            <p><strong>Note:</strong> E-books and digital content purchases are non-refundable once downloaded or accessed.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <h3>How do I return an item?</h3>
                        <div class="faq-answer">
                            <p>To return an item:</p>
                            <ol>
                                <li>Log into your account</li>
                                <li>Go to "My Orders"</li>
                                <li>Select the order containing the item you wish to return</li>
                                <li>Click "Return Item" and follow the instructions</li>
                                <li>Print the return shipping label</li>
                                <li>Package your item securely with the original packaging</li>
                                <li>Attach the return shipping label and drop off at any shipping location</li>
                            </ol>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <h3>When will I receive my refund?</h3>
                        <div class="faq-answer">
                            <p>Once we receive your returned item, we will inspect it to ensure it meets our return policy requirements. After approval:</p>
                            <ul>
                                <li>Credit card refunds typically appear on your statement within 5-7 business days</li>
                                <li>Store credit is issued immediately</li>
                                <li>Other payment methods may take 7-10 business days to process</li>
                            </ul>
                            <p>You will receive an email confirmation once your refund has been processed.</p>
                        </div>
                    </div>
                </section>
                
                <!-- More help sections would go here -->
                
                <div class="help-contact">
                    <h3>Still need help?</h3>
                    <p>Our customer support team is ready to assist you.</p>
                    <a href="contact.php" class="btn">Contact Us</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .help-container {
        display: flex;
        flex-direction: column;
        gap: 30px;
    }
    
    .help-sidebar {
        background-color: #f9f9f9;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    
    .help-search {
        display: flex;
        margin-bottom: 20px;
    }
    
    .help-search input {
        flex: 1;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px 0 0 4px;
        font-size: 16px;
    }
    
    .help-search button {
        background-color: var(--primary-color);
        color: white;
        border: none;
        padding: 0 15px;
        border-radius: 0 4px 4px 0;
        cursor: pointer;
    }
    
    .help-categories h3 {
        margin-bottom: 15px;
        color: var(--primary-color);
    }
    
    .help-categories ul {
        list-style: none;
        padding: 0;
    }
    
    .help-categories li {
        margin-bottom: 10px;
    }
    
    .help-categories a {
        display: block;
        padding: 8px 10px;
        color: #444;
        text-decoration: none;
        border-radius: 4px;
        transition: background-color 0.3s, color 0.3s;
    }
    
    .help-categories a:hover,
    .help-categories a.active {
        background-color: var(--primary-color);
        color: white;
    }
    
    .help-content {
        background-color: white;
        border-radius: 8px;
        padding: 30px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    
    .help-section {
        display: none;
    }
    
    .help-section.active {
        display: block;
    }
    
    .help-section h2 {
        color: var(--primary-color);
        margin-bottom: 25px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .faq-item {
        margin-bottom: 20px;
        border-bottom: 1px solid #f0f0f0;
        padding-bottom: 20px;
    }
    
    .faq-item h3 {
        font-size: 1.1rem;
        color: #444;
        margin-bottom: 10px;
        cursor: pointer;
        position: relative;
        padding-right: 20px;
    }
    
    .faq-item h3:after {
        content: '+';
        position: absolute;
        right: 5px;
        top: 0;
        font-size: 1.2rem;
    }
    
    .faq-item.active h3:after {
        content: '-';
    }
    
    .faq-answer {
        color: #666;
        line-height: 1.6;
    }
    
    .faq-answer p,
    .faq-answer ul,
    .faq-answer ol {
        margin-bottom: 15px;
    }
    
    .faq-answer ul,
    .faq-answer ol {
        padding-left: 20px;
    }
    
    .faq-answer li {
        margin-bottom: 5px;
    }
    
    .help-contact {
        margin-top: 40px;
        text-align: center;
        padding: 20px;
        background-color: #f9f9f9;
        border-radius: 8px;
    }
    
    .help-contact h3 {
        margin-bottom: 10px;
        color: var(--primary-color);
    }
    
    .help-contact p {
        margin-bottom: 15px;
        color: #666;
    }
    
    .btn {
        display: inline-block;
        padding: 10px 20px;
        background-color: var(--primary-color);
        color: white;
        text-decoration: none;
        border-radius: 4px;
        transition: background-color 0.3s;
    }
    
    .btn:hover {
        background-color: var(--secondary-color);
    }
    
    @media (min-width: 768px) {
        .help-container {
            flex-direction: row;
        }
        
        .help-sidebar {
            flex: 0 0 25%;
        }
        
        .help-content {
            flex: 0 0 75%;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle FAQ accordions
    const faqItems = document.querySelectorAll('.faq-item h3');
    
    faqItems.forEach(item => {
        item.addEventListener('click', function() {
            const parent = this.parentElement;
            parent.classList.toggle('active');
            
            const answer = this.nextElementSibling;
            if (parent.classList.contains('active')) {
                answer.style.display = 'block';
            } else {
                answer.style.display = 'none';
            }
        });
    });
    
    // Handle tab navigation
    const tabLinks = document.querySelectorAll('.help-categories a');
    const sections = document.querySelectorAll('.help-section');
    
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all links and sections
            tabLinks.forEach(l => l.classList.remove('active'));
            sections.forEach(s => s.classList.remove('active'));
            
            // Add active class to clicked link
            this.classList.add('active');
            
            // Show corresponding section
            const targetId = this.getAttribute('href').substring(1);
            document.getElementById(targetId).classList.add('active');
        });
    });
    
    // Initialize - hide all answers initially
    document.querySelectorAll('.faq-answer').forEach(answer => {
        answer.style.display = 'none';
    });
});
</script>

<?php
// Include footer
include_once '../templates/footer.php';
?>