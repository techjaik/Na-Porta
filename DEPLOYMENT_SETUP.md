# 🚀 Automated Deployment Setup for Na Porta

This guide will help you set up automated deployment from GitHub to your InfinityFree hosting.

## 📋 Prerequisites

- GitHub repository: `https://github.com/techjaik/Na-Porta.git`
- InfinityFree hosting account: `if0_40155099`
- FTP access to your hosting

## 🎯 Method 1: GitHub Actions (Recommended)

### Step 1: Get Your FTP Credentials

1. Log into your InfinityFree control panel
2. Go to "FTP Accounts" 
3. Note down:
   - **FTP Host**: Usually `ftpupload.net`
   - **Username**: `if0_40155099`
   - **Password**: Your FTP password

### Step 2: Add Secrets to GitHub

1. Go to your GitHub repository: `https://github.com/techjaik/Na-Porta`
2. Click **Settings** → **Secrets and variables** → **Actions**
3. Add these secrets:
   - `FTP_HOST`: `ftpupload.net`
   - `FTP_USERNAME`: `if0_40155099`
   - `FTP_PASSWORD`: Your FTP password

### Step 3: Enable GitHub Actions

The workflow file `.github/workflows/deploy.yml` is already created. It will:
- Trigger on every push to `main` branch
- Deploy files via FTP to your hosting
- Exclude unnecessary files (.git, tests, etc.)

## 🎯 Method 2: Manual Deployment Script

### Usage:
1. Edit `deploy.php` and add your FTP password
2. Run: `php deploy.php`
3. **Important**: Don't commit the password to Git!

## 🎯 Method 3: Webhook Auto-Deployment

### Step 1: Setup on Your Server

1. Upload `webhook.php` to your hosting
2. Edit the webhook secret in the file
3. Make sure Git is available on your server

### Step 2: Configure GitHub Webhook

1. Go to your repo → **Settings** → **Webhooks**
2. Add webhook:
   - **URL**: `https://naporta.free.nf/webhook.php`
   - **Content type**: `application/json`
   - **Secret**: Same as in webhook.php
   - **Events**: Just push events

## 🔧 InfinityFree Specific Setup

### Enable Git on Your Hosting:

1. Access your hosting file manager
2. Create a `.htaccess` file in your root directory:

```apache
# Enable Git commands (if supported)
<Files ".git*">
    Order allow,deny
    Deny from all
</Files>

# PHP settings for deployment
php_value max_execution_time 300
php_value memory_limit 256M
```

### Alternative: File Sync Method

If Git isn't available on your hosting, use the FTP deployment method:

1. Use GitHub Actions (Method 1) - **Most Reliable**
2. Use manual deployment script (Method 2)
3. Use a local sync tool like FileZilla with automation

## 📁 File Structure After Deployment

```
/htdocs/
├── admin/
├── api/
├── assets/
├── auth/
├── config/
├── database/
├── includes/
├── pages/
├── uploads/
├── index.php
├── products.php
├── cart.php
└── ... (other files)
```

## 🚨 Security Notes

1. **Never commit passwords** to your repository
2. Use GitHub Secrets for sensitive data
3. Exclude sensitive files in deployment
4. Set proper file permissions on your server

## 🧪 Testing Your Setup

1. Make a small change to your code
2. Push to GitHub: `git push origin main`
3. Check if files are updated on your hosting
4. Monitor deployment logs

## 🔍 Troubleshooting

### Common Issues:

1. **FTP Connection Failed**
   - Check FTP credentials
   - Verify server address
   - Try passive mode

2. **Files Not Updating**
   - Check file permissions
   - Verify deployment path
   - Check exclusion patterns

3. **GitHub Actions Failing**
   - Check secrets are set correctly
   - Review action logs
   - Verify FTP server accessibility

## 📞 Support

If you encounter issues:
1. Check GitHub Actions logs
2. Review webhook.log file
3. Test FTP connection manually
4. Contact InfinityFree support for server-specific issues

---

**Next Steps:**
1. Choose your preferred deployment method
2. Set up the credentials and secrets
3. Test with a small change
4. Enjoy automated deployments! 🎉
