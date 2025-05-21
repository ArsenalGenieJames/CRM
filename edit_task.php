<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is a client
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'client') {
    header('Location: login.php');
    exit;
}

// Get task ID from URL parameter
$task_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$task_id) {
    header('Location: client.php');
    exit;
}

// Get task details
try {
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND client_id = ?");
    $stmt->execute([$task_id, $_SESSION['user_id']]);
    $task = $stmt->fetch();

    if (!$task) {
        header('Location: client.php');
        exit;
    }

    // Get categories for dropdown
    $stmt = $pdo->prepare("SELECT * FROM task_categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll();

    // Get existing document for this task
    $stmt = $pdo->prepare("SELECT * FROM client_documents WHERE task_id = ? ORDER BY upload_date DESC LIMIT 1");
    $stmt->execute([$task_id]);
    $existing_document = $stmt->fetch();

    // Handle document deletion
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_document'])) {
        try {
            $pdo->beginTransaction();

            // Delete file from filesystem
            if (file_exists($existing_document['file_path'])) {
                unlink($existing_document['file_path']);
            }

            // Delete record from database
            $stmt = $pdo->prepare("DELETE FROM client_documents WHERE id = ?");
            $stmt->execute([$existing_document['id']]);

            $pdo->commit();
            
            // Refresh page to show updated state
            header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $task_id);
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Error deleting document: " . $e->getMessage());
            $errors[] = "Failed to delete document";
        }
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_task'])) {
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

                $stmt = $pdo->prepare("UPDATE tasks SET 
                    subject = ?, 
                    description = ?,
                    due_date = ?,
                    priority = ?,
                    category_id = ?,
                    budget = ?,
                    updated_at = NOW()
                    WHERE id = ? AND client_id = ?");

                $stmt->execute([
                    $subject,
                    $description, 
                    $due_date,
                    $priority,
                    $category_id,
                    $budget,
                    $task_id,
                    $_SESSION['user_id']
                ]);

                // Save document if uploaded
                if ($document_path) {
                    // Delete old document if exists
                    if ($existing_document) {
                        if (file_exists($existing_document['file_path'])) {
                            unlink($existing_document['file_path']);
                        }
                        $stmt = $pdo->prepare("DELETE FROM client_documents WHERE id = ?");
                        $stmt->execute([$existing_document['id']]);
                    }

                    $stmt = $pdo->prepare("
                        INSERT INTO client_documents (
                            client_id,
                            document_name,
                            document_type,
                            file_path,
                            task_id,
                            user_type
                        ) VALUES (
                            :client_id,
                            :document_name,
                            :document_type,
                            :file_path,
                            :task_id,
                            'Client'
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

                $_SESSION['success_message'] = "Task updated successfully";
                header('Location: client.php');
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Error updating task: " . $e->getMessage());
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $_SESSION['error_message'] = "A database error occurred. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Task</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Edit Task</h1>

            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <ul class="list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" onsubmit="return validateForm()" enctype="multipart/form-data">
                <div class="mb-4">
                    <label for="subject" class="block text-gray-700 font-bold mb-2">Subject</label>
                    <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($task['subject']); ?>"
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                </div>

                <div class="mb-4">
                    <label for="description" class="block text-gray-700 font-bold mb-2">Description</label>
                    <textarea id="description" name="description" rows="4"
                            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500"><?php echo htmlspecialchars($task['description']); ?></textarea>
                </div>

                <div class="mb-4">
                    <label for="due_date" class="block text-gray-700 font-bold mb-2">Due Date</label>
                    <input type="datetime-local" id="due_date" name="due_date" value="<?php echo date('Y-m-d\TH:i', strtotime($task['due_date'])); ?>"
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500"
                           min="<?php echo date('Y-m-d\TH:i'); ?>">
                </div>

                <div class="mb-4">
                    <label for="priority" class="block text-gray-700 font-bold mb-2">Priority</label>
                    <select id="priority" name="priority" 
                            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="Low" <?php echo $task['priority'] === 'Low' ? 'selected' : ''; ?>>Low</option>
                        <option value="Medium" <?php echo $task['priority'] === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="High" <?php echo $task['priority'] === 'High' ? 'selected' : ''; ?>>High</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="category_id" class="block text-gray-700 font-bold mb-2">Category</label>
                    <select id="category_id" name="category_id"
                            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                        <?php foreach($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                <?php echo $task['category_id'] === $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="budget" class="block text-gray-700 font-bold mb-2">Budget</label>
                    <input type="number" id="budget" name="budget" value="<?php echo $task['budget']; ?>"
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500"
                           min="0" step="0.01">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2">Current Document</label>
                    <?php if ($existing_document): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($existing_document['document_name']); ?></p>
                                <p class="text-xs text-gray-500">Uploaded: <?php echo date('Y-m-d H:i', strtotime($existing_document['upload_date'])); ?></p>
                            </div>
                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this document?');">
                                <button type="submit" name="delete_document" class="text-red-600 hover:text-red-800">
                                    Delete
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 text-sm">No document currently attached</p>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2" for="task_document">
                        Upload New Document
                    </label>
                    <input type="file" name="task_document" id="task_document"
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500"
                           accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png">
                    <p class="text-sm text-gray-500 mt-1">Allowed file types: PDF, DOC, DOCX, TXT, JPG, JPEG, PNG</p>
                </div>

                <div class="flex justify-end">
                    <a href="client.php" 
                       class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded mr-2">
                        Cancel
                    </a>
                    <button type="submit" name="update_task"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Update Task
                    </button>
                </div>
            </form>
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
    </script>
</body>
</html>
