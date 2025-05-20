<?php
require_once 'config/database.php';

// Get all tasks
try {
    $stmt = $pdo->query("
        SELECT t.*, 
               CASE 
                   WHEN t.related_type = 'account' THEN a.name
                   WHEN t.related_type = 'opportunity' THEN o.name
                   ELSE NULL
               END as related_name
        FROM tasks t
        LEFT JOIN accounts a ON t.related_id = a.id AND t.related_type = 'account'
        LEFT JOIN opportunities o ON t.related_id = o.id AND t.related_type = 'opportunity'
        ORDER BY t.due_date ASC
    ");
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log($e->getMessage());
    $tasks = [];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_task') {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO tasks (
                    subject, description, related_type, related_id,
                    due_date, priority, status, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $_POST['subject'],
                $_POST['description'],
                $_POST['related_type'],
                $_POST['related_id'] ?? null,
                $_POST['due_date'],
                $_POST['priority'],
                $_POST['status']
            ]);

            header("Location: index.php?module=task");
            exit();
        } catch(PDOException $e) {
            error_log($e->getMessage());
        }
    } elseif ($_POST['action'] === 'edit_task') {
        try {
            $stmt = $pdo->prepare("
                UPDATE tasks 
                SET subject = ?, description = ?, related_type = ?, 
                    related_id = ?, due_date = ?, priority = ?, 
                    status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $_POST['subject'],
                $_POST['description'], 
                $_POST['related_type'],
                $_POST['related_id'] ?? null,
                $_POST['due_date'],
                $_POST['priority'],
                $_POST['status'],
                $_POST['task_id']
            ]);

            header("Location: index.php?module=task");
            exit();
        } catch(PDOException $e) {
            error_log($e->getMessage());
        }
    } elseif ($_POST['action'] === 'delete_task') {
        try {
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
            $stmt->execute([$_POST['task_id']]);
            
            header("Location: index.php?module=task");
            exit();
        } catch(PDOException $e) {
            error_log($e->getMessage());
        }
    }
}
?>

<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">
            <i class="fas fa-tasks mr-2"></i>Tasks
        </h1>
        <button onclick="openModal('taskModal')" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-plus mr-2"></i>Add New Task
        </button>

        <!-- Add Task Modal -->
        <div id="taskModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h2 class="text-2xl font-bold mb-6">Add New Task</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="add_task">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="description">Description</label>
                            <textarea id="description" name="description" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="related_type">Related To</label>
                            <select id="related_type" name="related_type" onchange="updateRelatedOptions()" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="">None</option>
                                <option value="account">Account</option>
                                <option value="opportunity">Opportunity</option>
                            </select>
                        </div>
                        <div id="related_id_container" class="mb-4 hidden">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="related_id">Select Related Record</label>
                            <select id="related_id" name="related_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="due_date">Due Date</label>
                            <input type="date" id="due_date" name="due_date" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="priority">Priority</label>
                            <select id="priority" name="priority" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="High">High</option>
                                <option value="Medium">Medium</option>
                                <option value="Low">Low</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="status">Status</label>
                            <select id="status" name="status" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="Not Started">Not Started</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                                <option value="Deferred">Deferred</option>
                            </select>
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

    <!-- Tasks Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Task Subject</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Related To</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
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
                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($task['related_name']); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500"><?php echo date('M j, Y', strtotime($task['due_date'])); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            <?php
                            switch($task['priority']) {
                                case 'High':
                                    echo 'bg-red-100 text-red-800';
                                    break;
                                case 'Medium':
                                    echo 'bg-yellow-100 text-yellow-800';
                                    break;
                                default:
                                    echo 'bg-green-100 text-green-800';
                            }
                            ?>">
                            <?php echo htmlspecialchars($task['priority']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            <?php
                            switch($task['status']) {
                                case 'Completed':
                                    echo 'bg-green-100 text-green-800';
                                    break;
                                case 'In Progress':
                                    echo 'bg-blue-100 text-blue-800';
                                    break;
                                case 'Deferred':
                                    echo 'bg-gray-100 text-gray-800';
                                    break;
                                default:
                                    echo 'bg-yellow-100 text-yellow-800';
                            }
                            ?>">
                            <?php echo htmlspecialchars($task['status']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($task)); ?>)" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
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

async function updateRelatedOptions() {
    const relatedType = document.getElementById('related_type').value;
    const relatedIdContainer = document.getElementById('related_id_container');
    const relatedIdSelect = document.getElementById('related_id');
    
    if (!relatedType) {
        relatedIdContainer.classList.add('hidden');
        return;
    }

    try {
        const response = await fetch(`api/get_related_records.php?type=${relatedType}`);
        const data = await response.json();
        
        relatedIdSelect.innerHTML = '';
        data.forEach(record => {
            const option = document.createElement('option');
            option.value = record.id;
            option.textContent = record.name;
            relatedIdSelect.appendChild(option);
        });
        
        relatedIdContainer.classList.remove('hidden');
    } catch (error) {
        console.error('Error fetching related records:', error);
    }
}

function openEditModal(task) {
    // Create edit modal dynamically
    const modalHtml = `
        <div id="editModal${task.id}" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h2 class="text-2xl font-bold mb-6">Edit Task</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="edit_task">
                        <input type="hidden" name="task_id" value="${task.id}">
                        <!-- Add same form fields as Add Task modal but with pre-filled values -->
                        <!-- ... -->
                        <div class="flex items-center justify-between mt-6">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Save</button>
                            <button type="button" onclick="closeModal('editModal${task.id}')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    openModal(`editModal${task.id}`);
}
</script>