# Manual Upload Instructions for Na Porta

## If Git Push Fails, Use Manual Upload:

### Step 1: Create ZIP File
1. Select all files in the `Na Porta` folder
2. Right-click → "Send to" → "Compressed (zipped) folder"
3. Name it `na-porta-source.zip`

### Step 2: Upload to GitHub
1. Go to https://github.com/techjaik/Na-Porta
2. Click "uploading an existing file" link
3. Drag and drop your ZIP file OR click "choose your files"
4. Add commit message: "Upload Na Porta PHP application"
5. Click "Commit changes"

### Step 3: Extract on GitHub (if needed)
GitHub will automatically extract the ZIP file contents.

## Authentication Issues?
If you're having trouble with Git authentication:

1. **Create Personal Access Token:**
   - GitHub → Settings → Developer settings → Personal access tokens
   - Generate new token with `repo` permissions
   - Use token as password when Git asks

2. **Or use GitHub Desktop:**
   - Download GitHub Desktop app
   - Sign in with your GitHub account
   - Clone the repository
   - Copy your files to the cloned folder
   - Commit and push through the app

## Files Ready for Upload:
✅ All PHP application files
✅ Database export (database_export.sql)
✅ Deployment documentation
✅ .gitignore file
✅ Production-ready configuration

## Next Steps After Upload:
1. Choose hosting platform (InfinityFree recommended)
2. Upload files to web hosting
3. Import database_export.sql
4. Update database credentials for production
