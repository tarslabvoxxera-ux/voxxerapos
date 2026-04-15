<?php
$db = new mysqli('127.0.0.1', 'root', '', 'voxxera', 3306);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// 1. Enable Customer Rewards in App Config
$db->query("INSERT INTO ospos_app_config (`key`, `value`) VALUES ('customer_reward_enable', '1') ON DUPLICATE KEY UPDATE `value`='1'");

// 2. Insert or update default customer package to give 5% points
$db->query("INSERT INTO ospos_customers_packages (`package_name`, `points_percent`, `deleted`) VALUES ('Standard Rewards', 5, 0) ON DUPLICATE KEY UPDATE `points_percent`=5, `deleted`=0");

$package_id = $db->insert_id;
if (!$package_id) {
    $res = $db->query("SELECT package_id FROM ospos_customers_packages WHERE package_name = 'Standard Rewards' LIMIT 1");
    if ($row = $res->fetch_assoc()) {
        $package_id = $row['package_id'];
    }
}

// 3. Assign this package to all active customers who don't have a package
if ($package_id) {
    $db->query("UPDATE ospos_customers SET package_id = " . (int)$package_id . " WHERE deleted = 0 AND (package_id IS NULL OR package_id = 0)");
}

echo "Reward points configured successfully.\n";
$db->close();
