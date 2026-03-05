#!/bin/bash
# =============================================================================
# GreenPilot - Backup PostgreSQL + storage
# Usage: ./scripts/backup.sh
# Cron: 0 3 * * * /opt/greenpilot/scripts/backup.sh >> /var/log/greenpilot-backup.log 2>&1
# =============================================================================

set -euo pipefail

BACKUP_DIR="/opt/backups"
RETENTION_DAYS=7
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
DB_CONTAINER="greenpilot-postgres"

echo "[$(date)] Starting backup..."

# Create backup directory
mkdir -p "$BACKUP_DIR"

# PostgreSQL dump
echo "[$(date)] Dumping PostgreSQL..."
docker exec "$DB_CONTAINER" pg_dump -U ecogarage -Fc ecogarage > "$BACKUP_DIR/db_$TIMESTAMP.dump"

# Storage files (uploads)
echo "[$(date)] Backing up storage..."
tar -czf "$BACKUP_DIR/storage_$TIMESTAMP.tar.gz" -C /opt/greenpilot storage/app/ 2>/dev/null || true

# Rotate old backups
echo "[$(date)] Rotating backups older than $RETENTION_DAYS days..."
find "$BACKUP_DIR" -name "db_*.dump" -mtime +$RETENTION_DAYS -delete
find "$BACKUP_DIR" -name "storage_*.tar.gz" -mtime +$RETENTION_DAYS -delete

echo "[$(date)] Backup complete: db_$TIMESTAMP.dump"
