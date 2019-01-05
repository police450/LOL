<?php

/*$db->Execute("ALTER TABLE ".tbl("video")." DROP `server_ip` ");
$db->Execute("ALTER TABLE ".tbl("video")." DROP `file_server_path` ");
$db->Execute("ALTER TABLE ".tbl("video")." DROP `files_thumbs_path` ");
$db->Execute("ALTER TABLE ".tbl("video")." DROP `file_thumbs_count` ");
$db->Execute("ALTER TABLE ".tbl("video")." DROP `has_hq` ");
$db->Execute("ALTER TABLE ".tbl("video")." DROP `filegrp_size` ");
$db->Execute("ALTER TABLE ".tbl("video")." DROP `process_status` ");
$db->Execute("ALTER TABLE ".tbl("video")." DROP `has_hd` ");*/

$db->Execute('DROP TABLE'.tbl("servers")) ;

?>