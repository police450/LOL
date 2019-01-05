<?php
if(!defined('IN_CLIPBUCKET'))
exit('Invalid access');

$db->Execute("DROP TABLE IF EXISTS ".tbl('paid_configs'));
$db->Execute("DROP TABLE IF EXISTS ".tbl('paid_orders'));
$db->Execute("DROP TABLE IF EXISTS ".tbl('paid_packages'));
$db->Execute("DROP TABLE IF EXISTS ".tbl('paid_subscriptions'));
$db->Execute("DROP TABLE IF EXISTS ".tbl('paid_transactions'));
$db->Execute("DROP TABLE IF EXISTS ".tbl('paid_agreements'));
$db->Execute("DROP TABLE IF EXISTS ".tbl('paid_billing_plan'));
$db->Execute("DROP TABLE IF EXISTS ".tbl('paid_payments_success'));
?>