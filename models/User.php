<?php
class User {
    private $pdo;
    private $id;
    private $name;
    private $email;
    private $role;

    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        global $pdo;
        $this->pdo = $pdo;
    }

    public function login($email, $password) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $this->id = $user['id'];
                $this->name = $user['name'];
                $this->email = $user['email'];
                $this->role = $user['role'];
                return true;
            }
            return false;
        } catch(PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function register($name, $email, $password, $role = 'employee') {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("
                INSERT INTO users (name, email, password, role, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            return $stmt->execute([$name, $email, $hashedPassword, $role]);
        } catch(PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getRole() {
        return $this->role;
    }

    public function isLoggedIn() {
        return isset($this->id);
    }

    public function isAdmin() {
        return $this->role === 'admin';
    }

    public function isManager() {
        return $this->role === 'manager';
    }

    public function isEmployee() {
        return $this->role === 'employee';
    }

    public function findByEmail($email) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error finding user by email: " . $e->getMessage());
            return null;
        }
    }

    public function createUser($name, $email, $password, $role = 'employee') {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare(
                "INSERT INTO users (name, email, password, role, created_at) 
                 VALUES (?, ?, ?, ?, NOW())"
            );
            return $stmt->execute([$name, $email, $hashedPassword, $role]);
        } catch (PDOException $e) {
            error_log("Error creating user: " . $e->getMessage());
            return false;
        }
    }

    public function updateUser($id, $data) {
        try {
            $updates = [];
            $params = [];
            
            foreach ($data as $key => $value) {
                if ($key !== 'id' && $key !== 'password') {
                    $updates[] = "$key = ?";
                    $params[] = $value;
                }
            }
            
            if (isset($data['password'])) {
                $updates[] = "password = ?";
                $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            $params[] = $id;
            
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error updating user: " . $e->getMessage());
            return false;
        }
    }

    public function deleteUser($id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error deleting user: " . $e->getMessage());
            return false;
        }
    }

    public function getAllUsers() {
        try {
            $stmt = $this->pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching users: " . $e->getMessage());
            return [];
        }
    }

    public function getUserById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, name, email, role, created_at FROM users WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching user: " . $e->getMessage());
            return null;
        }
    }
} 