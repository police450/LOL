<?php
if(!defined('IN_CLIPBUCKET'))
exit('Invalid access');

$db->Execute("DROP TABLE IF EXISTS ".tbl('revsharing_requests'));
// $db->Execute("DROP TABLE IF EXISTS ".tbl('revsharing_rpm'));
// $db->Execute("DROP TABLE IF EXISTS ".tbl('revsharing_users'));
// $db->Execute("DROP TABLE IF EXISTS ".tbl('revsharing_earnings'));
// $db->Execute("DROP TABLE IF EXISTS ".tbl('revsharing_payments'));

$db->Execute("DELETE FROM ".tbl('config')." WHERE name='rev_view_per_matrix'");
$db->Execute("DELETE FROM ".tbl('config')." WHERE name='rev_currency'");
$db->Execute("DELETE FROM ".tbl('config')." WHERE name='rev_test_mode'");
$db->Execute("DELETE FROM ".tbl('config')." WHERE name='rev_paypal_email'");
$db->Execute("DELETE FROM ".tbl('config')." WHERE name='rev_paypal_sandbox_email'");
$db->Execute("DELETE FROM ".tbl('config')." WHERE name='rev_paypal_client_id'");
$db->Execute("DELETE FROM ".tbl('config')." WHERE name='rev_paypal_secret'");
$db->Execute("DELETE FROM ".tbl('config')." WHERE name='rev_paypal_rest_api'");
$revshare = new revSharing();
$revshare->flush_mongo_views();


?>