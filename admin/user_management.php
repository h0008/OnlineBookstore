<?php
session_start();
require_once '../connect.php';

// Initialize message variables
$successMessage = '';
$errorMessage = '';

// Check for success message in URL
if (isset($_GET['success'])) {
    $successMessage = $_GET['success'];
}

// Check if user is logged in and is an admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../php/login.php");
    exit();
}

// Get counts for dashboard statistics
$userCount = $conn->query("SELECT COUNT(*) as count FROM Users")->fetch_assoc()['count'];
$bookCount = $conn->query("SELECT COUNT(*) as count FROM Books")->fetch_assoc()['count'];
$categoryCount = $conn->query("SELECT COUNT(*) as count FROM Categories")->fetch_assoc()['count'];

// Get recent users
$recentUsers = $conn->query("SELECT user_id, username, email, role, name, address, phone, registration_date FROM Users ORDER BY registration_date DESC LIMIT 5");

// Get recent books
$recentBooks = $conn->query("SELECT b.book_id, b.title, b.author, b.price, c.category_name 
                             FROM Books b 
                             JOIN Categories c ON b.category_id = c.category_id 
                             ORDER BY b.book_id DESC LIMIT 5");

// Get the current admin's user ID
$currentAdminId = $_SESSION['user_id'];

// Message variables
$successMessage = '';
$errorMessage = '';

// Process user deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $userId = $_GET['delete'];
    
    // Check if admin is trying to delete their own account
    if ($userId == $currentAdminId) {
        $errorMessage = "You cannot delete your own admin account.";
    } else {
        // Delete the user
        $stmt = $conn->prepare("DELETE FROM Users WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        
        if ($stmt->execute()) {
            $successMessage = "User deleted successfully.";
            // Add this redirect
            header("Location: user_management.php?success=" . urlencode($successMessage));
            exit();
        } else {
            $errorMessage = "Error deleting user: " . $conn->error;
        }
        $stmt->close();
    }
}

// Process user addition/update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_user']) || isset($_POST['update_user'])) {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $name = $_POST['name'];
        $role = $_POST['role'];
        $address = $_POST['address'] ?? '';
        $phone = $_POST['phone'] ?? '';
        
        // For update
        if (isset($_POST['update_user'])) {
            $userId = $_POST['user_id'];
            
            // Check if admin is trying to change their own role
            if ($userId == $currentAdminId && $role != 'admin') {
                $errorMessage = "You cannot change your own admin role.";
            } else {
                // If password is provided, update it
                if (!empty($_POST['password'])) {
                    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE Users SET username=?, email=?, name=?, role=?, address=?, phone=?, password=? WHERE user_id=?");
                    $stmt->bind_param("sssssssi", $username, $email, $name, $role, $address, $phone, $password, $userId);
                } else {
                    // Don't update password
                    $stmt = $conn->prepare("UPDATE Users SET username=?, email=?, name=?, role=?, address=?, phone=? WHERE user_id=?");
                    $stmt->bind_param("ssssssi", $username, $email, $name, $role, $address, $phone, $userId);
                }
                
                if ($stmt->execute()) {
                    $successMessage = "User updated successfully.";
                    // Add this redirect
                    header("Location: user_management.php?success=" . urlencode($successMessage));
                    exit();
                } else {
                    $errorMessage = "Error updating user: " . $conn->error;
                }
                $stmt->close();
            }
        } 
        // For add
        else {
            // Password is required for new users
            if (empty($_POST['password'])) {
                $errorMessage = "Password is required for new users.";
            } else {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                
                // Check if username already exists
                $checkUsername = $conn->prepare("SELECT user_id FROM Users WHERE username = ?");
                $checkUsername->bind_param("s", $username);
                $checkUsername->execute();
                $checkUsername->store_result();
                
                if ($checkUsername->num_rows > 0) {
                    $errorMessage = "Username already exists.";
                } else {
                    $checkUsername->close();
                    
                    // Check if email already exists
                    $checkEmail = $conn->prepare("SELECT user_id FROM Users WHERE email = ?");
                    $checkEmail->bind_param("s", $email);
                    $checkEmail->execute();
                    $checkEmail->store_result();
                    
                    if ($checkEmail->num_rows > 0) {
                        $errorMessage = "Email already exists.";
                    } else {
                        $checkEmail->close();
                        
                        // Add the new user
                        $stmt = $conn->prepare("INSERT INTO Users (username, password, email, name, role, address, phone) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("sssssss", $username, $password, $email, $name, $role, $address, $phone);
                        
                        if ($stmt->execute()) {
                            $successMessage = "User added successfully.";
                            // Add this redirect
                            header("Location: user_management.php?success=" . urlencode($successMessage));
                            exit();
                        } else {
                            $errorMessage = "Error adding user: " . $conn->error;
                        }
                        $stmt->close();
                    }
                }
            }
        }
    }
}

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchCondition = '';
$searchParam = '';

