<?php
// shared/helpers/notifications.php

if (!function_exists('notifications_connection_ready')) {
    function notifications_connection_ready($conn): bool {
        return $conn instanceof mysqli && !$conn->connect_errno;
    }
}

if (!function_exists('ensure_notifications_table')) {
    function ensure_notifications_table(mysqli $conn): bool {
        static $ready = false;
        if ($ready) {
            return true;
        }

        if (!notifications_connection_ready($conn)) {
            return false;
        }

        $sql = "
            CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                actor_id INT NULL,
                type VARCHAR(60) NOT NULL,
                title VARCHAR(160) NOT NULL,
                message TEXT NOT NULL,
                link_url VARCHAR(255) NULL,
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                read_at TIMESTAMP NULL,
                INDEX idx_notifications_user_read_created (user_id, is_read, created_at),
                INDEX idx_notifications_actor (actor_id),
                CONSTRAINT fk_notifications_user
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_notifications_actor
                    FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";

        $ready = (bool) $conn->query($sql);
        return $ready;
    }
}

if (!function_exists('create_notification')) {
    function create_notification(
        mysqli $conn,
        int $user_id,
        string $type,
        string $title,
        string $message,
        ?string $link_url = null,
        ?int $actor_id = null
    ): bool {
        if (!notifications_connection_ready($conn) || $user_id <= 0) {
            return false;
        }

        $type = substr(trim($type), 0, 60);
        $title = substr(trim($title), 0, 160);
        $message = trim($message);
        $link_url = $link_url !== null ? substr(trim($link_url), 0, 255) : null;
        $actor = ($actor_id !== null && $actor_id > 0) ? $actor_id : null;

        if ($type === '' || $title === '' || $message === '') {
            return false;
        }

        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, actor_id, type, title, message, link_url)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("iissss", $user_id, $actor, $type, $title, $message, $link_url);
        return (bool) $stmt->execute();
    }
}

if (!function_exists('create_notifications')) {
    function create_notifications(
        mysqli $conn,
        array $user_ids,
        string $type,
        string $title,
        string $message,
        ?string $link_url = null,
        ?int $actor_id = null
    ): int {
        $unique_ids = [];
        foreach ($user_ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $unique_ids[$id] = $id;
            }
        }

        $created = 0;
        foreach ($unique_ids as $id) {
            if (create_notification($conn, $id, $type, $title, $message, $link_url, $actor_id)) {
                $created++;
            }
        }

        return $created;
    }
}

if (!function_exists('notification_user_ids_by_role')) {
    function notification_user_ids_by_role(mysqli $conn, string $role): array {
        if (!notifications_connection_ready($conn)) {
            return [];
        }

        $stmt = $conn->prepare("SELECT id FROM users WHERE role = ?");
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("s", $role);
        $stmt->execute();
        $res = $stmt->get_result();

        $ids = [];
        while ($row = $res->fetch_assoc()) {
            $ids[] = (int) $row['id'];
        }

        return $ids;
    }
}

if (!function_exists('notify_role')) {
    function notify_role(
        mysqli $conn,
        string $role,
        string $type,
        string $title,
        string $message,
        ?string $link_url = null,
        ?int $actor_id = null
    ): int {
        return create_notifications(
            $conn,
            notification_user_ids_by_role($conn, $role),
            $type,
            $title,
            $message,
            $link_url,
            $actor_id
        );
    }
}

