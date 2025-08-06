<?php
session_start(); // Start the session

// Check if the user is not logged in, then redirect to login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Include the database connection file
include 'db.php';

$message = "";
$message_type = "";

// Check for messages from other pages (e.g., delete_user.php, edit.php, add_user.php)
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'error';
}

// --- Fetch Registered Users ---
$registered_users = [];
$sql_users = "SELECT id, name, email, created_at FROM users ORDER BY name ASC";
$result_users = $conn->query($sql_users);

if ($result_users) {
    if ($result_users->num_rows > 0) {
        while ($row_user = $result_users->fetch_assoc()) {
            $registered_users[] = $row_user;
        }
    }
} else {
    $message .= "Error fetching users: " . $conn->error . "<br>";
    $message_type = 'error';
}


// --- Fetch Biodata Entries ---
$biodata = []; // Initialize an empty array to hold biodata records
$sql_biodata = "SELECT * FROM biodata ORDER BY name ASC"; // Order by name for better readability
$result_biodata = $conn->query($sql_biodata);

if ($result_biodata) {
    if ($result_biodata->num_rows > 0) {
        while ($row_biodata = $result_biodata->fetch_assoc()) {
            $biodata[] = $row_biodata;
        }
    }
} else {
    $message .= "Error fetching biodata: " . $conn->error;
    $message_type = 'error';
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #ccc;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
        }
        .header .user-info {
            display: flex;
            align-items: center;
        }
        .header .user-info p {
            margin: 0 15px;
            font-weight: bold;
        }
        .header .user-info a {
            text-decoration: none;
            color: #007bff;
        }
        .message-box {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .message-box.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message-box.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .add-link {
            display: inline-block;
            background-color: #007bff;
            color: #fff;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .content-section {
            margin-bottom: 40px;
        }
        .content-section h2 {
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .user-list, .biodata-list {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        }
        .user-card, .biodata-card {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .user-card h4, .biodata-card h4 {
            margin-top: 0;
        }
        .user-card p, .biodata-card p {
            margin: 5px 0;
        }
        .biodata-card img {
            max-width: 150px;
            height: auto;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .btn-group a {
            padding: 5px 10px;
            text-decoration: none;
            color: #fff;
            border-radius: 3px;
            display: inline-block;
            cursor: pointer;
        }
        .btn-group a:first-child {
            background-color: #28a745;
            margin-right: 5px;
        }
        .btn-group a.delete {
            background-color: #dc3545;
        }
        /* Modal styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            visibility: hidden;
            opacity: 0;
            transition: visibility 0s, opacity 0.3s;
        }
        .modal-overlay.active {
            visibility: visible;
            opacity: 1;
        }
        .modal-content {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        .modal-content p {
            margin-bottom: 20px;
        }
        .modal-buttons a, .modal-buttons button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            color: #fff;
        }
        .modal-buttons a.confirm-delete {
            background-color: #dc3545;
            margin-right: 10px;
        }
        .modal-buttons button.cancel-delete {
            background-color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to the Dashboard!</h1>
            <div class="user-info">
                <p>Logged in as: <?php echo htmlspecialchars($_SESSION['username']); ?></p>
                <a href="logout.php">Logout</a>
            </div>
        </div>

        <?php if ($message): ?>
            <p class="message-box <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>

        <div class="content-section">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2>Registered Users</h2>
                <a href="add_user.php" class="add-link" style="background-color: #17a2b8;">Add New User</a>
            </div>
            <?php if (empty($registered_users)): ?>
                <p>No registered users found.</p>
            <?php else: ?>
                <div class="user-list">
                    <?php foreach ($registered_users as $user): ?>
                        <div class="user-card">
                            <h4><?php echo htmlspecialchars($user['name'] ?? 'N/A'); ?></h4>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></p>
                            <p><strong>Registered:</strong> <?php echo htmlspecialchars($user['created_at'] ?? 'N/A'); ?></p>
                            <div class="btn-group">
                                <a href="edit_user.php?id=<?php echo htmlspecialchars($user['id']); ?>">Edit</a>
                                <a href="#" class="delete" onclick="showDeleteModal('delete_user.php?id=<?php echo htmlspecialchars($user['id']); ?>', 'Are you sure you want to delete the user <?php echo htmlspecialchars($user['name']); ?>? This action cannot be undone.'); return false;">Delete</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="content-section">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2>Biodata Entries</h2>
                <a href="create.php" class="add-link">Create New Biodata</a>
            </div>
            <?php if (empty($biodata)): ?>
                <p>No biodata entries found.</p>
            <?php else: ?>
                <div class="biodata-list">
                    <?php foreach ($biodata as $entry): ?>
                        <div class="biodata-card">
                            <?php if (!empty($entry['photo_path']) && file_exists($entry['photo_path'])): ?>
                                <img src="<?php echo htmlspecialchars($entry['photo_path']); ?>" alt="Profile Photo">
                            <?php else: ?>
                                <p>No Photo Available</p>
                            <?php endif; ?>
                            <h4><?php echo htmlspecialchars($entry['name'] ?? 'N/A'); ?></h4>
                            <p><strong>ID:</strong> <?php echo htmlspecialchars($entry['id'] ?? 'N/A'); ?></p>
                            <p><strong>Age:</strong> <?php echo htmlspecialchars($entry['age'] ?? 'N/A'); ?></p>
                            <p><strong>Gender:</strong> <?php echo htmlspecialchars($entry['gender'] ?? 'N/A'); ?></p>
                            <p><strong>Marital Status:</strong> <?php echo htmlspecialchars($entry['marital_status'] ?? 'N/A'); ?></p>
                            <p><strong>Religion:</strong> <?php echo htmlspecialchars($entry['religion'] ?? 'N/A'); ?></p>
                            <p><strong>Occupation:</strong> <?php echo htmlspecialchars($entry['occupation'] ?? 'N/A'); ?></p>
                            <p><strong>Contact:</strong> <?php echo htmlspecialchars($entry['contact_number'] ?? 'N/A'); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($entry['email'] ?? 'N/A'); ?></p>
                            <div class="btn-group">
                                <a href="edit.php?id=<?php echo htmlspecialchars($entry['id']); ?>">Edit</a>
                                <a href="#" class="delete" onclick="showDeleteModal('delete2.php?id=<?php echo htmlspecialchars($entry['id']); ?>', 'Are you sure you want to delete the biodata entry for <?php echo htmlspecialchars($entry['name']); ?>? This action cannot be undone.'); return false;">Delete</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Custom Delete Confirmation Modal -->
    <div id="deleteModal" class="modal-overlay">
        <div class="modal-content">
            <h3>Confirm Deletion</h3>
            <p id="modal-message">Are you sure you want to delete this item? This action cannot be undone.</p>
            <div class="modal-buttons">
                <a id="confirmDeleteButton" class="confirm-delete" href="#">Delete</a>
                <button class="cancel-delete" onclick="hideDeleteModal()">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        // Function to show the modal with a specific message and URL
        function showDeleteModal(url, message) {
            const modal = document.getElementById('deleteModal');
            const confirmButton = document.getElementById('confirmDeleteButton');
            const modalMessage = document.getElementById('modal-message');

            modalMessage.textContent = message;
            confirmButton.href = url;
            modal.classList.add('active');
        }

        // Function to hide the modal
        function hideDeleteModal() {
            const modal = document.getElementById('deleteModal');
            modal.classList.remove('active');
        }

        // Close modal when clicking outside of it
        document.getElementById('deleteModal').addEventListener('click', (e) => {
            if (e.target === e.currentTarget) {
                hideDeleteModal();
            }
        });
    </script>
</body>
</html>
