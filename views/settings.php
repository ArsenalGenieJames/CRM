<?php
require_once 'config/database.php';

// Get current manager details
try {
    $stmt = $pdo->prepare("
        SELECT * FROM managers 
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $manager = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log($e->getMessage());
    $manager = null;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        
        // Update manager details
        $stmt = $pdo->prepare("
            UPDATE managers 
            SET name = ?, email = ?, phone = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([$name, $email, $phone, $_SESSION['user_id']]);
        
        // Update password if provided
        if (!empty($_POST['new_password'])) {
            $hashedPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                UPDATE managers 
                SET password = ? 
                WHERE id = ?
            ");
            $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
        }
        
        // Refresh page to show updated info
        header("Location: index.php?modules=settings");
        exit();
        
    } catch(PDOException $e) {
        error_log($e->getMessage());
    }
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-bold flex items-center">
            <i class="fas fa-cog mr-3 text-gray-600"></i>
            Account Settings
        </h1>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-8 mx-auto">
        <form method="POST" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="name">
                        Full Name
                    </label>
                    <input type="text" name="name" id="name" 
                        value="<?php echo htmlspecialchars($manager['name'] ?? ''); ?>"
                        class="shadow-sm border border-gray-300 rounded-lg w-full py-2.5 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="email">
                        Email Address
                    </label>
                    <input type="email" name="email" id="email"
                        value="<?php echo htmlspecialchars($manager['email'] ?? ''); ?>"
                        class="shadow-sm border border-gray-300 rounded-lg w-full py-2.5 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="phone">
                        Phone Number
                    </label>
                    <input type="tel" name="phone" id="phone"
                        value="<?php echo htmlspecialchars($manager['phone'] ?? ''); ?>"
                        class="shadow-sm border border-gray-300 rounded-lg w-full py-2.5 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="new_password">
                        New Password
                    </label>
                    <input type="password" name="new_password" id="new_password" 
                        placeholder="Leave blank to keep current"
                        class="shadow-sm border border-gray-300 rounded-lg w-full py-2.5 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                </div>
            </div>

            <div class="flex items-center justify-end pt-6 border-t border-gray-200">
                <button type="submit" 
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-6 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
