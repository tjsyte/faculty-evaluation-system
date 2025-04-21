<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';

// Check if user is student
check_student();

// Check if evaluations are open
if (!is_evaluation_open($conn)) {
    set_flash_message("Evaluations are currently closed.", "bg-red-100 text-red-700");
    header("Location: index.php");
    exit;
}

// Get faculty ID from URL
$faculty_id = isset($_GET['faculty_id']) ? intval($_GET['faculty_id']) : 0;

// Verify faculty teaches student's section
$stmt = $conn->prepare("
    SELECT f.*, d.department_name, fs.subject_name
    FROM faculty f
    JOIN department d ON f.department_id = d.department_id
    JOIN faculty_subjects fs ON f.faculty_id = fs.faculty_id
    JOIN section s ON fs.section_id = s.section_id
    JOIN students st ON s.section_id = st.section_id
    WHERE f.faculty_id = ? AND st.student_id = ?
");
$stmt->bind_param("ii", $faculty_id, $_SESSION['user_id']);
$stmt->execute();
$faculty = $stmt->get_result()->fetch_assoc();

if (!$faculty) {
    set_flash_message("Invalid faculty member selected.", "bg-red-100 text-red-700");
    header("Location: index.php");
    exit;
}

// Check if student has already evaluated this faculty
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM evaluation 
    WHERE student_id = ? AND faculty_id = ?
");
$stmt->bind_param("ii", $_SESSION['user_id'], $faculty_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if ($result['count'] > 0) {
    set_flash_message("You have already evaluated this faculty member.", "bg-yellow-100 text-yellow-700");
    header("Location: index.php");
    exit;
}

// Get evaluation criteria
$criteria = get_evaluation_criteria($conn);

// Group criteria by title
$grouped_criteria = [];
foreach ($criteria as $criterion) {
    $grouped_criteria[$criterion['title']][] = $criterion;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = true;
    $conn->begin_transaction();

    try {
        foreach ($_POST['ratings'] as $criteria_id => $rating) {
            $comments = $_POST['comments'][$criteria_id] ?? '';

            $stmt = $conn->prepare("
                INSERT INTO evaluation (faculty_id, student_id, criteria_id, rating, comments)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iiiis", $faculty_id, $_SESSION['user_id'], $criteria_id, $rating, $comments);

            if (!$stmt->execute()) {
                throw new Exception("Error saving evaluation: " . $conn->error);
            }
        }

        // Generate updated report
        generate_report($conn, $faculty_id);

        $conn->commit();
        set_flash_message("Evaluation submitted successfully!", "bg-green-100 text-green-700");
        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        set_flash_message($e->getMessage(), "bg-red-100 text-red-700");
    }
}
?>

<div class="max-w-3xl mx-auto space-y-6">
    <!-- Faculty Information -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center space-x-4">
                <div class="flex-shrink-0">
                    <div class="h-16 w-16 rounded-full bg-indigo-100 flex items-center justify-center">
                        <i class="fas fa-user-tie text-2xl text-indigo-600"></i>
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-medium text-gray-900">
                        <?php echo htmlspecialchars($faculty['name']); ?>
                    </h3>
                    <p class="text-sm text-gray-500">
                        <?php echo htmlspecialchars($faculty['subject_name']); ?> -
                        <?php echo htmlspecialchars($faculty['department_name']); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Evaluation Instructions -->
    <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-400"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Instructions</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <ul class="list-disc pl-5 space-y-1">
                        <li>Rate each criterion on a scale of 1 to 5, where:
                            <ul class="pl-5 mt-1 space-y-1">
                                <li>5 = Excellent</li>
                                <li>4 = Very Good</li>
                                <li>3 = Good</li>
                                <li>2 = Fair</li>
                                <li>1 = Poor</li>
                            </ul>
                        </li>
                        <li>Comments are optional but encouraged for constructive feedback.</li>
                        <li>Your evaluation will remain anonymous to the faculty member.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Evaluation Form -->
    <form method="POST" class="space-y-6">
        <?php foreach ($grouped_criteria as $title => $criteria_group): ?>
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6 space-y-4">
                    <div>
                        <h4 class="text-lg font-medium text-gray-900">
                            <?php echo htmlspecialchars($title); ?>
                        </h4>
                        <?php foreach ($criteria_group as $criterion): ?>
                            <p class="mt-1 text-sm text-gray-500">
                                <?php echo htmlspecialchars($criterion['question']); ?>
                            </p>

                            <!-- Rating Stars for each question -->
                            <div class="flex items-center space-x-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <label class="rating-label cursor-pointer">
                                        <input type="radio" name="ratings[<?php echo $criterion['criteria_id']; ?>]"
                                            value="<?php echo $i; ?>" required
                                            class="hidden peer">
                                        <div class="w-12 h-12 rounded-full flex items-center justify-center text-2xl 
                                            border-2 border-gray-300 text-gray-400
                                            peer-checked:border-yellow-400 peer-checked:text-yellow-400
                                            hover:border-yellow-400 hover:text-yellow-400
                                            transition-colors">
                                            <?php echo $i; ?>
                                        </div>
                                    </label>
                                <?php endfor; ?>
                            </div>

                            <!-- Comments for each question -->
                            <div>
                                <label for="comments_<?php echo $criterion['criteria_id']; ?>"
                                    class="block text-sm font-medium text-gray-700">
                                    Comments (Optional)
                                </label>
                                <textarea id="comments_<?php echo $criterion['criteria_id']; ?>"
                                    name="comments[<?php echo $criterion['criteria_id']; ?>]" rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm 
                                        focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    placeholder="Share your thoughts about this aspect..."></textarea>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Submit Button -->
        <div class="flex justify-end space-x-3">
            <a href="index.php" class="btn-secondary">
                Cancel
            </a>
            <button type="submit" class="btn-primary">
                Submit Evaluation
            </button>
        </div>
    </form>
</div>

<style>
    .btn-primary {
        @apply inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500;
    }

    .btn-secondary {
        @apply inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500;
    }

    /* Custom styles for rating stars animation */
    .rating-label:hover~.rating-label div {
        @apply border-gray-300 text-gray-400;
    }

    .rating-label:hover div,
    .rating-label:hover~.rating-label div {
        @apply border-yellow-400 text-yellow-400;
    }
</style>

<script>
    // Confirm before leaving page if form has been modified
    let formModified = false;
    document.querySelector('form').addEventListener('change', function() {
        formModified = true;
    });

    window.addEventListener('beforeunload', function(e) {
        if (formModified) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // Don't show warning when submitting form
    document.querySelector('form').addEventListener('submit', function() {
        formModified = false;
    });
</script>

<?php require_once '../includes/footer.php'; ?>