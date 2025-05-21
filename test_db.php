<?php
require_once 'config/database.php';

try {
    // Test database connection
    echo "Testing database connection...<br>";
    $pdo->query("SELECT 1");
    echo "Database connection successful!<br><br>";

    // Check if tables exist
    echo "Checking tables...<br>";
    $tables = ['clients', 'employees', 'managers', 'tasks', 'task_categories', 'employee_task_assignments'];
    
    foreach ($tables as $table) {
        $result = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($result->rowCount() > 0) {
            echo "Table '$table' exists<br>";
            
            // Show table structure
            $columns = $pdo->query("SHOW COLUMNS FROM $table");
            echo "Columns in $table:<br>";
            while ($column = $columns->fetch()) {
                echo "- " . $column['Field'] . " (" . $column['Type'] . ")<br>";
            }
            echo "<br>";
        } else {
            echo "Table '$table' does not exist!<br><br>";
        }
    }

    // Check task categories
    echo "Checking task categories...<br>";
    $categories = $pdo->query("SELECT * FROM task_categories");
    if ($categories->rowCount() > 0) {
        echo "Found " . $categories->rowCount() . " categories:<br>";
        while ($category = $categories->fetch()) {
            echo "- " . $category['name'] . "<br>";
        }
    } else {
        echo "No categories found!<br>";
    }

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 