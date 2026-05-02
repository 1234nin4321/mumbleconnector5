# SeAT Mumble Connector 5

A SeAT 5 compatible plugin for syncing users and roles to a Mumble voice server. This version is optimized for **Ubuntu 24.04** and **Docker** environments.

## Features

- ✅ **User Sync** - Automatically sync SeAT users to Mumble
- ✅ **Group Mapping** - Map SeAT roles, squads, corporations, and alliances to Mumble groups
- ✅ **Guest Links** - Generate temporary connection links for non-members
- ✅ **REST Bridge** - Lightweight Node.js bridge for direct SQLite database access
- ✅ **Admin Dashboard** - Full admin interface for configuration and monitoring
- ✅ **Sync Logs** - Complete audit trail of all sync operations

## Quick Start (Ubuntu 24.04 Docker)

For a detailed walkthrough, see [Ubuntu24.md](Ubuntu24.md).

1.  **Mount the plugin** in your SeAT `docker-compose.yml`:
    ```yaml
    volumes:
      - ./mumble-connector:/var/www/seat/packages/seat-plugins/mumble-connector
    ```
2.  **Install the bridge**:
    ```bash
    cd mumble-bridge
    docker-compose up -d
    ```
3.  **Configure in SeAT**:
    - REST URL: `http://mumble-bridge:8082`
    - API Key: (Get from `docker logs mumble-bridge`)

## Documentation

- [INSTALL.md](INSTALL.md) - Detailed manual installation steps.
- [Ubuntu24.md](Ubuntu24.md) - Specific guide for Ubuntu 24.04 and Docker.

## File Structure

```
mumbleconnector5/
├── src/                # PHP Plugin Source
├── mumble-bridge/      # Node.js SQLite Bridge (Dockerized)
├── composer.json       # PHP Package Definition
├── README.md           # This file
├── Ubuntu24.md         # OS-Specific Guide
└── INSTALL.md          # Manual Installation Guide
```

## License

This package is open-source software licensed under the [GPL-2.0 license](LICENSE).
