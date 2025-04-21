<?php
session_start();
include __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluation System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0F610D',
                        secondary: '#6B7280'
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <nav class="bg-primary text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center space-x-2">
                        <i class="fas fa-graduation-cap text-2xl"></i>
                        <span class="font-bold text-xl">Evaluation System</span>
                    </a>
                </div>
                <?php if(isset($_SESSION['user_id'])): ?>
                <div class="flex items-center space-x-4">
                    <span class="text-sm">
                        <i class="fas fa-user mr-1"></i>
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                        (<?php echo ucfirst($_SESSION['user_type']); ?>)
                    </span>
                    <a href="../logout.php" class="text-sm hover:text-gray-200 flex items-center">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <?php if(isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin'): ?>
    <div class="bg-gray-800 text-white">
        <div class="max-w-8xl mx-auto px-4">
            <div class="flex justify-center space-x-8">
                <a href="index.php" class="py-3 px-2 hover:text-gray-300">
                    <i class="fas fa-dashboard mr-1"></i> Dashboard
                </a>
                <a href="departments.php" class="py-3 px-2 hover:text-gray-300">
                    <i class="fas fa-building mr-1"></i> Departments
                </a>
                <a href="courses.php" class="py-3 px-2 hover:text-gray-300">
                    <i class="fas fa-book mr-1"></i> Courses
                </a>
                <a href="sections.php" class="py-3 px-2 hover:text-gray-300">
                    <i class="fas fa-users mr-1"></i> Sections
                </a>
                <a href="faculty.php" class="py-3 px-2 hover:text-gray-300">
                    <i class="fas fa-chalkboard-teacher mr-1"></i> Faculty
                </a>
                <a href="faculty_subjects.php" class="py-3 px-2 hover:text-gray-300">
                    <i class="fas fa-book-open mr-1"></i> Faculty Subjects
                </a>
                <a href="students.php" class="py-3 px-2 hover:text-gray-300">
                    <i class="fas fa-user-graduate mr-1"></i> Students
                </a>
                <a href="criteria.php" class="py-3 px-2 hover:text-gray-300">
                    <i class="fas fa-list-check mr-1"></i> Criteria
                </a>
                <a href="reports.php" class="py-3 px-2 hover:text-gray-300">
                    <i class="fas fa-chart-bar mr-1"></i> Reports
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <main class="flex-grow max-w-7xl w-full mx-auto px-4 py-8">
        <?php if(isset($_SESSION['flash_message'])): ?>
        <div class="mb-4 p-4 rounded-lg <?php echo $_SESSION['flash_type'] ?? 'bg-blue-100 text-blue-700'; ?>">
            <?php 
            echo $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            unset($_SESSION['flash_type']);
            ?>
        </div>
        <?php endif; ?>
