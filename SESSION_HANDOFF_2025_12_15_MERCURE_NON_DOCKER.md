# Session Handoff: Mercure CORS Fix (Non-Docker / DreamHost)

## Issue
The user is experiencing CORS errors with Mercure (`Cross-Origin Request Blocked`) and indicated they are **not using Docker** (likely a standard VPS or shared hosting deployment on DreamHost).

## Diagnosis
Since the error shows the browser *can* reach `https://mercure.fwber.me`, the Mercure service is running. However, it is rejecting requests from `https://fwber.me` because it hasn't been told to trust that domain.

In a non-Docker environment, Mercure is typically run as a standalone binary (e.g., `./mercure`) or managed by a process monitor like `systemd` or `supervisor`.

## Solution

You need to set the `MERCURE_CORS_ALLOWED_ORIGINS` environment variable in the environment where the Mercure binary is running.

### Option A: Running via Systemd (Common for VPS)
If you have a service file (e.g., `/etc/systemd/system/mercure.service`), you need to edit it.

1.  **Open the service file**:
    ```bash
    sudo nano /etc/systemd/system/mercure.service
    ```

2.  **Add/Update the Environment line**:
    Add the `MERCURE_CORS_ALLOWED_ORIGINS` variable. Ensure it includes both your frontend domains.
    ```ini
    [Service]
    # ... other settings ...
    Environment="MERCURE_CORS_ALLOWED_ORIGINS=https://fwber.me https://www.fwber.me"
    # ... other settings ...
    ```
    *Note: Use a space to separate multiple domains.*

3.  **Reload and Restart**:
    ```bash
    sudo systemctl daemon-reload
    sudo systemctl restart mercure
    ```

### Option B: Running via Command Line / Script
If you start Mercure manually or via a shell script:

1.  **Export the variable before running**:
    ```bash
    export MERCURE_CORS_ALLOWED_ORIGINS="https://fwber.me https://www.fwber.me"
    ./mercure run
    ```

### Option C: Using a `.env` file (if supported)
If your Mercure setup loads a `.env` file (some wrappers do):

1.  **Edit the `.env` file**:
    ```dotenv
    MERCURE_CORS_ALLOWED_ORIGINS=https://fwber.me https://www.fwber.me
    ```
2.  **Restart Mercure**.

## Verification
1.  Visit your site at `https://fwber.me`.
2.  Open the Browser Console (F12).
3.  The "Cross-Origin Request Blocked" error should be gone, and you should see successful connection logs (if enabled).
