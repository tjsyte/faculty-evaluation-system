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
                $name = trim($_POST['name']);
                $username = trim($_POST['username']);
                $section_id = $_POST['section_id'];
                
                if (!empty($name) && !empty($username) && !empty($section_id)) {
                    // Generate a default password (can be changed later)
                    $default_password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 10);
                    
                    $stmt = $conn->prepare("INSERT INTO students (name, username, section_id, password) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssis", $name, $username, $section_id, $default_password);
                    
                    if ($stmt->execute()) {
                        set_flash_message(
                            "Student added successfully! Default password: $default_password", 
                            "bg-green-100 text-green-700"
                        );
                    } else {
                        set_flash_message("Error adding student: " . $conn->error, "bg-red-100 text-red-700");
                    }
                }
                break;

            case 'edit':
                $id = $_POST['student_id'];
                $name = trim($_POST['name']);
                $username = trim($_POST['username']);
                $section_id = $_POST['section_id'];
                
                if (!empty($name) && !empty($username) && !empty($section_id)) {
                    $stmt = $conn->prepare("UPDATE students SET name = ?, username = ?, section_id = ? WHERE student_id = ?");
                    $stmt->bind_param("ssii", $name, $username, $section_id, $id);
                    
                    if ($stmt->execute()) {
                        set_flash_message("Student updated successfully!", "bg-green-100 text-green-700");
                    } else {
                        set_flash_message("Error updating student: " . $conn->error, "bg-red-100 text-red-700");
                    }
                }
                break;

            case 'reset_password':
                $id = $_POST['student_id'];
                // Generate a new random password
                $new_password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 10);
                
                $stmt = $conn->prepare("UPDATE students SET password = ? WHERE student_id = ?");
                $stmt->bind_param("si", $new_password, $id);
                
                if ($stmt->execute()) {
                    set_flash_message(
                        "Password reset successfully! New password: $new_password",
                        "bg-green-100 text-green-700"
                    );
                } else {
                    set_flash_message("Error resetting password: " . $conn->error, "bg-red-100 text-red-700");
                }
                break;

            case 'delete':
                $id = $_POST['student_id'];
                // Check if student has submitted evaluations
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM evaluation WHERE student_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                
                if ($result['count'] > 0) {
                    set_flash_message(
                        "Cannot delete student: They have submitted evaluations", 
                        "bg-red-100 text-red-700"
                    );
                } else {
                    $stmt = $conn->prepare("DELETE FROM students WHERE student_id = ?");
                    $stmt->bind_param("i", $id);
                    if ($stmt->execute()) {
                        set_flash_message("Student deleted successfully!", "bg-green-100 text-green-700");
                    } else {
                        set_flash_message("Error deleting student: " . $conn->error, "bg-red-100 text-red-700");
                    }
                }
                break;
        }
    }
    // Redirect to prevent form resubmission
    header("Location: students.php");
    exit;
}

