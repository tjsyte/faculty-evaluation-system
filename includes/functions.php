<?php
// Authentication functions
function check_auth() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        header('Location: /index.php');
        exit;
    }
}

function check_admin() {
    check_auth();
    if ($_SESSION['user_type'] !== 'admin') {
        set_flash_message('You do not have permission to access this area.', 'bg-red-100 text-red-700');
        header('Location: /index.php');
        exit;
    }
}

function check_faculty() {
    check_auth();
    if ($_SESSION['user_type'] !== 'faculty') {
        set_flash_message('You do not have permission to access this area.', 'bg-red-100 text-red-700');
        header('Location: /index.php');
        exit;
    }
}

function check_student() {
    check_auth();
    if ($_SESSION['user_type'] !== 'students') {
        set_flash_message('You do not have permission to access this area.', 'bg-red-100 text-red-700');
        header('Location: /index.php');
        exit;
    }
}

// Flash message functions
function set_flash_message($message, $type = 'bg-blue-100 text-blue-700') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

// Database helper functions
function get_departments($conn) {
    $result = $conn->query("SELECT * FROM department ORDER BY department_name");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function get_courses($conn, $department_id = null) {
    $sql = "SELECT c.*, d.department_name 
            FROM course c 
            JOIN department d ON c.department_id = d.department_id";
    if ($department_id) {
        $sql .= " WHERE c.department_id = " . intval($department_id);
    }
    $sql .= " ORDER BY c.course_name";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function get_sections($conn, $course_id = null) {
    $sql = "SELECT s.*, c.course_name 
            FROM section s 
            JOIN course c ON s.course_id = c.course_id";
    if ($course_id) {
        $sql .= " WHERE s.course_id = " . intval($course_id);
    }
    $sql .= " ORDER BY s.section_name";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function get_faculty($conn, $department_id = null) {
    $sql = "SELECT f.*, d.department_name 
            FROM faculty f 
            JOIN department d ON f.department_id = d.department_id";
    if ($department_id) {
        $sql .= " WHERE f.department_id = " . intval($department_id);
    }
    $sql .= " ORDER BY f.name";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function get_faculty_subjects($conn, $faculty_id) {
    $sql = "SELECT fs.*, c.course_name, s.section_name, d.department_name
            FROM faculty_subjects fs
            JOIN course c ON fs.course_id = c.course_id
            JOIN section s ON fs.section_id = s.section_id
            JOIN department d ON c.department_id = d.department_id
            WHERE fs.faculty_id = ?
            ORDER BY c.course_name, s.section_name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $faculty_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function get_students($conn, $section_id = null) {
    $sql = "SELECT st.*, s.section_name, c.course_name, d.department_name
            FROM students st
            JOIN section s ON st.section_id = s.section_id
            JOIN course c ON s.course_id = c.course_id
            JOIN department d ON c.department_id = d.department_id";
    if ($section_id) {
        $sql .= " WHERE st.section_id = " . intval($section_id);
    }
    $sql .= " ORDER BY st.name";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function get_evaluation_criteria($conn) {
    $result = $conn->query("SELECT * FROM criteria ORDER BY criteria_id");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function is_evaluation_open($conn) {
    $result = $conn->query("SELECT is_open FROM evaluation_settings ORDER BY setting_id DESC LIMIT 1");
    if ($row = $result->fetch_assoc()) {
        return $row['is_open'];
    }
    // If no settings exist, create default setting (open)
    $conn->query("INSERT INTO evaluation_settings (is_open) VALUES (1)");
    return true;
}

function get_faculty_ratings($conn, $faculty_id) {
    $sql = "SELECT 
                COUNT(DISTINCT student_id) as total_students,
                COUNT(*) as total_evaluations,
                AVG(rating) as average_rating,
                MIN(evaluation_date) as first_evaluation,
                MAX(evaluation_date) as last_evaluation
            FROM evaluation 
            WHERE faculty_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $faculty_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function has_student_evaluated($conn, $student_id, $faculty_id) {
    $sql = "SELECT COUNT(*) as count FROM evaluation 
            WHERE student_id = ? AND faculty_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $student_id, $faculty_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['count'] > 0;
}

function generate_report($conn, $faculty_id) {
    $ratings = get_faculty_ratings($conn, $faculty_id);
    
    // Insert or update report
    $sql = "INSERT INTO reports (faculty_id, total_evaluations, average_rating)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            total_evaluations = VALUES(total_evaluations),
            average_rating = VALUES(average_rating),
            generated_at = CURRENT_TIMESTAMP";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iid", 
        $faculty_id, 
        $ratings['total_evaluations'], 
        $ratings['average_rating']
    );
    return $stmt->execute();
}

// Validation functions
function validate_password($password) {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
    return strlen($password) >= 8 && 
           preg_match('/[A-Z]/', $password) && 
           preg_match('/[a-z]/', $password) && 
           preg_match('/[0-9]/', $password);
}

// Utility functions
function format_date($date) {
    return date('F j, Y g:i A', strtotime($date));
}

function format_rating($rating) {
    return number_format($rating, 2);
}

function get_rating_class($rating) {
    if ($rating >= 4.5) return 'bg-green-100 text-green-800';
    if ($rating >= 4.0) return 'bg-blue-100 text-blue-800';
    if ($rating >= 3.0) return 'bg-yellow-100 text-yellow-800';
    return 'bg-red-100 text-red-800';
}
?>
