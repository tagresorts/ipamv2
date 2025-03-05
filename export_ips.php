<?php
session_start();
include 'config.php';

// Restrict export to logged-in users
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

// Build query (same as in dashboard.php)
$sql = "SELECT 
           ips.ip_address,
           subnets.subnet,
           ips.status,
           ips.assigned_to,
           ips.owner,
           ips.description,
           ips.type,
           ips.location,
           ips.created_at,
           u.username AS created_by_username,
           ips.last_updated,
           (
             SELECT GROUP_CONCAT(CONCAT(custom_fields.field_name, ': ', custom_fields.field_value) SEPARATOR '; ')
             FROM custom_fields
             WHERE custom_fields.ip_id = ips.id
           ) AS custom_fields
        FROM ips
        LEFT JOIN subnets ON ips.subnet_id = subnets.id
        LEFT JOIN users u ON ips.created_by = u.id
        WHERE 1=1 ";
$params = [];

if (!empty($search)) {
    $sql .= " AND (ips.ip_address LIKE ? OR ips.assigned_to LIKE ? OR ips.owner LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status)) {
    $sql .= " AND ips.status = ? ";
    $params[] = $status;
}

$sql .= " ORDER BY ips.ip_address ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ips = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=ips_export.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['IP Address', 'Subnet', 'Status', 'Assigned To', 'Owner', 'Description', 'Type', 'Location', 'Created At', 'Created by', 'Last Updated', 'Custom Fields']);

foreach ($ips as $ip) {
    fputcsv($output, [
        $ip['ip_address'],
        $ip['subnet'] ?? 'N/A',
        $ip['status'],
        $ip['assigned_to'],
        $ip['owner'],
        $ip['description'],
        $ip['type'],
        $ip['location'],
        $ip['created_at'],
        $ip['created_by_username'] ?? 'N/A',
        $ip['last_updated'],
        $ip['custom_fields'] ?? ''
    ]);
}
fclose($output);
exit;
