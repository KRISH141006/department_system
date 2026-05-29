<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

$page_title = "Community Leaderboard";
require_once __DIR__ . '/../../app/includes/header.php';

// Fetch top 10 students by community score
$leader_query = $conn->query("
    SELECT u.id, u.name, p.community_score, u.class_name, u.semester
    FROM users u
    JOIN profiles p ON u.id = p.user_id
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

        <div class="card" style="padding: 0; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead style="background: var(--bg-2); border-bottom: 1px solid var(--border);">
                    <tr>
                        <th style="padding: 1.25rem 2rem; color: var(--text-3); font-weight: 500; width: 80px;">Rank</th>
                        <th style="padding: 1.25rem; color: var(--text-3); font-weight: 500;">Student</th>
                        <th style="padding: 1.25rem; color: var(--text-3); font-weight: 500;">Class</th>
                        <th style="padding: 1.25rem; color: var(--text-3); font-weight: 500;">Badges</th>
                        <th style="padding: 1.25rem 2rem; color: var(--text-3); font-weight: 500; text-align: right;">Points</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    foreach ($leaders as $student): 
                        // Fetch badges for this student
                        $badge_query = $conn->prepare("SELECT badge_name, icon_class FROM student_badges WHERE student_id = ? LIMIT 3");
                        $badge_query->bind_param("i", $student['id']);
                        $badge_query->execute();
                        $badges = $badge_query->get_result()->fetch_all(MYSQLI_ASSOC);
                    ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 1.5rem 2rem; font-weight: 600; font-size: 1.1rem; color: var(--text-2);">
                                <?php if ($rank === 1): ?>
                                    <span style="color: #FFD700;">#1</span>
                                <?php elseif ($rank === 2): ?>
                                    <span style="color: #C0C0C0;">#2</span>
                                <?php elseif ($rank === 3): ?>
                                    <span style="color: #CD7F32;">#3</span>
                                <?php else: ?>
                                    #<?= $rank ?>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 1.5rem;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 32px; height: 32px; background: var(--bg-2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; color: var(--primary);">
                                        <?= substr($student['name'], 0, 1) ?>
                                    </div>
                                    <a href="view_student.php?id=<?= $student['id'] ?>" style="text-decoration: none; color: var(--text); font-weight: 600;">
                                        <?= htmlspecialchars($student['name']) ?>
                                    </a>
                                </div>
                            </td>
                            <td style="padding: 1.5rem; color: var(--text-2); font-size: 0.9rem;">
                                <?= htmlspecialchars($student['class_name']) ?> (Sem <?= $student['semester'] ?>)
                            </td>
                            <td style="padding: 1.5rem;">
                                <div style="display: flex; gap: 6px;">
                                    <?php foreach ($badges as $badge): ?>
                                        <span title="<?= htmlspecialchars($badge['badge_name']) ?>" style="background: var(--bg-2); width: 24px; height: 24px; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 0.75rem;">
                                            <i class="fa <?= $badge['icon_class'] ?>"></i>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td style="padding: 1.5rem 2rem; text-align: right; font-weight: 700; color: var(--primary); font-size: 1.1rem;">
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

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
