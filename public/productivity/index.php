<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
$page_title = 'Productivity';
require_once __DIR__ . '/../../app/includes/header.php';

$role = $_SESSION['role'] ?? 'student';
?>

<style>
    .productivity-container {
        max-width: 900px;
        margin: 2rem auto;
        padding: 0 1rem;
    }

    .promo-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        margin-top: 3rem;
    }

    .brutal-card {
        background: #fff;
        border: 4px solid #000;
        box-shadow: 8px 8px 0px #000;
        padding: 2rem;
        transition: all 0.2s ease;
        text-decoration: none;
        color: #000;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 250px;
    }

    .brutal-card:hover {
        transform: translate(-4px, -4px);
        box-shadow: 12px 12px 0px #000;
    }

    .brutal-card:active {
        transform: translate(2px, 2px);
        box-shadow: 4px 4px 0px #000;
    }

    .brutal-card h2 {
        font-family: 'DM Serif Display', serif;
        font-size: 2rem;
        margin-bottom: 1rem;
    }

    .brutal-card p {
        font-size: 1.1rem;
        margin-bottom: 2rem;
        color: #333;
    }

    .brutal-badge {
        display: inline-block;
        background: #000;
        color: #fff;
        padding: 0.5rem 1rem;
        font-weight: bold;
        text-transform: uppercase;
        align-self: flex-start;
    }

    .page-header-brutal {
        text-align: center;
        margin-bottom: 1rem;
    }

    .page-header-brutal h1 {
        font-family: 'DM Serif Display', serif;
        font-size: 3.5rem;
        text-transform: uppercase;
        letter-spacing: -2px;
        line-height: 1;
    }
</style>

<div class="productivity-container">
    <div class="page-header-brutal">
        <h1>Productivity</h1>
        <p class="page-subtitle">Master your time, master your life.</p>
    </div>

    <div class="promo-grid">
        <a href="tasks.php" class="brutal-card">
            <div>
                <h2>Personal Task Manager</h2>
                <p>Organize your daily routine, set deadlines, and track your personal progress.</p>
            </div>
            <span class="brutal-badge">Open Manager →</span>
        </a>

        <a href="#" class="brutal-card" style="background: #f0f0f0; opacity: 0.7; cursor: not-allowed;">
            <div>
                <h2>Assigned Task Manager</h2>
                <p>Track tasks assigned to you by faculty or department heads. (Coming Soon)</p>
            </div>
            <span class="brutal-badge" style="background: #666;">Locked</span>
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
