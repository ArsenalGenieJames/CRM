-- Users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','manager','employee') NOT NULL DEFAULT 'employee',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
);

-- Clients table
CREATE TABLE IF NOT EXISTS `clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(100) NOT NULL,
  `industry` enum('Technology','Healthcare','Finance','Manufacturing','Retail','Education','Other') NOT NULL,
  `contact_name` varchar(100),
  `contact_email` varchar(100),
  `contact_phone` varchar(20),
  `billing_address` text,
  `shipping_address` text,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
);

-- Managers table
CREATE TABLE IF NOT EXISTS `managers` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
);

-- Employees table
CREATE TABLE IF NOT EXISTS `employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
);

-- Tasks table
CREATE TABLE IF NOT EXISTS `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `subject` varchar(100) NOT NULL,
  `description` text,
  `due_date` datetime NOT NULL,
  `priority` enum('Low','Medium','High') NOT NULL,
  `status` enum('Not Started','In Progress','Completed','Deferred') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `client_id` int(11) NOT NULL,
  `manager_id` int(11),
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`),
  FOREIGN KEY (`manager_id`) REFERENCES `managers`(`id`)
);

-- Employee task assignments
CREATE TABLE IF NOT EXISTS `employee_task_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `task_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`),
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`)
);

-- Employee salaries
CREATE TABLE IF NOT EXISTS `employee_salaries` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `employee_id` int(11) NOT NULL,
  `base_salary` decimal(15,2) NOT NULL,
  `effective_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`)
);

-- Task payments
CREATE TABLE IF NOT EXISTS `task_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `task_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `additional_amount` decimal(15,2) NOT NULL,
  `payment_date` date NOT NULL,
  `description` text,
  FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`),
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`)
);

-- Task updates
CREATE TABLE IF NOT EXISTS `task_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `task_id` int(11) NOT NULL,
  `user_type` enum('Manager','Employee') NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('Not Started','In Progress','Completed','Deferred'),
  `update_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `comments` text,
  FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`)
); 