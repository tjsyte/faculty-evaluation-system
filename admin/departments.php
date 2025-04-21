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
                $name = trim($_POST['department_name']);
                if (!empty($name)) {
                    $stmt = $conn->prepare("INSERT INTO department (department_name) VALUES (?)");
                    $stmt->bind_param("s", $name);
                    if ($stmt->execute()) {
                        set_flash_message("Department added successfully!", "bg-green-100 text-green-700");
                    } else {
                        set_flash_message("Error adding department: " . $conn->error, "bg-red-100 text-red-700");
                    }
                }
                break;

            case 'edit':
                $id = $_POST['department_id'];
                $name = trim($_POST['department_name']);
                if (!empty($name) && !empty($id)) {
                    $stmt = $conn->prepare("UPDATE department SET department_name = ? WHERE department_id = ?");
                    $stmt->bind_param("si", $name, $id);
                    if ($stmt->execute()) {
                        set_flash_message("Department updated successfully!", "bg-green-100 text-green-700");
                    } else {
                        set_flash_message("Error updating department: " . $conn->error, "bg-red-100 text-red-700");
                    }
                }
                break;

            case 'delete':
                $id = $_POST['department_id'];
                // Check if department has associated courses
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM course WHERE department_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                
                if ($result['count'] > 0) {
                    set_flash_message("Cannot delete department: It has associated courses", "bg-red-100 text-red-700");
                } else {
                    $stmt = $conn->prepare("DELETE FROM department WHERE department_id = ?");
                    $stmt->bind_param("i", $id);
                    if ($stmt->execute()) {
                        set_flash_message("Department deleted successfully!", "bg-green-100 text-green-700");
                    } else {
                        set_flash_message("Error deleting department: " . $conn->error, "bg-red-100 text-red-700");
                    }
                }
                break;
        }
    }
    // Redirect to prevent form resubmission
    header("Location: departments.php");
    exit;
}

// Handle search and pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Modify the query to include search and pagination
$departments_query = "
    SELECT d.*, COUNT(c.course_id) as course_count 
    FROM department d 
    LEFT JOIN course c ON d.department_id = c.department_id 
    WHERE d.department_name LIKE ?
    GROUP BY d.department_id 
    ORDER BY d.department_name
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($departments_query);
$search_param = "%$search%";
$stmt->bind_param("sii", $search_param, $limit, $offset);
$stmt->execute();
$departments = $stmt->get_result();

// Get total number of departments for pagination
$total_departments_query = "
    SELECT COUNT(*) as total
    FROM department d
    WHERE d.department_name LIKE ?
";
$stmt = $conn->prepare($total_departments_query);
$stmt->bind_param("s", $search_param);
$stmt->execute();
$total_departments_result = $stmt->get_result()->fetch_assoc();
$total_departments = $total_departments_result['total'];
$total_pages = ceil($total_departments / $limit);
?>

<div class="space-y-6">
    <!-- Page Title and Search Bar -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">Manage Departments</h1>
        <div class="flex space-x-2">
            <form method="GET" class="flex space-x-2">
                <input type="text" id="search" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <button type="submit" class="btn-primary">
                    Search
                </button>
            </form>
            <button onclick="openAddModal()" class="btn-primary">
                <i class="fas fa-plus"></i> Add Department
            </button>
        </div>
    </div>

    <!-- Departments Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Department Name
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Courses
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($departments->num_rows > 0): ?>
                    <?php while ($dept = $departments->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($dept['department_name']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    <?php echo $dept['course_count']; ?> course(s)
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button onclick="openEditModal(<?php 
                                    echo htmlspecialchars(json_encode([
                                        'id' => $dept['department_id'],
                                        'name' => $dept['department_name']
                                    ])); 
                                ?>)" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <?php if ($dept['course_count'] == 0): ?>
                                    <button onclick="confirmDelete(<?php echo $dept['department_id']; ?>)" 
                                        class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-center text-gray-500">
                            No departments found
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="flex justify-between items-center mt-4">
        <div class="text-sm text-gray-700">
            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_departments); ?> of <?php echo $total_departments; ?> results
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

<!-- Add Department Modal -->
<div id="addModal" class="modal hidden fixed z-10 inset-0 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium text-gray-900">Add Department</h3>
                    <div class="mt-4">
                        <label for="department_name" class="block text-sm font-medium text-gray-700">
                            Department Name
                        </label>
                        <input type="text" name="department_name" id="department_name" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="btn-primary sm:ml-3">
                        Add Department
                    </button>
                    <button type="button" onclick="closeAddModal()" class="btn-secondary">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Department Modal -->
<div id="editModal" class="modal hidden fixed z-10 inset-0 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="department_id" id="edit_department_id">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium text-gray-900">Edit Department</h3>
                    <div class="mt-4">
                        <label for="edit_department_name" class="block text-sm font-medium text-gray-700">
                            Department Name
                        </label>
                        <input type="text" name="department_name" id="edit_department_name" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="btn-primary sm:ml-3">
                        Update Department
                    </button>
                    <button type="button" onclick="closeEditModal()" class="btn-secondary">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Department Form -->
<form id="deleteForm" method="POST" class="hidden">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="department_id" id="delete_department_id">
</form>

<script>
function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
}

function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
}

function openEditModal(department) {
    document.getElementById('edit_department_id').value = department.id;
    document.getElementById('edit_department_name').value = department.name;
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

function confirmDelete(departmentId) {
    if (confirm('Are you sure you want to delete this department? This action cannot be undone.')) {
        document.getElementById('delete_department_id').value = departmentId;
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
