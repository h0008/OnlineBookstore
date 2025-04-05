<?php
// Include database connection
require_once '../connect.php';

// Initialize error variables
$usernameError = "";
$emailError = "";
$passwordError = "";
$repeatPasswordError = "";
$nameError = "";
$registrationSuccess = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $repeatPassword = $_POST["repeatPassword"];
    $name = $_POST["name"];
    $address = $_POST["address"] ?? '';
    $phone = $_POST["phone"] ?? '';
    $valid = true;
    
    // Server-side validation
    // Username validation
    if (preg_match('/[^a-zA-Z0-9_]/', $username)) {
        $usernameError = "Username cannot contain special characters";
        $valid = false;
    }
    
    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailError = "Please enter a valid email address";
        $valid = false;
    }
    
    // Name validation
    if (empty($name)) {
        $nameError = "Name is required";
        $valid = false;
    }
    
    // Password validation
    if (strlen($password) < 6 || 
        !preg_match('/[A-Z]/', $password) || 
        !preg_match('/[a-z]/', $password) || 
        !preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
        $passwordError = "Password must be at least 6 characters with uppercase, lowercase, and special character";
        $valid = false;
    }
    
    // Confirm password matches
    if ($password !== $repeatPassword) {
        $repeatPasswordError = "Passwords do not match";
        $valid = false;
    }
    
    // Check if username already exists
    $checkUsername = $conn->prepare("SELECT user_id FROM Users WHERE username = ?");
    $checkUsername->bind_param("s", $username);
    $checkUsername->execute();
    $checkUsername->store_result();
    if ($checkUsername->num_rows > 0) {
        $usernameError = "Username already exists";
        $valid = false;
    }
    $checkUsername->close();
    
    // Check if email already exists
    $checkEmail = $conn->prepare("SELECT user_id FROM Users WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $checkEmail->store_result();
    if ($checkEmail->num_rows > 0) {
        $emailError = "Email already exists";
        $valid = false;
    }
    $checkEmail->close();
    
    // If all validations pass
    if ($valid) {
        // Hash the password for security
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Prepare and bind with all fields from Users table
        $stmt = $conn->prepare("INSERT INTO Users (username, password, email, name, address, phone, role) VALUES (?, ?, ?, ?, ?, ?, 'user')");
        $stmt->bind_param("ssssss", $username, $hashedPassword, $email, $name, $address, $phone);
        
        // Execute the statement
        if ($stmt->execute()) {
            $registrationSuccess = "Registration successful! You can now <a href='login.php'>login</a>.";
        } else {
            $registrationSuccess = "Error: " . $stmt->error;
        }
        
        $stmt->close();
    }
}

// Don't close connection here - it's handled by connect.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Page</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Stick+No+Bills">
    <link rel="stylesheet" href="../css/auth_styles.css">
    <style>
        .db-ref {
            color: #666;
            font-size: 0.8em;
            font-style: italic;
        }
        .optional-field {
            color: #888;
            font-size: 0.8em;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Registration Form</h2>
        
        <?php if (!empty($registrationSuccess)) { ?>
            <div class="success-message"><?php echo $registrationSuccess; ?></div>
        <?php } ?>
        
        <form id="registrationForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="username">Username: <span class="db-ref"></span></label>
                <input type="text" id="username" name="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                <div id="usernameError" class="error-message" <?php if (!empty($usernameError)) echo 'style="display: block"'; ?>>
                    <?php echo !empty($usernameError) ? $usernameError : 'Username cannot contain special characters'; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email: <span class="db-ref"></span></label>
                <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                <div id="emailError" class="error-message" <?php if (!empty($emailError)) echo 'style="display: block"'; ?>>
                    <?php echo !empty($emailError) ? $emailError : 'Please enter a valid email address'; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label for="name">Full Name: <span class="db-ref"></span></label>
                <input type="text" id="name" name="name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                <div id="nameError" class="error-message" <?php if (!empty($nameError)) echo 'style="display: block"'; ?>>
                    <?php echo !empty($nameError) ? $nameError : 'Name is required'; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label for="address">Address: <span class="db-ref"></span><span class="optional-field">(Optional)</span></label>
                <textarea id="address" name="address" rows="3"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone: <span class="db-ref"></span><span class="optional-field">(Optional)</span></label>
                <input type="tel" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password: <span class="db-ref"></span></label>
                <input type="password" id="password" name="password" required>
                <div class="password-strength-container">
                    <div class="password-strength-bar">
                        <div id="strengthBar" class="strength-value"></div>
                    </div>
                </div>
                <div id="passwordError" class="error-message" <?php if (!empty($passwordError)) echo 'style="display: block"'; ?>>
                    <?php echo !empty($passwordError) ? $passwordError : 'Password must be at least 6 characters with uppercase, lowercase, and special character'; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label for="repeatPassword">Repeat Password:</label>
                <input type="password" id="repeatPassword" name="repeatPassword" required>
                <div id="repeatPasswordError" class="error-message" <?php if (!empty($repeatPasswordError)) echo 'style="display: block"'; ?>>
                    <?php echo !empty($repeatPasswordError) ? $repeatPasswordError : 'Passwords do not match'; ?>
                </div>
            </div>
            
            <button type="submit">Register</button>
            
            <div class="login-link">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Client-side password strength visualization
            const password = document.getElementById('password');
            const strengthBar = document.getElementById('strengthBar');
            
            password.addEventListener('input', function() {
                updatePasswordStrength();
            });
            
            function updatePasswordStrength() {
                const passwordValue = password.value;
                let strength = 0;
                
                // Calculate strength based on requirements
                if (passwordValue.length >= 6) strength += 25;
                if (/[A-Z]/.test(passwordValue)) strength += 25;
                if (/[a-z]/.test(passwordValue)) strength += 25;
                if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(passwordValue)) strength += 25;
                
                // Update strength bar
                strengthBar.style.width = strength + '%';
                
                // Change color based on strength
                if (strength < 50) {
                    strengthBar.style.backgroundColor = '#ff4d4d'; // Weak (red)
                } else if (strength < 100) {
                    strengthBar.style.backgroundColor = '#ffdd57'; // Medium (yellow)
                } else {
                    strengthBar.style.backgroundColor = '#23d160'; // Strong (green)
                }
            }
        });
    </script>
</body>
</html>