<?php
require_once '../includes/admin_config.php';
$userquery->admin_login_check();
$pages->page_redir();
$userquery->login_check('admin_access');

if(!defined('MAIN_PAGE')){
    define('MAIN_PAGE', 'CB HTML5 Player Settings');
}
if(!defined('SUB_PAGE')){
    define('SUB_PAGE', 'Player Settings');
}

global $db;

assign('mode',$_GET['mode']);



//This is to update player control's logo 
if(isset($_POST['logo']))
{
    $logoFile = $_FILES['logo'];
    if($logoFile['name'])
    {
        $ext = getext($logoFile['name']);
        if($ext!='png')
            e("Please select PNG l file");
        else{
            $existedFile = BASEDIR.'/images/icons/country/hp-cb.png';
            if(file_exists($existedFile))
                unlink($existedFile);
            move_uploaded_file($logoFile['tmp_name'],$existedFile);
             e('Your html5 Player on <strong>controls logo</strong> has been changed','m');
        }
    }


    $logoFile = $_FILES['image'];
    if($logoFile['name'])
    {
        $ext = getext($logoFile['name']);
        if($ext!='png')
            e("Please select PNG file");
        else{
            $existedFile = BASEDIR.'/images/icons/country/ov.png';
            if(file_exists($existedFile))
                unlink($existedFile);
            move_uploaded_file($logoFile['tmp_name'],$existedFile);
             e('Your html5 Player <strong>on video logo</strong> has been changed','m');
        }
    }
}





if(isset($_POST['change']))
{
    $configs = $Cbucket->configs;
    $rows = array('logo_placement','player_logo_url');
    //Checking for logo 
    foreach($rows as $field)
    {
        $value = mysql_clean($_POST[$field]);
        $myquery->Set_Website_Details($field,$value);
    }
    e("Player <strong>Logo Settings</strong> Has Been Updated",'m');
}


if(isset($_GET['enable']))
{
    $logo_enable_val = $_GET['enable'];
    $db->update(tbl("config_html5"),
          array("value"),
          array($logo_enable_val), "name = 'iv_logo_enable'");
}



assign('l_logo', BASEURL.'/images/icons/country/hp-cb.png');
$existedFile = BASEDIR.'/images/icons/country/ov.png';
if(file_exists($existedFile)){
    $in_video_logo = BASEURL.'/images/icons/country/ov.png';
    assign('ov_logo',true);
    assign('ov_logo', $in_video_logo);    
}

$query = "SELECT value FROM ".tbl("config_html5")." WHERE name='iv_logo_enable'";
$iv_logo_enable = $db->_select($query);
assign('logo_enbale',$iv_logo_enable[0]['value']);





subtitle("Manage Players");
template_files('cb_html5_settings.html',CB_HTML5_PLUG_DIR.'/admin');

?>