<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';

// Check if user is admin
check_admin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $title = trim($_POST['title']);
                $question = trim($_POST['question']);

                if (!empty($title) && !empty($question)) {
                    $stmt = $conn->prepare("INSERT INTO criteria (title, question) VALUES (?, ?)");
                    $stmt->bind_param("ss", $title, $question);

                    if ($stmt->execute()) {
                        set_flash_message("Evaluation criteria added successfully!", "bg-green-100 text-green-700");
                    } else {
                        set_flash_message("Error adding criteria: " . $conn->error, "bg-red-100 text-red-700");
                    }
                }
                break;

            case 'edit':
                $id = $_POST['criteria_id'];
                $title = trim($_POST['title']);
                $question = trim($_POST['question']);

                if (!empty($title) && !empty($question)) {
                    $stmt = $conn->prepare("UPDATE criteria SET title = ?, question = ? WHERE criteria_id = ?");
                    $stmt->bind_param("ssi", $title, $question, $id);

                    if ($stmt->execute()) {
                        set_flash_message("Evaluation criteria updated successfully!", "bg-green-100 text-green-700");
                    } else {
                        set_flash_message("Error updating criteria: " . $conn->error, "bg-red-100 text-red-700");
                    }
                }
                break;
        }
    }
    // Redirect to prevent form resubmission
    header("Location: criteria.php");
    exit;
}

// Get all criteria with their usage counts
$criteria = $conn->query("
    SELECT c.*, COUNT(e.evaluation_id) as times_used
    FROM criteria c
    LEFT JOIN evaluation e ON c.criteria_id = e.criteria_id
    GROUP BY c.criteria_id
    ORDER BY c.title
");
?>

<div class="space-y-6">
    <!-- Page Title -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">Manage Evaluation Criteria</h1>
        <button onclick="openAddModal()" class="btn-primary">
            <i class="fas fa-plus"></i> Add Criteria
        </button>
    </div>

    <!-- Help Text -->
    <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-blue-700">
                    Evaluation criteria are the questions that students will answer when evaluating faculty members.
                    Each question will be rated on a scale of 1-5, where 1 is the lowest and 5 is the highest.
                </p>
            </div>
        </div>
    </div>

    <!-- Criteria Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Title
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Question
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Times Used
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($criteria->num_rows > 0): ?>
                    <?php while ($criterion = $criteria->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($criterion['title']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($criterion['question']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    <?php echo $criterion['times_used']; ?> evaluation(s)
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button onclick="openEditModal(<?php
                                                                echo htmlspecialchars(json_encode([
                                                                    'id' => $criterion['criteria_id'],
                                                                    'title' => $criterion['title'],
                                                                    'question' => $criterion['question']
                                                                ]));
                                                                ?>)" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                            No evaluation criteria found
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Criteria Modal -->
<div id="addModal" class="modal hidden fixed z-10 inset-0 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium text-gray-900">Add Evaluation Criteria</h3>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700">
                                Title
                            </label>
                            <input type="text" name="title" id="title" required
                                placeholder="e.g., Teaching Effectiveness"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="question" class="block text-sm font-medium text-gray-700">
                                Question
                            </label>
                            <textarea name="question" id="question" rows="3" required
                                placeholder="e.g., How effectively does the instructor explain course material?"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="btn-primary sm:ml-3">
                        Add Criteria
                    </button>
                    <button type="button" onclick="closeAddModal()" class="btn-secondary">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Criteria Modal -->
<div id="editModal" class="modal hidden fixed z-10 inset-0 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="criteria_id" id="edit_criteria_id">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium text-gray-900">Edit Evaluation Criteria</h3>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label for="edit_title" class="block text-sm font-medium text-gray-700">
                                Title
                            </label>
                            <input type="text" name="title" id="edit_title" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="edit_question" class="block text-sm font-medium text-gray-700">
                                Question
                            </label>
                            <textarea name="question" id="edit_question" rows="3" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="btn-primary sm:ml-3">
                        Update Criteria
                    </button>
                    <button type="button" onclick="closeEditModal()" class="btn-secondary">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('addModal').classList.remove('hidden');
    }

    function closeAddModal() {
        document.getElementById('addModal').classList.add('hidden');
    }

    function openEditModal(criteria) {
        document.getElementById('edit_criteria_id').value = criteria.id;
        document.getElementById('edit_title').value = criteria.title;
        document.getElementById('edit_question').value = criteria.question;
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.classList.add('hidden');
        }
    }
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