#!/bin/bash
# =============================================================================
# GreenPilot - VPS Provisioning Script
# Run as root on a fresh Ubuntu 22.04 server
# Usage: bash provision.sh
# =============================================================================

set -euo pipefail

echo "=== GreenPilot VPS Provisioning ==="

# 1. Update system
echo "[1/6] Updating system..."
apt update && apt upgrade -y

# 2. Install Docker
echo "[2/6] Installing Docker..."
if ! command -v docker &> /dev/null; then
    curl -fsSL https://get.docker.com | sh
else
    echo "Docker already installed."
fi

# 3. Create deploy user
echo "[3/6] Creating deploy user..."
if ! id "deploy" &>/dev/null; then
    adduser --disabled-password --gecos "Deploy" deploy
    usermod -aG sudo deploy
    usermod -aG docker deploy
    echo "deploy ALL=(ALL) NOPASSWD:ALL" > /etc/sudoers.d/deploy
    # Copy SSH keys if they exist
    mkdir -p /home/deploy/.ssh
    cp /root/.ssh/authorized_keys /home/deploy/.ssh/ 2>/dev/null || true
    chown -R deploy:deploy /home/deploy/.ssh
    chmod 700 /home/deploy/.ssh
    echo "User 'deploy' created."
else
    echo "User 'deploy' already exists."
fi

# 4. Configure UFW firewall
echo "[4/6] Configuring firewall..."
apt install -y ufw
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable
echo "Firewall configured."

# 5. Create project directories
echo "[5/6] Creating directories..."
mkdir -p /opt/greenpilot
mkdir -p /opt/backups
chown deploy:deploy /opt/greenpilot
chown deploy:deploy /opt/backups

# 6. Setup backup cron
echo "[6/6] Setting up backup cron..."
CRON_JOB="0 3 * * * /opt/greenpilot/scripts/backup.sh >> /var/log/greenpilot-backup.log 2>&1"
(crontab -l 2>/dev/null | grep -v "greenpilot-backup" ; echo "$CRON_JOB") | crontab -
echo "Backup cron configured (daily at 3 AM)."

echo ""
echo "=== Provisioning complete ==="
echo "Next steps:"
echo "  1. Copy project files to /opt/greenpilot/"
echo "  2. Create .env from .env.production template"
echo "  3. docker compose -f docker-compose.prod.yml build"
echo "  4. docker compose -f docker-compose.prod.yml up -d"
echo "  5. docker exec greenpilot-app php artisan migrate --seed"
echo "  6. docker exec greenpilot-app php artisan key:generate"
