# Deploying FWBer to DreamHost

This guide walks you through deploying FWBer (Laravel Backend + Next.js Frontend) to a DreamHost server.

## Prerequisites

1.  **SSH Access**: Ensure you have enabled Shell Access (SSH) for your user in the DreamHost Panel.
2.  **Domain Setup**: You should have a domain (e.g., `api.fwber.me` for backend, `fwber.me` for frontend) configured in DreamHost.
3.  **Database**: Create a MySQL database in the DreamHost Panel. Note the Hostname, Database Name, Username, and Password.

## Step 1: Connect to Server

Open your terminal and SSH into your DreamHost server:

```bash
ssh username@your-domain.com
```

## Step 2: Prepare Directory Structure

We will clone the repository into a folder in your user's home directory.

```bash
# Go to home directory
cd ~

# Clone the repository (replace with your actual repo URL)
git clone https://github.com/your-username/fwber.git fwber

# Enter the directory
cd fwber
```

## Step 3: Backend Deployment (Laravel)

### 3.1 Configure Environment

```bash
cd fwber-backend

# Copy the production config template
cp .env.production.example .env

# Edit the .env file with your database credentials
nano .env
```

**Update the following in `.env`:**
-   `APP_URL`: Your API domain (e.g., `https://api.fwber.me`)
-   `DB_HOST`: Your DreamHost database hostname (usually `mysql.yourdomain.com`)
-   `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`: From DreamHost Panel.
-   `APP_KEY`: Generate one locally (`php artisan key:generate --show`) and paste it here, or run the command on the server if possible.

### 3.2 Run Deployment Script

DreamHost usually has PHP installed. Check version:
```bash
php -v
```
If it's older than 8.2, you may need to specify the path to a newer PHP version or update it in the panel.

**Important: Composer Setup**
DreamHost does not always have `composer` in the global path. If you see a "composer not found" error:
1.  Install it locally:
    ```bash
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar composer
    chmod +x composer
    ```
2.  Or use the full path if it's already installed (e.g., `php composer.phar`).
3.  **Quick Fix**: Edit `deploy.sh` to use `./composer` if you installed it locally, or ensure `composer` is in your `$PATH`.

Run the deploy script:
```bash
bash deploy.sh --env=production
```

### 3.3 Point Web Directory

In the DreamHost Panel:
1.  Go to **Domains** > **Manage Domains**.
2.  Edit your API domain (e.g., `api.fwber.me`).
3.  Change the **Web Directory** to: `/home/username/fwber/fwber-backend/public`
4.  Save changes.

## Step 4: Frontend Deployment (Next.js)

**Note:** Running a Node.js server (Next.js) on DreamHost Shared Hosting can be complex. If you are on a VPS (DreamCompute), it is straightforward.

### Option A: VPS / DreamCompute (Recommended)

1.  **Install Node.js & PM2**:
    ```bash
    # Install NVM (Node Version Manager)
    curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash
    source ~/.bashrc
    nvm install 18
    npm install -g pm2
    ```

2.  **Configure Environment**:
    ```bash
    cd ~/fwber/fwber-frontend
    cp .env.example .env.local
    nano .env.local
    ```
    Update `NEXT_PUBLIC_API_URL` to point to your backend (e.g., `https://api.fwber.me`).

3.  **Run Deployment**:
    ```bash
    bash deploy.sh --env=production
    ```

4.  **Proxy Setup**:
    You need to proxy traffic from port 80/443 to the Next.js port (usually 3000).
    In DreamHost Panel, enable **Proxy Server** for your frontend domain and point it to `http://localhost:3000`.

### Option B: Shared Hosting (Static Export)

If your Next.js app can be static (no SSR), you can export it.
*Note: FWBer likely uses SSR, so this might not work fully.*

1.  Build locally: `npm run build` (ensure `output: 'export'` is in `next.config.js`).
2.  Upload the `out` folder to your domain's web directory on DreamHost.

### Option C: Vercel (Easiest for Frontend)

1.  Push your code to GitHub.
2.  Import the `fwber-frontend` folder into Vercel.
3.  Set `NEXT_PUBLIC_API_URL` environment variable to your DreamHost backend URL.

## Step 5: Verification

1.  Visit your API URL (`https://api.fwber.me/api/documentation`) to see Swagger docs.
2.  Visit your Frontend URL to see the app.
