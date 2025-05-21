<?php
require_once 'config/database.php';

// Get all employees and clients with their details
try {
    $stmt = $pdo->query("
        SELECT e.*, COUNT(eta.task_id) as task_count,
        SUM(CASE WHEN t.status = 'Completed' THEN tp.additional_amount ELSE 0 END) as total_earnings
        FROM employees e
        LEFT JOIN employee_task_assignments eta ON e.id = eta.employee_id
        LEFT JOIN tasks t ON eta.task_id = t.id
        LEFT JOIN task_payments tp ON t.id = tp.task_id AND e.id = tp.employee_id
        GROUP BY e.id
        ORDER BY e.created_at DESC
    ");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("
        SELECT c.*, COUNT(t.id) as task_count,
        SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) as completed_tasks
        FROM clients c
        LEFT JOIN tasks t ON c.id = t.client_id
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log($e->getMessage());
    $employees = [];
    $clients = [];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_employee'])) {
        try {
            $id = $_POST['employee_id'];
            
            // Check for active tasks
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM employee_task_assignments WHERE employee_id = ?");
            $stmt->execute([$id]);
            $hasActiveTasks = $stmt->fetchColumn() > 0;

            if($hasActiveTasks) {
                $_SESSION['error'] = "Cannot delete employee - they have active task assignments";
            } else {
                $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['success'] = "Employee deleted successfully";
            }
        } catch(PDOException $e) {
            error_log($e->getMessage());
            $_SESSION['error'] = "Error deleting employee";
        }
    } elseif (isset($_POST['delete_client'])) {
        try {
            $id = $_POST['client_id'];
            
            // Check for active tasks
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE client_id = ?");
            $stmt->execute([$id]);
            $hasActiveTasks = $stmt->fetchColumn() > 0;

            if($hasActiveTasks) {
                $_SESSION['error'] = "Cannot delete client - they have active tasks";
            } else {
                $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['success'] = "Client deleted successfully";
            }
        } catch(PDOException $e) {
            error_log($e->getMessage());
            $_SESSION['error'] = "Error deleting client";
        }
    } elseif (isset($_POST['edit_employee'])) {
        try {
            $stmt = $pdo->prepare("
                UPDATE employees 
                SET name = ?, email = ?, phone = ?, industry = ?, status = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $_POST['name'],
                $_POST['email'], 
                $_POST['phone'],
                $_POST['industry'],
                $_POST['status'],
                $_POST['employee_id']
            ]);

            $_SESSION['success'] = "Employee updated successfully";
        } catch(PDOException $e) {
            error_log($e->getMessage());
            $_SESSION['error'] = "Error updating employee";
        }
    } elseif (isset($_POST['edit_client'])) {
        try {
            $stmt = $pdo->prepare("
                UPDATE clients 
                SET name = ?, industry = ?, contact_name = ?, contact_email = ?, 
                    contact_phone = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $_POST['name'],
                $_POST['industry'],
                $_POST['contact_name'],
                $_POST['contact_email'],
                $_POST['contact_phone'],
                $_POST['client_id']
            ]);

            $_SESSION['success'] = "Client updated successfully";
        } catch(PDOException $e) {
            error_log($e->getMessage());
            $_SESSION['error'] = "Error updating client";
        }
    } else {
        // Handle adding new employee/client
        try {
            if (isset($_POST['add_employee'])) {
                $stmt = $pdo->prepare("
                    INSERT INTO employees (name, email, phone, industry, status, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $_POST['name'],
                    $_POST['email'],
                    $_POST['phone'],
                    $_POST['industry'],
                    $_POST['status']
                ]);

                $_SESSION['success'] = "Employee added successfully";
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO clients (name, industry, contact_name, contact_email, 
                                       contact_phone, password, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
                
                $stmt->execute([
                    $_POST['name'],
                    $_POST['industry'],
                    $_POST['contact_name'],
                    $_POST['contact_email'],
                    $_POST['contact_phone'],
                    $hashedPassword
                ]);

                $_SESSION['success'] = "Client added successfully";
            }
        } catch(PDOException $e) {
            error_log($e->getMessage());
            $_SESSION['error'] = "Error adding record";
        }
    }
    
    // Refresh data after changes
    header("Location: " . $_SERVER['PHP_SELF'] . "?modules=account");
    exit();
}
?>

