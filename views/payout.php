<?php
require_once 'config/database.php';

// Get employees with their base salaries, task payments and task details
try {
    $stmt = $pdo->query("
        SELECT 
            e.id,
            e.name,
            e.email,
            e.status,
            COALESCE(es.base_salary, 0) as base_salary,
            COALESCE(SUM(tp.additional_amount), 0) as total_additional,
            COUNT(DISTINCT t.id) as completed_tasks,
            GROUP_CONCAT(DISTINCT t.subject) as task_names
        FROM employees e
        LEFT JOIN employee_salaries es ON e.id = es.employee_id 
            AND es.effective_date = (
                SELECT MAX(effective_date) 
                FROM employee_salaries 
                WHERE employee_id = e.id
            )
        LEFT JOIN task_payments tp ON e.id = tp.employee_id
        LEFT JOIN tasks t ON tp.task_id = t.id AND t.status = 'Completed'
        GROUP BY e.id, e.name, e.email, e.status, es.base_salary
    ");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all completed tasks
    $taskStmt = $pdo->query("
        SELECT id, subject, client_id 
        FROM tasks 
        WHERE status = 'Completed'
    ");
    $tasks = $taskStmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log($e->getMessage());
    $employees = [];
    $tasks = [];
}

// Handle form submission for new payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_payment') {
        try {
            // Begin transaction
            $pdo->beginTransaction();

            // Insert payment record
            $stmt = $pdo->prepare("
                INSERT INTO task_payments (
                    task_id, 
                    employee_id, 
                    additional_amount, 
                    payment_date, 
                    description,
                    status
                ) VALUES (?, ?, ?, ?, ?, 'Pending')
            ");
            
            $stmt->execute([
                $_POST['task_id'],
                $_POST['employee_id'], 
                $_POST['additional_amount'],
                $_POST['payment_date'],
                $_POST['description']
            ]);

            // Commit transaction
            $pdo->commit();

            header('Location: index.php?modules=payout&success=1');
            exit;
        } catch(PDOException $e) {
            $pdo->rollBack();
            error_log($e->getMessage());
        }
    }
}

?>

<div class="container mx-auto px-4">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Employee Payouts</h1>
        <button onclick="openModal('payoutModal')" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Add New Payout
        </button>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline">Payment added successfully!</span>
    </div>
    <?php endif; ?>

    <div class="bg-white shadow-md rounded my-6">
        <table class="min-w-full table-auto">
            <thead>
                <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                    <th class="py-3 px-6 text-left">Employee</th>
                    <th class="py-3 px-6 text-left">Status</th>
                    <th class="py-3 px-6 text-right">Base Salary</th>
                    <th class="py-3 px-6 text-right">Additional Payments</th>
                    <th class="py-3 px-6 text-center">Completed Tasks</th>
                    <th class="py-3 px-6 text-right">Total Earnings</th>
                    <th class="py-3 px-6 text-center">Payment Status</th>
                    <th class="py-3 px-6 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 text-sm font-light">
                <?php foreach ($employees as $employee): ?>
                    <tr class="border-b border-gray-200 hover:bg-gray-100">
                        <td class="py-3 px-6 text-left">
                            <div>
                                <div class="font-medium"><?php echo htmlspecialchars($employee['name']); ?></div>
                                <div class="text-gray-500"><?php echo htmlspecialchars($employee['email']); ?></div>
                            </div>
                        </td>
                        <td class="py-3 px-6 text-left">
                            <span class="bg-<?php echo $employee['status'] === 'Active' ? 'green' : 'red'; ?>-200 text-<?php echo $employee['status'] === 'Active' ? 'green' : 'red'; ?>-600 py-1 px-3 rounded-full text-xs">
                                <?php echo htmlspecialchars($employee['status']); ?>
                            </span>
                        </td>
                        <td class="py-3 px-6 text-right">$<?php echo number_format($employee['base_salary'], 2); ?></td>
                        <td class="py-3 px-6 text-right">$<?php echo number_format($employee['total_additional'], 2); ?></td>
                        <td class="py-3 px-6 text-center">
                            <?php echo $employee['completed_tasks']; ?>
                            <?php if ($employee['task_names']): ?>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($employee['task_names']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-6 text-right font-medium">
                            $<?php echo number_format($employee['base_salary'] + $employee['total_additional'], 2); ?>
                        </td>
                        <td class="py-3 px-6 text-center">
                            <span class="bg-yellow-200 text-yellow-600 py-1 px-3 rounded-full text-xs">
                                Pending
                            </span>
                        </td>
                        <td class="py-3 px-6 text-center">
                            <button onclick="openModal('payoutModal<?php echo $employee['id']; ?>')" class="text-blue-600 hover:text-blue-900">Add Payment</button>
                            <button onclick="viewHistory(<?php echo $employee['id']; ?>)" class="text-green-600 hover:text-green-900 ml-2">History</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Payment Modal -->
<div id="payoutModal" class="fixed hidden inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Add New Payment</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add_payment">
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="employee">
                        Employee
                    </label>
                    <select name="employee_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        <option value="">Select Employee</option>
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?php echo $employee['id']; ?>"><?php echo htmlspecialchars($employee['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="task">
                        Completed Task
                    </label>
                    <select name="task_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        <option value="">Select Task</option>
                        <?php foreach ($tasks as $task): ?>
                            <option value="<?php echo $task['id']; ?>"><?php echo htmlspecialchars($task['subject']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="amount">
                        Additional Amount
                    </label>
                    <input type="number" step="0.01" name="additional_amount" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="payment_date">
                        Payment Date
                    </label>
                    <input type="date" name="payment_date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="description">
                        Description
                    </label>
                    <textarea name="description" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" rows="3"></textarea>
                </div>

                <div class="flex items-center justify-between mt-6">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Add Payment
                    </button>
                    <button type="button" onclick="closeModal('payoutModal')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

function viewHistory(employeeId) {
    // Implement payment history view functionality
    alert('Payment history feature coming soon!');
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('fixed')) {
        closeModal(event.target.id);
    }
}
</script>
