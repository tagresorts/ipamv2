# IP Management System

A lightweight web-based IP address management system that allows you to track, add, edit, and bulk upload IP addresses and subnets. This system supports custom fields, dynamic dropdown options (managed via a JSON file), filtering, exporting, printing, and column toggling. It’s built using PHP, MariaDB, and CSS.

## Features

- **User Authentication:**  
  Secure login with session management.

- **Dashboard:**  
  View a list of IP addresses with details including IP, subnet, status, assigned user, owner, description, hardware type, deployment location, creation details, last update, and custom fields.

- **Filtering & Sorting:**  
  Filter IP addresses by search keywords and status.

- **Bulk Upload:**  
  Upload multiple IP addresses via CSV.  
  - Download a CSV template to ensure correct formatting.

- **Export & Print:**  
  Export filtered results as CSV and print the current view with a custom header.

- **Column Toggle:**  
  Toggle columns (e.g., Created At, Created by, Last Updated, Custom Fields) via a user-friendly modal.

- **Custom Fields:**  
  Attach flexible metadata to IP addresses.

- **Dropdown Options Manager:**  
  Manage hardware type and deployment location options stored in a JSON file via a table-based editor. Options are stored in a subfolder (`data/dropdown_options.json`).

- **Subnet Management:**  
  Add, edit, and (when not in use) delete subnets with usage tracking.

## Setup

A setup script is provided to simplify installation and configuration.

1. **Run the Setup Script:**  
   The `setup.sh` script clones (or pulls) the latest code from GitHub, prompts for your database credentials, creates the database if it doesn't exist, applies the schema from `ipam-schema.sql`, and updates `config.php` with your settings.
   
   To run the setup:
   ```bash
   chmod +x setup.sh
   ./setup.sh
