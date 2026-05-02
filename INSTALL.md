# 🎙️ SeAT Mumble Connector 5 - Installation Guide

Welcome to the definitive installation guide for the **SeAT Mumble Connector**. This plugin seamlessly integrates your SeAT 5 installation with a Mumble server, providing automated user synchronization, group mapping, and certificate management.

---

## 📋 Prerequisites

Before proceeding, ensure your environment meets the following requirements:

*   **SeAT Version**: 5.x (Laravel 10 based)
*   **PHP**: 8.1 or higher
*   **Mumble Server**: 1.3+ (1.4+ required for gRPC)
*   **Access**: SSH access to your SeAT server and administrative privileges in SeAT.

---

## 🚀 Installation

### Option 1: Manual Upload (FTP/SFTP)
Use this method if you are uploading the plugin source files directly to your server without using a public repository.

1.  **Create Package Directory**: If it doesn't exist, create the path for your custom plugins:
    ```bash
    mkdir -p /var/www/seat/packages/seat-plugins
    ```
2.  **Upload Files**: Upload the plugin content into a folder named **EXACTLY** `mumble-connector` at:
    - Path: `/var/www/seat/packages/seat-plugins/mumble-connector`
    - **CRITICAL**: The plugin's `composer.json` must be at that exact path.
    - **Verify**: Run `ls /var/www/seat/packages/seat-plugins/mumble-connector/composer.json`. It should return the filename, not an error.
3.  **Update `composer.json`**: Edit the main `composer.json` in the SeAT root directory (`/var/www/seat/composer.json`). 

    **❌ WRONG (Common mistake):**
    ```json
    "prefer-stable": true
    }
    "repositories": [...]
    ```

    **✅ RIGHT:**
    ```json
    "prefer-stable": true,
    "repositories": [
        {
            "type": "path",
            "url": "packages/seat-plugins/mumble-connector"
        }
    ]
    }
    ```
    *(Note: Add a comma after `true` and make sure the ONLY closing `}` is at the very end of the file.)*
4.  **Require the Package**: Run the following command in the SeAT root. 
    *(Note: If you get a "not writable" error, use `sudo -u www-data` or check file ownership.)*
    ```bash
    composer require seat-plugins/mumble-connector
    ```

### Option 2: Install via Composer (Public Repository)
Navigate to your SeAT root directory (usually `/var/www/seat`) and run:

```bash
composer require seat-plugins/mumble-connector
```

### 2. Publish Assets & Configuration
Publish the plugin's configuration, views, and assets:

```bash
php artisan vendor:publish --provider="Seat\MumbleConnector\MumbleConnectorServiceProvider" --force
```

### 3. Run Database Migrations
Create the necessary database tables for group mappings, sync logs, and member data:

```bash
php artisan migrate
```

### 4. Clear Caches
Clear caches to ensure the new routes and configurations are detected:

```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear
```

**⚠️ Important**: Do NOT run `php artisan config:cache` or `route:cache` as this can interfere with the plugin's dynamic settings.

---

## ⚙️ Driver Configuration

The Mumble Connector supports several connection drivers. Choose the one that best fits your setup.

### 🏆 Recommended: SQLite Bridge (Ubuntu/Debian)

This is the easiest and most reliable method for Ubuntu. It uses a lightweight Node.js bridge that talks directly to the Mumble database.

#### 1. Install Node.js & Dependencies:
```bash
sudo apt update
sudo apt install nodejs npm -y
```

#### 2. Set Up the Bridge:
Navigate to the `mumble-bridge` directory in your plugin path:
```bash
cd /var/www/seat/packages/seat-plugins/mumble-connector/mumble-bridge
npm install
```

#### 3. Start the Bridge:
You can run it directly or use PM2 (recommended) to keep it running:
```bash
# Direct run (for testing)
npm start

# Using PM2 (recommended for production)
sudo npm install -g pm2
pm2 start server.js --name mumble-bridge
pm2 save
pm2 startup
```

**Security**: On first run, a key will be generated in `mumble-bridge.key`. Copy this key for the SeAT settings.
**Permissions**: Ensure the user running the bridge has write access to `/var/lib/mumble-server/mumble-server.sqlite`.

#### 4. Configure SeAT:
1. Go to **Mumble Admin > Settings**
2. Set **Driver** to **REST**
3. Set **REST URL** to `http://127.0.0.1:8082`
4. Enter the **API Key** from step 3.
5. Click **Save Settings** and **Test Connection**.

---

### Alternative: gRPC Driver (Mumble 1.4+)

gRPC is the easiest to set up as it requires no additional software on the Mumble server.

#### On the SeAT Server (Ubuntu):

```bash
# Install build dependencies
sudo apt install php-dev php-pear build-essential -y

# Install gRPC via PECL (takes a few minutes)
sudo pecl install grpc

# Enable the extension (replace 8.4 with your PHP version)
echo "extension=grpc.so" | sudo tee /etc/php/8.4/mods-available/grpc.ini
sudo phpenmod grpc

# Restart PHP-FPM
sudo systemctl restart php8.4-fpm

# Verify it's loaded
php -m | grep grpc
```

