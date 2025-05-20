<?php
require_once 'config/database.php';

// Get all accounts with opportunity counts
try {
    $stmt = $pdo->query("
        SELECT a.*, COUNT(o.id) as opportunity_count,
        SUM(CASE WHEN o.stage = 'Closed Won' THEN o.amount ELSE 0 END) as total_won,
        c.name as client_name,
        e.name as employee_name
        FROM accounts a
        LEFT JOIN opportunities o ON a.id = o.account_id
        LEFT JOIN clients c ON a.client_id = c.id 
        LEFT JOIN employees e ON a.employee_id = e.id
        GROUP BY a.id
        ORDER BY a.created_at DESC
    ");
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log($e->getMessage());
    $accounts = [];
}

// Handle form submission for adding new account
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_account'])) {
        // Handle delete request
        $id = $_POST['account_id'];
        try {
            // Check for related opportunities
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM opportunities WHERE account_id = ?");
            $stmt->execute([$id]);
            $hasOpportunities = $stmt->fetchColumn() > 0;

            if($hasOpportunities) {
                $_SESSION['error'] = "Cannot delete account - it has associated opportunities";
            } else {
                $stmt = $pdo->prepare("DELETE FROM accounts WHERE id = ?");
                $stmt->execute([$id]);
                header("Location: index.php?module=account");
                exit();
            }
        } catch(PDOException $e) {
            error_log($e->getMessage());
            $_SESSION['error'] = "Error deleting account";
        }
    } elseif (isset($_POST['edit_account'])) {
        // Handle edit request
        try {
            $stmt = $pdo->prepare("
                UPDATE accounts 
                SET name = ?, industry = ?, email = ?, phone = ?, 
                    client_id = ?, employee_id = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $_POST['name'],
                $_POST['industry'],
                $_POST['email'],
                $_POST['phone'],
                $_POST['client_id'],
                $_POST['employee_id'],
                $_POST['account_id']
            ]);

            header("Location: index.php?module=account");
            exit();
        } catch(PDOException $e) {
            error_log($e->getMessage());
            $_SESSION['error'] = "Error updating account";
        }
    } else {
        // Handle add new account
        try {
            $stmt = $pdo->prepare("
                INSERT INTO accounts (name, industry, email, phone, client_id, employee_id, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $_POST['name'],
                $_POST['industry'] ?? null,
                $_POST['email'],
                $_POST['phone'],
                $_POST['client_id'] ?? null,
                $_POST['employee_id'] ?? null
            ]);

            header("Location: index.php?module=account");
            exit();
        } catch(PDOException $e) {
            error_log($e->getMessage());
            $_SESSION['error'] = "Error creating account";
        }
    }
}

