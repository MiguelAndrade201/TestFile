<?php
include 'db_connection.php';
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_form.php");
    exit();
}

// Initialize variables
$orders = [];
$date_selected = '';  // To hold the date selected by the user

// Handle form submission for date filter
if (isset($_POST['date_selected'])) {
    $date_selected = $_POST['date_selected'];

    // Prepare the SQL query to fetch orders based on the selected date
    $query = "
        SELECT 
            o.id AS order_id, 
            o.total_amount, 
            o.status, 
            o.date_ordered, 
            delivery_opt.name AS shipping_method_name, 
            a.city AS shipping_city, 
            a.line1 AS shipping_address, 
            a.line2 AS shipping_address2, 
            a.postcode AS shipping_postcode, 
            u.first_name AS user_first_name, 
            u.last_name AS user_last_name, 
            u.phone_number AS user_phone_number, 
            u.email AS user_email, 
            GROUP_CONCAT(op.quantity) AS product_quantities, 
            GROUP_CONCAT(p.name) AS product_names
        FROM orders o
        LEFT JOIN delivery_options delivery_opt ON o.delivery_option_id = delivery_opt.id
        LEFT JOIN addresses a ON o.address_id = a.id
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN order_products op ON o.id = op.order_id
        LEFT JOIN products p ON op.product_id = p.id
        WHERE DATE(o.date_ordered) = ?
        GROUP BY o.id
        ORDER BY o.date_ordered DESC;
    ";

    // Prepare the query
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $date_selected);  // Bind the date parameter
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if any orders are found
    if ($result->num_rows > 0) {
        $orders = $result->fetch_all(MYSQLI_ASSOC);
    }

    $stmt->close();
    $conn->close();
}

// Handle status update when a dropdown value is selected
if (isset($_POST['order_id']) && isset($_POST['new_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['new_status'];

    // Prepare the SQL query to update the status of the order
    $update_query = "UPDATE orders SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $new_status, $order_id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Status updated successfully');</script>";
    } else {
        echo "<script>alert('Error updating status');</script>";
    }

    $stmt->close();
    $conn->close();

    // Redirect to refresh the page to see the updated status
    header("Location: admin-dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Orders</title>
    <!-- Link to external stylesheet -->
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

    <h1>Orders</h1>

    <!-- Date picker form to filter orders by date -->
    <div class="form-container">
        <form method="POST" action="">
            <label for="date_selected">Select Date: </label>
            <input type="date" id="date_selected" name="date_selected" required>
            <button type="submit">Filter Orders</button>
        </form>
    </div>

    <!-- Display orders based on selected date -->
    <?php if (empty($orders)): ?>
        <p class="error">No orders found for this date.</p>
    <?php else: ?>
        <h2>Orders for <?php echo htmlspecialchars($date_selected); ?></h2>
        
        <!-- Table Layout (visible on larger screens) -->
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer Name</th>
                    <th>Email</th>
                    <th>Phone Number</th>
                    <th>Address</th>
                    <th>Items Ordered</th>
                    <th>Status</th>
                    <th>Shipping Method</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                        <td><?php echo htmlspecialchars($order['user_first_name']) . ' ' . htmlspecialchars($order['user_last_name']); ?></td>
                        <td><?php echo htmlspecialchars($order['user_email']); ?></td>
                        <td><?php echo htmlspecialchars($order['user_phone_number']); ?></td>
                        <td><?php echo htmlspecialchars($order['shipping_address']) . ', ' . htmlspecialchars($order['shipping_address2']) . ', ' . htmlspecialchars($order['shipping_city']) . ' ' . htmlspecialchars($order['shipping_postcode']); ?></td>
                        <td>
                            <?php 
                            // Split product names and quantities to display each item with its quantity
                            $product_names = explode(',', $order['product_names']);
                            $product_quantities = explode(',', $order['product_quantities']);
                            $items_display = '';
                            for ($i = 0; $i < count($product_names); $i++) {
                                $items_display .= htmlspecialchars($product_quantities[$i]) . ' x ' . htmlspecialchars($product_names[$i]) . "\n";
                            }
                            echo nl2br($items_display);  // nl2br to ensure new lines are respected
                            ?>
                        </td>
                        <td>
                            <form method="POST" action="">
                                <select name="new_status" onchange="this.form.submit()">
                                    <option value="Pending" <?php echo (trim(strtolower($order['status'])) == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Shipped" <?php echo (trim(strtolower($order['status'])) == 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                                    <option value="Delivered" <?php echo (trim(strtolower($order['status'])) == 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="Cancelled" <?php echo (trim(strtolower($order['status'])) == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                                <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['order_id']); ?>">
                            </form>
                        </td>
                        <td><?php echo htmlspecialchars($order['shipping_method_name']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Flexbox Layout (visible on smaller screens) -->
        <div class="order-rows">
            <?php foreach ($orders as $order): ?>
                <div class="order-row">
                    <div><strong>Order ID:</strong> <?php echo htmlspecialchars($order['order_id']); ?></div>
                    <div><strong>Customer:</strong> <?php echo htmlspecialchars($order['user_first_name']) . ' ' . htmlspecialchars($order['user_last_name']); ?></div>
                    <div><strong>Email:</strong> <?php echo htmlspecialchars($order['user_email']); ?></div>
                    <div><strong>Phone Number:</strong> <?php echo htmlspecialchars($order['user_phone_number']); ?></div>
                    <div><strong>Address:</strong> <?php echo htmlspecialchars($order['shipping_address']) . ', ' . htmlspecialchars($order['shipping_address2']) . ', ' . htmlspecialchars($order['shipping_city']) . ' ' . htmlspecialchars($order['shipping_postcode']); ?></div>
                    <div><strong>Items Ordered:</strong><br>
                        <?php 
                        // Display items with quantities, line by line
                        $product_names = explode(',', $order['product_names']);
                        $product_quantities = explode(',', $order['product_quantities']);
                        $items_display = '';
                        for ($i = 0; $i < count($product_names); $i++) {
                            $items_display .= htmlspecialchars($product_quantities[$i]) . ' x ' . htmlspecialchars($product_names[$i]) . "\n";
                        }
                        echo nl2br($items_display);  // nl2br to ensure new lines are respected
                        ?>
                    </div>
                    <div><strong>Status:</strong>
                        <form method="POST" action="">
                            <select name="new_status" onchange="this.form.submit()">
                                <option value="Pending" <?php echo (trim(strtolower($order['status'])) == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="Shipped" <?php echo (trim(strtolower($order['status'])) == 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                                <option value="Delivered" <?php echo (trim(strtolower($order['status'])) == 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                                <option value="Cancelled" <?php echo (trim(strtolower($order['status'])) == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                            <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['order_id']); ?>">
                        </form>
                    </div>
                    <div><strong>Shipping Method:</strong> <?php echo htmlspecialchars($order['shipping_method_name']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</body>
</html>
