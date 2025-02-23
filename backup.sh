#!/bin/bash
# backup.sh
# This script creates a backup of the IPAM database using credentials stored in config.php.
# The backup is saved in the "backup" folder in the project root.

CONFIG_FILE="config.php"

# Check if config.php exists
if [ ! -f "$CONFIG_FILE" ]; then
  echo "Error: config.php not found!"
  exit 1
fi

# Extract credentials from config.php using grep and cut
DB_HOST=$(grep "^\$db_host" "$CONFIG_FILE" | cut -d"'" -f2)
DB_NAME=$(grep "^\$db_name" "$CONFIG_FILE" | cut -d"'" -f2)
DB_USER=$(grep "^\$db_user" "$CONFIG_FILE" | cut -d"'" -f2)
DB_PASS=$(grep "^\$db_pass" "$CONFIG_FILE" | cut -d"'" -f2)

# Set the backup directory inside the project folder
BACKUP_DIR="./backup"
TIMESTAMP=$(date +"%F_%T")

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

echo "Backing up database '$DB_NAME' on host '$DB_HOST' with user '$DB_USER'..."

# Dump the database and compress the output
mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_DIR/ipam_backup_$TIMESTAMP.sql"
gzip "$BACKUP_DIR/ipam_backup_$TIMESTAMP.sql"

echo "Backup saved to $BACKUP_DIR/ipam_backup_${TIMESTAMP}.sql.gz"
