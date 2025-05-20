<?php
require_once 'config/database.php';

// Get all contacts
try {
    $stmt = $pdo->query("
        SELECT c.*, a.name as account_name 
        FROM contacts c 
        LEFT JOIN accounts a ON c.account_id = a.id 
        ORDER BY c.created_at DESC
    ");
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log($e->getMessage());
    $contacts = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_contact') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO contacts (first_name, last_name, email, phone, account_id) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_POST['firstName'],
            $_POST['lastName'], 
            $_POST['email'],
            $_POST['phone'],
            $_POST['account']
        ]);

        // Redirect to refresh the page
        header("Location: " . $_SERVER['PHP_SELF'] . "?modules=contact");
        exit();
        
    } catch(PDOException $e) {
        error_log($e->getMessage());
    }
}
?>

<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">
            <i class="fas fa-users mr-2"></i>Contacts
        </h1>
        <button onclick="openModal('contactModal')" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-plus mr-2"></i>Add New Contact
        </button>

        <!-- Contact Modal -->
        <div id="contactModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Add New Contact</h3>
                    <form id="contactForm" class="mt-4" method="POST" action="">
                        <input type="hidden" name="action" value="add_contact">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="firstName">First Name</label>
                            <input type="text" id="firstName" name="firstName" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="lastName">Last Name</label>
                            <input type="text" id="lastName" name="lastName" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="email">Email</label>
                            <input type="email" id="email" name="email" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="phone">Phone</label>
                            <input type="tel" id="phone" name="phone" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="account">Account</label>
                            <div class="relative">
                                <select id="account" name="account" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    <option value="">Select an account</option>
                                    <?php
                                    try {
                                        $stmt = $pdo->query("SELECT * FROM accounts ORDER BY name ASC");
                                        $hasAccounts = false;
                                        while ($account = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            $hasAccounts = true;
                                            echo "<option value='" . htmlspecialchars($account['id']) . "'>" . htmlspecialchars($account['name']) . "</option>";
                                        }
                                        if (!$hasAccounts) {
                                            echo "<option value='' disabled>No accounts found</option>";
                                        }
                                    } catch(PDOException $e) {
                                        error_log($e->getMessage());
                                        echo "<option value='' disabled>Error loading accounts</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <?php if (!isset($hasAccounts) || !$hasAccounts): ?>
                            <p class="mt-2 text-sm text-gray-500">
                                No accounts found. <a href="?modules=account" class="text-blue-500 hover:text-blue-700">Create an account</a>
                            </p>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center justify-between mt-6">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Save</button>
                            <button type="button" onclick="closeModal('contactModal')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <!-- Contacts Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($contacts as $contact): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']); ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($contact['email']); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($contact['phone']); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="#" class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                        <a href="#" class="text-red-600 hover:text-red-900">Delete</a>
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
                document.getElementById('contactForm').reset();
            }

            document.getElementById('contactModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModal('contactModal');
                }
            });

</script>

