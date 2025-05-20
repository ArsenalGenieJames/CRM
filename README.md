# CRM System

A comprehensive Customer Relationship Management (CRM) system built with PHP and MySQL, featuring role-based access control and modern UI using Tailwind CSS.

## Features

### Authentication System
- Secure login system with role-based access (Manager, Employee, Client)
- Password hashing for enhanced security
- Session management
- Secure logout functionality

### User Management
- Multiple user roles:
  - Managers: Full system access
  - Employees: Task management and client interaction
  - Clients: View their tasks and information
- User profile management
- Role-based permissions

### Task Management
- Create and assign tasks
- Set task priorities (High, Medium, Low)
- Track task status (Not Started, In Progress, Completed, Deferred)
- Task updates and comments
- Due date tracking
- Task assignment to employees

### Client Management
- Client profile management
- Company information tracking
- Contact details management
- Industry classification
- Client task history

### Employee Dashboard
- View assigned tasks
- Track task progress
- View recent updates
- Employee profile management
- Task status updates

### Client Dashboard
- View company information
- Track assigned tasks
- View task status and progress
- Communication with managers

### Manager Dashboard
- Overview of all tasks
- Client management
- Employee management
- Task assignment
- Progress monitoring

### Database Features
- Secure database connection using PDO
- Automatic database initialization
- Table creation and management
- Data integrity constraints
- Foreign key relationships

### Security Features
- Password hashing
- SQL injection prevention
- XSS protection
- Session security
- Role-based access control

### UI/UX Features
- Modern interface using Tailwind CSS
- Responsive design
- Interactive elements
- Status indicators
- Priority badges
- Clean and intuitive navigation

## Technical Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- PDO PHP Extension
- mod_rewrite enabled (for Apache)

## Installation

1. Clone the repository to your web server directory
2. Create a MySQL database
3. Import the database schema from `config/schema.sql`
4. Configure database connection in `config/database.php`
5. Access the system through your web browser

## Directory Structure

```
CRM/
├── config/
│   ├── database.php
│   └── schema.sql
├── models/
│   └── User.php
├── views/
│   ├── client.php
│   ├── employee.php
│   └── index.php
├── login.php
├── register.php
├── logout.php
└── README.md
```

## Security Considerations

- All user inputs are sanitized
- Passwords are hashed using PHP's password_hash()
- Session management includes security measures
- Database queries use prepared statements
- XSS protection through output escaping

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a new Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.
