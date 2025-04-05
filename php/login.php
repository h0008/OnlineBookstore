<?php
session_start();

// Include database connection
require_once '../connect.php';

// Initialize error variables
$loginError = "";
$usernameError = "";
$passwordError = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];
    
    // Validate input exists
    if (empty($username)) {
        $usernameError = "Username is required";
    }
    
    if (empty($password)) {
        $passwordError = "Password is required";
    }
    
    // If no empty fields
    if (empty($usernameError) && empty($passwordError)) {
        // Prepare SQL statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT user_id, username, password, role FROM Users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Password is correct, create session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                
                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: ../admin/dashboard.php");
                } else {
                    header("Location: homepage.php");
                }
                exit();
            } else {
                // Password is incorrect
                $passwordError = "Invalid password";
            }
        } else {
            // User not found
            $usernameError = "User not found";
        }
        
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Stick+No+Bills">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/auth_styles.css">
    <style>
        .db-ref {
            color: #666;
            font-size: 0.8em;
            font-style: italic;
        }
        .password-field {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
        }
        .password-toggle:hover {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <?php if (!empty($loginError)) { ?>
            <div class="error-message" style="display:block"><?php echo $loginError; ?></div>
        <?php } ?>
        
        <form id="loginForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="username">Username: <span class="db-ref"></span></label>
                <input type="text" id="username" name="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                <div id="usernameError" class="error-message" <?php if (!empty($usernameError)) echo 'style="display: block"'; ?>>
                    <?php echo !empty($usernameError) ? $usernameError : 'Invalid username'; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password: <span class="db-ref"></span></label>
                <div class="password-field">
                    <input type="password" id="password" name="password" required>
                    <span class="password-toggle" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
                <div id="passwordError" class="error-message" <?php if (!empty($passwordError)) echo 'style="display: block"'; ?>>
                    <?php echo !empty($passwordError) ? $passwordError : 'Invalid password'; ?>
                </div>
            </div>
            
            <button type="submit">Login</button>
            
            <div class="register-link">
                Don't have an account? <a href="register.php">Register here</a>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const password = document.getElementById('password');
            
            togglePassword.addEventListener('click', function() {
                // Toggle password visibility
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                
                // Toggle eye icon
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
        });
    </script>
</body>
</html>