if (!empty($search)) {
    $searchCondition = "WHERE username LIKE ? OR email LIKE ? OR name LIKE ?";
    $searchParam = "%$search%";
}

// Count total users for pagination
if (!empty($searchCondition)) {
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM Users $searchCondition");
    $countStmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
} else {
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM Users");
}
$countStmt->execute();
$totalResult = $countStmt->get_result();
$totalUsers = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalUsers / $limit);
$countStmt->close();

// Get users
if (!empty($searchCondition)) {
    $stmt = $conn->prepare("SELECT * FROM Users $searchCondition ORDER BY user_id DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("sssii", $searchParam, $searchParam, $searchParam, $limit, $offset);
} else {
    $stmt = $conn->prepare("SELECT * FROM Users ORDER BY user_id DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$users = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Haitchal Books</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Stick+No+Bills">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin_styles.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --light-color: #ecf0f1;
            --dark-color: #34495e;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            margin: 0;
            padding: 0;
        }

        /* Update container class */
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar styles */
        .sidebar {
            width: 250px;
            background-color: var(--secondary-color);
            color: white;
            padding: 20px 0;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            font-family: 'Stick No Bills', sans-serif;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar li {
            margin-bottom: 5px;
        }

        .sidebar a {
            display: block;
            padding: 10px 20px;
            color: #ecf0f1;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .sidebar a:hover, .sidebar a.active {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Content area */
        .content {
            flex: 1;
            padding: 20px;
            background-color: #f5f5f5;
        }

        /* Header styles */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 15px 0;
            border-bottom: 1px solid #ddd;
        }

        /* Button styles */
        .btn {
            display: inline-block;
            padding: 8px 15px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 3px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #2980b9;
        }

        .btn-success {
            background-color: var(--success-color);
        }

        .btn-success:hover {
            background-color: #27ae60;
        }

        .btn-danger {
            background-color: var(--danger-color);
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }

        /* Alert styles */
        .message, .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 3px;
        }

        .message.success, .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .message.error, .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Card and section styles */
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: white;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
        }

        .recent-section, .panel {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .recent-section h2, .panel-header {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 0;
        }

        .recent-section h2 {
            font-size: 18px;
        }

        .panel-body, .recent-section > *:not(h2) {
            padding: 15px;
        }

        /* Table styles */
        .data-table, .table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th, .data-table td,
        .table th, .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .data-table th, .table th {
            background-color: #f9f9f9;
            font-weight: 600;
        }

        .data-table tr:hover, .table tr:hover {
            background-color: #f9f9f9;
        }

        /* Form styles */
        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .form-group input, .form-group select, .form-group textarea,
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 3px;
            box-sizing: border-box;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            border-radius: 5px;
            width: 50%;
            max-width: 700px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        /* Sidebar specific styles */
        .sidebar {
            width: 250px;
            background-color: var(--secondary-color);
            color: white;
            padding: 20px 0;
        }

        .logo {
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
            font-family: 'Stick No Bills', sans-serif;
        }

        .logo h1 {
            font-size: 24px;
            margin: 0;
        }

        .logo span {
            display: block;
            font-size: 14px;
            opacity: 0.7;
        }

        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-item {
            margin-bottom: 5px;
        }

        .nav-link {
            display: block;
            padding: 10px 20px;
            color: #ecf0f1;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .nav-link:hover, .nav-link.active {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Consistent card styling */
        .panel, .recent-section {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .panel-header, .recent-section h2 {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 0;
            font-size: 18px;
        }

        .panel-body {
            padding: 15px;
        }

        /* Make message styling match alert */
        .message, .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 3px;
        }

        .message.success, .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .message.error, .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Update stat cards */
        .stat-card {
            background-color: white;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
        }

        .stat-card i {
            font-size: 30px;
            margin-right: 20px;
            width: 50px;
            height: 50px;
            background-color: var(--light-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Fix for the container class */
        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Update main content styling */
        .main-content {
            flex: 1;
            padding: 20px;
            background-color: #f5f5f5;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <div class="logo">
                <h1>Haitchal Books</h1>
                <span>Admin Panel</span>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="user_management.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'user_management.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> User Management
                    </a>
                </li>
                <li class="nav-item">
                    <a href="product_management.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'product_management.php' ? 'active' : ''; ?>">
                        <i class="fas fa-book"></i> Product Management
                    </a>
                </li>
                <li class="nav-item">
                    <a href="category_management.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'category_management.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tags"></i> Categories
                    </a>
                </li>
                <li class="nav-item">
                    <a href="order_management.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'order_management.php' ? 'active' : ''; ?>">
                        <i class="fas fa-shopping-cart"></i> Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../php/homepage.php" class="nav-link">
                        <i class="fas fa-home"></i> Visit Store
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../php/logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <h1><?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'Dashboard' : 'User Management'; ?></h1>
                <?php if (basename($_SERVER['PHP_SELF']) == 'user_management.php'): ?>
                <a href="#" class="btn btn-success" onclick="openAddModal(); return false;">
                    <i class="fas fa-plus"></i> Add New User
                </a>
                <?php endif; ?>
            </div>

            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success"><?php echo $successMessage; ?></div>
            <?php endif; ?>

            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="icon books">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="info">
                        <h3><?php echo $userCount; ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="recent-section">
                <h2>Recently Registered Users</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Registration Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $recentUsers->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $user['user_id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($user['registration_date'])); ?></td>
                            <td class="actions">
                                <button class="btn" onclick="openEditModal(<?php 
                                    echo htmlspecialchars(json_encode([
                                        'id' => $user['user_id'],
                                        'username' => $user['username'],
                                        'email' => $user['email'],
                                        'name' => $user['name'] ?? '',
                                        'role' => $user['role'],
                                        'address' => $user['address'] ?? '',
                                        'phone' => $user['phone'] ?? ''
                                    ])); 
                                ?>)"><i class="fas fa-edit"></i> Edit</button>
                                <?php if ($user['user_id'] != $currentAdminId): ?>
                                    <a href="user_management.php?delete=<?php echo $user['user_id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this user?')"><i class="fas fa-trash"></i> Delete</a>
                                <?php else: ?>
                                    <button class="btn btn-danger" disabled title="You cannot delete your own account"><i class="fas fa-trash"></i> Delete</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <a href="user_management.php" class="btn view-all">View All Users</a>
            </div>

            <!-- Recent Books -->
            <div class="recent-section">
                <h2>Recently Added Books</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Price</th>
                            <th>Category</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($book = $recentBooks->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $book['book_id']; ?></td>
                            <td><?php echo htmlspecialchars($book['title']); ?></td>
                            <td><?php echo htmlspecialchars($book['author']); ?></td>
                            <td>$<?php echo number_format($book['price'], 2); ?></td>
                            <td><?php echo htmlspecialchars($book['category_name']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <a href="product_management.php" class="btn view-all">View All Books</a>
            </div>

            <!-- Add User Modal -->
            <div id="addUserModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Add New User</h2>
                        <span class="close" onclick="closeAddModal()">&times;</span>
                    </div>
                    <form action="user_management.php" method="post">
                        <div class="form-group">
                            <label for="username">Username *</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password *</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label for="name">Full Name *</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="role">Role *</label>
                            <select id="role" name="role" required>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="text" id="phone" name="phone">
                        </div>
                        <button type="submit" name="add_user" class="btn btn-success">Add User</button>
                    </form>
                </div>
            </div>

            <!-- Edit User Modal -->
            <div id="editUserModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Edit User</h2>
                        <span class="close" onclick="closeEditModal()">&times;</span>
                    </div>
                    <form action="user_management.php" method="post">
                        <input type="hidden" id="edit_user_id" name="user_id">
                        <div class="form-group">
                            <label for="edit_username">Username *</label>
                            <input type="text" id="edit_username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_email">Email *</label>
                            <input type="email" id="edit_email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_password">Password (leave blank to keep unchanged)</label>
                            <input type="password" id="edit_password" name="password">
                        </div>
                        <div class="form-group">
                            <label for="edit_name">Full Name *</label>
                            <input type="text" id="edit_name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_role">Role *</label>
                            <select id="edit_role" name="role" required>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_address">Address</label>
                            <textarea id="edit_address" name="address" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="edit_phone">Phone</label>
                            <input type="text" id="edit_phone" name="phone">
                        </div>
                        <button type="submit" name="update_user" class="btn btn-success">Update User</button>
                    </form>
                </div>
            </div>

            <script>
                // Modal functions
                function openAddModal() {
                    document.getElementById('addUserModal').style.display = 'block';
                }
                
                function closeAddModal() {
                    document.getElementById('addUserModal').style.display = 'none';
                }
                
                function openEditModal(userData) {
                    // Fill form with user data
                    document.getElementById('edit_user_id').value = userData.id;
                    document.getElementById('edit_username').value = userData.username;
                    document.getElementById('edit_email').value = userData.email;
                    document.getElementById('edit_name').value = userData.name;
                    document.getElementById('edit_role').value = userData.role;
                    document.getElementById('edit_address').value = userData.address || '';
                    document.getElementById('edit_phone').value = userData.phone || '';
                    
                    // Show modal
                    document.getElementById('editUserModal').style.display = 'block';
                }
                
                function closeEditModal() {
                    document.getElementById('editUserModal').style.display = 'none';
                }
                
                // Close modals when clicking outside
                window.onclick = function(event) {
                    if (event.target === document.getElementById('addUserModal')) {
                        closeAddModal();
                    }
                    if (event.target === document.getElementById('editUserModal')) {
                        closeEditModal();
                    }
                }
            </script>
        </div>
    </div>
</body>
</html>