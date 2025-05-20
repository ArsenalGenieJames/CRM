<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Set user name from session
$user_name = $_SESSION['name'] ?? 'Guest';
$user_role = $_SESSION['role'] ?? 'user';
$module = $_GET['modules'] ?? 'dashboard';
$action = $_GET['action'] ?? 'index';
$pageTitle = ucfirst($module) . ' - CRM System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">

    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-800 text-white">
            <div class="p-4">
                <h1 class="text-2xl font-bold">CRM System</h1>
                <p class="text-gray-400 text-sm mt-1">Welcome, <?php echo htmlspecialchars($user_name); ?></p>
            </div>
            <nav class="mt-4">
                <a href="index.php?modules=dashboard" class="block py-2 px-4 <?php echo $module === 'dashboard' ? 'bg-gray-900' : 'hover:bg-gray-700'; ?>">
                    <i class="fas fa-home mr-2"></i>Dashboard
                </a>
                <a href="index.php?modules=account" class="block py-2 px-4 <?php echo $module === 'account' ? 'bg-gray-900' : 'hover:bg-gray-700'; ?>">
                    <i class="fas fa-building mr-2"></i>Accounts
                </a>
                <a href="index.php?modules=contact" class="block py-2 px-4 <?php echo $module === 'contact' ? 'bg-gray-900' : 'hover:bg-gray-700'; ?>">
                    <i class="fas fa-users mr-2"></i>Contacts
                </a>
                <a href="index.php?modules=opportunity" class="block py-2 px-4 <?php echo $module === 'opportunity' ? 'bg-gray-900' : 'hover:bg-gray-700'; ?>">
                    <i class="fas fa-bullseye mr-2"></i>Opportunities
                </a>
                <a href="index.php?modules=task" class="block py-2 px-4 <?php echo $module === 'task' ? 'bg-gray-900' : 'hover:bg-gray-700'; ?>">
                    <i class="fas fa-tasks mr-2"></i>Tasks
                </a>
                <a href="index.php?modules=report" class="block py-2 px-4 <?php echo $module === 'report' ? 'bg-gray-900' : 'hover:bg-gray-700'; ?>">
                    <i class="fas fa-chart-bar mr-2"></i>Reports
                </a>
                <?php if ($user_role === 'admin'): ?>
                <a href="index.php?modules=setting" class="block py-2 px-4 <?php echo $module === 'setting' ? 'bg-gray-900' : 'hover:bg-gray-700'; ?>">
                    <i class="fas fa-cog mr-2"></i>Settings
                </a>
                <?php endif; ?>
                <a href="logout.php" class="block py-2 px-4 hover:bg-gray-700 mt-4">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-8">
            <?php
            // Include appropriate view based on module and action
            $viewFile = "views/{$module}" . ($action !== 'index' ? "_{$action}" : "") . ".php";
            if (file_exists($viewFile)) {
                include $viewFile;
            } else {
                echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">';
                echo '<span class="block sm:inline">View not found</span>';
                echo '</div>';
            }
            ?>
        </div>
    </div>

  
</body>
</html> 