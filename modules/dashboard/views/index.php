<?php
require_once __DIR__ . '/../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../shared/config/db.php';
$page_title = 'Dashboard';

$user_id = $_SESSION['user_id'];
$name_stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$name_stmt->bind_param("i", $user_id);
$name_stmt->execute();
$name_result = $name_stmt->get_result();
$user_name = ($name_result->num_rows > 0) ? $name_result->fetch_assoc()['name'] : ($_SESSION['name'] ?? 'User');

require_once __DIR__ . '/../../../shared/layout/header.php';
?>

<style>
.dashboard-landing {
    height: calc(100vh - 120px);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
}

/* Force specific dashboard sizes while leveraging global logo-floater gradients */
.dashboard-landing .logo-floater {
    margin: 0 !important;
    line-height: 1.1;
}

/* Ensure the dot can actually move (inline elements can't have transforms) */
.logo-dot {
    display: inline-block !important;
    cursor: pointer;
    user-select: none;
    transition: transform 0.1s;
}

.dribble-active {
    animation: dribbleAndDamp 2s ease-out forwards;
}

@keyframes dribbleAndDamp {
  0% { transform: translateY(0); animation-timing-function: ease-out; }
  15% { transform: translateY(-12px); animation-timing-function: ease-in; }
  30% { transform: translateY(0); animation-timing-function: ease-out; }
  45% { transform: translateY(-8px); animation-timing-function: ease-in; }
  60% { transform: translateY(0); animation-timing-function: ease-out; }
  75% { transform: translateY(-4px); animation-timing-function: ease-in; }
  85% { transform: translateY(0); animation-timing-function: ease-out; }
  92% { transform: translateY(-2px); animation-timing-function: ease-in; }
  100% { transform: translateY(0); }
}

/* Fade in for the whole landing area */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.dashboard-landing h1, .dashboard-landing .user-name-branded {
    animation: fadeInUp 0.8s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

@media (max-width: 768px) {
    .dashboard-landing .logo-floater { font-size: 3.5rem !important; }
    .dashboard-landing .user-name-branded { font-size: 1.8rem !important; }
}
</style>

<div class="wrapper">
    <div class="dashboard-landing">
        <!-- Main branded heading -->
        <h1 class="logo-floater" style="font-size: 5rem;">
            ICT<span class="logo-dot dribble-active" id="logoDotDashboard" onclick="triggerDribble(this)">.</span>Community
        </h1>
        
        <!-- User name using the same logo gradient style -->
        <span class="logo-floater user-name-branded" style="font-size: 2.5rem; opacity: 0.8; margin-top: 0.5rem; display: block;">
            <?= htmlspecialchars($user_name) ?>
        </span>
    </div>
</div>

<script>
function triggerDribble(el) {
    if (!el) return;
    el.classList.remove('dribble-active');
    void el.offsetWidth; // Trigger reflow
    el.classList.add('dribble-active');
}

// Auto-trigger dot animation every 10 seconds for visual flair
// Using the specific ID to avoid confusion with any other dots in the header
setInterval(() => {
    const dot = document.getElementById('logoDotDashboard');
    if (dot) {
        triggerDribble(dot);
    }
}, 10000);
</script>

<?php require_once __DIR__ . '/../../../shared/layout/footer.php'; ?>
