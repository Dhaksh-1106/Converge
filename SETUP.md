# Converge - Campus Research Platform

## Quick Start

### Prerequisites
- PHP 8.5.6 with extensions: curl, mbstring, openssl, pdo_sqlite, sqlite3, zip
- Composer (for dependencies)

### Setup
1. **Database**: Uses SQLite for development (file-based, no server needed)
2. **Extensions Already Enabled** in `C:\php\php.ini`:
   - curl, mbstring, openssl ✓
   - zip ✓
   - pdo_sqlite ✓
   - sqlite3 ✓

### Run the Development Server
From the project root:
```bash
php -S localhost:8000
```

Then open your browser:
- **Frontend**: http://localhost:8000/frontend/login.html
- **API Base**: http://localhost:8000/backend/

### Test Account
After registering via the frontend, log in with your credentials.

### Available Endpoints
- `POST /backend/register_process.php` - Register new user
- `POST /backend/login_process.php` - User login
- `GET /backend/get_pending.php` - Get pending projects (requires auth)
- `GET /backend/get_projects.php` - Get all projects
- `POST /backend/submit_project.php` - Submit a new project

### Database
- **Type**: SQLite (file-based)
- **Location**: `backend/converge.db`
- **Tables**: users, projects, audit_log

### Notes
- This uses a SQLite wrapper that emulates MongoDB for compatibility
- For production, install PHP ext-mongodb and configure real MongoDB
- Session handling uses PHP sessions (stored in `php_session_dir` from php.ini)

### Troubleshooting
If you see "Network error" on the frontend:
1. Check browser DevTools → Network tab for actual HTTP response
2. Ensure PHP server is running: `php -S localhost:8000`
3. Check PHP error logs for detailed messages
