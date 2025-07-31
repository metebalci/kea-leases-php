<?php
// === Configuration ===
$leaseFileV4 = '/var/lib/kea/kea-leases4.csv';
$leaseFileV6 = '/var/lib/kea/kea-leases6.csv';
date_default_timezone_set("Europe/Zurich");

// === Functions ===
function parseLeases($file, $isV6 = false) {
    if (!file_exists($file)) return [];

    $rows = [];
    $headers = [];

    if (($handle = fopen($file, "r")) !== FALSE) {
        while (($data = fgetcsv($handle)) !== FALSE) {
            if (empty($data) || str_starts_with($data[0], "#")) continue;

            if (empty($headers)) {
                $headers = $data;
                continue;
            }

            $row = array_combine($headers, $data);
            if (!$row) continue;

            if (!isset($row['expire']) || !is_numeric($row['expire']) || $row['expire'] <= time()) continue;

            if ($isV6) {
                $key = !empty($row['hwaddr']) ? $row['hwaddr'] : ($row['duid'] . '_' . $row['iaid']);
            } else {
                $key = $row['hwaddr'];
            }

            $rows[$key] = $row; // last seen wins
        }
        fclose($handle);
    }

    return $rows;
}

function formatTime($timestamp) {
    if (!$timestamp || !is_numeric($timestamp)) return "-";
    $dt = new DateTime("@$timestamp");  // UTC
    $dt->setTimezone(new DateTimeZone(date_default_timezone_get()));
    return $dt->format("Y-m-d H:i:s");
}

function sortByIPv4($a, $b) {
    return ip2long($a['address']) <=> ip2long($b['address']);
}

function sortByIPv6($a, $b) {
    $aBin = inet_pton($a['address']);
    $bBin = inet_pton($b['address']);
    return strcmp($aBin, $bBin);
}

// === Parse and sort ===
$leases4 = array_values(parseLeases($leaseFileV4, false));
$leases6 = array_values(parseLeases($leaseFileV6, true));

usort($leases4, 'sortByIPv4');
usort($leases6, 'sortByIPv6');
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Kea Active Leases</title>
    <style>
        body {
            font-family: monospace; /* Fixed-width font */
            margin: 20px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 40px;
            font-size: 14px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 6px 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        h2 {
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <h1>Kea DHCP Active Leases</h1>

    <h2>IPv4 Leases</h2>
    <table>
        <thead>
            <tr>
                <th>IP Address</th>
                <th>MAC</th>
                <th>Hostname</th>
                <th>Expire</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($leases4 as $lease): ?>
            <tr>
                <td><?= htmlspecialchars($lease['address']) ?></td>
                <td><?= htmlspecialchars($lease['hwaddr']) ?></td>
                <td><?= htmlspecialchars($lease['hostname']) ?></td>
                <td><?= formatTime($lease['expire']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>IPv6 Leases</h2>
    <table>
        <thead>
            <tr>
                <th>IP Address</th>
                <th>MAC / DUID+IAID</th>
                <th>Hostname</th>
                <th>Expire</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($leases6 as $lease): ?>
            <tr>
                <td><?= htmlspecialchars($lease['address']) ?></td>
                <td><?= htmlspecialchars(!empty($lease['hwaddr']) ? $lease['hwaddr'] : ($lease['duid'] . ' / ' . $lease['iaid'])) ?></td>
                <td><?= htmlspecialchars($lease['hostname']) ?></td>
                <td><?= formatTime($lease['expire']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
