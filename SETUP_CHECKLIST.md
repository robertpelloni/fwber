# FWBer Setup Checklist

## 1. Environment Setup ✓
- [x] XAMPP installed
- [ ] Apache running (port 80)
- [ ] MySQL running (port 3306)
- [ ] PHP accessible from command line
- [ ] Database connection working

## 2. Database Setup
- [ ] Import `setup-database.sql` into phpMyAdmin
- [ ] Verify database `fwber` exists with all tables
- [ ] Test database connection from PHP

## 3. API Keys Configuration
- [ ] Get Replicate API key for avatar generation
- [ ] Get Google Analytics tracking ID
- [ ] Get reCAPTCHA site keys
- [ ] Update `_secrets.php` with credentials

## 4. File Permissions
- [ ] Create `avatars/` directory with write permissions
- [ ] Create `api/` directory
- [ ] Ensure `js/` directory exists

## 5. Testing
- [ ] Landing page loads at http://localhost/index.php
- [ ] User registration works
- [ ] Avatar generation test
- [ ] Real-time demo accessible

## 6. Optional Enhancements
- [ ] Set up SSL certificate for HTTPS
- [ ] Configure email SMTP for notifications
- [ ] Set up cron jobs for cleanup tasks

## Quick Commands

### Start XAMPP Services
```
# Open XAMPP Control Panel
C:\xampp\xampp-control.exe
```

### Test Database Connection
```php
php -r "include '_secrets.php'; $link = new mysqli($dburl, $dbuser, $dbpass, $dbname); echo $link ? 'Connected!' : 'Failed!';"
```

### Import Database Schema
1. Open http://localhost/phpmyadmin
2. Create database 'fwber' if not exists
3. Import setup-database.sql

### Test Website
```
http://localhost/index.php
http://localhost/realtime-demo.php (after login)
```

## Gemini CLI Collaboration Points

**Ask Gemini CLI to help with:**
- Marketing copy for landing page
- User personas and target audience analysis
- Competitive analysis of dating apps
- Content strategy for SEO
- Business model validation
- Privacy policy and terms of service content
- Social media marketing strategy

**Claude Code will handle:**
- Technical implementation
- Database optimization
- Security features
- API integrations
- Performance monitoring
- Bug fixes and testing