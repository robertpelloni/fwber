# Fixing "Composer Not Found" on DreamHost

If you see `[ERROR] Required command 'composer' not found` when running `deploy.sh`, it means Composer is not in your global system `$PATH`. This is common on shared hosting.

## Option 1: Install Composer Locally (Recommended)

Run these commands on your DreamHost server (via SSH):

```bash
# 1. Download and install composer.phar
curl -sS https://getcomposer.org/installer | php

# 2. Rename it to 'composer'
mv composer.phar composer

# 3. Make it executable
chmod +x composer

# 4. Add current directory to PATH (temporary, for this session)
export PATH=$PWD:$PATH

# 5. Verify it works
composer --version
```

Now you can run the deploy script again:
```bash
bash deploy.sh --env=production
```

## Option 2: Use Full Path in Deploy Script

If you prefer not to modify your PATH, you can edit `deploy.sh` to point to your local composer file.

1. Find where you downloaded `composer.phar` (e.g., `/home/username/composer.phar`).
2. Edit `deploy.sh`:
   ```bash
   # Find this line:
   # composer install --no-dev --optimize-autoloader
   
   # Change it to:
   php /home/your_username/composer.phar install --no-dev --optimize-autoloader
   ```

## Option 3: Alias in .bashrc (Permanent Fix)

To make `composer` available every time you log in:

1. Edit your profile:
   ```bash
   nano ~/.bashrc
   ```
2. Add this line at the bottom:
   ```bash
   alias composer="php ~/.php/composer" 
   # OR wherever you installed it
   ```
3. Reload profile:
   ```bash
   source ~/.bashrc
   ```
