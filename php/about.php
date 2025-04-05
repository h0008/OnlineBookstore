<?php
// filepath: d:\XAMPP\htdocs\OnlineBookstore\php\about.php
session_start();
require_once '../connect.php';

// Check if user is logged in
$loggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$username = $loggedIn ? $_SESSION['username'] : '';
$isAdmin = $loggedIn && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$userId = $loggedIn ? $_SESSION['user_id'] : 0;

// Set page title
$pageTitle = "About Us";

// Include header
include_once '../templates/header.php';
?>

<div class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>About Haitchal Books</h1>
        </div>
        
        <div class="about-section">
            <div class="about-image">
                <img src="../images/bookstore.jpg" alt="Haitchal Books Store" onerror="this.src='../images/placeholder-store.jpg'">
            </div>
            
            <div class="about-content">
                <h2>Our Story</h2>
                <p>Founded in 2025, Haitchal Books began as a small independent bookstore with a passion for connecting readers with stories that inspire, entertain, and educate.</p>
                
                <p>What started as a corner shop has now grown into an online destination for book lovers across the country, while maintaining our commitment to personalized service and curated selections.</p>
                
                <p>At Haitchal Books, we believe that books have the power to change lives. Our mission is to make reading accessible to everyone, to promote diverse voices, and to create a community where ideas and stories can be shared.</p>
                
                <h2>Our Values</h2>
                <ul>
                    <li><strong>Quality</strong>: We carefully curate our selection to offer only the best in literature across all genres.</li>
                    <li><strong>Community</strong>: We actively engage with readers through events, book clubs, and social media.</li>
                    <li><strong>Diversity</strong>: We celebrate and promote diverse authors and perspectives.</li>
                    <li><strong>Sustainability</strong>: We are committed to environmentally responsible practices in our operations.</li>
                    <li><strong>Knowledge</strong>: We believe in the power of reading to educate and transform.</li>
                </ul>
                
                <h2>Meet Our Team</h2>
                <div class="team-members">
                    <div class="team-member">
                        <h3>Duy</h3>
                        <p class="title">Founder & CEO</p>
                        <p>Book lover, entrepreneur, and former librarian with a vision to make quality literature accessible to all.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .about-section {
        display: flex;
        flex-direction: column;
        gap: 30px;
    }
    
    .about-image {
        margin-bottom: 20px;
    }
    
    .about-image img {
        width: 100%;
        max-height: 400px;
        object-fit: cover;
        border-radius: 8px;
    }
    
    .about-content h2 {
        color: var(--primary-color);
        margin: 25px 0 15px;
    }
    
    .about-content p {
        line-height: 1.6;
        margin-bottom: 15px;
    }
    
    .about-content ul {
        padding-left: 20px;
        margin-bottom: 25px;
    }
    
    .about-content li {
        margin-bottom: 10px;
        line-height: 1.5;
    }
    
    .team-members {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 25px;
        margin-top: 20px;
    }
    
    .team-member {
        background-color: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
    }
    
    .team-member h3 {
        margin-bottom: 5px;
        color: var(--primary-color);
    }
    
    .team-member .title {
        font-weight: bold;
        margin-bottom: 10px;
        color: #666;
    }
    
    @media (min-width: 768px) {
        .about-section {
            flex-direction: row;
        }
        
        .about-image {
            flex: 0 0 40%;
            margin-bottom: 0;
        }
        
        .about-content {
            flex: 0 0 60%;
            padding-left: 30px;
        }
    }
</style>

<?php
// Include footer
include_once '../templates/footer.php';
?>