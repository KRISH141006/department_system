<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

$page_title = "Community Leaderboard";
require_once __DIR__ . '/../../../../shared/layout/header.php';

// Fetch top 10 students by community score
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
?>

<div class="wrapper" style="padding: 2rem;">
    <div style="max-width: 900px; margin: 0 auto;">
        <div style="text-align: center; margin-bottom: 3rem;">
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 3rem; margin-bottom: 0.5rem;">Community Leaderboard</h1>
            <p style="color: var(--text-2); font-size: 1.1rem;">Highlighting our top contributors and high performers.</p>
        </div>

        <div class="card" style="padding: 0; overflow: hidden; border: 1px solid var(--border);">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead style="background: var(--bg-2); border-bottom: 1px solid var(--border);">
                    <tr>
                        <th style="padding: 1.25rem 2rem; color: var(--text-3); font-weight: 600; width: 80px; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">Rank</th>
                        <th style="padding: 1.25rem; color: var(--text-3); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">Student</th>
                        <th style="padding: 1.25rem; color: var(--text-3); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">Class</th>
                        <th style="padding: 1.25rem; color: var(--text-3); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">Badges</th>
                        <th style="padding: 1.25rem 2rem; color: var(--text-3); font-weight: 600; text-align: right; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">Points</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    foreach ($leaders as $student): 
                        // Fetch badges for this student (Updated table names)
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
                        <tr style="border-bottom: 1px solid var(--border); transition: background 0.2s;" onmouseover="this.style.background='var(--bg-2)'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 1.5rem 2rem; font-weight: 800; font-size: 1.1rem; color: var(--text-2);">
                                <?php if ($rank === 1): ?>
                                    <span style="color: #FFD700; font-size: 1.4rem;">🥇</span>
                                <?php elseif ($rank === 2): ?>
                                    <span style="color: #C0C0C0; font-size: 1.4rem;">🥈</span>
                                <?php elseif ($rank === 3): ?>
                                    <span style="color: #CD7F32; font-size: 1.4rem;">🥉</span>
                                <?php else: ?>
                                    <span style="color: var(--text-3); margin-left: 5px;">#<?= $rank ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 1.5rem;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 36px; height: 36px; background: var(--accent); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 700; color: white; font-size: 0.9rem;">
                                        <?= strtoupper(substr($student['name'], 0, 1)) ?>
                                    </div>
                                    <a href="<?= $base_path ?>/community/view_student?id=<?= $student['id'] ?>" style="text-decoration: none; color: var(--text); font-weight: 700; font-size: 1rem;">
                                        <?= htmlspecialchars($student['name']) ?>
                                    </a>
                                </div>
                            </td>
                            <td style="padding: 1.5rem; color: var(--text-2); font-size: 0.9rem;">
                                <div style="font-weight: 600;"><?= htmlspecialchars($student['class_name']) ?></div>
                                <div style="font-size: 11px; color: var(--text-3);">Semester <?= $student['semester'] ?></div>
                            </td>
                            <td style="padding: 1.5rem;">
                                <div style="display: flex; gap: 8px;">
                                    <?php if (empty($badges)): ?>
                                        <span style="color: var(--text-3); font-size: 11px; font-style: italic;">No badges</span>
                                    <?php else: ?>
                                        <?php foreach ($badges as $badge): ?>
                                            <span title="<?= htmlspecialchars($badge['badge_name']) ?>" style="background: var(--bg-2); padding: 5px 8px; border-radius: 6px; color: var(--accent); font-size: 12px; border: 1px solid var(--border);">
                                                <?= htmlspecialchars($badge['icon'] ?? '🏆') ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td style="padding: 1.5rem 2rem; text-align: right; font-weight: 800; color: var(--accent); font-size: 1.2rem;">
                                <?= number_format($student['community_score']) ?>
                            </td>
                        </tr>
                    <?php 
                        $rank++;
                    endforeach; 
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