#### On the Mumble Server:

**Linux:**
Edit `/etc/mumble-server.ini`:
```ini
grpc="0.0.0.0:50051"
```
Then: `sudo systemctl restart mumble-server`

**Windows:**
Edit `murmur.ini`:
```ini
grpc="0.0.0.0:50051"
```
Restart the Mumble server.

Open firewall port 50051:
```powershell
New-NetFirewallRule -DisplayName "Mumble-gRPC" -Direction Inbound -Port 50051 -Protocol TCP -Action Allow
```

#### In SeAT:
1. Go to **Mumble > Settings**
2. Set **Driver** to **gRPC**
3. Set **gRPC Host** to your Mumble server address
4. Set **gRPC Port** to `50051`
5. Click **Save Settings**
6. Click **Test Connection**

---

### Alternative: REST Driver

Requires [Murmur-REST](https://github.com/alfg/murmur-rest) running on the Mumble server.

#### On the Mumble Server:

**Using Docker (Recommended):**
```bash
sudo docker run -d \
  --name murmur-rest \
  --restart unless-stopped \
  --network host \
  -e MURMUR_ICE="tcp -h 127.0.0.1 -p 6502" \
  alfg/murmur-rest
```

**Without Docker (Python 3.12):**
```bash
git clone https://github.com/alfg/murmur-rest.git
cd murmur-rest
python3 -m venv venv
source venv/bin/activate  # Windows: venv\Scripts\activate
pip install -r requirements.txt
python runserver.py
```

Open firewall port 8080.

#### In SeAT:
1. Go to **Mumble > Settings**
2. Set **Driver** to **REST**
3. Set **REST URL** to `http://your-mumble-server:8080`
4. Click **Save Settings** then **Test Connection**

---

### Alternative: ICE Driver

Requires the PHP Ice extension (complex to install on modern Ubuntu).

```bash
# Install ZeroC Ice (if available for your distro)
sudo apt install php-zeroc-ice

# Or via PECL
sudo pecl install ice
```

Enable in your Mumble server config:
```ini
ice="tcp -h 127.0.0.1 -p 6502"
```

---

## 🔐 Permissions & Ownership

SeAT requires the `storage` and `bootstrap/cache` directories to be writable by the web server. If you encounter "Permission Denied" errors, run:

```bash
sudo chown -R www-data:www-data /var/www/seat/storage
sudo chown -R www-data:www-data /var/www/seat/bootstrap/cache
sudo chmod -R 775 /var/www/seat/storage
sudo chmod -R 775 /var/www/seat/bootstrap/cache
```

**Pro Tip**: Always run artisan commands as the web user:
```bash
sudo -u www-data php artisan [command]
```

---

## 🔄 Synchronization

The plugin hooks into the SeAT scheduler automatically. To manually trigger a sync or monitor the process, use the following commands:

| Command | Description |
| :--- | :--- |
| `php artisan mumble:sync` | Queue a full synchronization of all users. |
| `php artisan mumble:sync --now` | Run the synchronization immediately in the foreground. |
| `php artisan mumble:sync-groups` | Update group memberships for all synced users. |
| `php artisan mumble:cleanup-guests` | Remove expired guest links and their Mumble accounts. |

---

## 🛠️ Troubleshooting

### Common Issues

| Issue | Solution |
|-------|----------|
| "Connection type field is required" | Clear route cache: `php artisan route:clear` |
| "Plugin not yet configured" | Save your settings in the admin panel |
| "cURL error 7: Failed to connect" | Check if the Mumble server/REST service is reachable |
| "ICE PHP extension not installed" | Use gRPC instead (easier to install) |
| "gRPC PHP extension not installed" | Run `sudo pecl install grpc` |

### Debug Commands

```bash
# Check PHP extensions
php -m | grep -E "(grpc|ice)"

# Check if settings are saved
sudo mysql seat -e "SELECT * FROM global_settings WHERE name LIKE 'mumble%';"

# View Laravel logs
tail -f /var/www/seat/storage/logs/laravel.log
```

---

## 📦 Publishing to Packagist

To make this plugin available via `composer require seat-plugins/mumble-connector`, follow these steps:

### 1. Push to GitHub
Packagist requires a public repository.
1. Create a public repo (e.g., `github.com/yourname/mumble-connector`).
2. Push your code:
   ```bash
   git init
   git remote add origin https://github.com/yourname/mumble-connector.git
   git add .
   git commit -m "Initial release"
   git push -u origin main
   ```

### 2. Submit to Packagist
1. Log in to [Packagist.org](https://packagist.org) (use GitHub login).
2. Click **Submit** and paste your GitHub URL.
3. Click **Check** then **Submit**.

### 3. Versioning
Tag a release to make it stable:
```bash
git tag v1.0.0
git push origin v1.0.0
```

---

## 📝 Support
For further assistance, please refer to the main repository documentation or join the SeAT community Discord.

*Built with ❤️ for the EVE Online community.*
