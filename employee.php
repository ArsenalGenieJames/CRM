<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is an employee
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employee') {
    header('Location: login.php');
    exit;
}

// Get employee information
try {
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $employee = $stmt->fetch();

    // Get employee's assigned tasks
    $stmt = $pdo->prepare("
        SELECT t.*, c.name as client_name, m.name as manager_name 
        FROM tasks t 
        JOIN employee_task_assignments eta ON t.id = eta.task_id
        LEFT JOIN clients c ON t.client_id = c.id
        LEFT JOIN managers m ON t.manager_id = m.id
        WHERE eta.employee_id = ? 
        ORDER BY t.due_date ASC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $tasks = $stmt->fetchAll();

    // Get task updates
    $stmt = $pdo->prepare("
        SELECT tu.*, t.subject as task_subject
        FROM task_updates tu
        JOIN tasks t ON tu.task_id = t.id
        JOIN employee_task_assignments eta ON t.id = eta.task_id
        WHERE eta.employee_id = ?
        ORDER BY tu.update_time DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_updates = $stmt->fetchAll();
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
    <title>Employee Dashboard - CRM System</title>
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
                    <span class="text-gray-700 mr-4">Welcome, <?php echo htmlspecialchars($employee['name']); ?></span>
                    <a href="logout.php" class="text-red-600 hover:text-red-800">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Employee Information -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Employee Information</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-gray-600">Name</p>
                    <p class="font-semibold"><?php echo htmlspecialchars($employee['name']); ?></p>
                </div>
                <div>
                    <p class="text-gray-600">Email</p>
                    <p class="font-semibold"><?php echo htmlspecialchars($employee['email']); ?></p>
                </div>
                <div>
                    <p class="text-gray-600">Phone</p>
                    <p class="font-semibold"><?php echo htmlspecialchars($employee['phone'] ?? 'Not provided'); ?></p>
                </div>
                <div>
                    <p class="text-gray-600">Status</p>
                    <p class="font-semibold"><?php echo htmlspecialchars($employee['status'] ?? 'Active'); ?></p>
                </div>
            </div>
        </div>

        <!-- Recent Updates -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Recent Updates</h2>
            <?php if (empty($recent_updates)): ?>
                <p class="text-gray-600">No recent updates.</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($recent_updates as $update): ?>
                        <div class="border-l-4 border-blue-500 pl-4 py-2">
                            <p class="text-sm text-gray-600">
                                <span class="font-semibold"><?php echo htmlspecialchars($update['task_subject']); ?></span>
                                - <?php echo htmlspecialchars($update['comments']); ?>
                            </p>
                            <p class="text-xs text-gray-500">
                                <?php echo date('M d, Y H:i', strtotime($update['update_time'])); ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Assigned Tasks -->
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Your Tasks</h2>
            <?php if (empty($tasks)): ?>
                <p class="text-gray-600">No tasks assigned.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Task</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
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
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($task['client_name']); ?></div>
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
