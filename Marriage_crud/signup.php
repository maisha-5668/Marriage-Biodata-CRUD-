<?php
session_start();

include 'db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (empty($name) || empty($email) || empty($password)) {
        $message = "<p class='error-message'>All fields are required.</p>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<p class='error-message'>Invalid email format.</p>";
    } elseif (strlen($password) < 6) {
        $message = "<p class='error-message'>Password must be at least 6 characters long.</p>";
    } else {
        $check_email_sql = "SELECT id FROM users WHERE email = ?";
        if ($stmt = $conn->prepare($check_email_sql)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $message = "<p class='error-message'>This email is already registered. Please use a different one.</p>";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";

                if ($stmt_insert = $conn->prepare($sql)) {
                    $stmt_insert->bind_param("sss", $name, $email, $hashed_password);
                    if ($stmt_insert->execute()) {
                        header("Location: login.php?message=" . urlencode("Account created successfully. You can now log in."));
                        exit;
                    } else {
                        $message = "<p class='error-message'>Error creating account: " . $stmt_insert->error . "</p>";
                    }
                    $stmt_insert->close();
                } else {
                    $message = "<p class='error-message'>Error preparing statement: " . $conn->error . "</p>";
                }
            }
            $stmt->close();
        } else {
            $message = "<p class='error-message'>Error checking email: " . $conn->error . "</p>";
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .auth-container {
            width: 400px;
            padding: 30px;
            margin: 50px auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .auth-container h1 {
            margin-bottom: 20px;
        }
        .auth-container form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .auth-container input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        .auth-container button {
            background-color: #28a745;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
        }
        .auth-container button:hover {
            background-color: #218838;
        }
        .auth-container p {
            margin-top: 20px;
        }
        .auth-container a {
            color: #007bff;
            text-decoration: none;
        }
        .auth-container a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <h1>Sign Up</h1>
        <?php if (!empty($message)) echo $message; ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <input type="text" name="name" placeholder="Name" required>
            </div>
            <div class="form-group">
                <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit">Sign Up</button>
        </form>
        <p>Already have an account? <a href="login.php">Login here</a>.</p>
    </div>
</body>
</html>