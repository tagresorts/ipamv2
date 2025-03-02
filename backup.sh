#!/bin/bash
# backup.sh
# This script creates a backup of the IPAM database using credentials stored in .env.
# The backup is saved in the "backup" folder in the project root.
# It retains only the last 5 backup files.

ENV_FILE=".env"

# Check if .env exists
if [ ! -f "$ENV_FILE" ]; then
  echo "Error: .env file not found!"
  exit 1
fi

# Extract credentials from .env
DB_HOST=$(grep "^DB_HOST=" "$ENV_FILE" | cut -d '=' -f2 | tr -d ' ')
DB_NAME=$(grep "^DB_NAME=" "$ENV_FILE" | cut -d '=' -f2 | tr -d ' ')
DB_USER=$(grep "^DB_USER=" "$ENV_FILE" | cut -d '=' -f2 | tr -d ' ')
DB_PASS=$(grep "^DB_PASS=" "$ENV_FILE" | cut -d '=' -f2 | tr -d ' ')

# Check if all credentials are extracted
if [ -z "$DB_HOST" ] || [ -z "$DB_NAME" ] || [ -z "$DB_USER" ] || [ -z "$DB_PASS" ]; then
  echo "Error: Missing credentials in .env file!"
  exit 1
fi

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

# Retain only the last 5 backup files
cd "$BACKUP_DIR"
backup_files=( $(ls -1tr ipam_backup_*.sql.gz 2>/dev/null) )
file_count=${#backup_files[@]}
if [ $file_count -gt 5 ]; then
  num_to_delete=$((file_count - 5))
  echo "Removing $num_to_delete old backup file(s)..."
  for ((i=0; i<num_to_delete; i++)); do
    rm "${backup_files[$i]}"
  done
fi
cd - > /dev/null
