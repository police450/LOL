<?php
/* 
 ***************************************************************
 | Copyright (c) 2007-2010 Clip-Bucket.com. All rights reserved.
 | @ Author 	: ArslanHassan									
 | @ Software 	: ClipBucket , © PHPBucket.com					
 ***************************************************************
*/

if(!defined('MAIN_PAGE')){
    define('MAIN_PAGE', 'CB Mass Embedder');
}
if(!defined('SUB_PAGE')){
    define('SUB_PAGE', 'Mass Embed Help');
}

define("THIS_PAGE","cb_mass_embed_help");


template_files('cb_mass_embed_help.html',PLUG_DIR.'/'.CB_MASS_EMBED_LOC.'/admin');

?>