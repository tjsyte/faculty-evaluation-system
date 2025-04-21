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
                $name = trim($_POST['section_name']);
                $course_id = $_POST['course_id'];
                if (!empty($name) && !empty($course_id)) {
                    $stmt = $conn->prepare("INSERT INTO section (section_name, course_id) VALUES (?, ?)");
                    $stmt->bind_param("si", $name, $course_id);
                    if ($stmt->execute()) {
                        set_flash_message("Section added successfully!", "bg-green-100 text-green-700");
                    } else {
                        set_flash_message("Error adding section: " . $conn->error, "bg-red-100 text-red-700");
                    }
                }
                break;

            case 'edit':
                $id = $_POST['section_id'];
                $name = trim($_POST['section_name']);
                $course_id = $_POST['course_id'];
                if (!empty($name) && !empty($course_id)) {
                    $stmt = $conn->prepare("UPDATE section SET section_name = ?, course_id = ? WHERE section_id = ?");
                    $stmt->bind_param("sii", $name, $course_id, $id);
                    if ($stmt->execute()) {
                        set_flash_message("Section updated successfully!", "bg-green-100 text-green-700");
                    } else {
                        set_flash_message("Error updating section: " . $conn->error, "bg-red-100 text-red-700");
                    }
                }
                break;

            case 'delete':
                $id = $_POST['section_id'];
                // Check if section has associated students
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM students WHERE section_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                
                if ($result['count'] > 0) {
                    set_flash_message("Cannot delete section: It has associated students", "bg-red-100 text-red-700");
                } else {
                    $stmt = $conn->prepare("DELETE FROM section WHERE section_id = ?");
                    $stmt->bind_param("i", $id);
                    if ($stmt->execute()) {
                        set_flash_message("Section deleted successfully!", "bg-green-100 text-green-700");
                    } else {
                        set_flash_message("Error deleting section: " . $conn->error, "bg-red-100 text-red-700");
                    }
                }
                break;
        }
    }
    // Redirect to prevent form resubmission
    header("Location: sections.php");
    exit;
}

// Get all departments and courses for dropdowns
$departments = get_departments($conn);
$courses = get_courses($conn);

// Handle search and pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_course = isset($_GET['filter_course']) ? intval($_GET['filter_course']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Modify the query to include search and pagination
$sections_query = "
    SELECT s.*, c.course_name, d.department_name, COUNT(st.student_id) as student_count 
    FROM section s 
    JOIN course c ON s.course_id = c.course_id 
    JOIN department d ON c.department_id = d.department_id 
    LEFT JOIN students st ON s.section_id = st.section_id 
    WHERE (s.section_name LIKE ? OR c.course_name LIKE ? OR d.department_name LIKE ?)
";

if ($filter_course) {
    $sections_query .= " AND s.course_id = $filter_course";
}

$sections_query .= " GROUP BY s.section_id ORDER BY d.department_name, c.course_name, s.section_name LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sections_query);
$search_param = "%$search%";
$stmt->bind_param("sssii", $search_param, $search_param, $search_param, $limit, $offset);
$stmt->execute();
$sections = $stmt->get_result();

// Get total number of sections for pagination
$total_sections_query = "
    SELECT COUNT(*) as total
    FROM section s
    JOIN course c ON s.course_id = c.course_id
    JOIN department d ON c.department_id = d.department_id
    WHERE (s.section_name LIKE ? OR c.course_name LIKE ? OR d.department_name LIKE ?)
";

if ($filter_course) {
    $total_sections_query .= " AND s.course_id = $filter_course";
}

$stmt = $conn->prepare($total_sections_query);
$stmt->bind_param("sss", $search_param, $search_param, $search_param);
$stmt->execute();
$total_sections_result = $stmt->get_result()->fetch_assoc();
$total_sections = $total_sections_result['total'];
$total_pages = ceil($total_sections / $limit);
?>

<div class="space-y-6">
    <!-- Page Title and Search Bar -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">Manage Sections</h1>
        <div class="flex space-x-2">
            <form method="GET" class="flex space-x-2">
                <input type="text" id="search" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <select name="filter_course" id="filter_course" onchange="this.form.submit()"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">All Courses</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['course_id']; ?>" <?php echo $filter_course == $course['course_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($course['course_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-primary">
                    Search
                </button>
            </form>
            <button onclick="openAddModal()" class="btn-primary">
                <i class="fas fa-plus"></i> Add Section
            </button>
        </div>
    </div>

    <!-- Sections Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Section Name
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Course
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Department
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Students
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($sections->num_rows > 0): ?>
                    <?php while ($section = $sections->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($section['section_name']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($section['course_name']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($section['department_name']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    <?php echo $section['student_count']; ?> student(s)
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button onclick="openEditModal(<?php 
                                    echo htmlspecialchars(json_encode([
                                        'id' => $section['section_id'],
                                        'name' => $section['section_name'],
                                        'course_id' => $section['course_id']
                                    ])); 
                                ?>)" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <?php if ($section['student_count'] == 0): ?>
                                    <button onclick="confirmDelete(<?php echo $section['section_id']; ?>)" 
                                        class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                            No sections found
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="flex justify-between items-center mt-4">
        <div class="text-sm text-gray-700">
            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_sections); ?> of <?php echo $total_sections; ?> results
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

<!-- Add Section Modal -->
<div id="addModal" class="modal hidden fixed z-10 inset-0 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium text-gray-900">Add Section</h3>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label for="course_id" class="block text-sm font-medium text-gray-700">
                                Course
                            </label>
                            <select name="course_id" id="course_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Select Course</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['course_id']; ?>">
                                        <?php echo htmlspecialchars($course['course_name'] . ' (' . $course['department_name'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="section_name" class="block text-sm font-medium text-gray-700">
                                Section Name
                            </label>
                            <input type="text" name="section_name" id="section_name" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="btn-primary sm:ml-3">
                        Add Section
                    </button>
                    <button type="button" onclick="closeAddModal()" class="btn-secondary">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Section Modal -->
<div id="editModal" class="modal hidden fixed z-10 inset-0 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="section_id" id="edit_section_id">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium text-gray-900">Edit Section</h3>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label for="edit_course_id" class="block text-sm font-medium text-gray-700">
                                Course
                            </label>
                            <select name="course_id" id="edit_course_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['course_id']; ?>">
                                        <?php echo htmlspecialchars($course['course_name'] . ' (' . $course['department_name'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="edit_section_name" class="block text-sm font-medium text-gray-700">
                                Section Name
                            </label>
                            <input type="text" name="section_name" id="edit_section_name" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="btn-primary sm:ml-3">
                        Update Section
                    </button>
                    <button type="button" onclick="closeEditModal()" class="btn-secondary">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Section Form -->
<form id="deleteForm" method="POST" class="hidden">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="section_id" id="delete_section_id">
</form>

<script>
function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
}

function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
}

function openEditModal(section) {
    document.getElementById('edit_section_id').value = section.id;
    document.getElementById('edit_section_name').value = section.name;
    document.getElementById('edit_course_id').value = section.course_id;
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

function confirmDelete(sectionId) {
    if (confirm('Are you sure you want to delete this section? This action cannot be undone.')) {
        document.getElementById('delete_section_id').value = sectionId;
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