// Get clients and employees for dropdowns
try {
    $clients = $pdo->query("SELECT id, name FROM clients ORDER BY name")->fetchAll();
    $employees = $pdo->query("SELECT id, name FROM employees ORDER BY name")->fetchAll();
} catch(PDOException $e) {
    error_log($e->getMessage());
    $clients = [];
    $employees = [];
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

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">
            <i class="fas fa-building mr-2"></i>Accounts
        </h1>

        <div class="flex space-x-4">
            <button onclick="openModal('employeeAccountModal')" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-user-plus mr-2"></i>Add New Employee Account
            </button>
            <button onclick="openModal('clientAccountModal')" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-building mr-2"></i>Add New Client Account
            </button>
        </div>

        <!-- Employee Account Modal -->
        <div id="employeeAccountModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Add New Employee Account</h3>
                    <form class="mt-4" method="POST" action="index.php?module=account">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="emp_name">Name</label>
                            <input type="text" id="emp_name" name="name" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="emp_email">Email</label>
                            <input type="email" id="emp_email" name="email" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="emp_phone">Phone</label>
                            <input type="tel" id="emp_phone" name="phone" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="employee_id">Employee</label>
                            <select id="employee_id" name="employee_id" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="">Select Employee</option>
                                <?php foreach($employees as $employee): ?>
                                    <option value="<?php echo $employee['id']; ?>"><?php echo htmlspecialchars($employee['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex items-center justify-between mt-4">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Save</button>
                            <button type="button" onclick="closeModal('employeeAccountModal')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Client Account Modal -->
        <div id="clientAccountModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Add New Client Account</h3>
                    <form class="mt-4" method="POST" action="index.php?module=account">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="client_name">Name</label>
                            <input type="text" id="client_name" name="name" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="client_industry">Industry</label>
                            <select id="client_industry" name="industry" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="">Select Industry</option>
                                <option value="Technology">Technology</option>
                                <option value="Healthcare">Healthcare</option>
                                <option value="Finance">Finance</option>
                                <option value="Manufacturing">Manufacturing</option>
                                <option value="Retail">Retail</option>
                                <option value="Education">Education</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="client_email">Email</label>
                            <input type="email" id="client_email" name="email" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="client_phone">Phone</label>
                            <input type="tel" id="client_phone" name="phone" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="client_id">Client</label>
                            <select id="client_id" name="client_id" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="">Select Client</option>
                                <?php foreach($clients as $client): ?>
                                    <option value="<?php echo $client['id']; ?>"><?php echo htmlspecialchars($client['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex items-center justify-between mt-4">
                            <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Save</button>
                            <button type="button" onclick="closeModal('clientAccountModal')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="flex flex-col md:flex-row gap-8">
            <div class="md:w-1/2 p-4">
                <h2 class="text-lg font-bold mb-4">Search Accounts</h2>
                <input type="text" id="searchInput" onkeyup="searchAccounts()" placeholder="Search by name" class="w-full p-2 border rounded">
            </div>
            <div class="md:w-1/2 p-4">
                <h2 class="text-lg font-bold mb-4">Filter by Industry</h2>
                <select id="industryFilter" onchange="filterAccounts()" class="w-full p-2 border rounded">
                    <option value="">All Industries</option>
                    <option value="Technology">Technology</option>
                    <option value="Healthcare">Healthcare</option>
                    <option value="Finance">Finance</option>
                    <option value="Manufacturing">Manufacturing</option>
                    <option value="Retail">Retail</option>
                    <option value="Education">Education</option>
                </select>
            </div>
        </div>
    
        <!-- Accounts Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="flex flex-col md:flex-row gap-8">
                <div class="md:w-1/2 p-4">
                    <h2 class="text-lg font-bold mb-4">Employee Accounts</h2>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Industry</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($accounts as $account): ?>
                                <?php if (!empty($account['employee_name'])): ?>
                                <tr class="account-row">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($account['name']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($account['industry']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($account['email']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($account['phone']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="openModal('editModal<?php echo $account['id']; ?>')" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="account_id" value="<?php echo $account['id']; ?>">
                                            <button type="submit" name="delete_account" onclick="return confirm('Are you sure you want to delete this account?')" class="text-red-600 hover:text-red-900">Delete</button>
                                        </form>

                                        <!-- Edit Modal -->
                                        <div id="editModal<?php echo $account['id']; ?>" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
                                            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                                                <div class="mt-3">
                                                    <h3 class="text-lg font-medium leading-6 text-gray-900">Edit Account</h3>
                                                    <form class="mt-4" method="POST" action="index.php?module=account">
                                                        <input type="hidden" name="account_id" value="<?php echo $account['id']; ?>">
                                                        <input type="hidden" name="edit_account" value="1">
                                                        <div class="mb-4">
                                                            <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_name<?php echo $account['id']; ?>">Name</label>
                                                            <input type="text" id="edit_name<?php echo $account['id']; ?>" name="name" value="<?php echo htmlspecialchars($account['name']); ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                                        </div>
                                                        <div class="mb-4">
                                                            <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_email<?php echo $account['id']; ?>">Email</label>
                                                            <input type="email" id="edit_email<?php echo $account['id']; ?>" name="email" value="<?php echo htmlspecialchars($account['email']); ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                                        </div>
                                                        <div class="mb-4">
                                                            <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_phone<?php echo $account['id']; ?>">Phone</label>
                                                            <input type="tel" id="edit_phone<?php echo $account['id']; ?>" name="phone" value="<?php echo htmlspecialchars($account['phone']); ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                                        </div>
                                                        <div class="mb-4">
                                                            <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_employee<?php echo $account['id']; ?>">Employee</label>
                                                            <select id="edit_employee<?php echo $account['id']; ?>" name="employee_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                                                <option value="">Select Employee</option>
                                                                <?php foreach($employees as $employee): ?>
                                                                    <option value="<?php echo $employee['id']; ?>" <?php echo ($account['employee_id'] == $employee['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($employee['name']); ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="flex items-center justify-between mt-4">
                                                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Save</button>
                                                            <button type="button" onclick="closeModal('editModal<?php echo $account['id']; ?>')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Cancel</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="md:w-1/2 p-4">
                    <h2 class="text-lg font-bold mb-4">Client Accounts</h2>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Industry</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($accounts as $account): ?>
                                <?php if (!empty($account['client_name'])): ?>
                                <tr class="account-row">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($account['name']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($account['industry']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($account['email']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($account['phone']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="openModal('editModal<?php echo $account['id']; ?>')" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="account_id" value="<?php echo $account['id']; ?>">
                                            <button type="submit" name="delete_account" onclick="return confirm('Are you sure you want to delete this account?')" class="text-red-600 hover:text-red-900">Delete</button>
                                        </form>

                                        <!-- Edit Modal -->
                                        <div id="editModal<?php echo $account['id']; ?>" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
                                            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                                                <div class="mt-3">
                                                    <h3 class="text-lg font-medium leading-6 text-gray-900">Edit Account</h3>
                                                    <form class="mt-4" method="POST" action="index.php?module=account">
                                                        <input type="hidden" name="account_id" value="<?php echo $account['id']; ?>">
                                                        <input type="hidden" name="edit_account" value="1">
                                                        <div class="mb-4">
                                                            <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_name<?php echo $account['id']; ?>">Name</label>
                                                            <input type="text" id="edit_name<?php echo $account['id']; ?>" name="name" value="<?php echo htmlspecialchars($account['name']); ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                                        </div>
                                                        <div class="mb-4">
                                                            <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_industry<?php echo $account['id']; ?>">Industry</label>
                                                            <select id="edit_industry<?php echo $account['id']; ?>" name="industry" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                                                <option value="">Select Industry</option>
                                                                <?php
                                                                $industries = ['Technology', 'Healthcare', 'Finance', 'Manufacturing', 'Retail', 'Education'];
                                                                foreach($industries as $industry): ?>
                                                                    <option value="<?php echo $industry; ?>" <?php echo ($account['industry'] == $industry) ? 'selected' : ''; ?>><?php echo $industry; ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="mb-4">
                                                            <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_email<?php echo $account['id']; ?>">Email</label>
                                                            <input type="email" id="edit_email<?php echo $account['id']; ?>" name="email" value="<?php echo htmlspecialchars($account['email']); ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                                        </div>
                                                        <div class="mb-4">
                                                            <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_phone<?php echo $account['id']; ?>">Phone</label>
                                                            <input type="tel" id="edit_phone<?php echo $account['id']; ?>" name="phone" value="<?php echo htmlspecialchars($account['phone']); ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                                        </div>
                                                        <div class="mb-4">
                                                            <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_client<?php echo $account['id']; ?>">Client</label>
                                                            <select id="edit_client<?php echo $account['id']; ?>" name="client_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                                                <option value="">Select Client</option>
                                                                <?php foreach($clients as $client): ?>
                                                                    <option value="<?php echo $client['id']; ?>" <?php echo ($account['client_id'] == $client['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($client['name']); ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="flex items-center justify-between mt-4">
                                                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Save</button>
                                                            <button type="button" onclick="closeModal('editModal<?php echo $account['id']; ?>')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Cancel</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function searchAccounts() {
    let input = document.getElementById('searchInput');
    let filter = input.value.toUpperCase();
    let rows = document.getElementsByClassName('account-row');

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

function filterAccounts() {
    let select = document.getElementById('industryFilter');
    let filter = select.value.toUpperCase();
    let rows = document.getElementsByClassName('account-row');

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