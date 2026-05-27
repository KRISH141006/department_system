<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';
$page_title = 'Productivity Tasks';
require_once __DIR__ . '/../../app/includes/header.php';

$user_id = $_SESSION['user_id'];

$pending_sql = "SELECT * FROM tasks WHERE user_id='$user_id' AND is_completed = 0 ORDER BY created_at DESC";
$pending_result = mysqli_query($conn, $pending_sql);

$completed_sql = "SELECT * FROM tasks WHERE user_id='$user_id' AND is_completed = 1 ORDER BY created_at DESC";
$completed_result = mysqli_query($conn, $completed_sql);

$pending_count = mysqli_num_rows($pending_result);
$completed_count = mysqli_num_rows($completed_result);

// Using a fallback for username since the auth might not set $_SESSION['username'] anymore
// Let's get the name from the DB
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$nameResult = $stmt->get_result();
$userName = "User";
if ($nameResult && $nameResult->num_rows > 0) {
    $userName = htmlspecialchars($nameResult->fetch_assoc()['name']);
}
?>

<div class="wrapper" style="padding: 2rem;">
    <div style="margin-bottom: 1rem;">
        <a href="index.php" style="text-decoration: none; color: var(--text-2); font-size: 0.9rem; display: inline-flex; align-items: center; gap: 5px; background: var(--border); padding: 5px 15px; border-radius: 20px;">
            ← Back to Productivity
        </a>
    </div>
    <h1>Welcome, <?php echo $userName; ?></h1>

    <div class="card" style="margin-top: 1.5rem; margin-bottom: 2rem; padding: 1.5rem;">
        <form method="POST" action="../../app/actions/productivity/add_task.php" style="display:flex; gap: 10px;">
            <input type="text" name="task" placeholder="Enter Task" required style="flex:1; padding: 0.5rem; border: 1px solid var(--border); border-radius: 4px;">
            <button type="submit" class="btn btn-primary">Add Task</button>
        </form>
    </div>

    <div class="grid-2">
        <div class="card">
            <h2>Pending Tasks (<?php echo $pending_count; ?>)</h2>
            <ul style="list-style:none; padding:0;">
                <?php while ($row = mysqli_fetch_assoc($pending_result)): ?>
                    <li style="padding: 0.75rem 0; border-bottom: 1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                        <span><?php echo htmlspecialchars($row['task']); ?></span>
                        <div>
                            <a href="../../app/actions/productivity/complete_task.php?id=<?php echo $row['id']; ?>" class="btn btn-sm" style="font-size:0.8rem;">Complete</a>
                            <a href="../../app/actions/productivity/delete_task.php?id=<?php echo $row['id']; ?>" class="btn btn-sm" style="font-size:0.8rem; background: #ffebee; color:#d32f2f;">Delete</a>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>

        <div class="card">
            <h2>Completed Tasks (<?php echo $completed_count; ?>)</h2>
            <ul style="list-style:none; padding:0;">
                <?php while ($row = mysqli_fetch_assoc($completed_result)): ?>
                    <li style="padding: 0.75rem 0; border-bottom: 1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                        <span style="text-decoration:line-through; color:var(--text-2);"><?php echo htmlspecialchars($row['task']); ?></span>
                        <div>
                            <a href="../../app/actions/productivity/undo_task.php?id=<?php echo $row['id']; ?>" class="btn btn-sm" style="font-size:0.8rem;">Undo</a>
                            <a href="../../app/actions/productivity/delete_task.php?id=<?php echo $row['id']; ?>" class="btn btn-sm" style="font-size:0.8rem; background: #ffebee; color:#d32f2f;">Delete</a>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>