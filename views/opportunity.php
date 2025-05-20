<?php
require_once 'config/database.php';

// Get all opportunities with related data
try {
    $stmt = $pdo->query("
        SELECT id, name, account_id, amount, stage, expected_close_date, created_by, created_at, updated_at 
        FROM opportunities 
        WHERE 1
    ");
    $opportunities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log($e->getMessage());
    $opportunities = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_opportunity') {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO opportunities (
                    name, account_id, amount, stage, 
                    expected_close_date, created_by, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $_POST['name'],
                $_POST['account_id'], 
                $_POST['amount'],
                $_POST['stage'],
                $_POST['close_date'],
                $_SESSION['user_id'] // Assuming user is logged in
            ]);

            header("Location: " . $_SERVER['PHP_SELF'] . "?modules=opportunity");
            exit();
            
        } catch(PDOException $e) {
            error_log($e->getMessage());
        }
    } elseif ($_POST['action'] === 'delete_opportunity' && isset($_POST['opportunity_id'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM opportunities WHERE id = ?");
            $stmt->execute([$_POST['opportunity_id']]);
            
            header("Location: " . $_SERVER['PHP_SELF'] . "?modules=opportunity");
            exit();
        } catch(PDOException $e) {
            error_log($e->getMessage());
        }
    }
}

$stages = ['Prospecting', 'Qualification', 'Needs Analysis', 'Value Proposition', 'Negotiation', 'Closed Won', 'Closed Lost'];
?>

<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">
            <i class="fas fa-funnel-dollar mr-2"></i>Sales Pipeline
        </h1>
        <button onclick="openModal('opportunityModal')" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-plus mr-2"></i>New Opportunity
        </button>

        <!-- Opportunity Modal -->
        <div id="opportunityModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Create New Opportunity</h3>
                    <form id="opportunityForm" class="mt-4" method="POST">
                        <input type="hidden" name="action" value="add_opportunity">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="name">Opportunity Name</label>
                            <input type="text" id="name" name="name" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="account">Account</label>
                            <select id="account" name="account_id" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="">Select an account</option>
                                <?php
                                try {
                                    $stmt = $pdo->query("SELECT id, name FROM accounts ORDER BY name ASC");
                                    while ($account = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        echo "<option value='" . htmlspecialchars($account['id']) . "'>" . htmlspecialchars($account['name']) . "</option>";
                                    }
                                } catch(PDOException $e) {
                                    error_log($e->getMessage());
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="amount">Amount ($)</label>
                            <input type="number" step="0.01" id="amount" name="amount" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="stage">Sales Stage</label>
                            <select id="stage" name="stage" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="">Select Stage</option>
                                <?php foreach($stages as $stage): ?>
                                    <option value="<?php echo htmlspecialchars($stage); ?>"><?php echo htmlspecialchars($stage); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="close_date">Expected Close Date</label>
                            <input type="date" id="close_date" name="close_date" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="flex items-center justify-between mt-6">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Save</button>
                            <button type="button" onclick="closeModal('opportunityModal')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Pipeline View -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Opportunity Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stage</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Close Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created By</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($opportunities as $opportunity): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($opportunity['name']); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($opportunity['account_id']); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500">$<?php echo number_format($opportunity['amount'], 2); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php 
                        $stageColors = [
                            'Prospecting' => 'gray',
                            'Qualification' => 'blue',
                            'Needs Analysis' => 'yellow',
                            'Value Proposition' => 'indigo',
                            'Negotiation' => 'purple',
                            'Closed Won' => 'green',
                            'Closed Lost' => 'red'
                        ];
                        $color = $stageColors[$opportunity['stage']] ?? 'gray';
                        ?>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?php echo $color; ?>-100 text-<?php echo $color; ?>-800">
                            <?php echo htmlspecialchars($opportunity['stage']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500"><?php echo date('M j, Y', strtotime($opportunity['expected_close_date'])); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($opportunity['created_by']); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button onclick="openModal('editModal<?php echo $opportunity['id']; ?>')" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                        <div id="editModal<?php echo $opportunity['id']; ?>" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
                            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                                <div class="mt-3">
                                    <h3 class="text-lg font-medium leading-6 text-gray-900">Edit Opportunity</h3>
                                    <form id="editOpportunityForm<?php echo $opportunity['id']; ?>" class="mt-4" method="POST">
                                        <input type="hidden" name="action" value="edit_opportunity">
                                        <input type="hidden" name="opportunity_id" value="<?php echo $opportunity['id']; ?>">
                                        <div class="mb-4">
                                            <label class="block text-gray-700 text-sm font-bold mb-2" for="name<?php echo $opportunity['id']; ?>">Opportunity Name</label>
                                            <input type="text" id="name<?php echo $opportunity['id']; ?>" name="name" value="<?php echo htmlspecialchars($opportunity['name']); ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                        </div>
                                        <div class="mb-4">
                                            <label class="block text-gray-700 text-sm font-bold mb-2" for="account<?php echo $opportunity['id']; ?>">Account</label>
                                            <select id="account<?php echo $opportunity['id']; ?>" name="account_id" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                                <option value="">Select an account</option>
                                                <?php
                                                try {
                                                    $stmt = $pdo->query("SELECT id, name FROM accounts ORDER BY name ASC");
                                                    while ($account = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                        $selected = ($account['id'] == $opportunity['account_id']) ? 'selected' : '';
                                                        echo "<option value='" . htmlspecialchars($account['id']) . "' $selected>" . htmlspecialchars($account['name']) . "</option>";
                                                    }
                                                } catch(PDOException $e) {
                                                    error_log($e->getMessage());
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="mb-4">
                                            <label class="block text-gray-700 text-sm font-bold mb-2" for="amount<?php echo $opportunity['id']; ?>">Amount ($)</label>
                                            <input type="number" step="0.01" id="amount<?php echo $opportunity['id']; ?>" name="amount" value="<?php echo htmlspecialchars($opportunity['amount']); ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                        </div>
                                        <div class="mb-4">
                                            <label class="block text-gray-700 text-sm font-bold mb-2" for="stage<?php echo $opportunity['id']; ?>">Sales Stage</label>
                                            <select id="stage<?php echo $opportunity['id']; ?>" name="stage" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                                <option value="">Select Stage</option>
                                                <?php foreach($stages as $stage): ?>
                                                    <?php $selected = ($stage == $opportunity['stage']) ? 'selected' : ''; ?>
                                                    <option value="<?php echo htmlspecialchars($stage); ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($stage); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-4">
                                            <label class="block text-gray-700 text-sm font-bold mb-2" for="close_date<?php echo $opportunity['id']; ?>">Expected Close Date</label>
                                            <input type="date" id="close_date<?php echo $opportunity['id']; ?>" name="close_date" value="<?php echo htmlspecialchars($opportunity['expected_close_date']); ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                        </div>
                                        <div class="flex items-center justify-between mt-6">
                                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Save</button>
                                            <button type="button" onclick="closeModal('editModal<?php echo $opportunity['id']; ?>')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete_opportunity">
                            <input type="hidden" name="opportunity_id" value="<?php echo $opportunity['id']; ?>">
                            <button type="submit" onclick="return confirm('Are you sure you want to delete this opportunity?')" class="text-red-600 hover:text-red-900">Delete</button>
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
    if(modalId === 'opportunityModal') {
        document.getElementById('opportunityForm').reset();
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('fixed')) {
        closeModal(event.target.id);
    }
}
</script>