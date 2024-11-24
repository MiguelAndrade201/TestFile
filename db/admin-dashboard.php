<?php
include 'db_connection.php';
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager' && $_SESSION['role'] !== 'editor')) {
    header("Location: login_form.php");
    exit();
}

// Get the user_id from the session (which corresponds to either 'users.id' or 'admin_users.id')
$user_id = $_SESSION['user_id'];

// Fetch user or admin data based on their role
if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager' || $_SESSION['role'] === 'editor') {
    // Query the admin_users table if the user is an admin or has an admin role
    $query = "SELECT first_name, last_name FROM admin_users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($user_first_name, $user_last_name);
    $stmt->fetch();
    $stmt->close();
    
    // If no result is found for admin, check in the users table
    if (!$user_first_name || !$user_last_name) {
        // If not found in admin_users, fall back to users table for regular users
        $query = "SELECT first_name, last_name FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($user_first_name, $user_last_name);
        $stmt->fetch();
        $stmt->close();
    }
}

// Check if user data was found
if (!$user_first_name || !$user_last_name) {
    die("User data not found for user_id: " . $user_id);
}

// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: login_form.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/admin_style.css"> 
</head>
<body>
    <!-- NAV -->
    <ul class="nav-links">
    <li><a href="admin-dashboard.php">Dashboard</a></li>
    <li class="center"><a href="admin_add_user.php">Add Users</a></li>
    <li class="upward"><a href="search_remove_user.php">Manage Users</a></li>
    <li class="forward"><a href="Add Catagory">Categories</a></li>
    <li class="forward"><a href="#">Products</a></li>
    <li class="forward"><a href="#">Delivery</a></li>
    <li class="forward"><a href="orders.php">Orders</a></li>

  </ul>

    <div class="container">
        <!-- Display user's first and last name -->
        <h1>Welcome, <?php echo htmlspecialchars($user_first_name) . ' ' . htmlspecialchars($user_last_name); ?>!</h1>
    </div>
</body>
</html>
