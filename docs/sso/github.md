# GitHub SSO Setup for Nextcloud Social Login

This guide explains how to configure GitHub Single Sign-On (SSO) for the Nextcloud Social Login app, including recommended options for best compatibility and security.

## 1. Register a GitHub OAuth App

1. Go to [GitHub Developer Settings](https://github.com/settings/developers).
2. Click **New OAuth App**.
3. Fill in the required fields:
   - **Application name:** Nextcloud Social Login
   - **Homepage URL:** Your Nextcloud instance URL (e.g., `https://cloud.example.com`)
   - **Authorization callback URL:**
     - **Recommended:** Copy the link from the GitHub login button on your Nextcloud login page and use it as the callback URL in your GitHub OAuth app settings. To make the button visible, temporarily fill provider settings with placeholder data and update later.
     - This ensures the callback URL matches your actual Nextcloud setup and routing.
     - If you cannot access the login button, the typical format is: `https://cloud.example.com/index.php/apps/sociallogin/oauth/GitHub` (replace with your actual Nextcloud URL).
4. Click **Register application**.
5. Copy the **Client ID** and **Client Secret** for use in Nextcloud.

## 2. Configure in Nextcloud

1. Go to **Settings > Social Login** in your Nextcloud admin panel.
2. Add a new provider or edit the GitHub provider.
3. Enter the **Client ID** and **Client Secret** from GitHub.
4. (Optional) Specify allowed organizations in the **Allow login only for specified organizations** field (comma-separated).
5. (Optional) Enable the option:
   - **Allow hidden organization members to register (adds read:org scope)**
   - This allows users who are hidden members of your organization to log in. It requires the `read:org` scope in your OAuth app.

## 3. Troubleshooting

- If users cannot log in, check that the callback URL matches exactly.
- Ensure the required scopes are set in your GitHub OAuth app.
- For organization restrictions, verify the organization names are correct and that the app has `read:org` permission.
- If you encounter callback URL errors despite correct settings, ensure your Nextcloud server generates HTTPS URLs by adding `'overwriteprotocol' => 'https'` to `config.php`.

## 4. Security Notes

To prevent unauthorized account creation via GitHub, consider the following options:
- **Restrict access to organization members:**  
  Only allow users who are members of specified GitHub organizations to register.

- **Disable auto-creation of new users:**  
  Prevent new Nextcloud accounts from being created automatically by users logging in with GitHub.

- **Create users with disabled accounts:**  
  New accounts are created in a disabled state and must be manually enabled by an administrator.

> **Note:**  
> Enable the `read:org` scope only if you want hidden organization members to be able to log in.

**Keep your Client Secret safe and never share it publicly.**

---
For more details, see the [Nextcloud Social Login documentation](https://github.com/zorn-v/nextcloud-social-login).
