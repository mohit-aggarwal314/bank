<?php
include 'config.php';
session_start();

// Check if a session is already active
if (isset($_SESSION['username'])) {
    header("Location: index.php"); // Redirect to index.php if a session is active
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        header("Location: index.php");
        exit();
    } else {
        $message = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Signin</title>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container">
        <h2>Signin</h2>
        <form method="post" action="signin.php">
            <label>Username:</label>
            <input type="text" name="username" required>
            <label>Password:</label>
            <input type="password" name="password" required>
            <input type="submit" value="Signin">
        </form>
        <p><?php echo isset($message) ? $message : ''; ?></p>
    </div>
</body>
</html>
