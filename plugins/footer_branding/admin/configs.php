<?php

$licence_key = $_POST['license_key'];


if (isset($_POST['brand_update']))
{
    global $db;
    $db->update(tbl("brand_configs"),array("value"),array($licence_key),"config_id=1") ;
    e(lang("your key has been updated"),"m");
    
}


template_files('configs.html',BRAND_REMOVAL_DIR.'/admin');

//$allow_user_access = $paidSub->configs[ 'allow_user_set_premium' ];

?>