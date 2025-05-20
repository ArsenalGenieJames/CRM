<?php
// Get database connection
require_once 'config/database.php';

// Initialize statistics
$stats = [
    'total_clients' => 0,
    'total_tasks' => 0,
    'total_employees' => 0,
    'total_managers' => 0
];

try {
    // Get total clients
    $stmt = $pdo->query("SELECT COUNT(*) FROM clients");
    $stats['total_clients'] = $stmt->fetchColumn();

    // Get total tasks
    $stmt = $pdo->query("SELECT COUNT(*) FROM tasks");
    $stats['total_tasks'] = $stmt->fetchColumn();

    // Get total employees
    $stmt = $pdo->query("SELECT COUNT(*) FROM employees");
    $stats['total_employees'] = $stmt->fetchColumn();

    // Get total managers
    $stmt = $pdo->query("SELECT COUNT(*) FROM managers");
    $stats['total_managers'] = $stmt->fetchColumn();

} catch(PDOException $e) {
    // Log error but don't expose details
    error_log($e->getMessage());
}
?>

<div class="container mx-auto">
    <h1 class="text-3xl font-bold mb-8">
        <i class="fas fa-home mr-2"></i>Dashboard
    </h1>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Clients Card -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-building text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Total Clients</p>
                    <p class="text-2xl font-semibold"><?php echo number_format($stats['total_clients']); ?></p>
                </div>
            </div>
        </div>

        <!-- Tasks Card -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <i class="fas fa-tasks text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Total Tasks</p>
                    <p class="text-2xl font-semibold"><?php echo number_format($stats['total_tasks']); ?></p>
                </div>
            </div>
        </div>

        <!-- Employees Card -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-users text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Total Employees</p>
                    <p class="text-2xl font-semibold"><?php echo number_format($stats['total_employees']); ?></p>
                </div>
            </div>
        </div>

        <!-- Managers Card -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <i class="fas fa-user-tie text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Total Managers</p>
                    <p class="text-2xl font-semibold"><?php echo number_format($stats['total_managers']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Unassigned Tasks</h2>
        <div class="space-y-4">
            <?php
            try {
                // Get unassigned tasks
                $stmt = $pdo->query("
                    SELECT t.*, c.name as client_name
                    FROM tasks t
                    LEFT JOIN clients c ON t.client_id = c.id 
                    WHERE t.assigned_to IS NULL
                    ORDER BY t.created_at DESC
                    LIMIT 5
                ");
                
                while ($task = $stmt->fetch()) {
                    $date = date('M j, Y', strtotime($task['created_at']));
                    echo "
                    <div class='flex items-center p-4 border-b bg-red-50'>
                        <i class='fas fa-exclamation-circle text-red-600 mr-4'></i>
                        <div>
                            <p class='font-medium'>" . htmlspecialchars($task['subject']) . "</p>
                            <p class='text-sm text-gray-500'>Client: " . htmlspecialchars($task['client_name']) . "</p>
                            <p class='text-sm text-gray-500'>Created on: {$date}</p>
                            <p class='text-sm text-red-600 font-semibold'>Status: Unassigned</p>
                        </div>
                    </div>";
                }

                if ($stmt->rowCount() == 0) {
                    echo "<p class='text-green-600 font-medium'>All tasks are currently assigned!</p>";
                }

            } catch(PDOException $e) {
                error_log($e->getMessage());
                echo "<p class='text-gray-500'>Unable to fetch unassigned tasks</p>";
            }
            ?>
        </div>
    </div>
</div> 