<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';

// Check if user is admin
check_admin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'toggle') {
        $is_open = isset($_POST['is_open']) ? 1 : 0;
        
        // Insert new setting (latest setting is always used)
        $stmt = $conn->prepare("INSERT INTO evaluation_settings (is_open) VALUES (?)");
        $stmt->bind_param("i", $is_open);
        
        if ($stmt->execute()) {
            set_flash_message(
                "Evaluation settings updated successfully!", 
                "bg-green-100 text-green-700"
            );
        } else {
            set_flash_message(
                "Error updating evaluation settings: " . $conn->error, 
                "bg-red-100 text-red-700"
            );
        }
        
        // Redirect to prevent form resubmission
        header("Location: evaluation_settings.php");
        exit;
    }
}

// Get current evaluation status
$is_open = is_evaluation_open($conn);

// Get evaluation statistics
$stats = $conn->query("
    SELECT 
        COUNT(DISTINCT student_id) as total_students,
        COUNT(DISTINCT faculty_id) as total_faculty,
        COUNT(*) as total_evaluations,
        MIN(evaluation_date) as first_evaluation,
        MAX(evaluation_date) as last_evaluation,
        AVG(rating) as average_rating
    FROM evaluation
")->fetch_assoc();

// Get recent evaluations
$recent_evaluations = $conn->query("
    SELECT e.*, f.name as faculty_name, s.name as student_name, c.title as criteria_title
    FROM evaluation e
    JOIN faculty f ON e.faculty_id = f.faculty_id
    JOIN students s ON e.student_id = s.student_id
    JOIN criteria c ON e.criteria_id = c.criteria_id
    ORDER BY e.evaluation_date DESC
    LIMIT 10
");
?>

<div class="space-y-6">
    <!-- Page Title -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">Evaluation Settings</h1>
    </div>

    <!-- Settings Card -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6">
            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="toggle">
                
                <!-- Evaluation Status Toggle -->
                <div>
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Evaluation Status</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                When evaluations are closed, students cannot submit new evaluations.
                            </p>
                        </div>
                        <div class="flex items-center">
                            <button type="submit" 
                                class="relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 <?php echo $is_open ? 'bg-indigo-600' : 'bg-gray-200'; ?>">
                                <span class="sr-only">Toggle evaluation status</span>
                                <span aria-hidden="true" 
                                    class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200 <?php echo $is_open ? 'translate-x-5' : 'translate-x-0'; ?>">
                                </span>
                            </button>
                            <input type="checkbox" name="is_open" class="hidden" <?php echo $is_open ? 'checked' : ''; ?>>
                            <span class="ml-3 text-sm font-medium text-gray-900">
                                <?php echo $is_open ? 'Open' : 'Closed'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Evaluation Statistics -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900">Evaluation Statistics</h3>
                <dl class="mt-4 space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500">Total Evaluations</dt>
                        <dd class="text-sm font-semibold text-gray-900">
                            <?php echo number_format($stats['total_evaluations']); ?>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500">Students Participated</dt>
                        <dd class="text-sm font-semibold text-gray-900">
                            <?php echo number_format($stats['total_students']); ?>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500">Faculty Evaluated</dt>
                        <dd class="text-sm font-semibold text-gray-900">
                            <?php echo number_format($stats['total_faculty']); ?>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500">Average Rating</dt>
                        <dd class="text-sm font-semibold text-gray-900">
                            <?php echo $stats['average_rating'] !== null ? number_format($stats['average_rating'], 2) : 'N/A'; ?>/5.00
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Evaluation Timeline -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900">Evaluation Timeline</h3>
                <dl class="mt-4 space-y-4">
                    <?php if ($stats['first_evaluation']): ?>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">First Evaluation</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <?php echo format_date($stats['first_evaluation']); ?>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Latest Evaluation</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <?php echo format_date($stats['last_evaluation']); ?>
                            </dd>
                        </div>
                    <?php else: ?>
                        <p class="text-sm text-gray-500">No evaluations submitted yet</p>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900">Quick Actions</h3>
                <div class="mt-4 space-y-4">
                    <a href="reports.php" class="btn-secondary w-full justify-center">
                        <i class="fas fa-chart-bar mr-2"></i> View Reports
                    </a>
                    <a href="criteria.php" class="btn-secondary w-full justify-center">
                        <i class="fas fa-list-check mr-2"></i> Manage Criteria
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Evaluations -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Recent Evaluations</h3>
        </div>
        <div class="divide-y divide-gray-200">
            <?php if ($recent_evaluations->num_rows > 0): ?>
                <?php while ($eval = $recent_evaluations->fetch_assoc()): ?>
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                        <i class="fas fa-user-graduate text-indigo-600"></i>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($eval['student_name']); ?>
                                    </h4>
                                    <p class="text-sm text-gray-500">
                                        evaluated <?php echo htmlspecialchars($eval['faculty_name']); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="ml-4 flex-shrink-0">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo get_rating_class($eval['rating']); ?>">
                                    <?php echo $eval['rating']; ?>/5
                                </span>
                                <div class="mt-1 text-xs text-gray-500">
                                    <?php echo format_date($eval['evaluation_date']); ?>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <p class="text-sm text-gray-600">
                                <span class="font-medium"><?php echo htmlspecialchars($eval['criteria_title']); ?>:</span>
                                <?php if (!empty($eval['comments'])): ?>
                                    "<?php echo htmlspecialchars($eval['comments']); ?>"
                                <?php else: ?>
                                    <span class="text-gray-400">No comments provided</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="p-6 text-center text-gray-500">
                    No evaluations submitted yet
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Update checkbox value when toggle button is clicked
document.querySelector('button[type="submit"]').addEventListener('click', function(e) {
    const checkbox = document.querySelector('input[name="is_open"]');
    checkbox.checked = !checkbox.checked;
});
</script>

<style>
.btn-primary {
    @apply inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500;
}

.btn-secondary {
    @apply inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500;
}
</style>

<?php require_once '../includes/footer.php'; ?>
