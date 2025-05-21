<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is an employee
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employee') {
    header('Location: login.php');
    exit;
}

try {
    // Get employee information including salary
    $stmt = $pdo->prepare("
        SELECT e.*, es.base_salary, es.effective_date
        FROM employees e
        LEFT JOIN employee_salaries es ON e.id = es.employee_id 
        WHERE e.id = ?
        ORDER BY es.effective_date DESC
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $employee = $stmt->fetch();

    // Get task payments
    $stmt = $pdo->prepare("
        SELECT tp.*, t.subject as task_subject
        FROM task_payments tp
        JOIN tasks t ON tp.task_id = t.id
        WHERE tp.employee_id = ?
        ORDER BY tp.payment_date DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_payments = $stmt->fetchAll();

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
    <title>Employee Profile - Creative Studios</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="employee.php" class="text-xl font-bold text-gray-800">Creative Studios</a>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="relative">
                        <button id="userMenuButton" class="flex items-center text-gray-700 hover:text-gray-900 focus:outline-none">
                            <span class="mr-2"><?php echo isset($employee['name']) ? htmlspecialchars($employee['name']) : 'Employee'; ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div id="userMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1">
                            <a href="profile_employee.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                            <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Logout</a>
                        </div>
                    </div>
                </div>  
            </div>
        </div>
    </nav>
    

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Employee Profile -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Employee Profile</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-gray-600">Name</p>
                    <p class="font-semibold"><?php echo htmlspecialchars($employee['name']); ?></p>
                </div>
                <div>
                    <p class="text-gray-600">Position</p>
                    <p class="font-semibold"><?php echo htmlspecialchars($employee['position'] ?? 'Not specified'); ?></p>
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
                <div>
                    <p class="text-gray-600">Base Salary</p>
                    <p class="font-semibold">$<?php echo number_format($employee['base_salary'] ?? 0, 2); ?></p>
                </div>
            </div>
        </div>

        <!-- Recent Payments -->
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Recent Payments</h2>
            <?php if (empty($recent_payments)): ?>
                <p class="text-gray-600">No recent payments.</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($recent_payments as $payment): ?>
                        <div class="border-l-4 border-green-500 pl-4 py-2">
                            <p class="text-sm text-gray-600">
                                <span class="font-semibold"><?php echo htmlspecialchars($payment['task_subject']); ?></span>
                                - Additional Amount: $<?php echo number_format($payment['additional_amount'], 2); ?>
                            </p>
                            <p class="text-xs text-gray-500">
                                <?php echo date('M d, Y', strtotime($payment['payment_date'])); ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
<script>
        const userMenuButton = document.getElementById('userMenuButton');
        const userMenu = document.getElementById('userMenu');
        
        userMenuButton.addEventListener('click', () => {
            userMenu.classList.toggle('hidden');
        });
        
        document.addEventListener('click', (e) => {
            if (!userMenuButton.contains(e.target) && !userMenu.contains(e.target)) {
                userMenu.classList.add('hidden');
            }
        });
    </script>
</html>
