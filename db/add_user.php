<?php
include('db_connection.php');

// Handle adding users (Admin & Regular)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_user') {
    // Collect form data
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $phone_number = $_POST['phone_number'];
    $subscribed = isset($_POST['subscribed']) ? 1 : 0; // Default to 0 if not set
    $role = isset($_POST['role']) ? $_POST['role'] : null;

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert user into users or admin_users table based on role
    if ($role === 'admin') {
        // Add as admin
        $stmt = $conn->prepare("INSERT INTO admin_users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $first_name, $last_name, $email, $hashed_password, $role);
        $stmt->execute();
        $stmt->close();
    } else {
        // Add as regular user
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, phone_number, subscribed) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $first_name, $last_name, $email, $hashed_password, $phone_number, $subscribed);
        $stmt->execute();
        $stmt->close();
    }

    // Optional: Log the action in audit_logs (Admin performing the action)
    $admin_id = 1; // This should be the logged-in admin's ID (example: hardcoded for now)
    $action = "Added a new user";
    $target_table = $role === 'admin' ? "admin_users" : "users";
    $target_id = $conn->insert_id;

    $log_stmt = $conn->prepare("INSERT INTO audit_logs (admin_id, action, target_table, target_id) VALUES (?, ?, ?, ?)");
    $log_stmt->bind_param("issi", $admin_id, $action, $target_table, $target_id);
    $log_stmt->execute();
    $log_stmt->close();

    echo "User added successfully!";
}

// Handle deleting a user
if (isset($_GET['delete_user_id'])) {
    $user_id = $_GET['delete_user_id'];

    // Check if the user is from the admin_users table or users table
    $stmt = $conn->prepare("SELECT role FROM admin_users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows > 0) {
        // Admin user found, delete from admin_users
        $stmt = $conn->prepare("DELETE FROM admin_users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    } else {
        // Regular user found, delete from users
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }

    // Optional: Log the delete action in audit_logs
    $admin_id = 1; // This should be the logged-in admin's ID
    $action = "Deleted a user";
    $target_table = $result->num_rows > 0 ? "admin_users" : "users";
    $target_id = $user_id;

    $log_stmt = $conn->prepare("INSERT INTO audit_logs (admin_id, action, target_table, target_id) VALUES (?, ?, ?, ?)");
    $log_stmt->bind_param("issi", $admin_id, $action, $target_table, $target_id);
    $log_stmt->execute();
    $log_stmt->close();

    echo "User deleted successfully!";
}

// Fetch admin users
$sql_admin_users = "
    SELECT id, first_name, last_name, email, role
    FROM admin_users
    ORDER BY first_name ASC;
";
$result_admin_users = $conn->query($sql_admin_users);

// Fetch regular users
$sql_regular_users = "
    SELECT id, first_name, last_name, email, subscribed
    FROM users
    ORDER BY first_name ASC;
";
$result_regular_users = $conn->query($sql_regular_users);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users and Admins</title>
</head>
<body>

<h1>Admin Users</h1>
<table border="1">
    <thead>
        <tr>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if ($result_admin_users->num_rows > 0) {
            while ($row = $result_admin_users->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['first_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['last_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                echo "<td>" . htmlspecialchars($row['role']) . "</td>";
                echo "<td><a href='?delete_user_id=" . $row['id'] . "' onclick='return confirm(\"Are you sure you want to delete this admin?\");'>Delete</a></td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='5'>No admin users found.</td></tr>";
        }
        ?>
    </tbody>
</table>

<h1>Regular Users</h1>
<table border="1">
    <thead>
        <tr>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Email</th>
            <th>Subscribed</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if ($result_regular_users->num_rows > 0) {
            while ($row = $result_regular_users->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['first_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['last_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                echo "<td>" . ($row['subscribed'] ? "Yes" : "No") . "</td>";
                echo "<td><a href='?delete_user_id=" . $row['id'] . "' onclick='return confirm(\"Are you sure you want to delete this user?\");'>Delete</a></td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='5'>No regular users found.</td></tr>";
        }
        ?>
    </tbody>
</table>

<h1>Add New User</h1>
<form action="" method="POST">
    <input type="hidden" name="action" value="add_user">
    <label for="first_name">First Name:</label><br>
    <input type="text" id="first_name" name="first_name" required><br>

    <label for="last_name">Last Name:</label><br>
    <input type="text" id="last_name" name="last_name" required><br>

    <label for="email">Email:</label><br>
    <input type="email" id="email" name="email" required><br>

    <label for="password">Password:</label><br>
    <input type="password" id="password" name="password" required><br>

    <label for="phone_number">Phone Number:</label><br>
    <input type="text" id="phone_number" name="phone_number"><br>

    <label for="subscribed">Subscribed:</label>
    <input type="checkbox" id="subscribed" name="subscribed"><br>

    <label for="role">User Type:</label>
    <select id="role" name="role">
        <option value="user">Regular User</option>
        <option value="admin">Admin</option>
    </
