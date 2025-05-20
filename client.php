<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is a client
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'client') {
    header('Location: login.php');
    exit;
}

// Get client information
try {
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $client = $stmt->fetch();

    // Get client's tasks
    $stmt = $pdo->prepare("
        SELECT t.*, m.name as manager_name 
        FROM tasks t 
        LEFT JOIN managers m ON t.manager_id = m.id 
        WHERE t.client_id = ? 
        ORDER BY t.due_date DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $tasks = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $error = "An error occurred while fetching data";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <h1 class="text-xl font-bold text-gray-800">CRM System</h1>
                    </div>
                </div>
                <div class="flex items-center">
                    <span class="text-gray-700 mr-4">Welcome, <?php echo htmlspecialchars($client['name']); ?></span>
                    <a href="logout.php" class="text-red-600 hover:text-red-800">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Client Information -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Client Information</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-gray-600">Company Name</p>
                    <p class="font-semibold"><?php echo htmlspecialchars($client['name']); ?></p>
                </div>
                <div>
                    <p class="text-gray-600">Industry</p>
                    <p class="font-semibold"><?php echo htmlspecialchars($client['industry']); ?></p>
                </div>
                <div>
                    <p class="text-gray-600">Contact Name</p>
                    <p class="font-semibold"><?php echo htmlspecialchars($client['contact_name']); ?></p>
                </div>
                <div>
                    <p class="text-gray-600">Contact Email</p>
                    <p class="font-semibold"><?php echo htmlspecialchars($client['contact_email']); ?></p>
                </div>
            </div>
        </div>

        <!-- Tasks -->
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Your Tasks</h2>
            <?php if (empty($tasks)): ?>
                <p class="text-gray-600">No tasks found.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Manager</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($tasks as $task): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($task['subject']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($task['description']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($task['manager_name']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($task['due_date'])); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $task['priority'] === 'High' ? 'bg-red-100 text-red-800' : 
                                            ($task['priority'] === 'Medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'); ?>">
                                        <?php echo htmlspecialchars($task['priority']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $task['status'] === 'Completed' ? 'bg-green-100 text-green-800' : 
                                            ($task['status'] === 'In Progress' ? 'bg-blue-100 text-blue-800' : 
                                            ($task['status'] === 'Deferred' ? 'bg-gray-100 text-gray-800' : 'bg-yellow-100 text-yellow-800')); ?>">
                                        <?php echo htmlspecialchars($task['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
