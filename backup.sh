#!/bin/bash
# backup.sh
# This script creates a backup of the IPAM database using credentials stored in config.php.
# The backup is saved in the "backup" folder in the project root.
# It retains only the last 5 backup files.

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
