<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is a client
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'client') {
    header('Location: login.php');
    exit;
}

// Get client information and categories
try {
    // Get client info
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $client = $stmt->fetch();

    if (!$client) {
        throw new Exception("Client not found");
    }

    // Get categories once
    $stmt = $pdo->prepare("SELECT * FROM task_categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll();

    // Handle task creation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_task'])) {
        // Validate inputs
        $errors = [];
        
        $subject = trim($_POST['subject']);
        if (empty($subject)) {
            $errors[] = "Subject is required";
        }
        
        $description = trim($_POST['description']);
        if (empty($description)) {
            $errors[] = "Description is required";
        }
        
        $due_date = trim($_POST['due_date']);
        if (empty($due_date) || strtotime($due_date) === false) {
            $errors[] = "Valid due date is required";
        }
        
        $priority = $_POST['priority'];
        if (!in_array($priority, ['Low', 'Medium', 'High'])) {
            $errors[] = "Invalid priority level";
        }
        
        $category_id = filter_var($_POST['category_id'], FILTER_VALIDATE_INT);
        if (!$category_id) {
            $errors[] = "Valid category is required";
        }
        
        $budget = filter_var($_POST['budget'], FILTER_VALIDATE_FLOAT);
        if ($budget === false || $budget < 0) {
            $errors[] = "Valid budget amount is required";
        }

        // Handle document upload
        $document_path = '';
        if (isset($_FILES['task_document']) && $_FILES['task_document']['error'] == 0) {
            $allowed = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png'];
            $filename = $_FILES['task_document']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (!in_array(strtolower($filetype), $allowed)) {
                $errors[] = "Invalid file type. Allowed types: " . implode(', ', $allowed);
            } else {
                $upload_dir = 'uploads/client_documents/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $document_path = $upload_dir . time() . '_' . $filename;
                
                if (!move_uploaded_file($_FILES['task_document']['tmp_name'], $document_path)) {
                    $errors[] = "Failed to upload document";
                }
            }
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("
                    INSERT INTO tasks (
                        subject, 
                        description, 
                        due_date, 
                        priority, 
                        status, 
                        client_id,
                        category_id,
                        budget
                    ) VALUES (
                        :subject,
                        :description,
                        :due_date,
                        :priority,
                        'Not Started',
                        :client_id,
                        :category_id,
                        :budget
                    )
                ");
                
                $stmt->execute([
                    ':subject' => $subject,
                    ':description' => $description,
                    ':due_date' => $due_date,
                    ':priority' => $priority,
                    ':client_id' => $_SESSION['user_id'],
                    ':category_id' => $category_id,
                    ':budget' => $budget
                ]);

                $task_id = $pdo->lastInsertId();

                // Save document if uploaded
                if ($document_path) {
                    $stmt = $pdo->prepare("
                        INSERT INTO client_documents (
                            client_id,
                            document_name,
                            document_type,
                            file_path,
                            task_id
                        ) VALUES (
                            :client_id,
                            :document_name,
                            :document_type,
                            :file_path,
                            :task_id
                        )
                    ");

                    $stmt->execute([
                        ':client_id' => $_SESSION['user_id'],
                        ':document_name' => $filename,
                        ':document_type' => $filetype,
                        ':file_path' => $document_path,
                        ':task_id' => $task_id
                    ]);
                }

                $pdo->commit();
                
                $_SESSION['success_message'] = "Task created successfully";
                header('Location: client.php');
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Error creating task: " . $e->getMessage());
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
        
        if (!empty($errors)) {
            $_SESSION['error_message'] = implode("<br>", $errors);
        }
    }

    // Get client's tasks with manager names
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
    error_log("Database Error: " . $e->getMessage());
    $_SESSION['error_message'] = "A database error occurred. Please try again later.";
} catch(Exception $e) {
    error_log("General Error: " . $e->getMessage());
    $_SESSION['error_message'] = $e->getMessage();
}

if (!isset($client) || !$client) {
    header('Location: login.php');
    exit;
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
                        <h1 class="text-xl font-bold text-gray-800 ">Creative Studios</h1>
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
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $_SESSION['error_message']; ?></span>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $_SESSION['success_message']; ?></span>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <!-- Create Task Button -->
        <div class="mb-6">
            <button onclick="document.getElementById('createTaskModal').classList.remove('hidden')" 
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Create New Task
            </button>
        </div>

        <!-- Create Task Modal -->
        <div id="createTaskModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Create New Task</h3>
                    <form method="POST" action="" onsubmit="return validateForm()" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="subject">
                                Subject
                            </label>
                            <input type="text" name="subject" id="subject" required
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="description">
                                Description
                            </label>
                            <textarea name="description" id="description" required
                                      class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                      rows="4" placeholder="Enter Description of the task here"></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="due_date">
                                Due Date
                            </label>
                            <input type="datetime-local" name="due_date" id="due_date" required
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                   min="<?php echo date('Y-m-d\TH:i'); ?>">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="priority">
                                Priority
                            </label>
                            <select name="priority" id="priority" required
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="category_id">
                                Category
                            </label>
                            <select name="category_id" id="category_id" required
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="budget">
                                Budget
                            </label>
                            <input type="number" name="budget" id="budget" required min="0" step="0.01"
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                   placeholder="Enter task budget">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="task_document">
                                Upload Document
                            </label>
                            <input type="file" name="task_document" id="task_document"
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                   accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png">
                            <p class="text-sm text-gray-500 mt-1">Allowed file types: PDF, DOC, DOCX, TXT, JPG, JPEG, PNG</p>
                        </div>
                        <div class="flex justify-end">
                            <button type="button" onclick="document.getElementById('createTaskModal').classList.add('hidden')"
                                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded mr-2">
                                Cancel
                            </button>
                            <button type="submit" name="create_task"
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Create Task
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Client Information -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Client Information</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-gray-600">Name</p>
                    <p class="font-semibold"><?php echo htmlspecialchars($client['name']); ?></p>
                </div>
                <div>
                    <p class="text-gray-600">Email</p>
                    <p class="font-semibold"><?php echo htmlspecialchars($client['contact_email'] ?? 'Not provided'); ?></p>
                </div>
                <div>
                    <p class="text-gray-600">Phone</p>
                    <p class="font-semibold"><?php echo htmlspecialchars($client['contact_phone'] ?? 'Not provided'); ?></p>
                </div>
                <div>
                    <p class="text-gray-600">Status</p>
                    <p class="font-semibold"><?php echo htmlspecialchars($client['status']); ?></p>
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Budget</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($tasks as $task): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($task['subject']); ?></div>
                                    <div class="text-sm text-gray-500 description-cell" style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($task['description']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($task['manager_name'] ?? 'Unassigned'); ?></div>
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
                                    <div class="text-sm text-gray-900">₱<?php echo number_format($task['budget'], 2); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($task['category_name'] ?? 'Unknown'); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $task['status'] === 'Completed' ? 'bg-green-100 text-green-800' : 
                                            ($task['status'] === 'In Progress' ? 'bg-blue-100 text-blue-800' : 
                                            ($task['status'] === 'Deferred' ? 'bg-gray-100 text-gray-800' : 'bg-yellow-100 text-yellow-800')); ?>">
                                        <?php echo htmlspecialchars($task['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-left text-sm font-medium">
                                    <a href="edit_task.php?id=<?php echo $task['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</a>
                                    <a href="#" onclick="confirmDelete(<?php echo $task['id']; ?>)" class="text-red-600 hover:text-red-900">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function validateForm() {
        const subject = document.getElementById('subject').value.trim();
        const description = document.getElementById('description').value.trim();
        const dueDate = new Date(document.getElementById('due_date').value);
        const budget = parseFloat(document.getElementById('budget').value);
        const fileInput = document.getElementById('task_document');
        
        if (!subject) {
            alert('Please enter a subject');
            return false;
        }
        
        if (!description) {
            alert('Please enter a description');
            return false;
        }
        
        if (isNaN(dueDate.getTime())) {
            alert('Please select a valid due date');
            return false;
        }
        
        if (dueDate < new Date()) {
            alert('Due date cannot be in the past');
            return false;
        }
        
        if (isNaN(budget) || budget < 0) {
            alert('Please enter a valid budget amount');
            return false;
        }

        if (fileInput.files.length > 0) {
            const file = fileInput.files[0];
            const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain', 'image/jpeg', 'image/png'];
            if (!allowedTypes.includes(file.type)) {
                alert('Invalid file type. Please upload PDF, DOC, DOCX, TXT, JPG, JPEG or PNG files only.');
                return false;
            }
            if (file.size > 5242880) { // 5MB in bytes
                alert('File size must be less than 5MB');
                return false;
            }
        }
        
        return true;
    }

    function confirmDelete(taskId) {
              if (confirm('Are you sure you want to delete this task?')) {
                       window.location.href = 'delete_task.php?id=' + taskId;
                      }
             }
    </script>
</body>
</html>
