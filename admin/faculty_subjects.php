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
                $faculty_id = $_POST['faculty_id'];
                $course_id = $_POST['course_id'];
                $section_id = $_POST['section_id'];
                $subject_name = trim($_POST['subject_name']);
                
                if (!empty($faculty_id) && !empty($course_id) && !empty($section_id) && !empty($subject_name)) {
                    $stmt = $conn->prepare("INSERT INTO faculty_subjects (faculty_id, course_id, section_id, subject_name) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iiis", $faculty_id, $course_id, $section_id, $subject_name);
                    
                    if ($stmt->execute()) {
                        set_flash_message("Faculty subject assigned successfully!", "bg-green-100 text-green-700");
                    } else {
                        set_flash_message("Error assigning faculty subject: " . $conn->error, "bg-red-100 text-red-700");
                    }
                }
                break;
        }
    }
    // Redirect to prevent form resubmission
    header("Location: faculty_subjects.php");
    exit;
}

// Get all faculty for dropdowns
$faculty = $conn->query("SELECT faculty_id, name, department_id FROM faculty ORDER BY name");

// Get all courses and sections for dropdowns
$courses = $conn->query("
    SELECT DISTINCT c.course_id, c.course_name, d.department_id, d.department_name 
    FROM course c 
    JOIN department d ON c.department_id = d.department_id 
    ORDER BY d.department_name, c.course_name
");

$sections = $conn->query("
    SELECT s.section_id, s.section_name, s.course_id 
    FROM section s 
    ORDER BY s.section_name
");

// Handle search and pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_course = isset($_GET['filter_course']) ? intval($_GET['filter_course']) : '';
$filter_section = isset($_GET['filter_section']) ? intval($_GET['filter_section']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Modify the query to include search and pagination
$faculty_subjects_query = "
    SELECT fs.*, f.name as faculty_name, c.course_name, s.section_name, d.department_name 
    FROM faculty_subjects fs 
    JOIN faculty f ON fs.faculty_id = f.faculty_id 
    JOIN course c ON fs.course_id = c.course_id 
    JOIN section s ON fs.section_id = s.section_id 
    JOIN department d ON c.department_id = d.department_id 
    WHERE (f.name LIKE ? OR c.course_name LIKE ? OR s.section_name LIKE ? OR d.department_name LIKE ? OR fs.subject_name LIKE ?)
";

if ($filter_course) {
    $faculty_subjects_query .= " AND fs.course_id = $filter_course";
}

if ($filter_section) {
    $faculty_subjects_query .= " AND fs.section_id = $filter_section";
}

$faculty_subjects_query .= " ORDER BY f.name, d.department_name, c.course_name, s.section_name LIMIT ? OFFSET ?";

$stmt = $conn->prepare($faculty_subjects_query);
$search_param = "%$search%";
$stmt->bind_param("ssssssi", $search_param, $search_param, $search_param, $search_param, $search_param, $limit, $offset);
$stmt->execute();
$faculty_subjects = $stmt->get_result();

// Get total number of faculty subjects for pagination
$total_faculty_subjects_query = "
    SELECT COUNT(*) as total
    FROM faculty_subjects fs
    JOIN faculty f ON fs.faculty_id = f.faculty_id 
    JOIN course c ON fs.course_id = c.course_id 
    JOIN section s ON fs.section_id = s.section_id 
    JOIN department d ON c.department_id = d.department_id 
    WHERE (f.name LIKE ? OR c.course_name LIKE ? OR s.section_name LIKE ? OR d.department_name LIKE ? OR fs.subject_name LIKE ?)
";

if ($filter_course) {
    $total_faculty_subjects_query .= " AND fs.course_id = $filter_course";
}

if ($filter_section) {
    $total_faculty_subjects_query .= " AND fs.section_id = $filter_section";
}

$stmt = $conn->prepare($total_faculty_subjects_query);
$stmt->bind_param("sssss", $search_param, $search_param, $search_param, $search_param, $search_param);
$stmt->execute();
$total_faculty_subjects_result = $stmt->get_result()->fetch_assoc();
$total_faculty_subjects = $total_faculty_subjects_result['total'];
$total_pages = ceil($total_faculty_subjects / $limit);
?>

<div class="space-y-6">
    <!-- Page Title and Search Bar -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">Manage Faculty Subjects</h1>
        <div class="flex space-x-2">
            <form method="GET" class="flex space-x-2">
                <input type="text" id="search" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <select name="filter_course" id="filter_course" onchange="filterSectionsByCourseFilter()"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">All Courses</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['course_id']; ?>" <?php echo $filter_course == $course['course_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($course['course_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="filter_section" id="filter_section" onchange="this.form.submit()"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">All Sections</option>
                    <?php foreach ($sections as $section): ?>
                        <option value="<?php echo $section['section_id']; ?>" <?php echo $filter_section == $section['section_id'] ? 'selected' : ''; ?>
                            data-course-id="<?php echo $section['course_id']; ?>">
                            <?php echo htmlspecialchars($section['section_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-primary">
                    Search
                </button>
            </form>
            <button onclick="openAddModal()" class="btn-primary">
                <i class="fas fa-plus"></i> Assign Subject
            </button>
        </div>
    </div>

    <!-- Faculty Subjects Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Faculty Name
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Department
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Course
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Section
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Subject Name
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($faculty_subjects->num_rows > 0): ?>
                    <?php while ($fs = $faculty_subjects->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($fs['faculty_name']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($fs['department_name']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($fs['course_name']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($fs['section_name']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($fs['subject_name']); ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                            No faculty subjects found
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="flex justify-between items-center mt-4">
        <div class="text-sm text-gray-700">
            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_faculty_subjects); ?> of <?php echo $total_faculty_subjects; ?> results
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

<!-- Add Faculty Subject Modal -->
<div id="addModal" class="modal hidden fixed z-10 inset-0 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium text-gray-900">Assign Faculty Subject</h3>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label for="faculty_id" class="block text-sm font-medium text-gray-700">
                                Faculty
                            </label>
                            <select name="faculty_id" id="faculty_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Select Faculty</option>
                                <?php while ($f = $faculty->fetch_assoc()): ?>
                                    <option value="<?php echo $f['faculty_id']; ?>">
                                        <?php echo htmlspecialchars($f['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div>
                            <label for="course_id" class="block text-sm font-medium text-gray-700">
                                Course
                            </label>
                            <select name="course_id" id="course_id" required onchange="filterSectionsByCourse()"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Select Course</option>
                                <?php while ($course = $courses->fetch_assoc()): ?>
                                    <option value="<?php echo $course['course_id']; ?>">
                                        <?php echo htmlspecialchars($course['course_name'] . ' - ' . $course['department_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div>
                            <label for="section_id" class="block text-sm font-medium text-gray-700">
                                Section
                            </label>
                            <select name="section_id" id="section_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Select Section</option>
                                <?php while ($section = $sections->fetch_assoc()): ?>
                                    <option value="<?php echo $section['section_id']; ?>" data-course-id="<?php echo $section['course_id']; ?>">
                                        <?php echo htmlspecialchars($section['section_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div>
                            <label for="subject_name" class="block text-sm font-medium text-gray-700">
                                Subject Name
                            </label>
                            <input type="text" name="subject_name" id="subject_name" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="btn-primary sm:ml-3">
                        Assign Subject
                    </button>
                    <button type="button" onclick="closeAddModal()" class="btn-secondary">
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

function filterSectionsByCourse() {
    const courseSelect = document.getElementById('course_id');
    const selectedCourse = courseSelect.options[courseSelect.selectedIndex];
    const courseId = selectedCourse ? selectedCourse.value : '';

    const sectionSelect = document.getElementById('section_id');
    const sectionOptions = sectionSelect.options;

    for (let i = 0; i < sectionOptions.length; i++) {
        const sectionOption = sectionOptions[i];
        if (sectionOption.getAttribute('data-course-id') === courseId) {
            sectionOption.style.display = '';
        } else {
            sectionOption.style.display = 'none';
        }
    }

    sectionSelect.value = '';
}

function filterSectionsByCourseFilter() {
    const courseSelect = document.getElementById('filter_course');
    const selectedCourse = courseSelect.options[courseSelect.selectedIndex];
    const courseId = selectedCourse ? selectedCourse.value : '';

    const sectionSelect = document.getElementById('filter_section');
    const sectionOptions = sectionSelect.options;

    for (let i = 0; i < sectionOptions.length; i++) {
        const sectionOption = sectionOptions[i];
        if (sectionOption.getAttribute('data-course-id') === courseId || courseId === '') {
            sectionOption.style.display = '';
        } else {
            sectionOption.style.display = 'none';
        }
    }

    sectionSelect.value = '';
    // Remove the form submission to allow user to select section before submitting
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
