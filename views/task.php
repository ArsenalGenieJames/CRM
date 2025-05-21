<?php
require_once 'config/database.php';

// Get all tasks with related data
try {
    $stmt = $pdo->query("
        SELECT t.id, t.subject, t.description, t.due_date, t.priority, t.status,
               c.name as client_name, m.name as manager_name,
               t.created_at, t.updated_at
        FROM tasks t
        LEFT JOIN clients c ON t.client_id = c.id 
        LEFT JOIN managers m ON t.manager_id = m.id
        WHERE t.status != 'Completed'
    ");
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log($e->getMessage());
    $tasks = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_task') {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO tasks (
                    subject, description, due_date, priority, status,
                    client_id, manager_id, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $_POST['subject'],
                $_POST['description'],
                $_POST['due_date'],
                $_POST['priority'],
                'Not Started', // Default status for new tasks
                $_POST['client_id'],
                $_SESSION['manager_id'] // Assuming manager is logged in
            ]);

            header("Location: " . $_SERVER['PHP_SELF'] . "?modules=tasks");
            exit();
            
        } catch(PDOException $e) {
            error_log($e->getMessage());
        }
    } elseif ($_POST['action'] === 'delete_task' && isset($_POST['task_id'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
            $stmt->execute([$_POST['task_id']]);
            
            header("Location: " . $_SERVER['PHP_SELF'] . "?modules=tasks");
            exit();
        } catch(PDOException $e) {
            error_log($e->getMessage());
        }
    }
}

$priorities = ['Low', 'Medium', 'High'];
$statuses = ['Not Started', 'In Progress', 'Deferred'];
?>

<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">
            <i class="fas fa-tasks mr-2"></i>Task Management
        </h1>
        <button onclick="openModal('taskModal')" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-plus mr-2"></i>New Task
        </button>

        <!-- Task Modal -->
        <div id="taskModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Assign Task</h3>
                    <form id="taskForm" class="mt-4" method="POST">
                        <input type="hidden" name="action" value="add_task">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="client">Select Client</label>
                            <select id="client" name="client_id" required onchange="loadClientTasks(this.value)" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="">Select Client</option>
                                <?php
                                try {
                                    $stmt = $pdo->query("SELECT id, name FROM clients ORDER BY name ASC");
                                    while ($client = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        echo "<option value='" . htmlspecialchars($client['id']) . "'>" . htmlspecialchars($client['name']) . "</option>";
                                    }
                                } catch(PDOException $e) {
                                    error_log($e->getMessage());
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="subject">Task Subject</label>
                            <select id="subject" name="subject" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="">Select Task</option>
                                <?php
                                if (isset($_GET['client_id'])) {
                                    try {
                                        $stmt = $pdo->prepare("SELECT id, subject FROM tasks WHERE client_id = ? AND status != 'Completed' ORDER BY created_at DESC");
                                        $stmt->execute([$_GET['client_id']]);
                                        while ($task = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            echo "<option value='" . htmlspecialchars($task['id']) . "'>" . htmlspecialchars($task['subject']) . "</option>";
                                        }
                                    } catch(PDOException $e) {
                                        error_log($e->getMessage());
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="description">Description</label>
                            <textarea id="description" name="description" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="employee">Assign To Employee</label>
                            <select id="employee" name="employee_id" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="">Select an employee</option>
                                <?php
                                try {
                                    $stmt = $pdo->query("SELECT id, name FROM employees ORDER BY name ASC");
                                    while ($employee = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        echo "<option value='" . htmlspecialchars($employee['id']) . "'>" . htmlspecialchars($employee['name']) . "</option>";
                                    }
                                } catch(PDOException $e) {
                                    error_log($e->getMessage());
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="priority">Priority</label>
                            <select id="priority" name="priority" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="">Select Priority</option>
                                <?php foreach($priorities as $priority): ?>
                                    <option value="<?php echo htmlspecialchars($priority); ?>"><?php echo htmlspecialchars($priority); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="due_date">Due Date</label>
                            <input type="datetime-local" id="due_date" name="due_date" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="flex items-center justify-between mt-6">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Save</button>
                            <button type="button" onclick="closeModal('taskModal')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Task List View -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee Assigned</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
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
                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($task['client_name']); ?></div>
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
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($task['manager_name']); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button onclick="openModal('editModal<?php echo $task['id']; ?>')" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete_task">
                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                            <button type="submit" onclick="return confirm('Are you sure you want to delete this task?')" class="text-red-600 hover:text-red-900">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function openModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
    document.body.style.overflow = 'auto';
    if(modalId === 'taskModal') {
        document.getElementById('taskForm').reset();
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('fixed')) {
        closeModal(event.target.id);
    }
}

function loadClientTasks(clientId) {
    if (!clientId) {
        document.getElementById('subject').innerHTML = '<option value="">Select Task</option>';
        return;
    }

    fetch(`get_client_tasks.php?client_id=${clientId}`)
        .then(response => response.json())
        .then(tasks => {
            let options = '<option value="">Select Task</option>';
            tasks.forEach(task => {
                options += `<option value="${task.id}">${task.subject}</option>`;
            });
            document.getElementById('subject').innerHTML = options;
        })
        .catch(error => console.error('Error loading tasks:', error));
}
</script>