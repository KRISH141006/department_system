<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
<<<<<<< HEAD
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
=======
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
>>>>>>> d372500425377a8d51258631dce6afd6278f51dd
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