<div class="container mx-auto">
    <?php if(isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">
            <i class="fas fa-building mr-2"></i>Accounts Management
        </h1>

        <div class="flex space-x-4">
            <button onclick="openModal('addEmployeeModal')" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-user-plus mr-2"></i>Add Employee
            </button>
            <button onclick="openModal('addClientModal')" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-building mr-2"></i>Add Client
            </button>
        </div>
    </div>

    <!-- Employees Table -->
    <div class="bg-white shadow-md rounded my-6">
        <div class="p-4 border-b bg-gray-50">
            <h2 class="text-xl font-semibold">Employees</h2>
            <div class="mt-2 flex gap-4">
                <input type="text" id="employeeSearchInput" onkeyup="searchAccounts('employee')" 
                       placeholder="Search employees..." 
                       class="px-3 py-2 border rounded">
                <select id="employeeIndustryFilter" onchange="filterByIndustry('employee')"
                        class="px-3 py-2 border rounded">
                    <option value="">All Industries</option>
                    <option value="Technology">Technology</option>
                    <option value="Healthcare">Healthcare</option>
                    <option value="Finance">Finance</option>
                    <option value="Education">Education</option>
                    <option value="Other">Other</option>
                </select>
            </div>
        </div>
        <table class="min-w-full">
            <thead>
                <tr class="bg-gray-100">
                    <th class="text-left py-3 px-4">Name</th>
                    <th class="text-left py-3 px-4">Industry</th>
                    <th class="text-left py-3 px-4">Email</th>
                    <th class="text-left py-3 px-4">Phone</th>
                    <th class="text-left py-3 px-4">Status</th>
                    <th class="text-left py-3 px-4">Tasks</th>
                    <th class="text-left py-3 px-4">Earnings</th>
                    <th class="text-left py-3 px-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employees as $employee): ?>
                <tr class="employee-row border-b hover:bg-gray-50">
                    <td class="py-3 px-4"><?php echo htmlspecialchars($employee['name']); ?></td>
                    <td class="py-3 px-4"><?php echo htmlspecialchars($employee['industry']); ?></td>
                    <td class="py-3 px-4"><?php echo htmlspecialchars($employee['email']); ?></td>
                    <td class="py-3 px-4"><?php echo htmlspecialchars($employee['phone']); ?></td>
                    <td class="py-3 px-4">
                        <span class="px-2 py-1 rounded text-sm <?php echo $employee['status'] === 'Active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo htmlspecialchars($employee['status']); ?>
                        </span>
                    </td>
                    <td class="py-3 px-4"><?php echo $employee['task_count']; ?></td>
                    <td class="py-3 px-4">$<?php echo number_format($employee['total_earnings'], 2); ?></td>
                    <td class="py-3 px-4">
                        <button onclick="openModal('editEmployeeModal<?php echo $employee['id']; ?>')" 
                                class="text-blue-600 hover:text-blue-800 mr-2">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="openModal('deleteEmployeeModal<?php echo $employee['id']; ?>')" 
                                class="text-red-600 hover:text-red-800">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Clients Table -->
    <div class="bg-white shadow-md rounded my-6">
        <div class="p-4 border-b bg-gray-50">
            <h2 class="text-xl font-semibold">Clients</h2>
            <div class="mt-2 flex gap-4">
                <input type="text" id="clientSearchInput" onkeyup="searchAccounts('client')" 
                       placeholder="Search clients..." 
                       class="px-3 py-2 border rounded">
                <select id="clientIndustryFilter" onchange="filterByIndustry('client')"
                        class="px-3 py-2 border rounded">
                    <option value="">All Industries</option>
                    <option value="Technology">Technology</option>
                    <option value="Healthcare">Healthcare</option>
                    <option value="Finance">Finance</option>
                    <option value="Education">Education</option>
                    <option value="Other">Other</option>
                </select>
            </div>
        </div>
        <table class="min-w-full">
            <thead>
                <tr class="bg-gray-100">
                    <th class="text-left py-3 px-4">Name</th>
                    <th class="text-left py-3 px-4">Industry</th>
                    <th class="text-left py-3 px-4">Contact Name</th>
                    <th class="text-left py-3 px-4">Contact Email</th>
                    <th class="text-left py-3 px-4">Contact Phone</th>
                    <th class="text-left py-3 px-4">Tasks</th>
                    <th class="text-left py-3 px-4">Completed</th>
                    <th class="text-left py-3 px-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clients as $client): ?>
                <tr class="client-row border-b hover:bg-gray-50">
                    <td class="py-3 px-4"><?php echo htmlspecialchars($client['name']); ?></td>
                    <td class="py-3 px-4"><?php echo htmlspecialchars($client['industry']); ?></td>
                    <td class="py-3 px-4"><?php echo htmlspecialchars($client['contact_name']); ?></td>
                    <td class="py-3 px-4"><?php echo htmlspecialchars($client['contact_email']); ?></td>
                    <td class="py-3 px-4"><?php echo htmlspecialchars($client['contact_phone']); ?></td>
                    <td class="py-3 px-4"><?php echo $client['task_count']; ?></td>
                    <td class="py-3 px-4"><?php echo $client['completed_tasks']; ?></td>
                    <td class="py-3 px-4">
                        <button onclick="openModal('editClientModal<?php echo $client['id']; ?>')" 
                                class="text-blue-600 hover:text-blue-800 mr-2">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="openModal('deleteClientModal<?php echo $client['id']; ?>')" 
                                class="text-red-600 hover:text-red-800">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Employee Modal -->
    <div id="addEmployeeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden">
        <div class="flex items-center justify-center min-h-screen">
            <div class="bg-white rounded-lg w-1/2">
                <div class="flex justify-between items-center p-6 border-b">
                    <h3 class="text-xl font-semibold">Add New Employee</h3>
                    <button onclick="closeModal('addEmployeeModal')" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" class="p-6">
                    <input type="hidden" name="add_employee" value="1">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Name</label>
                        <input type="text" name="name" required 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                        <select name="email" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            <?php
                            $stmt = $pdo->query("SELECT email FROM employees WHERE status = 'Active'");
                            while ($row = $stmt->fetch()) {
                                echo '<option value="' . htmlspecialchars($row['email']) . '">' . 
                                     htmlspecialchars($row['email']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Phone</label>
                        <input type="tel" name="phone" required 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Industry</label>
                        <select name="industry" required 
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            <option value="Technology">Technology</option>
                            <option value="Healthcare">Healthcare</option>
                            <option value="Finance">Finance</option>
                            <option value="Education">Education</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Status</label>
                        <select name="status" required 
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Add Employee
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Client Modal -->
    <div id="addClientModal" class="fixed inset-0 bg-black bg-opacity-50 hidden">
        <div class="flex items-center justify-center min-h-screen">
            <div class="bg-white rounded-lg w-1/2">
                <div class="flex justify-between items-center p-6 border-b">
                    <h3 class="text-xl font-semibold">Add New Client</h3>
                    <button onclick="closeModal('addClientModal')" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" class="p-6">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Company Name</label>
                        <input type="text" name="name" required 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Industry</label>
                        <select name="industry" required 
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            <option value="Technology">Technology</option>
                            <option value="Healthcare">Healthcare</option>
                            <option value="Finance">Finance</option>
                            <option value="Education">Education</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Contact Name</label>
                        <input type="text" name="contact_name" required 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Contact Email</label>
                        <input type="email" name="contact_email" required 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Contact Phone</label>
                        <input type="tel" name="contact_phone" required 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                        <input type="password" name="password" required 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                            Add Client
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit/Delete Modals for Employees -->
    <?php foreach ($employees as $employee): ?>
        <!-- Edit Employee Modal -->
        <div id="editEmployeeModal<?php echo $employee['id']; ?>" class="fixed inset-0 bg-black bg-opacity-50 hidden">
            <div class="flex items-center justify-center min-h-screen">
                <div class="bg-white rounded-lg w-1/2">
                    <div class="flex justify-between items-center p-6 border-b">
                        <h3 class="text-xl font-semibold">Edit Employee</h3>
                        <button onclick="closeModal('editEmployeeModal<?php echo $employee['id']; ?>')" 
                                class="text-gray-600 hover:text-gray-800">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <form method="POST" class="p-6">
                        <input type="hidden" name="edit_employee" value="1">
                        <input type="hidden" name="employee_id" value="<?php echo $employee['id']; ?>">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($employee['name']); ?>" required 
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($employee['email']); ?>" required 
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Phone</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($employee['phone']); ?>" required 
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Industry</label>
                            <select name="industry" required 
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                                <option value="Technology" <?php echo $employee['industry'] === 'Technology' ? 'selected' : ''; ?>>Technology</option>
                                <option value="Healthcare" <?php echo $employee['industry'] === 'Healthcare' ? 'selected' : ''; ?>>Healthcare</option>
                                <option value="Finance" <?php echo $employee['industry'] === 'Finance' ? 'selected' : ''; ?>>Finance</option>
                                <option value="Education" <?php echo $employee['industry'] === 'Education' ? 'selected' : ''; ?>>Education</option>
                                <option value="Other" <?php echo $employee['industry'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Status</label>
                            <select name="status" required 
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                                <option value="Active" <?php echo $employee['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo $employee['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Update Employee
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Employee Modal -->
        <div id="deleteEmployeeModal<?php echo $employee['id']; ?>" class="fixed inset-0 bg-black bg-opacity-50 hidden">
            <div class="flex items-center justify-center min-h-screen">
                <div class="bg-white rounded-lg w-1/3">
                    <div class="flex justify-between items-center p-6 border-b">
                        <h3 class="text-xl font-semibold">Delete Employee</h3>
                        <button onclick="closeModal('deleteEmployeeModal<?php echo $employee['id']; ?>')" 
                                class="text-gray-600 hover:text-gray-800">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="p-6">
                        <p class="mb-4">Are you sure you want to delete this employee? This action cannot be undone.</p>
                        <form method="POST" class="flex justify-end">
                            <input type="hidden" name="delete_employee" value="1">
                            <input type="hidden" name="employee_id" value="<?php echo $employee['id']; ?>">
                            <button type="button" onclick="closeModal('deleteEmployeeModal<?php echo $employee['id']; ?>')" 
                                    class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded mr-2">
                                Cancel
                            </button>
                            <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Edit/Delete Modals for Clients -->
    <?php foreach ($clients as $client): ?>
        <!-- Edit Client Modal -->
        <div id="editClientModal<?php echo $client['id']; ?>" class="fixed inset-0 bg-black bg-opacity-50 hidden">
            <div class="flex items-center justify-center min-h-screen">
                <div class="bg-white rounded-lg w-1/2">
                    <div class="flex justify-between items-center p-6 border-b">
                        <h3 class="text-xl font-semibold">Edit Client</h3>
                        <button onclick="closeModal('editClientModal<?php echo $client['id']; ?>')" 
                                class="text-gray-600 hover:text-gray-800">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <form method="POST" class="p-6">
                        <input type="hidden" name="edit_client" value="1">
                        <input type="hidden" name="client_id" value="<?php echo $client['id']; ?>">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Company Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($client['name']); ?>" required 
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Industry</label>
                            <select name="industry" required 
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                                <option value="Technology" <?php echo $client['industry'] === 'Technology' ? 'selected' : ''; ?>>Technology</option>
                                <option value="Healthcare" <?php echo $client['industry'] === 'Healthcare' ? 'selected' : ''; ?>>Healthcare</option>
                                <option value="Finance" <?php echo $client['industry'] === 'Finance' ? 'selected' : ''; ?>>Finance</option>
                                <option value="Education" <?php echo $client['industry'] === 'Education' ? 'selected' : ''; ?>>Education</option>
                                <option value="Other" <?php echo $client['industry'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Contact Name</label>
                            <input type="text" name="contact_name" value="<?php echo htmlspecialchars($client['contact_name']); ?>" required 
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Contact Email</label>
                            <input type="email" name="contact_email" value="<?php echo htmlspecialchars($client['contact_email']); ?>" required 
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Contact Phone</label>
                            <input type="tel" name="contact_phone" value="<?php echo htmlspecialchars($client['contact_phone']); ?>" required 
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Update Client
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Client Modal -->
        <div id="deleteClientModal<?php echo $client['id']; ?>" class="fixed inset-0 bg-black bg-opacity-50 hidden">
            <div class="flex items-center justify-center min-h-screen">
                <div class="bg-white rounded-lg w-1/3">
                    <div class="flex justify-between items-center p-6 border-b">
                        <h3 class="text-xl font-semibold">Delete Client</h3>
                        <button onclick="closeModal('deleteClientModal<?php echo $client['id']; ?>')" 
                                class="text-gray-600 hover:text-gray-800">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="p-6">
                        <p class="mb-4">Are you sure you want to delete this client? This action cannot be undone.</p>
                        <form method="POST" class="flex justify-end">
                            <input type="hidden" name="delete_client" value="1">
                            <input type="hidden" name="client_id" value="<?php echo $client['id']; ?>">
                            <button type="button" onclick="closeModal('deleteClientModal<?php echo $client['id']; ?>')" 
                                    class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded mr-2">
                                Cancel
                            </button>
                            <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
function searchAccounts(type) {
    let input = document.getElementById(type + 'SearchInput');
    let filter = input.value.toUpperCase();
    let rows = document.getElementsByClassName(type + '-row');

    for (let row of rows) {
        let nameCell = row.getElementsByTagName('td')[0];
        let name = nameCell.textContent || nameCell.innerText;
        if (name.toUpperCase().indexOf(filter) > -1) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    }
}

function filterByIndustry(type) {
    let select = document.getElementById(type + 'IndustryFilter');
    let filter = select.value.toUpperCase();
    let rows = document.getElementsByClassName(type + '-row');

    for (let row of rows) {
        let industryCell = row.getElementsByTagName('td')[1];
        let industry = industryCell.textContent || industryCell.innerText;
        if (filter === '' || industry.toUpperCase() === filter) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    }
}

function openModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('fixed')) {
        closeModal(event.target.id);
    }
}
</script>