// Get all sections with their course and department names for dropdowns
$sections = $conn->query("
    SELECT s.*, c.course_name, d.department_name 
    FROM section s 
    JOIN course c ON s.course_id = c.course_id 
    JOIN department d ON c.department_id = d.department_id 
    ORDER BY d.department_name, c.course_name, s.section_name
");

// Handle search and pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Modify the query to include search and pagination
$students_query = "
    SELECT st.*, s.section_name, c.course_name, d.department_name,
           COUNT(e.evaluation_id) as evaluations_submitted
    FROM students st
    JOIN section s ON st.section_id = s.section_id
    JOIN course c ON s.course_id = c.course_id
    JOIN department d ON c.department_id = d.department_id
    LEFT JOIN evaluation e ON st.student_id = e.student_id
    WHERE st.name LIKE ? OR st.username LIKE ?
    GROUP BY st.student_id
    ORDER BY st.name
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($students_query);
$search_param = "%$search%";
$stmt->bind_param("ssii", $search_param, $search_param, $limit, $offset);
$stmt->execute();
$students = $stmt->get_result();

// Get total number of students for pagination
$total_students_query = "
    SELECT COUNT(*) as total
    FROM students st
    WHERE st.name LIKE ? OR st.username LIKE ?
";
$stmt = $conn->prepare($total_students_query);
$stmt->bind_param("ss", $search_param, $search_param);
$stmt->execute();
$total_students_result = $stmt->get_result()->fetch_assoc();
$total_students = $total_students_result['total'];
$total_pages = ceil($total_students / $limit);
?>

<div class="space-y-6">
    <!-- Page Title and Search Bar -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">Manage Students</h1>
        <div class="flex space-x-2">
            <form method="GET" class="flex space-x-2">
                <input type="text" id="search" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <button type="submit" class="btn-primary">
                    Search
                </button>
            </form>
            <button onclick="openAddModal()" class="btn-primary">
                <i class="fas fa-plus"></i> Add Student
            </button>
        </div>
    </div>

    <!-- Students Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Name
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Username
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Section
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Course
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Department
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Evaluations
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($students->num_rows > 0): ?>
                    <?php while ($student = $students->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($student['name']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($student['username']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($student['section_name']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($student['course_name']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($student['department_name']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    <?php echo $student['evaluations_submitted']; ?> submitted
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                <button onclick="openEditModal(<?php 
                                    echo htmlspecialchars(json_encode([
                                        'id' => $student['student_id'],
                                        'name' => $student['name'],
                                        'username' => $student['username'],
                                        'section_id' => $student['section_id']
                                    ])); 
                                ?>)" class="text-indigo-600 hover:text-indigo-900">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="confirmResetPassword(<?php echo $student['student_id']; ?>)" 
                                    class="text-yellow-600 hover:text-yellow-900">
                                    <i class="fas fa-key"></i>
                                </button>
                                <?php if ($student['evaluations_submitted'] == 0): ?>
                                    <button onclick="confirmDelete(<?php echo $student['student_id']; ?>)" 
                                        class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                            No students found
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="flex justify-between items-center mt-4">
        <div class="text-sm text-gray-700">
            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_students); ?> of <?php echo $total_students; ?> results
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

<!-- Add Student Modal -->
<div id="addModal" class="modal hidden fixed z-10 inset-0 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium text-gray-900">Add Student</h3>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">
                                Full Name
                            </label>
                            <input type="text" name="name" id="name" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700">
                                Username
                            </label>
                            <input type="text" name="username" id="username" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="section_id" class="block text-sm font-medium text-gray-700">
                                Section
                            </label>
                            <select name="section_id" id="section_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Select Section</option>
                                <?php while ($section = $sections->fetch_assoc()): ?>
                                    <option value="<?php echo $section['section_id']; ?>">
                                        <?php echo htmlspecialchars(
                                            $section['section_name'] . ' - ' . 
                                            $section['course_name'] . ' (' . 
                                            $section['department_name'] . ')'
                                        ); ?>
                                    </option>
                                <?php endwhile; ?>
                                <?php $sections->data_seek(0); // Reset pointer for edit modal ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="btn-primary sm:ml-3">
                        Add Student
                    </button>
                    <button type="button" onclick="closeAddModal()" class="btn-secondary">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Student Modal -->
<div id="editModal" class="modal hidden fixed z-10 inset-0 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="student_id" id="edit_student_id">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium text-gray-900">Edit Student</h3>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label for="edit_name" class="block text-sm font-medium text-gray-700">
                                Full Name
                            </label>
                            <input type="text" name="name" id="edit_name" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="edit_username" class="block text-sm font-medium text-gray-700">
                                Username
                            </label>
                            <input type="text" name="username" id="edit_username" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="edit_section_id" class="block text-sm font-medium text-gray-700">
                                Section
                            </label>
                            <select name="section_id" id="edit_section_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <?php while ($section = $sections->fetch_assoc()): ?>
                                    <option value="<?php echo $section['section_id']; ?>">
                                        <?php echo htmlspecialchars(
                                            $section['section_name'] . ' - ' . 
                                            $section['course_name'] . ' (' . 
                                            $section['department_name'] . ')'
                                        ); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="btn-primary sm:ml-3">
                        Update Student
                    </button>
                    <button type="button" onclick="closeEditModal()" class="btn-secondary">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden Forms -->
<form id="resetPasswordForm" method="POST" class="hidden">
    <input type="hidden" name="action" value="reset_password">
    <input type="hidden" name="student_id" id="reset_student_id">
</form>

<form id="deleteForm" method="POST" class="hidden">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="student_id" id="delete_student_id">
</form>

<script>
function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
}

function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
}

function openEditModal(student) {
    document.getElementById('edit_student_id').value = student.id;
    document.getElementById('edit_name').value = student.name;
    document.getElementById('edit_username').value = student.username;
    document.getElementById('edit_section_id').value = student.section_id;
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

function confirmResetPassword(studentId) {
    if (confirm('Are you sure you want to reset this student\'s password?')) {
        document.getElementById('reset_student_id').value = studentId;
        document.getElementById('resetPasswordForm').submit();
    }
}

function confirmDelete(studentId) {
    if (confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
        document.getElementById('delete_student_id').value = studentId;
        document.getElementById('deleteForm').submit();
    }
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
