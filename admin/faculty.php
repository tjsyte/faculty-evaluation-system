<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';

check_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = trim($_POST['name']);
                $email = trim($_POST['email']);
                $department_id = $_POST['department_id'];

                if (!empty($name) && !empty($email) && !empty($department_id)) {
                    $stmt = $conn->prepare("INSERT INTO faculty (name, email, department_id) VALUES (?, ?, ?)");
                    $stmt->bind_param("ssi", $name, $email, $department_id);

                    if ($stmt->execute()) {
                        set_flash_message("Faculty added successfully!", "bg-green-100 text-green-700");
                    } else {
                        set_flash_message("Error adding faculty: " . $conn->error, "bg-red-100 text-red-700");
                    }
                }
                break;

            case 'edit':
                $id = $_POST['faculty_id'];
                $name = trim($_POST['name']);
                $email = trim($_POST['email']);
                $department_id = $_POST['department_id'];

                if (!empty($name) && !empty($email) && !empty($department_id)) {
                    $stmt = $conn->prepare("UPDATE faculty SET name = ?, email = ?, department_id = ? WHERE faculty_id = ?");
                    $stmt->bind_param("ssii", $name, $email, $department_id, $id);

                    if ($stmt->execute()) {
                        set_flash_message("Faculty updated successfully!", "bg-green-100 text-green-700");
                    } else {
                        set_flash_message("Error updating faculty: " . $conn->error, "bg-red-100 text-red-700");
                    }
                }
                break;

            case 'delete':
                $id = $_POST['faculty_id'];
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM evaluation WHERE faculty_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();

                if ($result['count'] > 0) {
                    set_flash_message("Cannot delete faculty: They have associated evaluations", "bg-red-100 text-red-700");
                } else {
                    $stmt = $conn->prepare("DELETE FROM faculty WHERE faculty_id = ?");
                    $stmt->bind_param("i", $id);
                    if ($stmt->execute()) {
                        set_flash_message("Faculty deleted successfully!", "bg-green-100 text-green-700");
                    } else {
                        set_flash_message("Error deleting faculty: " . $conn->error, "bg-red-100 text-red-700");
                    }
                }
                break;
        }
    }
    header("Location: faculty.php");
    exit;
}

$departments = get_departments($conn);

// Handle search and pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Modify the query to include search and pagination
$faculty_query = "
    SELECT f.*, d.department_name, 
           COUNT(DISTINCT e.student_id) as total_evaluators,
           COUNT(e.evaluation_id) as total_evaluations,
           AVG(e.rating) as average_rating
    FROM faculty f 
    JOIN department d ON f.department_id = d.department_id 
    LEFT JOIN evaluation e ON f.faculty_id = e.faculty_id 
    WHERE f.name LIKE ? OR f.email LIKE ?
    GROUP BY f.faculty_id 
    ORDER BY f.name
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($faculty_query);
$search_param = "%$search%";
$stmt->bind_param("ssii", $search_param, $search_param, $limit, $offset);
$stmt->execute();
$faculty = $stmt->get_result();

// Get total number of faculty for pagination
$total_faculty_query = "
    SELECT COUNT(*) as total
    FROM faculty f
    WHERE f.name LIKE ? OR f.email LIKE ?
";
$stmt = $conn->prepare($total_faculty_query);
$stmt->bind_param("ss", $search_param, $search_param);
$stmt->execute();
$total_faculty_result = $stmt->get_result()->fetch_assoc();
$total_faculty = $total_faculty_result['total'];
$total_pages = ceil($total_faculty / $limit);
?>

