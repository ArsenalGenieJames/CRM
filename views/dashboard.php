<?php
// Get database connection
require_once 'config/database.php';

// Initialize statistics
$stats = [
    'total_accounts' => 0,
    'total_contacts' => 0, 
    'total_opportunities' => 0,
    'total_tasks' => 0
];

try {
    // Get total accounts
    $stmt = $pdo->query("SELECT COUNT(*) FROM accounts WHERE created_at IS NOT NULL");
    $stats['total_accounts'] = $stmt->fetchColumn();

    // Get total contacts
    $stmt = $pdo->query("SELECT COUNT(*) FROM contacts"); 
    $stats['total_contacts'] = $stmt->fetchColumn();

    // Get total opportunities
    $stmt = $pdo->query("SELECT COUNT(*) FROM opportunities");
    $stats['total_opportunities'] = $stmt->fetchColumn();

    // Get total tasks
    $stmt = $pdo->query("SELECT COUNT(*) FROM tasks");
    $stats['total_tasks'] = $stmt->fetchColumn();

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
        <!-- Accounts Card -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-building text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Total Accounts</p>
                    <p class="text-2xl font-semibold"><?php echo number_format($stats['total_accounts']); ?></p>
                </div>
            </div>
        </div>

        <!-- Contacts Card -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-users text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Total Contacts</p>
                    <p class="text-2xl font-semibold"><?php echo number_format($stats['total_contacts']); ?></p>
                </div>
            </div>
        </div>

        <!-- Opportunities Card -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <i class="fas fa-bullseye text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Total Opportunities</p>
                    <p class="text-2xl font-semibold"><?php echo number_format($stats['total_opportunities']); ?></p>
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
    </div>

    <!-- Recent Activities -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Recent Activities</h2>
        <div class="space-y-4">
            <?php
            try {
                // Get recent activities (tasks, opportunities, etc.)
                $stmt = $pdo->query("
                    SELECT 'task' as type, title, created_at 
                    FROM tasks 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    UNION
                    SELECT 'opportunity' as type, name as title, created_at 
                    FROM opportunities 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    ORDER BY created_at DESC
                    LIMIT 5
                ");
                
                while ($activity = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $icon = $activity['type'] === 'task' ? 'fa-tasks text-purple-600' : 'fa-bullseye text-yellow-600';
                    $date = date('M j, Y', strtotime($activity['created_at']));
                    echo "
                    <div class='flex items-center p-4 border-b'>
                        <i class='fas {$icon} mr-4'></i>
                        <div>
                            <p class='font-medium'>" . htmlspecialchars($activity['title']) . "</p>
                            <p class='text-sm text-gray-500'>{$date}</p>
                        </div>
                    </div>";
                }
            } catch(PDOException $e) {
                error_log($e->getMessage());
                echo "<p class='text-gray-500'>No recent activities to display</p>";
            }
            ?>
        </div>
    </div>
</div> 