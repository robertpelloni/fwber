# Session Handoff: Mercure CORS Fix (Shared Hosting / No Sudo)

## Issue
The user is experiencing CORS errors with Mercure (`Cross-Origin Request Blocked`) on a shared hosting environment (DreamHost) where they do not have `sudo` access or Docker.

## Solution
Since you cannot edit system service files, you must set the environment variables in the user session that runs the Mercure binary.

We have created a helper script to make this easy.

## Steps to Fix

1.  **Pull the latest code** to your server:
    ```bash
    cd fwber-backend
    git pull origin main
    ```

2.  **Make the script executable**:
    ```bash
    chmod +x scripts/start_mercure_shared.sh
    ```

3.  **Stop the currently running Mercure process**:
    Find the process ID (PID) and kill it.
    ```bash
    ps aux | grep mercure
    kill <PID>
    ```
    *(Replace `<PID>` with the number found in the first column)*

4.  **Start Mercure using the new script**:
    This script automatically loads your `.env` file and sets the correct CORS headers.
    ```bash
    ./scripts/start_mercure_shared.sh
    ```

5.  **Verify**:
    Check your browser console at `https://fwber.me`. The CORS error should be gone.

## Running in the Background
If you want Mercure to stay running after you disconnect, you can modify the script or run it with `nohup`:

```bash
nohup ./scripts/start_mercure_shared.sh > mercure.log 2>&1 &
```

## Cron Job (Optional)
To ensure it starts automatically if the server reboots, you can add a cron job (run `crontab -e`):

```bash
@reboot cd /path/to/fwber-backend && ./scripts/start_mercure_shared.sh > mercure.log 2>&1
```
