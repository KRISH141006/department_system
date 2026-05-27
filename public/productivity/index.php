<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';
$page_title = 'Productivity Dashboard';
require_once __DIR__ . '/../../app/includes/header.php';

$user_id = $_SESSION['user_id'];
?>

<style>
    .productivity-landing {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2.5rem;
        padding: 2rem 0;
        min-height: 400px;
    }

    .landing-card {
        background: #fff;
        border: 2px solid #1a1a1a;
        border-radius: 20px;
        box-shadow: 8px 8px 0px #1a1a1a;
        padding: 4rem 2rem;
        text-align: center;
        text-decoration: none;
        color: #1a1a1a;
        transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        cursor: pointer;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
    }

    .landing-card:hover {
        transform: translate(-5px, -5px);
        box-shadow: 13px 13px 0px #1a1a1a;
        background: #fdfdfd;
    }

    .landing-card:active {
        transform: translate(3px, 3px);
        box-shadow: 2px 2px 0px #1a1a1a;
        background: #fff3cd !important;
    }

    .landing-card h2 {
        font-family: 'DM Serif Display', serif;
        font-size: 2.2rem;
        margin-bottom: 1.5rem;
        line-height: 1.2;
    }

    .landing-card .icon {
        font-size: 4.5rem;
        margin-bottom: 2rem;
        transition: transform 0.3s;
    }

    .landing-card:hover .icon {
        transform: scale(1.1) rotate(5deg);
    }

    .landing-card p {
        font-weight: 600;
        color: #64748b;
        font-size: 1rem;
        max-width: 250px;
    }

    @media (max-width: 768px) {
        .productivity-landing {
            grid-template-columns: 1fr;
            min-height: auto;
        }
        .landing-card {
            padding: 3rem 2rem;
        }
    }
</style>

<div class="page-wrap medium">
    <div class="productivity-landing">
        <!-- Personal Task Manager -->
        <a href="tasks.php" class="landing-card">
            <div class="icon">✍️</div>
            <h2>Personal Task Manager</h2>
            <p>Organize your own thoughts, deadlines, and priorities.</p>
        </a>

        <!-- Assigned Task Manager -->
        <div class="landing-card assigned-card" onclick="alert('Assigned Task Manager coming soon!')">
            <div class="icon">📋</div>
            <h2>Assigned Task Manager</h2>
            <p>Tasks assigned to you by faculty or department leads.</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
