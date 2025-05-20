<?php
require_once 'config/database.php';

// Get statistics for reports
try {
    // Opportunities by stage
    $stmt = $pdo->query("
        SELECT stage, COUNT(*) as count, SUM(amount) as total_amount
        FROM opportunities
        GROUP BY stage
    ");
    $opportunitiesByStage = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Opportunities by month
    $stmt = $pdo->query("
        SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
               COUNT(*) as count,
               SUM(amount) as total_amount
        FROM opportunities
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY month
        ORDER BY month DESC
    ");
    $opportunitiesByMonth = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tasks by status
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as count
        FROM tasks
        GROUP BY status
    ");
    $tasksByStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log($e->getMessage());
    $opportunitiesByStage = [];
    $opportunitiesByMonth = [];
    $tasksByStatus = [];
}
?>

<div class="container mx-auto">
    <h1 class="text-3xl font-bold mb-8">
        <i class="fas fa-chart-bar mr-2"></i>Reports
    </h1>

    <!-- Opportunities by Stage -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">Opportunities by Stage</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach ($opportunitiesByStage as $stage): ?>
            <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($stage['stage']); ?></h3>
                <p class="text-2xl font-bold text-blue-600"><?php echo number_format($stage['count']); ?></p>
                <p class="text-sm text-gray-500">$<?php echo number_format($stage['total_amount'], 2); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Monthly Opportunities -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">Monthly Opportunities</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Month</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Count</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($opportunitiesByMonth as $month): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo number_format($month['count']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">$<?php echo number_format($month['total_amount'], 2); ?></div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tasks by Status -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Tasks by Status</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach ($tasksByStatus as $status): ?>
            <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($status['status']); ?></h3>
                <p class="text-2xl font-bold text-blue-600"><?php echo number_format($status['count']); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div> 