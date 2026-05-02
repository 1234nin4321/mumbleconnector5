# 🐧 SeAT Mumble Connector - Ubuntu 24.04 Guide

This guide covers the complete installation and configuration of the Mumble Connector for SeAT 5 on **Ubuntu 24.04** using the **SQLite Bridge** via **Docker**.

---

## 1. Plugin Installation (SeAT in Docker)

If your SeAT is running in Docker (e.g., via `eveseat/docker`), follow these steps:

### Step 1: Mount the Plugin
In your SeAT `docker-compose.yml`, mount the plugin directory into both the `seat-web` and `seat-worker` services:

```yaml
services:
  seat-web:
    # ...
    volumes:
      - /path/to/mumble-connector:/var/www/seat/packages/seat-plugins/mumble-connector
  
  seat-worker:
    # ...
    volumes:
      - /path/to/mumble-connector:/var/www/seat/packages/seat-plugins/mumble-connector
```

### Step 2: Register & Install
Run these commands from your SeAT Docker directory:

```bash
# Register the local repository inside the container
docker-compose exec seat-web composer config repositories.mumble-connector path /var/www/seat/packages/seat-plugins/mumble-connector

# Install the package
docker-compose exec seat-web composer require seat-plugins/mumble-connector

# Publish assets and run migrations
docker-compose exec seat-web php artisan vendor:publish --provider="Seat\MumbleConnector\MumbleConnectorServiceProvider" --force
docker-compose exec seat-web php artisan migrate
```

---

## 2. Mumble Bridge Setup (Docker Side)

The bridge allows SeAT to write to the Mumble database securely.

### Step 1: Permissions
Ensure the directory containing your Mumble database is accessible:
```bash
sudo chown -R www-data:mumble-server /var/lib/mumble-server
sudo chmod -R 775 /var/lib/mumble-server
```

### Step 2: Launch Bridge
Navigate to the bridge directory and start the container:
```bash
cd /var/www/seat/packages/seat-plugins/mumble-connector/mumble-bridge
docker-compose up -d
```

### Step 3: Retrieve API Key
Check the logs to find the auto-generated security key:
```bash
docker logs mumble-bridge
```
*Copy the 64-character hex string starting with `[SECURITY] GENERATED NEW API KEY`.*

---

## 3. Configuration in SeAT

1.  Log into your **SeAT Admin Panel**.
2.  Navigate to **Mumble Admin > Settings**.
3.  Configure the following:
    *   **Driver**: REST
    *   **REST URL**: `http://mumble-bridge:8082` (Note: Ensure both SeAT and the bridge are in the same Docker network)
    *   **REST API Key**: *The key you copied in Step 2.3*
    *   **Server Address**: Your public Mumble server IP/Hostname.
4.  Click **Save Settings** and then **Test Connection**.

---

## 4. Temporary Connection Links

This feature allows you to invite people not in your alliance.

### How to generate:
1.  Go to **Mumble Admin > Temporary Links**.
2.  Enter the **Guest Name** and **Duration** (hours).
3.  Copy the generated link and send it to your guest.
4.  The guest will see a premium landing page with their credentials and a "Connect Now" button.

### Maintenance (Automatic Cleanup):
To automatically remove expired links and their Mumble accounts, add this to your SeAT scheduler (`app/Console/Kernel.php`):
```php
$schedule->command('mumble:cleanup-guests')->hourly();
```
*Alternatively, run it manually:* `php artisan mumble:cleanup-guests`.

---

## 💡 Troubleshooting
*   **Bridge Unreachable**: Ensure port `8082` is open in your firewall (`ufw allow 8082/tcp`).
*   **Permission Denied**: Check that the Docker volume mount has write access to your `.sqlite` file on the host.
*   **Mumble Connection Failed**: Ensure the "Server Address" in SeAT settings matches what users use to connect.

*Guide generated for SeAT Mumble Connector 5.0*
