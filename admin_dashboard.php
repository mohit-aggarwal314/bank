<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: signin.php");
    exit();
}
include 'config.php';

// Handle approval of debit transactions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['approve'])) {
    $transactionId = $_POST['transaction_id'];

    // Update the transaction to set it as approved
    $stmt = $conn->prepare("UPDATE transactions SET approved = 1 WHERE id = :transaction_id AND type = 'debit' AND approved IS NULL");
    $stmt->bindParam(':transaction_id', $transactionId);
    if ($stmt->execute()) {
        $message = "Transaction approved successfully!";
    } else {
        $message = "Error approving transaction.";
    }
}

// Fetch all transactions
$stmt = $conn->prepare("SELECT t.*, u.username AS user_username, tu.username AS target_username
                        FROM transactions t
                        LEFT JOIN users u ON t.user_id = u.id
                        LEFT JOIN users tu ON t.target_user_id = tu.id
                        ORDER BY t.created_at DESC");
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <?php include 'navbar.php'; ?>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .container {
            width: 80%;
            margin: auto;
            padding: 20px;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .approve-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
        }
        .approve-btn:hover {
            background-color: #45a049;
        }
        .message {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f2f2f2;
            border-left: 4px solid #4CAF50;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Admin Dashboard</h2>
        <?php if (isset($message)): ?>
            <div class="message">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <h3>All Transactions</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Target User</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
            <?php foreach ($transactions as $transaction): ?>
            <tr>
                <td><?php echo htmlspecialchars($transaction['id']); ?></td>
                <td><?php echo htmlspecialchars($transaction['user_username']); ?></td>
                <td><?php echo htmlspecialchars($transaction['type']); ?></td>
                <td><?php echo htmlspecialchars($transaction['amount']); ?></td>
                <td><?php echo htmlspecialchars($transaction['target_username']); ?></td>
                <td><?php echo htmlspecialchars($transaction['created_at']); ?></td>
                <td>
                    <?php if ($transaction['type'] == 'debit' && is_null($transaction['approved'])): ?>
                    <form method="post" action="admin_dashboard.php">
                        <input type="hidden" name="transaction_id" value="<?php echo htmlspecialchars($transaction['id']); ?>">
                        <input type="submit" name="approve" value="Approve" class="approve-btn">
                    </form>
                    <?php elseif ($transaction['type'] == 'debit'): ?>
                    <?php echo is_null($transaction['approved']) ? 'Pending' : ($transaction['approved'] ? 'Approved' : 'Rejected'); ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
