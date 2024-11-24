<?php
include('db_connection.php');

// Handle adding users (Admin & Regular)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'Add User') {
    // Collect form data
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone_number = $_POST['phone_number'];
    $subscribed = isset($_POST['subscribed']) ? 1 : 0; // Default to 0 if not set
    $role = isset($_POST['role']) ? $_POST['role'] : 'user';  // Default to 'user' if not selected

    // Check if passwords match
    if ($password !== $confirm_password) {
        echo "Passwords do not match!";
        exit;
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert user into users or admin_users table based on role
    if ($role === 'admin') {
        // Add as admin
        $stmt = $conn->prepare("INSERT INTO admin_users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $first_name, $last_name, $email, $hashed_password, $role);

        if ($stmt->execute()) {
            echo "Admin user added successfully!";
        } else {
            echo "Error adding admin user: " . $stmt->error; // Show error if query fails
        }
        $stmt->close();
    } else {
        // Add as regular user
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, phone_number, subscribed) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $first_name, $last_name, $email, $hashed_password, $phone_number, $subscribed);

        if ($stmt->execute()) {
            echo "Regular user added successfully!";
        } else {
            echo "Error adding regular user: " . $stmt->error; // Show error if query fails
        }
        $stmt->close();
    }
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
    <h1>Add New User</h1>
    <form action="" method="POST">
        <label for="first_name">First Name:</label><br>
        <input type="text" id="first_name" name="first_name" required><br>

        <label for="last_name">Last Name:</label><br>
        <input type="text" id="last_name" name="last_name" required><br>

        <label for="email">Email:</label><br>
        <input type="email" id="email" name="email" required><br>

        <label for="password">Password:</label><br>
        <input type="password" id="password" name="password" required><br>

        <label for="confirm_password">Confirm Password:</label><br>
        <input type="password" id="confirm_password" name="confirm_password" required><br>

        <label for="phone_number">Phone Number:</label><br>
        <input type="text" id="phone_number" name="phone_number"><br>

        <div class="checkbox-wrapper">
            <input type="checkbox" id="subscribed" name="subscribed">
            <label for="subscribed">Subscribed</label>
        </div>

        <label for="role">User Type:</label>
        <select id="role" name="role">
            <option value="user">Regular User</option>
            <option value="admin">Admin</option>
        </select><br><br>

        <input type="submit" name="action" value="Add User">
    </form>

    <div class="tables-section">
        <h1>Admin Users</h1>
        <table  >
            <thead>
                <tr>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Role</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Check if there are any admin users to display
                if ($result_admin_users->num_rows > 0) {
                    while ($row = $result_admin_users->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['first_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['last_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['role']) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>No admin users found.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <h1>Regular Users</h1>
        <table>
            <thead>
                <tr>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Subscribed</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Check if there are any regular users to display
                if ($result_regular_users->num_rows > 0) {
                    while ($row = $result_regular_users->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['first_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['last_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                        echo "<td>" . ($row['subscribed'] ? "Yes" : "No") . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>No regular users found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>

<?php
$conn->close();
?>
