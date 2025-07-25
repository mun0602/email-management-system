# Email Management System

A PHP-based email management system with public sharing functionality.

## Features

- **User Authentication**: Admin and user roles
- **Email Management**: Store and organize email accounts with passwords
- **Public Sharing**: Create shareable collections of emails
- **Password Protection**: Optional password protection for shared collections
- **Session Management**: Secure access to protected shares
- **Copy Functionality**: Easy copy-to-clipboard for emails and passwords
- **Responsive Design**: Mobile-friendly interface
- **Analytics**: Track views and access logs for shared collections

## Installation

1. **Prerequisites**
   - PHP 7.4 or higher
   - MySQL 5.7 or higher
   - Web server (Apache/Nginx)

2. **Database Setup**
   ```bash
   # Run the database setup script
   php database/setup.php
   ```

3. **Configuration**
   - Update database credentials in `config/database.php`
   - Configure app settings in `config/app.php`

4. **File Permissions**
   ```bash
   chmod 755 shared/
   chmod 755 api/
   ```

## Usage

### Default Credentials
- **Admin**: username: `admin`, password: `admin123`
- **User**: username: `user1`, password: `user123`

### Creating Shares
1. Login to your account
2. Navigate to Share Manager
3. Select emails to share
4. Set collection name and optional password
5. Generate and share the public link

### Accessing Shared Collections
- Public links format: `https://yourdomain.com/shared/?token=SHARE_TOKEN`
- Password-protected collections require authentication
- Sessions are valid for 24 hours

## File Structure

```
/
├── admin/
│   └── share-manager.php     # Admin share management
├── member/
│   └── share-manager.php     # User share management
├── shared/
│   ├── index.php            # Public sharing page
│   └── password.php         # Password authentication
├── api/
│   ├── create-share.php     # Create shared collection
│   ├── update-share.php     # Update collection
│   ├── delete-share.php     # Delete collection
│   └── verify-share-password.php # Password verification
├── config/
│   ├── app.php             # Application configuration
│   └── database.php        # Database configuration
├── includes/
│   ├── functions.php       # Core utility functions
│   ├── share-functions.php # Share-specific functions
│   ├── header.php          # Page header template
│   └── footer.php          # Page footer template
├── assets/
│   ├── css/style.css       # Custom styles
│   └── js/app.js          # JavaScript functions
└── database/
    ├── schema.sql          # Database schema
    └── setup.php          # Setup script
```

## Security Features

- **Password Hashing**: bcrypt for all passwords
- **Session Management**: Secure session handling
- **Input Sanitization**: XSS protection
- **Access Control**: Role-based permissions
- **Token Security**: Random 32-character share tokens
- **Rate Limiting**: Protection against abuse

## API Endpoints

- `POST /api/create-share.php` - Create new shared collection
- `POST /api/update-share.php` - Update collection settings
- `POST /api/delete-share.php` - Delete shared collection
- `POST /api/verify-share-password.php` - Verify share password

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open source and available under the [MIT License](LICENSE).