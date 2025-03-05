/*M!999999\- enable the sandbox mode */

-- Insert an extra dummy company (now total becomes at least 4)
INSERT INTO `companies` 
    (`company_name`, `enrollment_options`, `created_at`, `description`, `status`, `updated_at`) 
VALUES 
    ('Dummy Company 4', '', NOW(), 'A dummy company for sandbox testing', 'Active', NOW());

-- Insert dummy data for custom_fields (at least 3 records)
INSERT INTO `custom_fields` 
    (`ip_id`, `field_name`, `field_value`) 
VALUES 
    (5, 'OS', 'Ubuntu 20.04 LTS'),
    (6, 'OS', 'CentOS 7'),
    (5, 'Location', 'Data Center A');

-- Insert dummy data for history_log (at least 3 records)
INSERT INTO `history_log` 
    (`ip_id`, `user_id`, `action`, `timestamp`) 
VALUES 
    (5, 4, 'Assigned IP to new server', NOW()),
    (6, 6, 'Updated firewall settings', NOW()),
    (5, 7, 'Reviewed IP allocation', NOW());

-- Insert dummy data for ip_addresses (at least 3 records)
INSERT INTO `ip_addresses` 
    (`ip_address`, `subnet_id`, `status`, `assigned_to`, `owner`, `description`, `type`, `location`, `created_at`, `created_by`, `last_updated`, `company_id`) 
VALUES 
    ('10.0.0.1', 3, 'Assigned', 'Server1', 'IT Dept', 'Primary server IP', 'Server', 'Headquarters', NOW(), 'Admin', NOW(), 1),
    ('10.0.0.2', 3, 'Available', NULL, NULL, 'Reserved for backup', 'Server', 'Headquarters', NOW(), 'Admin', NOW(), 1),
    ('10.0.0.3', 3, 'Reserved', 'Backup Server', 'IT Dept', 'Backup IP address', 'Server', 'Headquarters', NOW(), 'Admin', NOW(), 1);

-- Insert an additional dummy record for ips (to reach at least 3 records)
INSERT INTO `ips` 
    (`ip_address`, `subnet_id`, `status`, `description`, `assigned_to`, `owner`, `created_by`, `expires_at`, `created_at`, `last_updated`, `type`, `location`, `company_id`) 
VALUES 
    ('10.10.10.10', 6, 'Available', 'Test IP for sandbox', 'DevOps', 'Sandbox', 7, DATE_ADD(NOW(), INTERVAL 30 DAY), NOW(), NOW(), 'Test', 'Sandbox', 1);
