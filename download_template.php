<?php
// download_template.php
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=ip_template.csv');

$output = fopen('php://output', 'w');

// Write CSV header row with the company_id column
fputcsv($output, [
    'ip_address',
    'subnet',
    'status',
    'description',
    'assigned_to',
    'owner',
    'type',
    'location',
    'company_id'
]);

// Write dummy records as examples
fputcsv($output, [
    '192.168.1.10',
    '192.168.1.0/24',
    'Assigned',
    'Server 1',
    'John Doe',
    'IT Dept',
    'Server',
    'Data Center',
    '1'
]);
fputcsv($output, [
    '192.168.1.11',
    '192.168.1.0/24',
    'Available',
    'Workstation A',
    'Jane Doe',
    'HR',
    'Workstation',
    'Office',
    '1'
]);
fclose($output);
exit;
?>