<div class="space-y-6">
    <!-- Page Title and Search Bar -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">Manage Faculty</h1>
        <div class="flex space-x-2">
            <form method="GET" class="flex space-x-2">
                <input type="text" id="search" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <button type="submit" class="btn-primary">
                    Search
                </button>
            </form>
            <button onclick="openAddModal()" class="btn-primary">
                <i class="fas fa-plus"></i> Add Faculty
            </button>
        </div>
    </div>

    <!-- Faculty Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Name
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Email
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Department
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Evaluations
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Rating
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($faculty->num_rows > 0): ?>
                    <?php while ($f = $faculty->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($f['name']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500" title="<?php echo htmlspecialchars($f['email']); ?>">
                                    <?php echo strlen($f['email']) > 20 ? substr(htmlspecialchars($f['email']), 0, 20) . '...' : htmlspecialchars($f['email']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500" title="<?php echo htmlspecialchars($f['department_name']); ?>">
                                    <?php echo strlen($f['department_name']) > 20 ? substr(htmlspecialchars($f['department_name']), 0, 20) . '...' : htmlspecialchars($f['department_name']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    <?php
                                    echo $f['total_evaluations'] . ' evaluation(s) by ' .
                                        $f['total_evaluators'] . ' student(s)';
                                    ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($f['average_rating']): ?>
                                    <div class="text-sm font-medium <?php echo get_rating_class($f['average_rating']); ?> inline-block px-2 py-1 rounded">
                                        <?php echo number_format($f['average_rating'], 2); ?>/5.00
                                    </div>
                                <?php else: ?>
                                    <span class="text-sm text-gray-500">No ratings yet</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                <button onclick="openViewModal(<?php echo $f['faculty_id']; ?>)" class="text-blue-600 hover:text-blue-900">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="openEditModal(<?php
                                                                echo htmlspecialchars(json_encode([
                                                                    'id' => $f['faculty_id'],
                                                                    'name' => $f['name'],
                                                                    'email' => $f['email'],
                                                                    'department_id' => $f['department_id']
                                                                ]));
                                                                ?>)" class="text-indigo-600 hover:text-indigo-900">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($f['total_evaluations'] == 0): ?>
                                    <button onclick="confirmDelete(<?php echo $f['faculty_id']; ?>)"
                                        class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            No faculty members found
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="flex justify-between items-center mt-4">
        <div class="text-sm text-gray-700">
            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_faculty); ?> of <?php echo $total_faculty; ?> results
        </div>
        <div class="space-x-2">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>"
                    class="px-3 py-1 rounded-md <?php echo $i === $page ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>
</div>

<div id="addModal" class="modal hidden fixed z-10 inset-0 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium text-gray-900">Add Faculty</h3>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">
                                Full Name
                            </label>
                            <input type="text" name="name" id="name" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">
                                Email Address
                            </label>
                            <input type="email" name="email" id="email" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="department_id" class="block text-sm font-medium text-gray-700">
                                Department
                            </label>
                            <select name="department_id" id="department_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['department_id']; ?>">
                                        <?php echo htmlspecialchars($dept['department_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="btn-primary sm:ml-3">
                        Add Faculty
                    </button>
                    <button type="button" onclick="closeAddModal()" class="btn-secondary">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="editModal" class="modal hidden fixed z-10 inset-0 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="faculty_id" id="edit_faculty_id">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium text-gray-900">Edit Faculty</h3>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label for="edit_name" class="block text-sm font-medium text-gray-700">
                                Full Name
                            </label>
                            <input type="text" name="name" id="edit_name" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="edit_email" class="block text-sm font-medium text-gray-700">
                                Email Address
                            </label>
                            <input type="email" name="email" id="edit_email" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="edit_department_id" class="block text-sm font-medium text-gray-700">
                                Department
                            </label>
                            <select name="department_id" id="edit_department_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['department_id']; ?>">
                                        <?php echo htmlspecialchars($dept['department_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="btn-primary sm:ml-3">
                        Update Faculty
                    </button>
                    <button type="button" onclick="closeEditModal()" class="btn-secondary">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="viewModal" class="modal hidden fixed z-10 inset-0 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full"> <!-- Palakihin ang max-w dito -->
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <h3 class="text-lg font-medium text-gray-900">View Evaluations</h3>
                <div id="evaluationContent" class="mt-4 space-y-4 max-h-96 overflow-y-auto">
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="closeViewModal()" class="btn-secondary">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<form id="deleteForm" method="POST" class="hidden">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="faculty_id" id="delete_faculty_id">
</form>

<script>
    function openViewModal(facultyId) {
        fetch(`get_evaluations.php?faculty_id=${facultyId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                const evaluationContent = document.getElementById('evaluationContent');
                evaluationContent.innerHTML = '';

                const groupedEvaluations = {};
                data.evaluations.forEach(evaluation => {
                    const title = evaluation.title;
                    if (!groupedEvaluations[title]) {
                        groupedEvaluations[title] = [];
                    }
                    groupedEvaluations[title].push(evaluation);
                });

                for (const title in groupedEvaluations) {
                    const questions = groupedEvaluations[title];
                    const groupDiv = document.createElement('div');
                    groupDiv.classList.add('border-b', 'pb-4', 'mb-4');

                    const titleElement = document.createElement('h4');
                    titleElement.classList.add('text-md', 'font-semibold', 'text-gray-800');
                    titleElement.innerText = title;

                    const table = document.createElement('table');
                    table.classList.add('min-w-full', 'divide-y', 'divide-gray-200', 'mt-2');

                    const thead = document.createElement('thead');
                    const headerRow = document.createElement('tr');
                    const questionHeader = document.createElement('th');
                    questionHeader.classList.add('px-6', 'py-3', 'text-left', 'text-xs', 'font-medium', 'text-gray-500', 'uppercase', 'tracking-wider');
                    questionHeader.innerText = 'Question';
                    const ratingHeader = document.createElement('th');
                    ratingHeader.classList.add('px-6', 'py-3', 'text-left', 'text-xs', 'font-medium', 'text-gray-500', 'uppercase', 'tracking-wider');
                    ratingHeader.innerText = 'Rating';

                    headerRow.appendChild(questionHeader);
                    headerRow.appendChild(ratingHeader);
                    thead.appendChild(headerRow);
                    table.appendChild(thead);

                    const tbody = document.createElement('tbody');
                    questions.forEach(question => {
                        const row = document.createElement('tr');
                        const questionCell = document.createElement('td');
                        questionCell.classList.add('px-6', 'py-4', 'whitespace-nowrap');
                        questionCell.innerText = question.question;

                        const ratingCell = document.createElement('td');
                        ratingCell.classList.add('px-6', 'py-4', 'whitespace-nowrap');

                        // Check if average_rating is a valid number
                        const averageRating = parseFloat(question.average_rating);
                        if (!isNaN(averageRating)) {
                            ratingCell.innerText = `${question.total_count} student, Average: ${averageRating.toFixed(2)}/5`;
                        } else {
                            ratingCell.innerText = `${question.total_count} student, Average: N/A`;
                        }

                        row.appendChild(questionCell);
                        row.appendChild(ratingCell);
                        tbody.appendChild(row);
                    });

                    table.appendChild(tbody);

                    // Add total row for this question
                    const totalRow = document.createElement('tr');
                    const totalCell = document.createElement('td');
                    totalCell.colSpan = 2;
                    totalCell.classList.add('px-6', 'py-4', 'font-bold');
                    const totalCount = data.totals[title].count;
                    const averageRating = (data.totals[title].average / totalCount).toFixed(2);
                    totalCell.innerText = `Total Ratings: ${totalCount}, Average Rating: ${averageRating}/5`;
                    totalRow.appendChild(totalCell);
                    tbody.appendChild(totalRow);

                    groupDiv.appendChild(titleElement);
                    groupDiv.appendChild(table);
                    evaluationContent.appendChild(groupDiv);
                }

                document.getElementById('viewModal').classList.remove('hidden');
            })
            .catch(error => console.error('Error fetching evaluations:', error));
    }

    function closeViewModal() {
        document.getElementById('viewModal').classList.add('hidden');
    }

    function openAddModal() {
        document.getElementById('addModal').classList.remove('hidden');
    }

    function closeAddModal() {
        document.getElementById('addModal').classList.add('hidden');
    }

    function openEditModal(faculty) {
        document.getElementById('edit_faculty_id').value = faculty.id;
        document.getElementById('edit_name').value = faculty.name;
        document.getElementById('edit_email').value = faculty.email;
        document.getElementById('edit_department_id').value = faculty.department_id;
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }

    function confirmDelete(facultyId) {
        if (confirm('Are you sure you want to delete this faculty member? This action cannot be undone.')) {
            document.getElementById('delete_faculty_id').value = facultyId;
            document.getElementById('deleteForm').submit();
        }
    }

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

    .truncate {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
</style>

<?php require_once '../includes/footer.php'; ?>