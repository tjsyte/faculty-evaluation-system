<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';

// Check if user is admin
check_admin();

// Get department filter if set
$department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : null;

// Handle report generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate') {
    // Generate reports for all faculty
    $faculty = $conn->query("SELECT faculty_id FROM faculty");
    while ($f = $faculty->fetch_assoc()) {
        generate_report($conn, $f['faculty_id']);
    }
    set_flash_message("Reports generated successfully!", "bg-green-100 text-green-700");
    header("Location: reports.php");
    exit;
}

// Handle Excel export
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'export') {
    export_reports_to_excel($conn, $department_id);
    exit;
}

// Get all departments for filter
$departments = get_departments($conn);

// Get faculty evaluation reports
$sql = "
    SELECT r.*, f.name as faculty_name, f.email, d.department_name,
           COUNT(DISTINCT fs.faculty_subject_id) as subjects_count
    FROM reports r
    JOIN faculty f ON r.faculty_id = f.faculty_id
    JOIN department d ON f.department_id = d.department_id
    LEFT JOIN faculty_subjects fs ON f.faculty_id = fs.faculty_id
";

if ($department_id) {
    $sql .= " WHERE f.department_id = " . $department_id;
}

$sql .= " GROUP BY r.report_id ORDER BY d.department_name, f.name";

$reports = $conn->query($sql);

// Get overall statistics
$stats = $conn->query("
    SELECT 
        COUNT(DISTINCT e.faculty_id) as evaluated_faculty,
        COUNT(DISTINCT e.student_id) as total_evaluators,
        COUNT(*) as total_evaluations,
        AVG(e.rating) as overall_rating,
        MIN(e.evaluation_date) as first_evaluation,
        MAX(e.evaluation_date) as last_evaluation
    FROM evaluation e
")->fetch_assoc();
?>

<div class="space-y-6">
    <!-- Page Title and Actions -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Faculty Evaluation Reports</h1>
            <p class="mt-2 text-sm text-gray-700">
                View and analyze faculty performance based on student evaluations.
            </p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none space-x-3">
            <!-- Department Filter -->
            <form method="GET" class="inline-block">
                <select name="department_id" onchange="this.form.submit()" 
                    class="block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['department_id']; ?>" 
                            <?php echo $department_id == $dept['department_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept['department_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <!-- Generate Reports Button -->
            <form method="POST" class="inline-block">
                <input type="hidden" name="action" value="generate">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-sync-alt mr-2"></i> Generate Reports
                </button>
            </form>

            <!-- Export Reports Button -->
            <form method="POST" class="inline-block">
                <input type="hidden" name="action" value="export">
                <button type="submit" class="btn-secondary">
                    <i class="fas fa-file-excel mr-2"></i> Export to Excel
                </button>
            </form>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Total Faculty Evaluated -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-chalkboard-teacher text-2xl text-indigo-600"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Faculty Evaluated
                            </dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900">
                                    <?php echo $stats['evaluated_faculty']; ?>
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Evaluations -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-clipboard-list text-2xl text-green-600"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Total Evaluations
                            </dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900">
                                    <?php echo $stats['total_evaluations']; ?>
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Students Participated -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-user-graduate text-2xl text-purple-600"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Students Participated
                            </dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900">
                                    <?php echo $stats['total_evaluators']; ?>
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overall Rating -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-star text-2xl text-yellow-500"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Overall Rating
                            </dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900">
                                    <?php echo $stats['overall_rating'] !== null ? number_format($stats['overall_rating'], 2) : 'N/A'; ?>/5.00
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reports Table -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="mt-8 flow-root">
                <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead>
                                <tr>
                                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900">
                                        Faculty Name
                                    </th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                        Department
                                    </th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                        Subjects
                                    </th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                        Total Evaluations
                                    </th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                        Average Rating
                                    </th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                        Last Updated
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php if ($reports->num_rows > 0): ?>
                                    <?php while ($report = $reports->fetch_assoc()): ?>
                                        <tr>
                                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm">
                                                <div class="font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($report['faculty_name']); ?>
                                                </div>
                                                <div class="text-gray-500">
                                                    <?php echo htmlspecialchars($report['email']); ?>
                                                </div>
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                                <?php echo htmlspecialchars($report['department_name']); ?>
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                                <?php echo $report['subjects_count']; ?> subject(s)
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                                <?php echo $report['total_evaluations']; ?> evaluation(s)
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo get_rating_class($report['average_rating']); ?>">
                                                    <?php echo $report['average_rating'] !== null ? number_format($report['average_rating'], 2) : 'N/A'; ?>/5.00
                                                </span>
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                                <?php echo format_date($report['generated_at']); ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                            No reports available
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Evaluation Period -->
    <?php if ($stats['first_evaluation']): ?>
        <div class="bg-gray-50 border-t border-b border-gray-200 p-4">
            <div class="text-center text-sm text-gray-500">
                Evaluation period: <?php echo format_date($stats['first_evaluation']); ?> to <?php echo format_date($stats['last_evaluation']); ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.btn-primary {
    @apply inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500;
}

.btn-secondary {
    @apply inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500;
}
</style>

<?php require_once '../includes/footer.php'; ?>

<?php
function export_reports_to_excel($conn, $department_id) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="faculty_evaluation_reports.xls"');
    header('Cache-Control: max-age=0');

    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #f2f2f2;'>
            <th style='padding: 8px; text-align: left;'>Faculty Name</th>
            <th style='padding: 8px; text-align: left;'>Department</th>
            <th style='padding: 8px; text-align: left;'>Subjects</th>
            <th style='padding: 8px; text-align: left;'>Total Evaluations</th>
            <th style='padding: 8px; text-align: left;'>Average Rating</th>
            <th style='padding: 8px; text-align: left;'>Last Updated</th>
          </tr>";

    $sql = "
        SELECT r.*, f.name as faculty_name, f.email, d.department_name,
               COUNT(DISTINCT fs.faculty_subject_id) as subjects_count
        FROM reports r
        JOIN faculty f ON r.faculty_id = f.faculty_id
        JOIN department d ON f.department_id = d.department_id
        LEFT JOIN faculty_subjects fs ON f.faculty_id = fs.faculty_id
    ";

    if ($department_id) {
        $sql .= " WHERE f.department_id = " . $department_id;
    }

    $sql .= " GROUP BY r.report_id ORDER BY d.department_name, f.name";

    $reports = $conn->query($sql);

    while ($report = $reports->fetch_assoc()) {
        echo "<tr>
                <td style='padding: 8px;'>" . htmlspecialchars($report['faculty_name']) . "</td>
                <td style='padding: 8px;'>" . htmlspecialchars($report['department_name']) . "</td>
                <td style='padding: 8px;'>" . $report['subjects_count'] . " subject(s)</td>
                <td style='padding: 8px;'>" . $report['total_evaluations'] . " evaluation(s)</td>
                <td style='padding: 8px;'>" . number_format($report['average_rating'], 2) . "/5.00</td>
                <td style='padding: 8px;'>" . format_date($report['generated_at']) . "</td>
              </tr>";
    }

    echo "</table>";
}
?>