if (!function_exists('notification_user_ids_by_permission')) {
    function notification_user_ids_by_permission(mysqli $conn, string $permission): array {
        if (!notifications_connection_ready($conn)) {
            return [];
        }

        $stmt = $conn->prepare("
            SELECT DISTINCT u.id
            FROM users u
            JOIN role_permissions rp ON rp.role = u.role
            JOIN permissions p ON p.id = rp.permission_id
            WHERE p.permission_name = ?
        ");

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("s", $permission);
        $stmt->execute();
        $res = $stmt->get_result();

        $ids = [];
        while ($row = $res->fetch_assoc()) {
            $ids[] = (int) $row['id'];
        }

        return $ids;
    }
}

if (!function_exists('notify_permission')) {
    function notify_permission(
        mysqli $conn,
        string $permission,
        string $type,
        string $title,
        string $message,
        ?string $link_url = null,
        ?int $actor_id = null
    ): int {
        return create_notifications(
            $conn,
            notification_user_ids_by_permission($conn, $permission),
            $type,
            $title,
            $message,
            $link_url,
            $actor_id
        );
    }
}

if (!function_exists('get_user_notifications')) {
    function get_user_notifications(mysqli $conn, int $user_id, int $limit = 10): array {
        if (!notifications_connection_ready($conn) || $user_id <= 0) {
            return [];
        }

        $limit = max(1, min(30, $limit));
        $stmt = $conn->prepare("
            SELECT id, type, title, message, link_url, is_read, created_at
            FROM notifications
            WHERE user_id = ?
            ORDER BY is_read ASC, created_at DESC
            LIMIT $limit
        ");

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

if (!function_exists('get_unread_notification_count')) {
    function get_unread_notification_count(mysqli $conn, int $user_id): int {
        if (!notifications_connection_ready($conn) || $user_id <= 0) {
            return 0;
        }

        $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM notifications WHERE user_id = ? AND is_read = 0");
        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return (int) ($row['count'] ?? 0);
    }
}

if (!function_exists('mark_notification_read')) {
    function mark_notification_read(mysqli $conn, int $notification_id, int $user_id): bool {
        if (!notifications_connection_ready($conn) || $notification_id <= 0 || $user_id <= 0) {
            return false;
        }

        $stmt = $conn->prepare("
            UPDATE notifications
            SET is_read = 1, read_at = COALESCE(read_at, NOW())
            WHERE id = ? AND user_id = ?
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("ii", $notification_id, $user_id);
        return (bool) $stmt->execute();
    }
}

if (!function_exists('mark_all_notifications_read')) {
    function mark_all_notifications_read(mysqli $conn, int $user_id): bool {
        if (!notifications_connection_ready($conn) || $user_id <= 0) {
            return false;
        }

        $stmt = $conn->prepare("
            UPDATE notifications
            SET is_read = 1, read_at = COALESCE(read_at, NOW())
            WHERE user_id = ? AND is_read = 0
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $user_id);
        return (bool) $stmt->execute();
    }
}

if (!function_exists('notification_class_subject_context')) {
    function notification_class_subject_context(mysqli $conn, int $class_subject_id): ?array {
        if (!notifications_connection_ready($conn) || $class_subject_id <= 0) {
            return null;
        }

        $stmt = $conn->prepare("
            SELECT
                cs.id,
                cs.class_id,
                cs.subject_id,
                s.name AS subject_name,
                s.type AS subject_type,
                c.name AS class_name,
                c.semester
            FROM class_subjects cs
            JOIN subjects s ON s.id = cs.subject_id
            JOIN classes c ON c.id = cs.class_id
            WHERE cs.id = ?
            LIMIT 1
        ");

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("i", $class_subject_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }
}

if (!function_exists('notification_student_ids_for_class_subject')) {
    function notification_student_ids_for_class_subject(mysqli $conn, int $class_subject_id, bool $all_elective_students = false): array {
        $context = notification_class_subject_context($conn, $class_subject_id);
        if (!$context) {
            return [];
        }

        $ids = [];
        if ($context['subject_type'] === 'elective') {
            if ($all_elective_students) {
                $stmt = $conn->prepare("
                    SELECT DISTINCT ss.student_id
                    FROM student_subjects ss
                    JOIN class_subjects cs ON cs.id = ss.class_subject_id
                    WHERE cs.subject_id = ? AND ss.status = 'enrolled'
                ");
                $subject_id = (int) $context['subject_id'];
                if ($stmt) {
                    $stmt->bind_param("i", $subject_id);
                }
            } else {
                $stmt = $conn->prepare("
                    SELECT DISTINCT student_id
                    FROM student_subjects
                    WHERE class_subject_id = ? AND status = 'enrolled'
                ");
                if ($stmt) {
                    $stmt->bind_param("i", $class_subject_id);
                }
            }
        } elseif (strcasecmp((string) $context['class_name'], 'ALL') === 0) {
            $stmt = $conn->prepare("
                SELECT DISTINCT st.user_id
                FROM students st
                JOIN classes c ON c.id = st.class_id
                WHERE c.semester = ?
            ");
            $semester = (int) $context['semester'];
            if ($stmt) {
                $stmt->bind_param("i", $semester);
            }
        } else {
            $stmt = $conn->prepare("SELECT DISTINCT user_id FROM students WHERE class_id = ?");
            $class_id = (int) $context['class_id'];
            if ($stmt) {
                $stmt->bind_param("i", $class_id);
            }
        }

        if (!$stmt) {
            return [];
        }

        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $ids[] = (int) ($row['student_id'] ?? $row['user_id']);
        }

        return array_values(array_unique(array_filter($ids)));
    }
}
