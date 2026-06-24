<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

$page_title = "Community Leaderboard";
require_once __DIR__ . '/../../../../shared/layout/header.php';

$leader_query = $conn->query("
    SELECT u.id, u.name, p.community_score, c.name as class_name, c.semester
    FROM users u
    JOIN profiles p ON u.id = p.user_id
    JOIN students s ON u.id = s.user_id
    JOIN classes c ON s.class_id = c.id
    WHERE u.role = 'student'
    ORDER BY p.community_score DESC, u.name ASC
    LIMIT 10
");
$leaders = $leader_query->fetch_all(MYSQLI_ASSOC);
$top_score = (int) ($leaders[0]['community_score'] ?? 0);
?>

<div class="wrapper">
    <section class="ux-workspace-hero">
        <div class="ux-workspace-hero-main">
            <span class="ux-kicker">Community</span>
            <h1 class="ux-hero-title">Leaderboard</h1>
            <p class="ux-hero-copy">A clean ranking of students earning community score through reviews, participation, and verified contribution.</p>
            <div class="ux-hero-actions">
                <a href="<?= $base_path ?>/community/request" class="btn btn-primary">Skill Validation</a>
                <button type="button" class="btn btn-secondary" data-open-command-palette>Search Services</button>
            </div>
        </div>
        <aside class="ux-workspace-hero-side">
            <span class="ux-subtle-note">Community snapshot</span>
            <div class="ux-stat-grid">
                <div class="ux-stat-card"><strong><?= count($leaders) ?></strong><span>Ranked</span></div>
                <div class="ux-stat-card is-good"><strong><?= number_format($top_score) ?></strong><span>Top Score</span></div>
            </div>
        </aside>
    </section>

    <section class="ux-section-card ux-compact-table-card">
        <div class="ux-section-heading">
            <div>
                <h2>Top Contributors</h2>
                <p>Open a student profile for deeper review context.</p>
            </div>
        </div>

        <?php if (empty($leaders)): ?>
            <div class="ux-empty-panel">
                <span class="ux-feature-mark">LB</span>
                <strong>No leaderboard data yet</strong>
                <span>Students will appear here once community scores are available.</span>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Student</th>
                            <th>Class</th>
                            <th>Badges</th>
                            <th style="text-align: right;">Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leaders as $index => $student): ?>
                            <?php
                            $rank = $index + 1;
                            $badge_query = $conn->prepare("
                                SELECT b.name as badge_name, b.icon
                                FROM user_badges ub
                                JOIN badges b ON ub.badge_id = b.id
                                WHERE ub.user_id = ?
                                LIMIT 3
                            ");
                            $badge_query->bind_param("i", $student['id']);
                            $badge_query->execute();
                            $badges = $badge_query->get_result()->fetch_all(MYSQLI_ASSOC);
                            ?>
                            <tr>
                                <td><span class="badge <?= $rank <= 3 ? 'badge-primary' : '' ?>">#<?= $rank ?></span></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <span class="ux-mark"><?= strtoupper(substr($student['name'], 0, 1)) ?></span>
                                        <a href="<?= $base_path ?>/community/view_student?id=<?= (int) $student['id'] ?>" style="text-decoration: none; color: var(--text); font-weight: 850;">
                                            <?= htmlspecialchars($student['name']) ?>
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 700;"><?= htmlspecialchars($student['class_name']) ?></div>
                                    <div style="font-size: 0.78rem; color: var(--text-3);">Semester <?= htmlspecialchars($student['semester']) ?></div>
                                </td>
                                <td>
                                    <?php if (empty($badges)): ?>
                                        <span class="badge">No badges</span>
                                    <?php else: ?>
                                        <span class="ux-meta-line">
                                            <?php foreach ($badges as $badge): ?>
                                                <span class="badge badge-success" title="<?= htmlspecialchars($badge['badge_name']) ?>">
                                                    <?= htmlspecialchars($badge['badge_name']) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right; font-weight: 900; color: var(--accent); font-size: 1.08rem;">
                                    <?= number_format((int) $student['community_score']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
