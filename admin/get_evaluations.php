<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/functions.php';
require_once '../config/database.php';

if (isset($_GET['faculty_id'])) {
    $faculty_id = intval($_GET['faculty_id']);

    // Fetch evaluations for the faculty grouped by title
    $stmt = $conn->prepare("
    SELECT c.title, c.question, COUNT(e.rating) as total_count, AVG(e.rating) as average_rating
    FROM evaluation e
    JOIN criteria c ON e.criteria_id = c.criteria_id
    WHERE e.faculty_id = ?
    GROUP BY c.title, c.question
    ORDER BY c.title, c.question");
    $stmt->bind_param("i", $faculty_id);

    if (!$stmt->execute()) {
        echo json_encode(['error' => 'Query execution failed: ' . $stmt->error]);
        exit;
    }

    $result = $stmt->get_result();
    $evaluations = [];
    $totals = [];

    while ($row = $result->fetch_assoc()) {
        $evaluations[] = $row;
        $title = $row['title'];
        if (!isset($totals[$title])) {
            $totals[$title] = ['count' => 0, 'average' => 0];
        }
        $totals[$title]['count'] += $row['total_count'];
        $totals[$title]['average'] += $row['average_rating'];
    }

    echo json_encode(['evaluations' => $evaluations, 'totals' => $totals]);
}
