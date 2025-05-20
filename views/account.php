<?php
require_once 'config/database.php';

// Get all accounts with opportunity counts
try {
    $stmt = $pdo->query("
        SELECT a.*, COUNT(o.id) as opportunity_count, 
        SUM(CASE WHEN o.stage = 'Closed Won' THEN o.amount ELSE 0 END) as total_won
        FROM accounts a
        LEFT JOIN opportunities o ON a.id = o.account_id
        GROUP BY a.id
        ORDER BY a.created_at DESC
    ");
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log($e->getMessage());
    $accounts = [];
}

// Handle form submission for adding new account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_account'])) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO accounts (name, industry, email, phone, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $_POST['name'],
            $_POST['industry'],
            $_POST['email'], 
            $_POST['phone']
        ]);

        // Redirect to refresh the page
        header("Location: " . $_SERVER['PHP_SELF'] . "?modules=account");
        exit();
        
    } catch(PDOException $e) {
        error_log($e->getMessage());
    }
}

// Handle delete request - check for related opportunities first
if(isset($_POST['delete_account'])) {
    $id = $_POST['account_id'];
    try {
        // Check for related opportunities
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM opportunities WHERE account_id = ?");
        $stmt->execute([$id]);
        $hasOpportunities = $stmt->fetchColumn() > 0;

        if($hasOpportunities) {
            // Set error message
            $_SESSION['error'] = "Cannot delete account - it has associated opportunities";
        } else {
            $stmt = $pdo->prepare("DELETE FROM accounts WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    } catch(PDOException $e) {
        error_log($e->getMessage());
    }
}
?>

<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">
            <i class="fas fa-building mr-2"></i>Accounts
        </h1>

        <button onclick="openModal('accountModal')" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-plus mr-2"></i>Add New Account
        </button>

        <!-- Account Modal -->
        <div id="accountModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Add New Account</h3>
                    <form class="mt-4" method="POST" action="">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="name">Account Name</label>
                            <input type="text" id="name" name="name" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="industry">Industry</label>
                            <select id="industry" name="industry" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="">Select Industry</option>
                                <option value="Technology">Technology</option>
                                <option value="Healthcare">Healthcare</option>
                                <option value="Finance">Finance</option>
                                <option value="Manufacturing">Manufacturing</option>
                                <option value="Retail">Retail</option>
                                <option value="Education">Education</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="email">Email</label>
                            <input type="email" id="email" name="email" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="phone">Phone</label>
                            <input type="tel" id="phone" name="phone" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="flex items-center justify-between mt-4">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Save</button>
                            <button type="button" onclick="closeModal('accountModal')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <!-- Accounts Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Industry</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Opportunities</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Won</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($accounts as $account): ?>
                <tr>
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
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500">
                            <a href="?modules=opportunity&account=<?php echo $account['id']; ?>" class="text-blue-600 hover:text-blue-900">
                                <?php echo $account['opportunity_count']; ?> opportunities
                            </a>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500">
                            $<?php echo number_format($account['total_won'], 2); ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button onclick="openModal('editModal<?php echo $account['id']; ?>')" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                        <div id="editModal<?php echo $account['id']; ?>" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
                            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                                <div class="mt-3">
                                    <h2 class="text-2xl font-bold mb-6">Edit Account</h2>
                                    <form action="phpfunction/edit.php" method="POST">
                                        <input type="hidden" name="id" value="<?php echo $account['id']; ?>">
                                        <div class="mb-4">
                                            <label class="block text-gray-700 text-sm font-bold mb-2" for="name<?php echo $account['id']; ?>">Account Name</label>
                                            <input type="text" id="name<?php echo $account['id']; ?>" name="name" value="<?php echo htmlspecialchars($account['name']); ?>" required 
                                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                        </div>
                                        <div class="mb-4">
                                            <label class="block text-gray-700 text-sm font-bold mb-2" for="industry<?php echo $account['id']; ?>">Industry</label>
                                            <select id="industry<?php echo $account['id']; ?>" name="industry" required 
                                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                                <option value="">Select Industry</option>
                                                <?php
                                                $industries = ['Technology', 'Healthcare', 'Finance', 'Manufacturing', 'Retail', 'Education', 'Other'];
                                                foreach ($industries as $industry) {
                                                    $selected = ($industry === $account['industry']) ? 'selected' : '';
                                                    echo "<option value=\"$industry\" $selected>$industry</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="mb-4">
                                            <label class="block text-gray-700 text-sm font-bold mb-2" for="email<?php echo $account['id']; ?>">Email</label>
                                            <input type="email" id="email<?php echo $account['id']; ?>" name="email" value="<?php echo htmlspecialchars($account['email']); ?>" required
                                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                        </div>
                                        <div class="mb-4">
                                            <label class="block text-gray-700 text-sm font-bold mb-2" for="phone<?php echo $account['id']; ?>">Phone</label>
                                            <input type="tel" id="phone<?php echo $account['id']; ?>" name="phone" value="<?php echo htmlspecialchars($account['phone']); ?>" required
                                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                        </div>
                                        <div class="flex items-center justify-between mt-6">
                                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                                Update Account
                                            </button>
                                            <button type="button" onclick="closeModal('editModal<?php echo $account['id']; ?>')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                                Cancel
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="account_id" value="<?php echo $account['id']; ?>">
                            <button type="submit" name="delete_account" onclick="return confirm('Are you sure you want to delete this account? Any associated opportunities will also be deleted.')" class="text-red-600 hover:text-red-900">Delete</button>
                        </form>

                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div> 


<script src="./js/script.js"></script>