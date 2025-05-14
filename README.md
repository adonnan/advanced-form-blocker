# Advanced Forms Blocker for WordPress

**A WordPress plugin to block form submissions from unwanted email addresses and domains, with a secure API for external list access.**

[![License: GPL v2 or later](https://img.shields.io/badge/license-GPL--2.0%2B-blue.svg?style=flat-square)](https://www.gnu.org/licenses/gpl-2.0.html)
<!-- Add other badges if you have them, e.g., version, build status -->

Advanced Forms Blocker helps you maintain cleaner form submissions by preventing entries from specified email addresses or entire domains. It's designed to integrate with popular WordPress form plugins (starting with Gravity Forms) and offers a secure way for external systems like CRMs (e.g., Pardot/Salesforce Marketing Cloud Account Engagement, HubSpot) to consume your blocklist.

## Features

*   **Block by Specific Email Address:** Prevent individual email addresses from submitting forms.
*   **Block by Entire Domain:** Block all email addresses from a specific domain (e.g., `spamdomain.com`).
*   **JSON Blocklist Management:** Easily manage your blocklist by uploading a simple JSON file directly in the WordPress admin area.
*   **Form Plugin Integration:**
    *   Currently supports **Gravity Forms**.
    *   Architected for easier integration with other form plugins (e.g., Contact Form 7, WPForms) in the future.
*   **Secure REST API Endpoint:** Expose your blocklist via a secure, read-only REST API endpoint.
*   **API Key Authentication:** The REST API endpoint is protected by a unique API key, ensuring only authorized systems can access your list.
*   **Customizable Error Messages:** Define custom messages that users see when their email address or domain is blocked.
*   **User-Friendly Settings Page:** A dedicated settings page in WordPress admin to manage all plugin options.
*   **Easy API URL Access:** The settings page provides a readily copyable full API URL when the API is enabled and a key is set.

## Installation

1.  **Download:**
    *   Download the latest release `.zip` file from the [GitHub Releases page](https://github.com/adonnan/advanced-forms-blocker-public/releases).
    *   OR, clone the repository: `git clone https://github.com/adonnan/[advanced-forms-blocker-public.git`
2.  **Upload to WordPress:**
    *   **Via WordPress Admin:**
        1.  Go to "Plugins" > "Add New" in your WordPress dashboard.
        2.  Click "Upload Plugin".
        3.  Choose the downloaded `.zip` file and click "Install Now".
    *   **Via FTP:**
        1.  Unzip the downloaded file.
        2.  Upload the `advanced-forms-blocker` (or your plugin's folder name) directory to the `/wp-content/plugins/` directory on your server.
3.  **Activate:**
    *   Go to "Plugins" in your WordPress dashboard.
    *   Find "Advanced Forms Blocker" and click "Activate".

## Configuration

After activating the plugin, navigate to **Settings > Form Blocker** in your WordPress admin sidebar.

1.  **Blocklist Management:**
    *   **Prepare your `blocklist.json` file:**
        The file should be a JSON object with two main keys: `domains` (an array of domain strings) and `emails` (an array of email address strings).
        Example `blocklist.json`:
        ```json
        {
          "domains": [
            "blockeddomain.com",
            "another-spammer.org"
          ],
          "emails": [
            "spammer@example.com",
            "unwanted@test.net"
          ]
        }
        ```
    *   **Upload New Blocklist:**
        1.  Under "Blocklist Management", click "Choose File".
        2.  Select your prepared `blocklist.json` file.
        3.  Click "Upload File".
    *   **Current Blocklist Content:** This section displays the content of the currently active `blocklist.json` file for review.

2.  **API Key Management:**
    *   An API key is automatically generated upon plugin activation.
    *   You can view the current API key here.
    *   If needed, click "Regenerate Key" to create a new unique API key. This will invalidate the old key.

3.  **API Configuration & Status:**
    *   This section displays the current status of your API and the URL to access it.
    *   **Enable API Access:** Check the box "Allow external access to the blocklist via REST API" to activate the API endpoint. *The API is disabled by default.*
    *   If the API is enabled and a key is set, a full, copyable API URL (including the key) will be displayed here.
    *   Click "Save API & Message Settings" after changing the "Enable API Access" status.

4.  **Custom Block Messages:**
    *   **Blocked Email Message:** Enter the message users will see if their specific email address is blocked.
    *   **Blocked Domain Message:** Enter the message users will see if the domain of their email address is blocked.
    *   Click "Save API & Message Settings" to save your custom messages.

## Using the Blocklist with External Systems (Pardot/CRMs)

The primary purpose of the REST API is to allow trusted external systems (like a backend script for your CRM or a marketing automation platform) or administrators to securely fetch the current blocklist.

1.  **Enable the API** and ensure an **API Key** is generated in the plugin settings.
2.  Copy the **full API Endpoint URL** displayed under "API Configuration & Status". It will look something like:
    `https://yourdomain.com/wp-json/afb/v1/list?key=YOUR_ACTUAL_API_KEY`
3.  **Provide this URL to your external system or script.**
    *   **For Pardot (Salesforce Marketing Cloud Account Engagement), HubSpot, or other CRMs:**
        *   **Automated Sync (Recommended for dynamic updates):** A developer can create a server-side script (e.g., a scheduled cron job, a cloud function) that:
            1.  Periodically calls your plugin's API endpoint using the API key.
            2.  Parses the returned JSON (domains, emails, and messages).
            3.  Uses the CRM's API to update a suppression list, dynamic list, or perform other actions based on the blocklist data.
            4.  A script can live on the landing page template, preventing any address or domain listed from submitting the form.
    *   **Important:** The API key should be treated as a secret. Do not expose it in public client-side code.

The API will return a JSON response like this:
```json
{
  "domains": ["blockeddomain.com"],
  "emails": ["spammer@example.com"],
  "messages": {
    "blocked_email": "This email address is not allowed for submission.",
    "blocked_domain": "Submissions from this email domain are not allowed."
  }
}