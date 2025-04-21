<?php
include '../includes/header.php';
include '../includes/functions.php';

// Check if user is student
check_student();

// Get student information
$student_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT s.*, sec.section_name, c.course_name, d.department_name
    FROM students s
    JOIN section sec ON s.section_id = sec.section_id
    JOIN course c ON sec.course_id = c.course_id
    JOIN department d ON c.department_id = d.department_id
    WHERE s.student_id = ?
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Get faculty members to evaluate
$stmt = $conn->prepare("
    SELECT DISTINCT f.faculty_id, f.name as faculty_name, f.email,
           d.department_name, fs.subject_name,
           CASE WHEN e.evaluation_id IS NOT NULL THEN 1 ELSE 0 END as is_evaluated
    FROM faculty_subjects fs
    JOIN faculty f ON fs.faculty_id = f.faculty_id
    JOIN department d ON f.department_id = d.department_id
    JOIN section s ON fs.section_id = s.section_id
    LEFT JOIN evaluation e ON f.faculty_id = e.faculty_id AND e.student_id = ?
    WHERE s.section_id = ?
    ORDER BY f.name
");
$stmt->bind_param("ii", $student_id, $student['section_id']);
$stmt->execute();
$faculty_to_evaluate = $stmt->get_result();

// Get evaluation statistics
$stmt = $conn->prepare("
    SELECT COUNT(*) as total_evaluations,
           COUNT(DISTINCT faculty_id) as faculty_evaluated
    FROM evaluation
    WHERE student_id = ?
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Check if evaluations are open
$evaluations_open = is_evaluation_open($conn);
?>

<div class="space-y-6">
    <!-- Student Information -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center space-x-4">
                <div class="flex-shrink-0">
                    <div class="h-16 w-16 rounded-full bg-indigo-100 flex items-center justify-center">
                        <i class="fas fa-user-graduate text-2xl text-indigo-600"></i>
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-medium text-gray-900">
                        <?php echo htmlspecialchars($student['name']); ?>
                    </h3>
                    <p class="text-sm text-gray-500">
                        <?php echo htmlspecialchars($student['section_name']); ?> - 
                        <?php echo htmlspecialchars($student['course_name']); ?> - 
                        <?php echo htmlspecialchars($student['department_name']); ?>
                    </p>
                </div>
                <div class="ml-auto">
                    <a href="../logout.php" class="btn-secondary">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Evaluation Status -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900">Evaluation Status</h3>
            <div class="mt-4">
                <?php if ($evaluations_open): ?>
                    <div class="rounded-md bg-green-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">
                                    Evaluations are currently open
                                </p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="rounded-md bg-yellow-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-yellow-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-yellow-800">
                                    Evaluations are currently closed
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500">Faculty Evaluated</dt>
                <dd class="mt-1 flex justify-between items-baseline md:block lg:flex">
                    <div class="flex items-baseline text-2xl font-semibold text-indigo-600">
                        <?php echo $stats['faculty_evaluated']; ?>
                    </div>
                </dd>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500">Total Evaluations Submitted</dt>
                <dd class="mt-1 flex justify-between items-baseline md:block lg:flex">
                    <div class="flex items-baseline text-2xl font-semibold text-indigo-600">
                        <?php echo $stats['total_evaluations']; ?>
                    </div>
                </dd>
            </div>
        </div>
    </div>

    <!-- Faculty List -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg font-medium text-gray-900">Faculty to Evaluate</h3>
            <p class="mt-1 text-sm text-gray-500">
                List of faculty members teaching your section
            </p>
        </div>
        <div class="border-t border-gray-200">
            <ul role="list" class="divide-y divide-gray-200">
                <?php if ($faculty_to_evaluate->num_rows > 0): ?>
                    <?php while ($faculty = $faculty_to_evaluate->fetch_assoc()): ?>
                        <li class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="h-12 w-12 rounded-full bg-gray-100 flex items-center justify-center">
                                            <i class="fas fa-user text-gray-600"></i>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <h4 class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($faculty['faculty_name']); ?>
                                        </h4>
                                        <p class="text-sm text-gray-500">
                                            <?php echo htmlspecialchars($faculty['subject_name']); ?> - 
                                            <?php echo htmlspecialchars($faculty['department_name']); ?>
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <?php if ($faculty['is_evaluated']): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i> Evaluated
                                        </span>
                                    <?php else: ?>
                                        <?php if ($evaluations_open): ?>
                                            <a href="evaluate.php?faculty_id=<?php echo $faculty['faculty_id']; ?>" 
                                                class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-full shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                <i class="fas fa-star mr-1"></i> Evaluate
                                            </a>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                <i class="fas fa-lock mr-1"></i> Closed
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </li>
                    <?php endwhile; ?>
                <?php else: ?>
                    <li class="px-4 py-4 sm:px-6 text-center text-gray-500">
                        No faculty members found for your section
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
