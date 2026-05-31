# MongoDB PHP Extension Installation Guide
## For PHP 8.5.6 NTS x64 on Windows

### Step 1: Download the Correct DLL

Your PHP Configuration:
- **Version**: 8.5.6
- **Thread Safety**: NTS (Non-Thread-Safe)
- **Architecture**: x64

**Download from**: https://windows.php.net/downloads/pecl/releases/mongodb/

Look for a file matching: `php_mongodb-*-8.5-nts-x64.zip`

**Direct links to try** (as of May 2026):
- https://windows.php.net/downloads/pecl/releases/mongodb/1.17.3/php_mongodb-1.17.3-8.5-nts-x64.zip
- https://windows.php.net/downloads/pecl/releases/mongodb/1.18.0/php_mongodb-1.18.0-8.5-nts-x64.zip
- https://windows.php.net/downloads/pecl/releases/mongodb/ (browse for latest 8.5-nts-x64)

### Step 2: Extract the DLL

1. Download the ZIP file (from link above)
2. Extract it to a temporary folder
3. Find `php_mongodb.dll` inside
4. Copy `php_mongodb.dll` to: **C:\php\ext\**

```
Example path: C:\php\ext\php_mongodb.dll
```

### Step 3: Enable in PHP

1. Open: `C:\php\php.ini` (use Notepad or VS Code)
2. Find the extensions section (search for "extension=")
3. Add this line:
   ```
   extension=php_mongodb.dll
   ```
4. Save the file

**Optional**: Verify you also have these enabled (they should already be):
```
extension=curl
extension=mbstring
extension=openssl
extension=zip
extension=pdo_sqlite
extension=sqlite3
```

### Step 4: Restart PHP Server

1. **Stop** the current PHP server (Ctrl+C in terminal)
2. Wait 2 seconds
3. **Restart** the server:
   ```
   cd c:\Harshith\Projects\SkillLabs\Converge
   php -S localhost:8000
   ```

### Step 5: Verify Installation

Run this command to confirm MongoDB is loaded:
```
php -m | findstr mongodb
```

Should output: `mongodb`

### Step 6: Test Connection

Visit in browser: http://localhost:8000/backend/ext_mongodb_setup.php

Should show: "✓ Connected to MongoDB successfully!"

---

## Troubleshooting

### If you see: "php_mongodb.dll not found"
- Make sure the file is in `C:\php\ext\` 
- Check the filename spelling (case-sensitive in some systems)
- Restart the terminal/command prompt

### If you see: "Cannot load extension"
- Wrong NTS/ZTS version - re-verify with `php --version` output
- Missing PHP build tools - may need Visual C++ redistributable
- Extension directory mismatch - verify with `php -i | findstr extension_dir`

### If connection fails (after successful install)
- Check .env file has correct DB_URI
- Verify MongoDB Atlas firewall allows your IP
- Test with: `telnet cluster0.cwd1iq7.mongodb.net 27017` (if telnet available)

### If still stuck
- Run: `php -i` to check all loaded extensions
- Email your PHP setup: `php -i > php_info.txt`
- Contact MongoDB support for authentication issues

---

## Once Installed

Your website will automatically:
1. ✓ Connect to MongoDB Atlas
2. ✓ Store all registrations in MongoDB
3. ✓ Store all project submissions in MongoDB  
4. ✓ Reflect all data in real-time

The local dev server will run at: **http://localhost:8000**

