<?php
// filepath: d:\XAMPP\htdocs\OnlineBookstore\php\contact.php
session_start();
require_once '../connect.php';

// Check if user is logged in
$loggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$username = $loggedIn ? $_SESSION['username'] : '';
$isAdmin = $loggedIn && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$userId = $loggedIn ? $_SESSION['user_id'] : 0;

// Set page title
$pageTitle = "Contact Us";

// Process form submission
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    // Simple validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $errorMessage = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Please enter a valid email address.";
    } else {
        // In a real application, you would save this to a database
        // For now, we'll just simulate success
        $successMessage = "Thank you for your message! We'll get back to you soon.";
    }
}

// Include header
include_once '../templates/header.php';
?>

<div class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>Contact Us</h1>
            <p>We'd love to hear from you. Fill out the form below to get in touch with our team.</p>
        </div>
        
        <div class="contact-container">
            <div class="contact-info">
                <h2>Get in Touch</h2>
                <p>Have questions, suggestions, or feedback? We're here to help!</p>
                
                <div class="info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <div>
                        <h3>Address</h3>
                        <p>123 Book Avenue, Reading District<br>New York, NY 10001</p>
                    </div>
                </div>
                
                <div class="info-item">
                    <i class="fas fa-phone"></i>
                    <div>
                        <h3>Phone</h3>
                        <p>Customer Service: (555) 123-4567<br>Order Status: (555) 987-6543</p>
                    </div>
                </div>
                
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <div>
                        <h3>Email</h3>
                        <p>Customer Support: support@haitchalbooks.com<br>Orders: orders@haitchalbooks.com</p>
                    </div>
                </div>
                
                <div class="info-item">
                    <i class="fas fa-clock"></i>
                    <div>
                        <h3>Business Hours</h3>
                        <p>Monday - Friday: 9:00 AM - 6:00 PM<br>Saturday: 10:00 AM - 4:00 PM<br>Sunday: Closed</p>
                    </div>
                </div>
                
                <div class="social-links">
                    <h3>Connect With Us</h3>
                    <div class="social-icons">
                        <a href="#" target="_blank"><i class="fab fa-facebook"></i></a>
                        <a href="#" target="_blank"><i class="fab fa-twitter"></i></a>
                        <a href="#" target="_blank"><i class="fab fa-instagram"></i></a>
                        <a href="#" target="_blank"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="contact-form">
                <h2>Send Us a Message</h2>
                
                <?php if (!empty($successMessage)): ?>
                    <div class="success-message">
                        <?php echo $successMessage; ?>
                    </div>
                <?php elseif (!empty($errorMessage)): ?>
                    <div class="error-message">
                        <?php echo $errorMessage; ?>
                    </div>
                <?php endif; ?>
                
                <form action="contact.php" method="post">
                    <div class="form-group">
                        <label for="name">Your Name*</label>
                        <input type="text" id="name" name="name" required 
                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address*</label>
                        <input type="email" id="email" name="email" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject*</label>
                        <input type="text" id="subject" name="subject" required
                               value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message*</label>
                        <textarea id="message" name="message" rows="5" required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="contact_submit" class="submit-btn">Send Message</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="map-container">
            <h2>Find Us</h2>
            <div class="map">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d193595.15830869428!2d-74.119763973046!3d40.69766374874431!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c24fa5d33f083b%3A0xc80b8f06e177fe62!2sNew%20York%2C%20NY%2C%20USA!5e0!3m2!1sen!2s!4v1632139095590!5m2!1sen!2s" 
                        width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
            </div>
        </div>
    </div>
</div>

<style>
    .contact-container {
        display: flex;
        flex-direction: column;
        gap: 30px;
        margin-bottom: 40px;
    }
    
    .contact-info,
    .contact-form {
        background-color: #fff;
        border-radius: 8px;
        padding: 30px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .contact-info h2,
    .contact-form h2,
    .map-container h2 {
        color: var(--primary-color);
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .info-item {
        display: flex;
        margin-bottom: 25px;
    }
    
    .info-item i {
        font-size: 24px;
        color: var(--primary-color);
        margin-right: 20px;
        margin-top: 5px;
    }
    
    .info-item h3 {
        margin-bottom: 5px;
        font-size: 1.1rem;
        color: #444;
    }
    
    .social-links h3 {
        margin-bottom: 15px;
        font-size: 1.1rem;
        color: #444;
    }
    
    .social-icons {
        display: flex;
        gap: 15px;
    }
    
    .social-icons a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        background-color: var(--primary-color);
        color: white;
        border-radius: 50%;
        transition: background-color 0.3s, transform 0.3s;
    }
    
    .social-icons a:hover {
        background-color: var(--secondary-color);
        transform: translateY(-3px);
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
        color: #444;
    }
    
    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 16px;
        transition: border-color 0.3s;
    }
    
    .form-group input:focus,
    .form-group textarea:focus {
        border-color: var(--primary-color);
        outline: none;
    }
    
    .submit-btn {
        background-color: var(--primary-color);
        color: white;
        border: none;
        padding: 12px 25px;
        font-size: 16px;
        font-weight: bold;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.3s;
    }
    
    .submit-btn:hover {
        background-color: var(--secondary-color);
    }
    
    .success-message,
    .error-message {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
    
    .success-message {
        background-color: #e8f5e9;
        color: #2e7d32;
        border: 1px solid #a5d6a7;
    }
    
    .error-message {
        background-color: #ffebee;
        color: #c62828;
        border: 1px solid #ef9a9a;
    }
    
    .map-container {
        margin-bottom: 40px;
    }
    
    .map {
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    @media (min-width: 768px) {
        .contact-container {
            flex-direction: row;
        }
        
        .contact-info {
            flex: 0 0 35%;
        }
        
        .contact-form {
            flex: 0 0 65%;
        }
    }
</style>

<?php
// Include footer
include_once '../templates/footer.php';
?>