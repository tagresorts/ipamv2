#!/bin/bash

REPO_URL="https://github.com/tagresorts/ipam"
BRANCH="main"
CONFIG_FILE="config.php"
SCHEMA_FILE="ipam-schema.sql"  # Fixed schema file

echo "Starting IPAM setup..."

# Pull the latest code from GitHub
if [ -d ".git" ]; then
    echo "Pulling latest code from $BRANCH branch..."
    git pull origin $BRANCH
else
    echo "Git repository not found. Cloning..."
    git clone -b $BRANCH $REPO_URL .
fi

# Prompt for database details
read -p "Enter database host (default: localhost): " DB_HOST
read -p "Enter database name: " DB_NAME
read -p "Enter MySQL/MariaDB username: " DB_USER
read -s -p "Enter password: " DB_PASS
echo ""

# Set default values if user input is empty
DB_HOST=${DB_HOST:-localhost}

# Check if MariaDB is running
if ! systemctl is-active --quiet mariadb; then
    echo "MariaDB is not running. Starting it..."
    sudo systemctl start mariadb
fi

# Create the database if it doesn't exist
echo "Creating database if not exists..."
mysql -u"$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME;"

# Apply the schema if the file exists
if [ -f "$SCHEMA_FILE" ]; then
    echo "Applying schema from $SCHEMA_FILE..."
    mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$SCHEMA_FILE"
else
    echo "Schema file $SCHEMA_FILE not found!"
    exit 1  # Exit with error if the schema file is missing
fi

# Update config.php with new database details
echo "Updating $CONFIG_FILE with new database credentials..."
cat > "$CONFIG_FILE" <<EOL
<?php
\$db_host = '$DB_HOST';
\$db_name = '$DB_NAME';
\$db_user = '$DB_USER';
\$db_pass = '$DB_PASS';

try {
  \$pdo = new PDO("mysql:host=\$db_host;dbname=\$db_name", \$db_user, \$db_pass);
  \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException \$e) {
  die("Database connection failed: " . \$e->getMessage());
}
?>
EOL

echo "Database setup complete! config.php has been updated."
