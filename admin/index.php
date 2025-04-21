<?php
include '../includes/header.php';
include '../includes/functions.php';

// Check if user is admin
check_admin();

// Get statistics
$stats = [];

// Total Departments
$result = $conn->query("SELECT COUNT(*) as count FROM department");
$stats['departments'] = $result->fetch_assoc()['count'];

// Total Courses
$result = $conn->query("SELECT COUNT(*) as count FROM course");
$stats['courses'] = $result->fetch_assoc()['count'];

// Total Sections
$result = $conn->query("SELECT COUNT(*) as count FROM section");
$stats['sections'] = $result->fetch_assoc()['count'];

// Total Faculty
$result = $conn->query("SELECT COUNT(*) as count FROM faculty");
$stats['faculty'] = $result->fetch_assoc()['count'];

// Total Students
$result = $conn->query("SELECT COUNT(*) as count FROM students");
$stats['students'] = $result->fetch_assoc()['count'];

// Total Evaluations
$result = $conn->query("SELECT COUNT(*) as count FROM evaluation");
$stats['evaluations'] = $result->fetch_assoc()['count'];

// Average Rating
$result = $conn->query("SELECT AVG(rating) as avg FROM evaluation");
$stats['avg_rating'] = number_format($result->fetch_assoc()['avg'] ?? 0, 2);

// Recent Evaluations
$recent_evaluations = $conn->query("
    SELECT e.*, f.name as faculty_name, s.name as student_name, c.title as criteria_title
    FROM evaluation e
    JOIN faculty f ON e.faculty_id = f.faculty_id
    JOIN students s ON e.student_id = s.student_id
    JOIN criteria c ON e.criteria_id = c.criteria_id
    ORDER BY e.evaluation_date DESC
    LIMIT 5
");

// Top Rated Faculty
$top_faculty = $conn->query("
    SELECT 
        f.faculty_id,
        f.name,
        COUNT(e.evaluation_id) as total_evaluations,
        AVG(e.rating) as average_rating
    FROM faculty f
    LEFT JOIN evaluation e ON f.faculty_id = e.faculty_id
    GROUP BY f.faculty_id, f.name
    HAVING total_evaluations > 0
    ORDER BY average_rating DESC
    LIMIT 5
");
?>

<div class="space-y-6">
    <!-- Page Title -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">Admin Dashboard</h1>
        <div class="flex items-center space-x-4">
            <span class="text-sm text-gray-500">
                <?php echo is_evaluation_open($conn) ? 
                    '<span class="text-green-600"><i class="fas fa-check-circle"></i> Evaluations are open</span>' : 
                    '<span class="text-red-600"><i class="fas fa-times-circle"></i> Evaluations are closed</span>'; 
                ?>
            </span>
            <a href="evaluation_settings.php" class="btn-primary">
                <i class="fas fa-cog"></i> Settings
            </a>
            <a href="../logout.php" class="btn-secondary">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Departments Card -->
        <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Departments</p>
                    <h3 class="text-2xl font-bold text-gray-900"><?php echo $stats['departments']; ?></h3>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <i class="fas fa-building text-blue-600"></i>
                </div>
            </div>
            <a href="departments.php" class="mt-4 text-sm text-blue-600 hover:text-blue-800 flex items-center">
                View Details <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        <!-- Faculty Card -->
        <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Faculty</p>
                    <h3 class="text-2xl font-bold text-gray-900"><?php echo $stats['faculty']; ?></h3>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <i class="fas fa-chalkboard-teacher text-green-600"></i>
                </div>
            </div>
            <a href="faculty.php" class="mt-4 text-sm text-green-600 hover:text-green-800 flex items-center">
                View Details <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        <!-- Students Card -->
        <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Students</p>
                    <h3 class="text-2xl font-bold text-gray-900"><?php echo $stats['students']; ?></h3>
                </div>
                <div class="bg-purple-100 p-3 rounded-full">
                    <i class="fas fa-user-graduate text-purple-600"></i>
                </div>
            </div>
            <a href="students.php" class="mt-4 text-sm text-purple-600 hover:text-purple-800 flex items-center">
                View Details <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        <!-- Evaluations Card -->
        <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Evaluations</p>
                    <h3 class="text-2xl font-bold text-gray-900"><?php echo $stats['evaluations']; ?></h3>
                </div>
                <div class="bg-yellow-100 p-3 rounded-full">
                    <i class="fas fa-star text-yellow-600"></i>
                </div>
            </div>
            <p class="mt-2 text-sm text-gray-500">Average Rating: <?php echo $stats['avg_rating']; ?>/5.00</p>
        </div>
    </div>

    <!-- Recent Activity and Top Faculty -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Evaluations -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h2 class="text-lg font-semibold text-gray-900">Recent Evaluations</h2>
            </div>
            <div class="p-6">
                <?php if ($recent_evaluations->num_rows > 0): ?>
                    <div class="space-y-4">
                        <?php while ($eval = $recent_evaluations->fetch_assoc()): ?>
                            <div class="flex items-start space-x-4">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center">
                                        <i class="fas fa-user text-gray-600"></i>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($eval['student_name']); ?> evaluated 
                                        <?php echo htmlspecialchars($eval['faculty_name']); ?>
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        Rating: <?php echo $eval['rating']; ?>/5
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        <?php echo format_date($eval['evaluation_date']); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center">No recent evaluations</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Top Rated Faculty -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h2 class="text-lg font-semibold text-gray-900">Top Rated Faculty</h2>
            </div>
            <div class="p-6">
                <?php if ($top_faculty->num_rows > 0): ?>
                    <div class="space-y-4">
                        <?php while ($faculty = $top_faculty->fetch_assoc()): ?>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                            <i class="fas fa-user text-blue-600"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($faculty['name']); ?>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            <?php echo $faculty['total_evaluations']; ?> evaluations
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo number_format($faculty['average_rating'], 2); ?>/5.00
                                    </div>
                                    <div class="text-xs text-yellow-500">
                                        <?php 
                                        $stars = round($faculty['average_rating']);
                                        for ($i = 0; $i < 5; $i++) {
                                            echo $i < $stars ? '★' : '☆';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center">No faculty ratings available</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
