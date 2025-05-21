<?php
require_once 'config/database.php';

// Get all contacts from both clients and employees
try {
    // Get client contacts
    $stmt = $pdo->query("
        SELECT 
            c.id,
            c.name as company_name,
            c.contact_name as first_name,
            c.contact_email as email,
            c.contact_phone as phone,
            c.industry,
            'Client' as contact_type,
            c.created_at
        FROM clients c
        ORDER BY c.created_at DESC
    ");
    $clientContacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get employee contacts
    $stmt = $pdo->query("
        SELECT 
            e.id,
            e.name as first_name,
            e.email,
            e.phone,
            e.industry,
            e.status,
            'Employee' as contact_type,
            e.created_at
        FROM employees e
        ORDER BY e.created_at DESC
    ");
    $employeeContacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Combine both contact types
    $contacts = array_merge($clientContacts, $employeeContacts);
    
    // Sort by creation date
    usort($contacts, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
} catch(PDOException $e) {
    error_log($e->getMessage());
    $contacts = [];
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

    <h1 class="text-3xl font-bold mb-6">
        <i class="fas fa-users mr-2"></i>Contacts
    </h1>

    <!-- Filter -->
    <div class="mb-4">
        <select id="contactTypeFilter" onchange="filterContacts()" class="shadow border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            <option value="All">All Types</option>
            <option value="Client">Clients Only</option>
            <option value="Employee">Employees Only</option>
        </select>
    </div>

    <!-- Contacts Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Industry</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($contacts as $contact): ?>
                <tr class="contact-row" data-type="<?php echo $contact['contact_type']; ?>">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full <?php echo $contact['contact_type'] === 'Client' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'; ?>">
                            <?php echo htmlspecialchars($contact['contact_type']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">
                            <?php 
                            if ($contact['contact_type'] === 'Client') {
                                echo htmlspecialchars($contact['company_name']);
                            } else {
                                echo htmlspecialchars($contact['first_name']);
                            }
                            ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($contact['email']); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($contact['phone']); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($contact['industry']); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($contact['contact_type'] === 'Employee'): ?>
                        <span class="px-2 py-1 text-xs rounded-full <?php echo $contact['status'] === 'Active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo htmlspecialchars($contact['status']); ?>
                        </span>
                        <?php endif; ?>
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
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('fixed')) {
            closeModal(event.target.id);
        }
    }

    function filterContacts() {
        const filterValue = document.getElementById('contactTypeFilter').value;
        const rows = document.getElementsByClassName('contact-row');
        
        for (let row of rows) {
            if (filterValue === 'All' || row.dataset.type === filterValue) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    }
</script>
