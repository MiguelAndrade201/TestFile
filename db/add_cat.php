<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$dbname = 'cakeaway_db';
$username = 'root';
$password = 'root';

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$name = $show = "";
$success = $error = "";
$image_path = "";
$selected_delivery_options = [];

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $show = isset($_POST['show']) ? 1 : 0;
    $selected_delivery_options = $_POST['delivery_options'] ?? []; // Array of selected delivery options

    // Validate required fields
    if (empty($name)) {
        $error = "Category name is required.";
    } elseif (empty($selected_delivery_options)) {
        $error = "At least one delivery option must be selected.";
    } else {
        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "../images/"; // Ensure this folder exists and is writable
            $file_name = uniqid() . "_" . basename($_FILES['image']['name']);
            $target_file = $target_dir . $file_name;

            // Check file type
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($imageFileType, $allowed_types)) {
                $error = "Only JPG, JPEG, PNG & GIF files are allowed.";
            } elseif (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $error = "There was an error uploading the image.";
            } else {
                $image_path = $target_file;
            }
        } else {
            $error = "Image upload failed. Please try again.";
        }

        // Insert data into the database if no errors
        if (empty($error)) {
            $conn->begin_transaction(); // Begin transaction
            try {
                // Insert the category
                $stmt = $conn->prepare("INSERT INTO categories (name, image_path, is_visible) VALUES (?, ?, ?)");
                $stmt->bind_param("ssi", $name, $image_path, $show);

                if ($stmt->execute()) {
                    $category_id = $stmt->insert_id; // Get the inserted category ID

                    // Insert selected delivery options
                    $delivery_stmt = $conn->prepare("INSERT INTO category_delivery_options (category_id, delivery_option_id) VALUES (?, ?)");
                    foreach ($selected_delivery_options as $delivery_option_id) {
                        $delivery_stmt->bind_param("ii", $category_id, $delivery_option_id);
                        $delivery_stmt->execute();
                    }

                    $conn->commit(); // Commit the transaction
                    $success = "Category added successfully!";

                    // Clear form data after successful submission
                    $name = $show = "";
                    $image_path = "";
                    $selected_delivery_options = [];

                } else {
                    throw new Exception("Error executing category insertion: " . $stmt->error);
                }

                $stmt->close(); // Close category insertion statement
                $delivery_stmt->close(); // Close delivery options statement

            } catch (Exception $e) {
                $conn->rollback(); // Rollback transaction on error
                $error = $e->getMessage();
            }
        }
    }
}

// Handle delete category
if (isset($_GET['delete'])) {
    $category_id = $_GET['delete'];

    // Delete from category_delivery_options first to maintain referential integrity
    $stmt = $conn->prepare("DELETE FROM category_delivery_options WHERE category_id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $stmt->close();

    // Now delete from categories
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $stmt->close();

    echo "Category and its delivery options have been deleted!";
}

// Fetch available delivery options
$delivery_options = [];
$sql_delivery = "SELECT id, name FROM delivery_options";
$result_delivery = $conn->query($sql_delivery);
if ($result_delivery->num_rows > 0) {
    while ($row = $result_delivery->fetch_assoc()) {
        $delivery_options[] = $row;
    }
}

// Fetch all categories and their delivery options
$categories = [];
$sql = "
    SELECT c.id AS category_id, c.name AS category_name, c.image_path, cd.delivery_option_id, do.name AS delivery_option_name
    FROM categories c
    LEFT JOIN category_delivery_options cd ON c.id = cd.category_id
    LEFT JOIN delivery_options do ON cd.delivery_option_id = do.id;
";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

$conn->close(); // Close the database connection
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories and Delivery Options</title>
</head>
<body>
    <h1>Categories and Delivery Options</h1>

    <!-- Form for adding new category -->
    <h2>Add New Category</h2>
    <form method="POST" enctype="multipart/form-data">
        <?php if ($success): ?>
            <p style="color: green;"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p style="color: red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <label for="name">Category Name:</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($name) ?>" required><br><br>

        <label for="image">Image:</label>
        <input type="file" id="image" name="image" required><br><br>

        <label for="show">Visible:</label>
        <input type="checkbox" id="show" name="show" <?= $show ? 'checked' : '' ?>><br><br>

        <label for="delivery_options">Select Delivery Options:</label><br>
        <?php foreach ($delivery_options as $option): ?>
            <input type="checkbox" name="delivery_options[]" value="<?= $option['id'] ?>" 
                <?= in_array($option['id'], $selected_delivery_options) ? 'checked' : '' ?>>
            <?= htmlspecialchars($option['name']) ?><br>
        <?php endforeach; ?><br>

        <input type="submit" value="Add Category">
    </form>

    <h2>Existing Categories</h2>
    <table border="1">
        <thead>
            <tr>
                <th>Category Name</th>
                <th>Image</th>
                <th>Delivery Options</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($categories) > 0): ?>
                <?php
                $current_category_id = null;
                $current_category_name = "";
                $current_category_image = "";
                $delivery_options = [];

                foreach ($categories as $category) {
                    if ($category['category_id'] !== $current_category_id) {
                        if ($current_category_id !== null) {
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($current_category_name) ?></td>
                                <td><img src="<?= htmlspecialchars($current_category_image) ?>" alt="Category Image" width="100"></td>
                                <td>
                                    <?php echo !empty($delivery_options) ? implode(", ", $delivery_options) : "No Delivery Options"; ?>
                                </td>
                                <td>
                                    <a href="edit_category.php?id=<?= $current_category_id ?>">Edit</a> | 
                                    <a href="?delete=<?= $current_category_id ?>" onclick="return confirm('Are you sure you want to delete this category?');">Delete</a>
                                </td>
                            </tr>
                            <?php
                        }

                        $current_category_id = $category['category_id'];
                        $current_category_name = $category['category_name'];
                        $current_category_image = $category['image_path'];
                        $delivery_options = [];
                    }

                    if (!empty($category['delivery_option_name'])) {
                        $delivery_options[] = $category['delivery_option_name'];
                    }
                }
                ?>
                <tr>
                    <td><?= htmlspecialchars($current_category_name) ?></td>
                    <td><img src="<?= htmlspecialchars($current_category_image) ?>" alt="Category Image" width="100"></td>
                    <td><?= !empty($delivery_options) ? implode(", ", $delivery_options) : "No Delivery Options"; ?></td>
                    <td>
                        <a href="edit_category.php?id=<?= $current_category_id ?>">Edit</a> | 
                        <a href="?delete=<?= $current_category_id ?>" onclick="return confirm('Are you sure you want to delete this category?');">Delete</a>
                    </td>
                </tr>
            <?php else: ?>
                <tr>
                    <td colspan="4">No categories found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
