# Vercel Configuration for Monorepo

Since the project structure has changed to a monorepo (single repository with `fwber-frontend` and `fwber-backend` folders), you need to update your Vercel project settings to ensure the frontend deploys correctly.

## Steps

1.  Go to your **Vercel Dashboard**.
2.  Select your **fwber-frontend** project.
3.  Go to **Settings** > **General**.
4.  Find the **Root Directory** section.
5.  Click **Edit**.
6.  Enter `fwber-frontend` in the input field.
7.  Click **Save**.

## Ignored Build Step (Optional)

To prevent Vercel from rebuilding the frontend when only backend files change, you can configure the "Ignored Build Step".

1.  Go to **Settings** > **Git**.
2.  Find **Ignored Build Step**.
3.  Enter the following command:
    ```bash
    git diff --quiet HEAD^ HEAD ./
    ```
    *(Note: Since the Root Directory is set to `fwber-frontend`, `./` refers to that folder. This command checks if there are changes inside `fwber-frontend`.)*

## Verify

Trigger a new deployment by pushing a change or manually redeploying from the Vercel dashboard. Vercel should now correctly pick up the `package.json` inside `fwber-frontend`.
