<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is a client or manager
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['client', 'manager'])) {
    header('Location: login.php');
    exit;
}

// Check if task ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid task ID";
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

$task_id = $_GET['id'];

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Delete related records first due to foreign key constraints
    $stmt = $pdo->prepare("DELETE FROM task_updates WHERE task_id = ?");
    $stmt->execute([$task_id]);
    
    $stmt = $pdo->prepare("DELETE FROM task_payments WHERE task_id = ?");
    $stmt->execute([$task_id]);
    
    $stmt = $pdo->prepare("DELETE FROM employee_task_assignments WHERE task_id = ?");
    $stmt->execute([$task_id]);
    
    $stmt = $pdo->prepare("DELETE FROM client_feedback WHERE task_id = ?");
    $stmt->execute([$task_id]);
    
    // Finally delete the task
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->execute([$task_id]);
    
    // Commit transaction
    $pdo->commit();
    
    $_SESSION['success_message'] = "Task deleted successfully";
    
} catch (PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    error_log("Error deleting task: " . $e->getMessage());
    $_SESSION['error_message'] = "Error deleting task. Please try again.";
}

// Redirect back to previous page
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;
?>
