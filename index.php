<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: signin.php");
    exit();
}
include 'config.php';

// Fetch user data
$stmt = $conn->prepare("SELECT id, balance FROM users WHERE username = :username");
$stmt->bindParam(':username', $_SESSION['username']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found!";
    exit();
}

$userId = $user['id'];
$balance = $user['balance'];
$message = "";

// Handle credit
if (isset($_POST['credit'])) {
    $amount = $_POST['amount'];
    $stmt = $conn->prepare("UPDATE users SET balance = balance + :amount WHERE id = :user_id");
    $stmt->bindParam(':amount', $amount);
    $stmt->bindParam(':user_id', $userId);
    if ($stmt->execute()) {
        $message = "Amount credited successfully!";
        $balance += $amount;

        // Record the transaction
        $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount) VALUES (:user_id, 'credit', :amount)");
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':amount', $amount);
        $stmt->execute();
    } else {
        $message = "Error in crediting amount.";
    }
}

// Handle debit
if (isset($_POST['debit'])) {
    $amount = $_POST['amount'];
    if ($amount > $balance) {
        $message = "Insufficient balance!";
    } else {
        $stmt = $conn->prepare("UPDATE users SET balance = balance - :amount WHERE id = :user_id");
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':user_id', $userId);
        if ($stmt->execute()) {
            $message = "Amount debited successfully!";
            $balance -= $amount;

            // Record the transaction
            $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount) VALUES (:user_id, 'debit', :amount)");
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':amount', $amount);
            $stmt->execute();
        } else {
            $message = "Error in debiting amount.";
        }
    }
}

// Handle transfer
if (isset($_POST['transfer'])) {
    $amount = $_POST['amount'];
    $targetUsername = $_POST['target_username'];

    // Check if the target user exists
    $stmt = $conn->prepare("SELECT id, role FROM users WHERE username = :target_username");
    $stmt->bindParam(':target_username', $targetUsername);
    $stmt->execute();
    $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$targetUser) {
        $message = "Target user not found!";
    } elseif ($targetUser['role'] == 'admin') {
        $message = "Cannot transfer to admin!";
    } else {
        $targetUserId = $targetUser['id'];

        if ($amount > $balance) {
            $message = "Insufficient balance!";
        } else {
            try {
                $conn->beginTransaction();

                // Debit from sender
                $stmt = $conn->prepare("UPDATE users SET balance = balance - :amount WHERE id = :user_id");
                $stmt->bindParam(':amount', $amount);
                $stmt->bindParam(':user_id', $userId);
                $stmt->execute();

                // Credit to receiver
                $stmt = $conn->prepare("UPDATE users SET balance = balance + :amount WHERE id = :target_user_id");
                $stmt->bindParam(':amount', $amount);
                $stmt->bindParam(':target_user_id', $targetUserId);
                $stmt->execute();

                // Record the transaction
                $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, target_user_id) VALUES (:user_id, 'transfer', :amount, :target_user_id)");
                $stmt->bindParam(':user_id', $userId);
                $stmt->bindParam(':amount', $amount);
                $stmt->bindParam(':target_user_id', $targetUserId);
                $stmt->execute();

                $conn->commit();
                $message = "Amount transferred successfully!";
                $balance -= $amount;
            } catch (Exception $e) {
                $conn->rollBack();
                $message = "Error in transferring amount: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Home</title>
    <?php include 'navbar.php'; ?>
</head>
<body>
    <div class="container">
        <h2>Welcome to the Home Page</h2>
        <p>You are logged in as <?php echo htmlspecialchars($_SESSION['username']); ?>.</p>
        <p>Your current balance is: $<?php echo number_format($balance, 2); ?></p>
        <p><?php echo $message; ?></p>
        
        <h3>Credit Amount</h3>
        <form method="post" action="index.php">
            <label>Amount:</label>
            <input type="number" step="0.01" name="amount" required>
            <input type="submit" name="credit" value="Credit">
        </form>

        <h3>Debit Amount</h3>
        <form method="post" action="index.php">
            <label>Amount:</label>
            <input type="number" step="0.01" name="amount" required>
            <input type="submit" name="debit" value="Debit">
        </form>

        <h3>Transfer Amount</h3>
        <form method="post" action="index.php">
            <label>Target Username:</label>
            <input type="text" name="target_username" required>
            <label>Amount:</label>
            <input type="number" step="0.01" name="amount" required>
            <input type="submit" name="transfer" value="Transfer">
        </form>
    </div>
</body>
</html>
