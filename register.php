<?php
session_start();
require_once 'config/database.php';

// If user is already logged in, redirect to index
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';
    
    if ($role === 'employee') {
        // Handle employee registration
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $phone = trim($_POST['phone'] ?? '');
        
        // Validate input
        if (empty($name) || empty($email) || empty($password)) {
            $error = "Name, email and password are required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters long";
        } else {
            try {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM employees WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = "Email already exists";
                } else {
                    // Create new employee
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        INSERT INTO employees (name, email, phone, password, created_at) 
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    if ($stmt->execute([$name, $email, $phone, $hashedPassword])) {
                        $_SESSION['success'] = "Account created successfully! Please login.";
                        header('Location: login.php');
                        exit;
                    } else {
                        $error = "Failed to create account. Please try again.";
                    }
                }
            } catch(PDOException $e) {
                error_log("Registration error: " . $e->getMessage());
                $error = "Database error: " . $e->getMessage();
            }
        }
    } elseif ($role === 'client') {
        // Handle client registration
        $name = trim($_POST['name'] ?? '');
        $industry = $_POST['industry'] ?? '';
        $contact_name = trim($_POST['contact_name'] ?? '');
        $contact_email = trim($_POST['contact_email'] ?? '');
        $contact_phone = trim($_POST['contact_phone'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Validate input
        if (empty($name) || empty($industry) || empty($contact_email) || empty($password)) {
            $error = "Company name, industry, contact email and password are required";
        } elseif (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters long";
        } else {
            try {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM clients WHERE contact_email = ?");
                $stmt->execute([$contact_email]);
                if ($stmt->fetch()) {
                    $error = "Email already exists";
                } else {
                    // Create new client
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        INSERT INTO clients (name, industry, contact_name, contact_email, contact_phone, password, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ");
                    if ($stmt->execute([$name, $industry, $contact_name, $contact_email, $contact_phone, $hashedPassword])) {
                        $_SESSION['success'] = "Account created successfully! Please login.";
                        header('Location: login.php');
                        exit;
                    } else {
                        $error = "Failed to create account. Please try again.";
                    }
                }
            } catch(PDOException $e) {
                error_log("Registration error: " . $e->getMessage());
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Creative Studios</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .hidden {
            display: none !important;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full mb-8 p-8 bg-white rounded-lg shadow">
            <div class="text-center" id="accountTypeSelector">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Choose Account Type</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div onclick="showForm('employee')" class="flex flex-col items-center p-6 border-2 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-colors border-gray-200 cursor-pointer">
                        <i class="fas fa-user-tie text-4xl mb-3 text-blue-600"></i>
                        <span class="font-medium text-gray-900">Employee</span>
                    </div>

                    <div onclick="showForm('client')" class="flex flex-col items-center p-6 border-2 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-colors border-gray-200 cursor-pointer">
                        <i class="fas fa-building text-4xl mb-3 text-blue-600"></i>
                        <span class="font-medium text-gray-900">Client</span>
                    </div>
                </div>

                <p class="mt-2 text-center text-sm text-gray-600">
                    Or
                    <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">
                        sign in to your account
                    </a>
                </p>
            </div>

            <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endif; ?>

            <div id="employeeForm" class="max-w-md w-full space-y-8 p-8 bg-white rounded-lg shadow hidden">
                <div>
                    <h2 class="text-center text-3xl font-extrabold text-gray-900">
                        Create Employee Account
                    </h2>
                    <p class="mt-2 text-center text-sm text-gray-600">
                        Or
                        <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">
                            sign in to your account
                        </a>
                    </p>
                </div>
                
                <form class="mt-8 space-y-6" method="POST">
                    <input type="hidden" name="role" value="employee">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="sr-only">Full Name</label>
                            <input id="name" name="name" type="text" required 
                                   class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                                   placeholder="Full Name"
                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                        </div>
                        <div>
                            <label for="email" class="sr-only">Email Address</label>
                            <input id="email" name="email" type="email" required 
                                   class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                                   placeholder="Email Address"
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                        <div>
                            <label for="phone" class="sr-only">Phone Number</label>
                            <input id="phone" name="phone" type="tel" 
                                   class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                                   placeholder="Phone Number"
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                        </div>
                        <div class="col-span-2">
                            <label for="password" class="sr-only">Password</label>
                            <input id="password" name="password" type="password" required 
                                   class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                                   placeholder="Password">
                        </div>
                    </div>

                    <div>
                        <button type="submit" 
                                class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <i class="fas fa-user-plus"></i>
                            </span>
                            Create Account
                        </button>
                    </div>
                </form>
            </div>

            <div id="clientForm" class="max-w-md w-full space-y-8 p-8 bg-white rounded-lg shadow hidden">
                <div>
                    <h2 class="text-center text-3xl font-extrabold text-gray-900">
                        Create Client Account
                    </h2>
                    <p class="mt-2 text-center text-sm text-gray-600">
                        Or
                        <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">
                            sign in to your account
                        </a>
                    </p>
                </div>

                <form class="mt-8 space-y-6" method="POST">
                    <input type="hidden" name="role" value="client">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="sr-only">Company Name</label>
                            <input id="name" name="name" type="text" required 
                                   class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                                   placeholder="Name"
                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                        </div>
                        <div>
                            <label for="industry" class="sr-only">Industry</label>
                            <select id="industry" name="industry" required
                                   class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm">
                                <option value="">Select Industry</option>
                                <option value="Technology" <?php echo (isset($_POST['industry']) && $_POST['industry'] === 'Technology') ? 'selected' : ''; ?>>Technology</option>
                                <option value="Healthcare" <?php echo (isset($_POST['industry']) && $_POST['industry'] === 'Healthcare') ? 'selected' : ''; ?>>Healthcare</option>
                                <option value="Finance" <?php echo (isset($_POST['industry']) && $_POST['industry'] === 'Finance') ? 'selected' : ''; ?>>Finance</option>
                                <option value="Manufacturing" <?php echo (isset($_POST['industry']) && $_POST['industry'] === 'Manufacturing') ? 'selected' : ''; ?>>Manufacturing</option>
                                <option value="Retail" <?php echo (isset($_POST['industry']) && $_POST['industry'] === 'Retail') ? 'selected' : ''; ?>>Retail</option>
                                <option value="Education" <?php echo (isset($_POST['industry']) && $_POST['industry'] === 'Education') ? 'selected' : ''; ?>>Education</option>
                                <option value="Other" <?php echo (isset($_POST['industry']) && $_POST['industry'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div>
                            <label for="contact_name" class="sr-only">Name</label>
                            <input id="contact_name" name="contact_name" type="text"
                                   class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                                   placeholder="Contact Name"
                                   value="<?php echo isset($_POST['contact_name']) ? htmlspecialchars($_POST['contact_name']) : ''; ?>">
                        </div>
                        <div>
                            <label for="contact_email" class="sr-only">Contact Email</label>
                            <input id="contact_email" name="contact_email" type="email"
                                   class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                                   placeholder="Contact Email"
                                   value="<?php echo isset($_POST['contact_email']) ? htmlspecialchars($_POST['contact_email']) : ''; ?>">
                        </div>
                        <div>
                            <label for="contact_phone" class="sr-only">Contact Phone</label>
                            <input id="contact_phone" name="contact_phone" type="tel"
                                   class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                                   placeholder="Contact Phone"
                                   value="<?php echo isset($_POST['contact_phone']) ? htmlspecialchars($_POST['contact_phone']) : ''; ?>">
                        </div>
                        <div class="col-span-2">
                            <label for="password" class="sr-only">Password</label>
                            <input id="password" name="password" type="password" required
                                   class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                                   placeholder="Password">
                        </div>
                    </div>

                    <div>
                        <button type="submit"
                                class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <i class="fas fa-building"></i>
                            </span>
                            Create Client Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function showForm(type) {
        // Hide account type selector
        document.getElementById('accountTypeSelector').classList.add('hidden');
        
        // Hide both forms first
        document.getElementById('employeeForm').classList.add('hidden');
        document.getElementById('clientForm').classList.add('hidden');
        
        // Show the selected form
        if (type === 'employee') {
            document.getElementById('employeeForm').classList.remove('hidden');
        } else if (type === 'client') {
            document.getElementById('clientForm').classList.remove('hidden');
        }
    }
    </script>
</body>
</html>