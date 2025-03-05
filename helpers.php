<?php
// helpers.php

/**
 * Retrieves an array of company IDs associated with the given user.
 */
function getUserCompanyIds($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT company_id FROM user_companies WHERE user_id = ?");
    $stmt->execute([$userId]);
    $companyIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return empty($companyIds) ? [0] : $companyIds;
}

/**
 * Retrieves the list of companies for the user (for dropdown filtering).
 */
function getUserCompanies($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT c.company_id, c.company_name
                           FROM companies c
                           INNER JOIN user_companies uc ON c.company_id = uc.company_id
                           WHERE uc.user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Builds the common WHERE clause for filtering IPs based on search and company filter.
 * Returns an array with the SQL fragment and parameters.
 */
function buildFilterClause($search, $companyFilter, $companyIds) {
    $whereClause = " WHERE 1=1";
    $params = [];
    if (!empty($search)) {
        $whereClause .= " AND (ips.ip_address LIKE ? OR ips.assigned_to LIKE ? OR ips.owner LIKE ? OR ips.description LIKE ? OR ips.location LIKE ? OR ips.type LIKE ?)";
        $params = array_merge($params, array_fill(0, 6, "%$search%"));
    }
    if (!empty($companyFilter)) {
        $whereClause .= " AND ips.company_id = ?";
        $params[] = $companyFilter;
    } else {
        $placeholders = implode(',', array_fill(0, count($companyIds), '?'));
        $whereClause .= " AND ips.company_id IN ($placeholders)";
        $params = array_merge($params, $companyIds);
    }
    return [$whereClause, $params];
}

/**
 * Returns the total count of IPs based on the filter.
 */
function getTotalIPCount($pdo, $whereClause, $params) {
    $sql = "SELECT COUNT(*) as total FROM ips" . $whereClause;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

/**
 * Retrieves the IP list with sorting and pagination.
 * Returns an array: [IP records, total pages, current page, total IP count].
 */
function getIPList($pdo, $whereClause, $params, $allowedSortColumns, $defaultSort = 'ip_address', $perPage = 10, $currentPage = 1) {
    $sort = in_array($_GET['sort'] ?? '', $allowedSortColumns) ? $_GET['sort'] : $defaultSort;
    $direction = (isset($_GET['direction']) && $_GET['direction'] === 'DESC') ? 'DESC' : 'ASC';
    $sql = "SELECT
               ips.id,
               ips.ip_address,
               ips.status,
               ips.assigned_to,
               ips.owner,
               ips.description,
               ips.type,
               ips.location,
               ips.created_at,
               ips.last_updated,
               subnets.subnet,
               u.username AS created_by_username,
               c.company_name,
               (
                 SELECT GROUP_CONCAT(CONCAT(custom_fields.field_name, ': ', custom_fields.field_value) SEPARATOR '; ')
                 FROM custom_fields
                 WHERE custom_fields.ip_id = ips.id
               ) AS custom_fields
            FROM ips
            LEFT JOIN subnets ON ips.subnet_id = subnets.id
            LEFT JOIN users u ON ips.created_by = u.id
            LEFT JOIN companies c ON ips.company_id = c.company_id" . $whereClause;
    if ($sort === 'ip_address') {
        $sql .= " ORDER BY INET_ATON(ips.ip_address) $direction";
    } else {
        $sql .= " ORDER BY $sort $direction";
    }
    // Calculate total items for pagination
    $countSql = "SELECT COUNT(*) as total FROM (" . $sql . ") AS countTable";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalItems = $countStmt->fetchColumn();
    $totalPages = ceil($totalItems / $perPage);
    $offset = ($currentPage - 1) * $perPage;
    $sql .= " LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $ips = $stmt->fetchAll();
    return [$ips, $totalPages, $currentPage, $totalItems];
}

/**
 * Executes a chart query and returns the data formatted for Chart.js.
 */
function fetchChartData($pdo, $baseQuery, $params) {
    $stmt = $pdo->prepare($baseQuery);
    $stmt->execute($params);
    $labels = [];
    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Use known keys: 'type', 'company', 'location', or 'subnet'
        if (isset($row['type'])) {
            $labels[] = $row['type'] ? $row['type'] : 'N/A';
        } elseif (isset($row['company'])) {
            $labels[] = $row['company'] ? $row['company'] : 'N/A';
        } elseif (isset($row['location'])) {
            $labels[] = $row['location'] ? $row['location'] : 'N/A';
        } elseif (isset($row['subnet'])) {
            $labels[] = $row['subnet'];
        } elseif (isset($row['label'])) {
            $labels[] = $row['label'];
        }
        $data[] = (int)$row['count'];
    }
    return ['labels' => $labels, 'datasets' => [[
        'label' => '',
        'data' => $data,
        'backgroundColor' => [
            'rgba(75, 192, 192, 0.6)',
            'rgba(255, 205, 86, 0.6)',
            'rgba(54, 162, 235, 0.6)',
            'rgba(255, 99, 132, 0.6)',
            'rgba(153, 102, 255, 0.6)',
            'rgba(255, 159, 64, 0.6)'
        ]
    ]]];
}
