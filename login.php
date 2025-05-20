<?php
session_start();

// Include database configuration
require_once 'config/database.php';

// If user is already logged in, redirect to appropriate page
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['user_type']) {
        case 'manager':
            header('Location: index.php');
            break;
        case 'client':
            header('Location: client.php');
            break;
        case 'employee':
            header('Location: employee.php');
            break;
        default:
            header('Location: index.php');
    }
    exit;
}

require_once 'models/User.php';

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter email and password';
    } else {
        // Debug information
        error_log("Attempting login for email: " . $email);
        
        try {
            // Try each user type table in sequence
            $tables = ['managers', 'clients', 'employees'];
            $email_field = ['email', 'contact_email', 'email'];
            $logged_in = false;

            for($i = 0; $i < count($tables); $i++) {
                $stmt = $pdo->prepare("SELECT * FROM " . $tables[$i] . " WHERE " . $email_field[$i] . " = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_type'] = str_replace('s', '', $tables[$i]); // Remove 's' from table name
                    
                    error_log("Login successful as {$_SESSION['user_type']}: " . $user['name']);
                    
                    // Redirect based on user type
                    switch ($_SESSION['user_type']) {
                        case 'manager':
                            header('Location: index.php');
                            break;
                        case 'client':
                            header('Location: client.php');
                            break;
                        case 'employee':
                            header('Location: employee.php');
                            break;
                        default:
                            header('Location: index.php');
                    }
                    exit;
                }
            }
            
            error_log("Login failed for email: " . $email);
            $error = 'Invalid email or password';
            
        } catch(PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'An error occurred during login';
        }
    }
}

// Display success message if set
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CRM System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full space-y-8 p-8 bg-white rounded-lg shadow-md">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Sign in to your account
                </h2>
            </div>
            
            <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($success); ?></span>
            </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form id="loginForm" class="mt-8 space-y-6" method="POST">
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="email" class="sr-only">Email address</label>
                        <input id="email" name="email" type="email" required 
                               class="appearance-none rounded-t-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
                               placeholder="Email address">
                    </div>
                    <div>
                        <label for="password" class="sr-only">Password</label>
                        <input id="password" name="password" type="password" required 
                               class="appearance-none rounded-b-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
                               placeholder="Password">
                    </div>
                </div>

                <div>
                    <button type="submit" 
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Sign in
                    </button>
                </div>
            </form>
            <div class="mt-4 text-center">
                <a href="register.php" class="text-blue-500 hover:text-blue-600">
                    Don't have an account? Register here
                </a>
            </div>
        </div>
    </div>
</body>
</html>
