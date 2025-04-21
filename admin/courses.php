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
                $name = trim($_POST['course_name']);
                $department_id = $_POST['department_id'];
                if (!empty($name) && !empty($department_id)) {
                    $stmt = $conn->prepare("INSERT INTO course (course_name, department_id) VALUES (?, ?)");
                    $stmt->bind_param("si", $name, $department_id);
                    if ($stmt->execute()) {
                        set_flash_message("Course added successfully!", "bg-green-100 text-green-700");
                    } else {
                        set_flash_message("Error adding course: " . $conn->error, "bg-red-100 text-red-700");
                    }
                }
                break;

            case 'edit':
                $id = $_POST['course_id'];
                $name = trim($_POST['course_name']);
                $department_id = $_POST['department_id'];
                if (!empty($name) && !empty($department_id)) {
                    $stmt = $conn->prepare("UPDATE course SET course_name = ?, department_id = ? WHERE course_id = ?");
                    $stmt->bind_param("sii", $name, $department_id, $id);
                    if ($stmt->execute()) {
                        set_flash_message("Course updated successfully!", "bg-green-100 text-green-700");
                    } else {
                        set_flash_message("Error updating course: " . $conn->error, "bg-red-100 text-red-700");
                    }
                }
                break;

            case 'delete':
                $id = $_POST['course_id'];
                // Check if course has associated sections
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM section WHERE course_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();

                if ($result['count'] > 0) {
                    set_flash_message("Cannot delete course: It has associated sections", "bg-red-100 text-red-700");
                } else {
                    $stmt = $conn->prepare("DELETE FROM course WHERE course_id = ?");
                    $stmt->bind_param("i", $id);
                    if ($stmt->execute()) {
                        set_flash_message("Course deleted successfully!", "bg-green-100 text-green-700");
                    } else {
                        set_flash_message("Error deleting course: " . $conn->error, "bg-red-100 text-red-700");
                    }
                }
                break;
        }
    }
    // Redirect to prevent form resubmission
    header("Location: courses.php");
    exit;
}

// Get all departments for dropdowns
$departments = get_departments($conn);

// Handle search and pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Modify the query to include search and pagination
$courses_query = "
    SELECT c.*, d.department_name, COUNT(s.section_id) as section_count 
    FROM course c 
    JOIN department d ON c.department_id = d.department_id 
    LEFT JOIN section s ON c.course_id = s.course_id 
    WHERE c.course_name LIKE ? OR d.department_name LIKE ?
    GROUP BY c.course_id 
    ORDER BY d.department_name, c.course_name
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($courses_query);
$search_param = "%$search%";
$stmt->bind_param("ssii", $search_param, $search_param, $limit, $offset);
$stmt->execute();
$courses = $stmt->get_result();

// Get total number of courses for pagination
$total_courses_query = "
    SELECT COUNT(*) as total
    FROM course c
    JOIN department d ON c.department_id = d.department_id
    WHERE c.course_name LIKE ? OR d.department_name LIKE ?
";
$stmt = $conn->prepare($total_courses_query);
$stmt->bind_param("ss", $search_param, $search_param);
$stmt->execute();
$total_courses_result = $stmt->get_result()->fetch_assoc();
$total_courses = $total_courses_result['total'];
$total_pages = ceil($total_courses / $limit);
?>

<div class="space-y-6">
    <!-- Page Title and Search Bar -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">Manage Courses</h1>
        <div class="flex space-x-2">
            <form method="GET" class="flex space-x-2">
                <input type="text" id="search" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <button type="submit" class="btn-primary">
                    Search
                </button>
            </form>
            <button onclick="openAddModal()" class="btn-primary">
                <i class="fas fa-plus"></i> Add Course
            </button>
        </div>
    </div>

    <!-- Courses Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Course Name
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Department
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Sections
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($courses->num_rows > 0): ?>
                    <?php while ($course = $courses->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($course['course_name']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($course['department_name']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    <?php echo $course['section_count']; ?> section(s)
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button onclick="openEditModal(<?php
                                                                echo htmlspecialchars(json_encode([
                                                                    'id' => $course['course_id'],
                                                                    'name' => $course['course_name'],
                                                                    'department_id' => $course['department_id']
                                                                ]));
                                                                ?>)" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <?php if ($course['section_count'] == 0): ?>
                                    <button onclick="confirmDelete(<?php echo $course['course_id']; ?>)"
                                        class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                            No courses found
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="flex justify-between items-center mt-4">
        <div class="text-sm text-gray-700">
            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_courses); ?> of <?php echo $total_courses; ?> results
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

<!-- Add Course Modal -->
<div id="addModal" class="modal hidden fixed z-10 inset-0 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium text-gray-900">Add Course</h3>
                    <div class="mt-4 space-y-4">
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
                        <div>
                            <label for="course_name" class="block text-sm font-medium text-gray-700">
                                Course Name
                            </label>
                            <input type="text" name="course_name" id="course_name" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="btn-primary sm:ml-3">
                        Add Course
                    </button>
                    <button type="button" onclick="closeAddModal()" class="btn-secondary">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Course Modal -->
<div id="editModal" class="modal hidden fixed z-10 inset-0 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="course_id" id="edit_course_id">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium text-gray-900">Edit Course</h3>
                    <div class="mt-4 space-y-4">
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
                        <div>
                            <label for="edit_course_name" class="block text-sm font-medium text-gray-700">
                                Course Name
                            </label>
                            <input type="text" name="course_name" id="edit_course_name" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="btn-primary sm:ml-3">
                        Update Course
                    </button>
                    <button type="button" onclick="closeEditModal()" class="btn-secondary">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Course Form -->
<form id="deleteForm" method="POST" class="hidden">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="course_id" id="delete_course_id">
</form>

<script>
    function openAddModal() {
        document.getElementById('addModal').classList.remove('hidden');
    }

    function closeAddModal() {
        document.getElementById('addModal').classList.add('hidden');
    }

    function openEditModal(course) {
        document.getElementById('edit_course_id').value = course.id;
        document.getElementById('edit_course_name').value = course.name;
        document.getElementById('edit_department_id').value = course.department_id;
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }

    function confirmDelete(courseId) {
        if (confirm('Are you sure you want to delete this course? This action cannot be undone.')) {
            document.getElementById('delete_course_id').value = courseId;
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