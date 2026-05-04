# Gestion Reclamation Technic

A web-based complaint and claim management system designed for managing technical service requests and user complaints.

## Project Description

Gestion Reclamation Technic is a management system that allows clients to submit, track, and manage technical complaints or claims. The system supports multiple user roles including administrators, clients, and technicians, each with their own interface and functionalities.

## Features

- **User Management**: Support for multiple user roles (Admin, Client, Technician)
- **Authentication**: Secure login system for different user types
- **Complaint Tracking**: Clients can submit and track their technical complaints
- **Admin Dashboard**: Administrators can manage users and view system statistics
- **Technician Interface**: Technicians can view and respond to assigned complaints
- **User Profiles**: Each user has a profile page to manage their information

## Project Structure

```
├── index.html              # Main entry point
├── css/                    # Stylesheets
│   ├── admin.css          # Admin dashboard styles
│   ├── adminuser.css      # Admin user management styles
│   ├── client.css         # Client interface styles
│   ├── login.css          # Login page styles
│   ├── profile.css        # Profile page styles
│   └── tech.css           # Technician interface styles
└── php/                    # Backend logic
    ├── admin.php          # Admin dashboard
    ├── admin_users.php    # User management
    ├── adminprofile.php   # Admin profile
    ├── client.php         # Client dashboard
    ├── client_history.php # Client complaint history
    ├── config.php         # Database configuration
    ├── login.php          # Authentication logic
    ├── profile.php        # User profile management
    ├── tech_profile.php   # Technician profile
    └── technicien.php     # Technician dashboard
```

## Requirements

- PHP 7.0 or higher
- Web server (Apache, Nginx, etc.)
- MySQL/MariaDB database
- Modern web browser with JavaScript support

## Installation

1. **Clone or extract the project** to your web server directory
2. **Configure the database**:
   - Update database credentials in `php/config.php`
   - Create the necessary database tables
3. **Set up file permissions**: Ensure the web server has appropriate read/write permissions
4. **Access the application**: Open `index.html` in your web browser or navigate to `http://localhost/path-to-project`

## Usage

### For Clients
1. Login with your credentials
2. Submit new complaints through the client interface
3. View complaint history and status updates
4. Update your profile information

### For Technicians
1. Login with technician credentials
2. View assigned complaints
3. Update complaint status and add notes
4. Manage your profile

### For Administrators
1. Login with admin credentials
2. Manage users (add, edit, delete)
3. View system statistics and reports
4. Monitor all complaints and system activity

## Configuration

Update `php/config.php` with your database connection details:

```php
$host = 'localhost';
$database = 'your_database_name';
$username = 'your_username';
$password = 'your_password';
```

## Security Notes

- Never commit sensitive information (database passwords, API keys) to version control
- Use HTTPS in production environments
- Implement proper input validation and sanitization
- Consider using prepared statements to prevent SQL injection
- Regularly update dependencies and apply security patches

## Contributing

Please feel free to submit issues and contributions to improve this project.

## License

[Specify your license here]

## Support

For support or questions, please contact the project maintainer.
