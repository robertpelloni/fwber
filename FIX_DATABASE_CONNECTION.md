# Fix Database Connection - Final Step!

**Issue:** Laravel can't connect to MySQL  
**Solution:** Update database credentials in `.env` file  
**Time:** 2 minutes

---

## 🔧 Quick Fix

### Option 1: Use Root MySQL Credentials (Easiest)

Edit `fwber-backend/.env` and change:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fwber
DB_USERNAME=root
DB_PASSWORD=YOUR_MYSQL_ROOT_PASSWORD
```

Replace `YOUR_MYSQL_ROOT_PASSWORD` with your actual MySQL root password.

---

### Option 2: Create New MySQL User

If you want to keep the 'fwber' user, create it in MySQL:

```sql
-- Open MySQL as root
mysql -u root -p

-- Create user and database
CREATE DATABASE IF NOT EXISTS fwber;
CREATE USER IF NOT EXISTS 'fwber'@'localhost' IDENTIFIED BY 'Temppass0!';
GRANT ALL PRIVILEGES ON fwber.* TO 'fwber'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Then the original `.env` settings will work:
```env
DB_USERNAME=fwber
DB_PASSWORD=Temppass0!
```

---

## ✅ After Fixing

Run these commands:

```bash
cd C:\Users\mrgen\fwber\fwber-backend

# Test connection
php ..\test-db-connection.php

# If successful, run migrations
php artisan migrate --force

# Start Laravel server
php artisan serve
```

---

## 🎯 Then You're Done!

Once migrations run successfully, you'll have:

✅ All multi-AI implemented features deployed  
✅ Laravel backend operational  
✅ Block/report tables created  
✅ API ready for Next.js  
✅ Security fixes active  

---

**Almost there!** Just update the database password and run the migration! 🚀
