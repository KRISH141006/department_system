<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

$subject = $_GET['subject'] ?? '';
$unit = $_GET['unit'] ?? '';

$units = [
    1 => [
        "name" => "Internet Basics",
        "topics" => [
            "Internet", "World Wide Web", "URL", "Web Server", "Web Browser", "Internet Connectivity",
            "Internet Network", "Services on Internet", "Current Trends on Internet", "Concept of WWW",
            "HTTP Response and Request", "Features of Web 2.0"
        ]
    ],
    2 => [
        "name" => "HTML and CSS",
        "topics" => [
            "Basics of HTML", "HTML tags and attributes", "Meta tags", "Character entities", "Hyperlink",
            "Table", "Lists", "Images", "Forms", "Divs", "XHTML", "Browser Architecture and website structure",
            "Overview and features of HTML 5", "Need for CSS", "Basic syntax and structure of CSS",
            "Background images", "Colors and properties", "Manipulating texts", "Fonts", "Borders and boxes",
            "Margin", "Padding", "Lists in CSS", "Positioning using CSS", "Gradients", "Shadow effects",
            "Transformation", "Transition and animations", "CSS Flex", "Media queries", "Overview of CSS",
            "CSS2 and features of CSS3"
        ]
    ],
    3 => [
        "name" => "JavaScript",
        "topics" => [
            "Client-side scripting with JavaScript", "Variables", "Functions", "Conditions", "Loops and repetition",
            "Pop up boxes", "JavaScript and objects", "JavaScript own objects", "DOM and web browser environments",
            "Manipulation using DOM", "Forms and validations", "DHTML", "Combining HTML, CSS and JavaScript",
            "Events and buttons", "Introduction to jQuery", "jQuery syntax", "Selectors", "Events", "Effects",
            "jQuery HTML", "Access / Manipulate web browser elements using jQuery"
        ]
    ],
    4 => [
        "name" => "XML",
        "topics" => [
            "Introduction to XML", "Uses of XML", "Simple XML", "XML key components", "DTD and Schemas",
            "Using XML with application", "Transforming XML using XSL and XSLT"
        ]
    ],
    5 => [
        "name" => "PHP",
        "topics" => [
            "Introduction and basic syntax of PHP", "Decision and looping with examples", "PHP and HTML",
            "Arrays", "Functions", "Browser control and detection", "String", "Form processing", "Files",
            "Cookies and Sessions", "Object Oriented Programming with PHP"
        ]
    ],
    6 => [
        "name" => "PHP and MySQL",
        "topics" => [
            "Basic commands with PHP examples", "Connection to server", "Creating database", "Selecting a database",
            "Listing database", "Listing table names", "Creating a table", "Inserting data", "Altering tables",
            "Queries", "Deleting database", "Deleting data and tables", "PHPMyAdmin", "Database bugs"
        ]
    ],
    7 => [
        "name" => "Latest Trends in PHP",
        "topics" => [
            "Overview of Laravel", "Laravel Application Structure", "Introduction to WordPress",
            "WordPress Dashboard", "Overview of Joomla", "Joomla Architecture", "Application of Joomla"
        ]
    ]
];

if ($subject != "IWT") {
    $page_title = "Error";
    require_once __DIR__ . '/../../app/includes/header.php';
    echo "<div class='wrapper' style='padding:2rem;'><div class='alert alert-error'>Syllabus for $subject is not yet uploaded.</div><a href='student_dashboard.php' class='btn btn-primary'>Back</a></div>";
    require_once __DIR__ . '/../../app/includes/footer.php';
    exit();
}

$canGiveFeedback = false;
$student_id = (int) $_SESSION['user_id'];
$today = date('Y-m-d');
$feedChk = $conn->prepare("SELECT 1 FROM feedback_selector WHERE selected_student_id = ? AND selected_date = ?");
$feedChk->bind_param("is", $student_id, $today);
$feedChk->execute();
if ($feedChk->get_result()->num_rows > 0) {
    $canGiveFeedback = true;
}

$page_title = "$subject Syllabus";
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div class="dashboard-header" style="margin-bottom: 2rem;">
        <div class="dashboard-title">
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; color: var(--text);"><?php echo htmlspecialchars($subject); ?> Syllabus</h1>
            <p style="color: var(--text-2);">Select a unit to view or track covered topics.</p>
        </div>
        <div class="dashboard-actions">
            <a href="student_dashboard.php" class="btn btn-secondary">Back to Academics</a>
        </div>
    </div>

    <?php if ($unit == '') { ?>
        <div class="grid-2">
            <?php foreach ($units as $number => $data) { ?>
                <a href="units.php?subject=<?php echo urlencode($subject); ?>&unit=<?php echo $number; ?>" class="card" style="text-decoration: none; color: inherit;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <p style="color: var(--accent); font-weight: 700; font-size: 13px;">UNIT <?php echo $number; ?></p>
                            <h3 style="margin-top: 4px;"><?php echo htmlspecialchars($data['name']); ?></h3>
                        </div>
                        <span class="badge badge-success"><?php echo count($data['topics']); ?> Topics</span>
                    </div>
                </a>
            <?php } ?>
        </div>
    <?php } else { ?>
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
                <div>
                    <p style="color: var(--accent); font-weight: 700; font-size: 13px;">UNIT <?php echo $unit; ?></p>
                    <h1 style="font-family: 'DM Serif Display', serif;"><?php echo htmlspecialchars($units[$unit]['name']); ?></h1>
                </div>
                <a href="units.php?subject=<?php echo urlencode($subject); ?>" class="btn btn-secondary">All Units</a>
            </div>

            <form action="../../app/actions/academics/save_topics.php" method="POST">
                <input type="hidden" name="subject" value="<?php echo htmlspecialchars($subject); ?>">
                <input type="hidden" name="unit_no" value="<?php echo $unit; ?>">
                
                <table class="table-minimal" style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 1px solid var(--border);">
                            <th style="width: 50px; padding: 12px;">Status</th>
                            <th style="padding: 12px;">Topic Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($units[$unit]['topics'] as $topic) { 
                            $covered = 0;
                            $checkTopic = $conn->prepare("SELECT is_covered FROM topic_progress WHERE subject=? AND unit_no=? AND topic_name=?");
                            $checkTopic->bind_param("sis", $subject, $unit, $topic);
                            $checkTopic->execute();
                            $res = $checkTopic->get_result();
                            if ($res->num_rows > 0) {
                                $data = $res->fetch_assoc();
                                $covered = $data['is_covered'];
                            }
                        ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 12px;">
                                    <input type="checkbox" name="topics[]" value="<?php echo htmlspecialchars($topic); ?>" 
                                           <?php if ($covered == 1) echo "checked"; ?>
                                           <?php if (!$canGiveFeedback) echo "disabled"; ?>
                                           style="width: 20px; height: 20px; cursor: pointer;">
                                </td>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($topic); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>

                <?php if ($canGiveFeedback) { ?>
                    <div style="margin-top: 32px; text-align: right;">
                        <button type="submit" class="btn btn-primary">Confirm Covered Topics</button>
                    </div>
                <?php } else { ?>
                    <p style="margin-top: 24px; color: var(--text-2); font-size: 14px; font-style: italic;">
                        * You can only mark topics as covered when selected for today's feedback.
                    </p>
                <?php } ?>
            </form>
        </div>
    <?php } ?>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>