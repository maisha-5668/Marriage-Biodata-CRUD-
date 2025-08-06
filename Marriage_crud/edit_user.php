<?php
session_start();

// Check if the user is not logged in, then redirect to login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Include the database connection file
include 'db.php';

$message = "";
$user_data = []; // Initialize to hold current user data

// Logic to fetch user data if ID is present in GET
if (isset($_GET['id']) && !empty(trim($_GET['id']))) {
    $id = trim($_GET['id']);
    $sql_select = "SELECT id, name, email FROM users WHERE id = ?";
    if ($stmt = $conn->prepare($sql_select)) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows == 1) {
                $user_data = $result->fetch_assoc();
            } else {
                $message = "No user found with that ID.";
                header("Location: read.php?message=" . urlencode($message));
                exit;
            }
        } else {
            $message = "Error executing select statement: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $message = "Error preparing select statement: " . $conn->error;
    }
} else if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    // This block handles the form submission for updating user data
    $id = trim($_POST['id']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $new_password = trim($_POST['new_password']);
    $confirm_new_password = trim($_POST['confirm_new_password']);

    // Basic validation
    if (empty($name)) {
        $message = "Please enter a name.";
    } elseif (empty($email)) {
        $message = "Please enter an email.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email format.";
    } else {
        // Check if email or name already exists for *another* user
        $sql_check_duplicate = "SELECT id FROM users WHERE (email = ? OR name = ?) AND id != ?";
        if ($stmt_check = $conn->prepare($sql_check_duplicate)) {
            $stmt_check->bind_param("ssi", $email, $name, $id);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $message = "This email or name is already taken by another user.";
            } else {
                $password_update_sql = "";
                $params = [$name, $email, $id];
                $types = "ssi";

                // Handle password change if provided
                if (!empty($new_password)) {
                    if (strlen($new_password) < 6) {
                        $message = "New password must have at least 6 characters.";
                    } elseif ($new_password !== $confirm_new_password) {
                        $message = "New passwords do not match.";
                    } else {
                        $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $password_update_sql = ", password = ?";
                        $params = [$name, $email, $hashed_new_password, $id];
                        $types = "sssi"; // Add 's' for the new password string
                    }
                }

                if (empty($message)) { // Only proceed if no password validation errors
                    $sql_update = "UPDATE users SET name = ?, email = ? " . $password_update_sql . " WHERE id = ?";

                    if ($stmt_update = $conn->prepare($sql_update)) {
                        $stmt_update->bind_param($types, ...$params);

                        if ($stmt_update->execute()) {
                            $message = "User updated successfully!";
                            // Re-fetch the updated data to display in the form
                            $sql_select_after_update = "SELECT id, name, email FROM users WHERE id = ?";
                            if ($stmt_select_after_update = $conn->prepare($sql_select_after_update)) {
                                $stmt_select_after_update->bind_param("i", $id);
                                $stmt_select_after_update->execute();
                                $result_after_update = $stmt_select_after_update->get_result();
                                $user_data = $result_after_update->fetch_assoc(); // Update the form with fresh data
                                $stmt_select_after_update->close();
                            }
                        } else {
                            $message = "Error updating user: " . $stmt_update->error;
                        }
                        $stmt_update->close();
                    } else {
                        $message = "Error preparing update statement: " . $conn->error;
                    }
                }
            }
            $stmt_check->close();
        } else {
            $message = "Error preparing duplicate check statement: " . $conn->error;
        }
    }
} else {
    $message = "Invalid request or missing user ID.";
    header("Location: read.php?message=" . urlencode($message));
    exit;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <style>
        body {
            font-family: sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 400px;
            max-width: 90%;
            text-align: center;
        }
        h2 {
            color: #0056b3;
            margin-bottom: 20px;
        }
        .message {
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 5px;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: calc(100% - 22px);
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        input[type="submit"] {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            width: 100%;
            margin-top: 10px;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .back-link {
            display: block;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit User</h2>
        <?php if ($message): ?>
            <p class="message <?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>"><?php echo $message; ?></p>
        <?php endif; ?>

        <?php if (!empty($user_data)): ?>
            <form action="edit_user.php" method="post">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($user_data['id']); ?>">
                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user_data['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password (leave blank to keep current):</label>
                    <input type="password" id="new_password" name="new_password">
                </div>
                <div class="form-group">
                    <label for="confirm_new_password">Confirm New Password:</label>
                    <input type="password" id="confirm_new_password" name="confirm_new_password">
                </div>
                <input type="submit" value="Update User">
            </form>
        <?php else: ?>
            <p>User data could not be loaded.</p>
        <?php endif; ?>
        <a href="read.php" class="back-link">Back to Dashboard</a>
    </div>
</body>
</html>