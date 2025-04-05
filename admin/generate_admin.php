<?php
// Include database connection
require_once '../connect.php';

// Initialize variables
$username = '';
$email = '';
$password = '';
$hashedPassword = '';
$generationSuccess = false;
$sqlStatement = '';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    
    // Validate inputs
    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required";
    } else {
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $generationSuccess = true;
        
        // Generate SQL statement
        $sqlStatement = "INSERT INTO Users (username, password, email, name, role) VALUES ('$username', '$hashedPassword', '$email', '$username', 'admin');";
        
        // Optional: Insert directly into database
        if (isset($_POST['insert_db']) && $_POST['insert_db'] == 'yes') {
            $stmt = $conn->prepare("INSERT INTO Users (username, password, email, name, role) VALUES (?, ?, ?, ?, 'admin')");
            $stmt->bind_param("ssss", $username, $hashedPassword, $email, $username);
            
            if ($stmt->execute()) {
                $dbMessage = "Admin user successfully added to database!";
            } else {
                $dbError = "Database Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Account Generator</title>
    <link rel="stylesheet" href="../css/auth_styles.css">
    <style>
        .code-box {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin: 15px 0;
            font-family: monospace;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .db-insert-option {
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Admin Account Generator</h2>
        <p>Use this tool to generate a hashed password for an admin account.</p>
        
        <div class="warning">
            <strong>Security Note:</strong> This tool should only be used by authorized personnel. 
            Delete or restrict access to this file after creating admin accounts.
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-message" style="display:block"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($dbMessage)): ?>
            <div class="success-message"><?php echo $dbMessage; ?></div>
        <?php endif; ?>
        
        <?php if (isset($dbError)): ?>
            <div class="error-message" style="display:block"><?php echo $dbError; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="username">Admin Username: <span class="db-ref">(Users.username)</span></label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Admin Email: <span class="db-ref">(Users.email)</span></label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Admin Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="db-insert-option">
                <label>
                    <input type="checkbox" name="insert_db" value="yes"> 
                    Insert directly into database
                </label>
            </div>
            
            <button type="submit">Generate Admin Account</button>
        </form>
        
        <?php if ($generationSuccess): ?>
            <h3>Password Hash Generated</h3>
            <div class="code-box"><?php echo htmlspecialchars($hashedPassword); ?></div>
            
            <h3>SQL Statement</h3>
            <div class="code-box"><?php echo htmlspecialchars($sqlStatement); ?></div>
            
            <div class="warning">
                <p><strong>Important:</strong> Store this information securely. The original password cannot be recovered from the hash.</p>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 20px;">
            <a href="../index.php">Return to homepage</a>
        </div>
    </div>
</body>
</html>