# Na Porta - Deployment Guide

## Hosting Options for PHP + MySQL Application

### ❌ Why Netlify Won't Work
- Netlify only supports static sites and serverless functions
- Cannot run PHP applications or MySQL databases
- Designed for JAMstack, not traditional server-side applications

### ✅ Recommended Hosting Platforms

#### 1. **InfinityFree** (Best Free Option)
- **URL**: https://infinityfree.net/
- **Features**: Free PHP hosting with MySQL
- **Storage**: 5GB
- **Bandwidth**: Unlimited
- **PHP Version**: 8.x supported

#### 2. **000webhost**
- **URL**: https://www.000webhost.com/
- **Features**: Free PHP hosting with MySQL
- **Storage**: 1GB
- **Bandwidth**: 10GB/month

#### 3. **Heroku** (Professional Option)
- **URL**: https://heroku.com/
- **Features**: Git-based deployment
- **Database**: ClearDB MySQL addon
- **Free Tier**: Available (limited hours)

## Deployment Steps

### Step 1: Prepare Your Code
1. Update database configuration for production
2. Test all functionality locally
3. Export your database

### Step 2: Set Up GitHub Repository
```bash
git init
git add .
git commit -m "Initial commit"
git remote add origin https://github.com/yourusername/na-porta.git
git push -u origin main
```

### Step 3: Deploy to Hosting Platform

#### For InfinityFree/000webhost:
1. Create account and get FTP credentials
2. Upload files via FTP or File Manager
3. Import database via phpMyAdmin
4. Update database credentials

#### For Heroku:
1. Install Heroku CLI
2. Create Heroku app
3. Add ClearDB MySQL addon
4. Configure environment variables
5. Deploy via Git

### Step 4: Database Setup
1. Export your local database:
   ```sql
   mysqldump -u root -p na_porta_db > na_porta_db.sql
   ```
2. Import to production database via hosting panel

### Step 5: Configuration
Update these files for production:
- `config/database.php` - Use production database credentials
- Any hardcoded localhost URLs
- File upload paths if needed

## Environment Variables (for Heroku)
```
DB_HOST=your-mysql-host
DB_NAME=your-database-name
DB_USER=your-mysql-username
DB_PASS=your-mysql-password
```

## Security Checklist
- [ ] Remove debug files (test-*.php, debug-*.php)
- [ ] Update database credentials
- [ ] Enable error logging instead of displaying errors
- [ ] Secure admin panel with proper authentication
- [ ] Use HTTPS in production

## File Structure
```
Na Porta/
├── admin/          # Admin panel
├── api/           # API endpoints
├── assets/        # CSS, JS, images
├── config/        # Database configuration
├── includes/      # Shared PHP files
├── pages/         # Main application pages
├── uploads/       # User uploads (configure permissions)
└── index.php      # Entry point
```
