<?php
require_once 'config/database.php';

// Get tasks assigned to the logged in client
try {
    $stmt = $pdo->prepare("
        SELECT t.id, t.subject, t.description, t.due_date, t.priority, t.status,
               c.name as client_name,
               e.name as employee_name, eta.assigned_at,
               t.created_at, t.updated_at
        FROM tasks t
        LEFT JOIN clients c ON t.client_id = c.id 
        LEFT JOIN employee_task_assignments eta ON t.id = eta.task_id
        LEFT JOIN employees e ON eta.employee_id = e.id
        WHERE t.client_id = ?
        ORDER BY t.due_date ASC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all available employees
    $stmt = $pdo->query("SELECT id, name FROM employees WHERE status = 'Active'");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log($e->getMessage());
    $tasks = [];
    $employees = [];
}

// Handle task status updates and employee assignments
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        try {
            // Update task status
            $stmt = $pdo->prepare("
                UPDATE tasks 
                SET status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['status'],
                $_POST['task_id']
            ]);

            // Add task update record
            $stmt = $pdo->prepare("
                INSERT INTO task_updates (
                    task_id, user_type, user_id, status, comments
                ) VALUES (?, 'Client', ?, ?, ?)
            ");
            $stmt->execute([
                $_POST['task_id'],
                $_SESSION['user_id'],
                $_POST['status'],
                $_POST['comments'] ?? null
            ]);

            header("Location: index.php?modules=task");
            exit();
        } catch(PDOException $e) {
            error_log($e->getMessage());
        }
    } elseif ($_POST['action'] === 'assign_employee') {
        try {
            // First remove any existing assignment
            $stmt = $pdo->prepare("DELETE FROM employee_task_assignments WHERE task_id = ?");
            $stmt->execute([$_POST['task_id']]);

            // Add new assignment
            $stmt = $pdo->prepare("
                INSERT INTO employee_task_assignments (
                    task_id, employee_id, assigned_at
                ) VALUES (?, ?, NOW())
            ");
            $stmt->execute([
                $_POST['task_id'],
                $_POST['employee_id']
            ]);

            header("Location: index.php?modules=task");
            exit();
        } catch(PDOException $e) {
            error_log($e->getMessage());
        }
    }
}

$statuses = ['Not Started', 'In Progress', 'Completed', 'Deferred'];
?>

<div class="container mx-auto">
    <h1 class="text-3xl font-bold mb-6">
        <i class="fas fa-tasks mr-2"></i>Client Task 
    </h1>

    <!-- Task List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Task Details</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($tasks)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No tasks found</td>
                </tr>
                <?php else: ?>
                <?php foreach ($tasks as $task): ?>
                <tr>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900 cursor-pointer" onclick="openModal('assignModal<?php echo $task['id']; ?>')">
                            <?php echo htmlspecialchars($task['subject']); ?>
                        </div>
                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($task['description']); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($task['employee_name'] ?: 'Not assigned'); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php 
                        $priorityColors = [
                            'Low' => 'green',
                            'Medium' => 'yellow', 
                            'High' => 'red'
                        ];
                        $color = $priorityColors[$task['priority']] ?? 'gray';
                        ?>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?php echo $color; ?>-100 text-<?php echo $color; ?>-800">
                            <?php echo htmlspecialchars($task['priority']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php 
                        $statusColors = [
                            'Not Started' => 'gray',
                            'In Progress' => 'blue',
                            'Completed' => 'green',
                            'Deferred' => 'yellow'
                        ];
                        $color = $statusColors[$task['status']] ?? 'gray';
                        ?>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?php echo $color; ?>-100 text-<?php echo $color; ?>-800">
                            <?php echo htmlspecialchars($task['status']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500"><?php echo date('M j, Y g:i A', strtotime($task['due_date'])); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button onclick="openModal('updateModal<?php echo $task['id']; ?>')" class="text-blue-600 hover:text-blue-900">Update Status</button>

                        <!-- Update Status Modal -->
                        <div id="updateModal<?php echo $task['id']; ?>" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
                            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Update Task Status</h3>
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    
                                    <div class="mb-4">
                                        <label class="block text-gray-700 text-sm font-bold mb-2">Status</label>
                                        <select name="status" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                            <?php foreach($statuses as $status): ?>
                                                <option value="<?php echo $status; ?>" <?php echo $task['status'] === $status ? 'selected' : ''; ?>>
                                                    <?php echo $status; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-4">
                                        <label class="block text-gray-700 text-sm font-bold mb-2">Comments</label>
                                        <textarea name="comments" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" rows="3"></textarea>
                                    </div>

                                    <div class="flex justify-between">
                                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Update</button>
                                        <button type="button" onclick="closeModal('updateModal<?php echo $task['id']; ?>')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Assign Employee Modal -->
                        <div id="assignModal<?php echo $task['id']; ?>" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
                            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Assign Employee to Task</h3>
                                <form method="POST">
                                    <input type="hidden" name="action" value="assign_employee">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    
                                    <div class="mb-4">
                                        <label class="block text-gray-700 text-sm font-bold mb-2">Select Employee</label>
                                        <select name="employee_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                            <option value="">Select an employee</option>
                                            <?php foreach($employees as $employee): ?>
                                                <option value="<?php echo $employee['id']; ?>" <?php echo ($task['employee_name'] === $employee['name']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($employee['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="flex justify-between">
                                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Assign</button>
                                        <button type="button" onclick="closeModal('assignModal<?php echo $task['id']; ?>')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function openModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('fixed')) {
        closeModal(event.target.id);
    }
}
</script>