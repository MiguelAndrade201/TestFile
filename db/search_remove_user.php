<?php

include('db_connection.php');


// Handle deleting users (Admin & Regular)
if (isset($_GET['delete']) && isset($_GET['role'])) {
    $user_id = $_GET['delete'];
    $role = $_GET['role']; // Fetch the role to decide which table to delete from

    if ($role === 'admin') {
        // Delete admin user
        $stmt = $conn->prepare("DELETE FROM admin_users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            echo "Admin user deleted successfully!";
        } else {
            echo "Error deleting admin user: " . $stmt->error;
        }
    } else {
        // Delete regular user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            echo "Regular user deleted successfully!";
        } else {
            echo "Error deleting regular user: " . $stmt->error;
        }
    }
    $stmt->close();
}

// Handle search functionality
$search_results = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect search data
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];

    // Build the SQL query based on search input
    $sql = "SELECT id, first_name, last_name, email, role FROM admin_users WHERE first_name LIKE ? OR last_name LIKE ? 
            UNION 
            SELECT id, first_name, last_name, email, 'user' as role FROM users WHERE first_name LIKE ? OR last_name LIKE ? 
            ORDER BY role DESC, first_name ASC";

    $stmt = $conn->prepare($sql);
    $search_term = "%" . $first_name . "%";
    $stmt->bind_param("ssss", $search_term, $search_term, $search_term, $search_term);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch the results
    if ($result->num_rows > 0) {
        $search_results = $result->fetch_all(MYSQLI_ASSOC);
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search and Manage Users</title>
    <style>
        /* Your existing styles can be reused or you can modify them as needed */
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            padding: 8px 12px;
            border: 1px solid #ccc;
        }

        h1 {
            color: #333;
        }

        form {
            margin-top: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            margin-bottom: 20px;
        }

        label {
            font-size: 14px;
            margin-bottom: 5px;
            display: inline-block;
        }

        input[type="text"], input[type="submit"], select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            margin-top: 5px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
            padding: 10px 20px;
        }

        input[type="submit"]:hover {
            background-color: #45a049;
        }

        .tables-section {
            margin-top: 30px;
        }
    </style>
</head>
<body>

<h1>Search and Manage Users</h1>

<!-- Search Form -->
<form action="" method="POST">
    <label for="first_name">Enter Name:</label><br>
    <input type="text" id="first_name" name="first_name" placeholder="Enter first name"><br>

    <label for="last_name">Last Name:</label><br>
    <input type="text" id="last_name" name="last_name" placeholder="Enter last name"><br><br>

    <input type="submit" value="Search Users">
</form>

<!-- Display Search Results -->
<?php if (!empty($search_results)): ?>
    <h2>Search Results</h2>
    <table>
        <thead>
            <tr>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($search_results as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['first_name']); ?></td>
                    <td><?php echo htmlspecialchars($user['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars($user['role']); ?></td>
                    <td>
                        <a href="?delete=<?php echo $user['id']; ?>&role=<?php echo $user['role']; ?>" 
                           onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <p>No users found matching your search criteria.</p>
<?php endif; ?>

</body>
</html>

<?php
$conn->close();
?>
