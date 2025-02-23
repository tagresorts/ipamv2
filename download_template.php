<?php
// download_template.php
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=ip_template.csv');
$output = fopen('php://output', 'w');

// Write CSV header row
fputcsv($output, ['ip_address', 'subnet', 'status', 'description', 'assigned_to', 'owner', 'type', 'location']);

fclose($output);
exit;
?>
