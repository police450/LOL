<?php

include "../../includes/config.inc.php";

$post = $_POST;
$mode = $post['mode'];
switch ($mode) {
	case 'save_ad':{
		
   		/**
            * this section only to verify if banner is of valid size
         */
         if (isset($_FILES['ad_image']) && !empty($_FILES['ad_image']['name'])){

            $banner_details =  getimagesize($_FILES['ad_image']["tmp_name"]);
            $width = $banner_details[0];
            $height = $banner_details[1];

            if ($width != '728' || $height != '90'){
               $response['err'] = 'Please Upload banner of size 728x90 !';
               exit(json_encode($response));
            }
           
         }


   		$ad_data['ad_tag'] = $post['ad-code'];
   		$ad_data['ad_desc'] = $post['ad-desc'];
   		$ad_data['target_imp'] = $post['ad-t-imp'];

   		$start_datetime = $post['ad-start-datetime'];
   		$end_datetime = $post['ad-end-datetime'];
   		
   		$ad_data['start_date'] = strtotime($start_datetime);
   		$ad_data['end_date'] = strtotime($end_datetime);
   	
   		/**
   		* for now this is static , due to having only one type of ad 
   		*/
   		$ad_data['ad_type'] = $post["ad_type"];
		   $ad_data['linear_type'] = $post["linear_type"];
   		$ad_data['ad_status'] = $post['ad-status'];

         if ( is_array($post['category_id']) ){
            $category_id = $post['category_id'];
            $ad_data['category_id'] = implode(',',$category_id);
         }else if( empty($post['category_id']) ){
            $ad_data['category_id'] = $post['category_id'];
         }
         if ( is_array($post['country']) ){
            $country = $post['country'];
            $ad_data['country'] = implode(',',$country);
         }else if( empty($post['country']) ){
            $ad_data['country'] = $post['country'];
         }

   		$ad_data['ad_time'] = $post["ad_time"];
   		$ad_data['skippable'] = $post["skippable"];
   		$ad_data['skip_time'] = $post["skip-time"];
   		$ad_id = $CbUads->add_ultimate_ad($ad_data);
         

         if (isset($_FILES['ad_image']) && $ad_id){
            $file = $_FILES['ad_image'];
            $banner = $CbUads->update_non_linear_banner($file,$ad_id,true);
         }

   		if (!empty($ad_id)){
			   $reponse['msg'] = 'Your ad Has been added succesfully !';
            if($banner){
               $reponse['banner'] = $banner;
            }
		}else{
			$reponse['err'] = 'Something went wrong with adding Ad !';
		}

		echo json_encode($reponse);
   		
	}	
	break;

	case 'edit_ad':{

       /**
         * this section only to verify if banner is of valid size
      */
      if (isset($_FILES['ad_image']) && !empty($_FILES['ad_image']['name'])){

         $banner_details =  getimagesize($_FILES['ad_image']["tmp_name"]);
         $width = $banner_details[0];
         $height = $banner_details[1];

         if ($width != '728' || $height != '90'){
            $response['err'] = 'Please Upload banner of size 728x90 !';
            exit(json_encode($response));
         }
        
      }

		$ad_data['ad_id'] = $post['ad-id'];
	   $ad_data['ad_tag'] = $post['ad-code'];
		$ad_data['ad_desc'] = $post['ad-desc'];
		$ad_data['target_imp'] = $post['ad-t-imp'];

		$start_datetime = $post['ad-start-datetime'];
		$end_datetime = $post['ad-end-datetime'];
		
		$ad_data['start_date'] = strtotime($start_datetime);
		$ad_data['end_date'] = strtotime($end_datetime);
		
		$ad_data['ad_type'] = $post["ad_type"];
		$ad_data['linear_type'] = $post["linear_type"];
		$ad_data['ad_status'] = $post['ad-status'];

		if ( is_array($post['category_id']) ){
         $category_id = $post['category_id'];
         $ad_data['category_id'] = implode(',',$category_id);
      }else if( empty($post['category_id']) ){
         $ad_data['category_id'] = $post['category_id'];
      }
      if ( is_array($post['country']) ){
         $country = $post['country'];
         $ad_data['country'] = implode(',',$country);
      }else if( empty($post['country']) ){
         $ad_data['country'] = $post['country'];
      }
 
		$ad_data['ad_time'] = $post["ad_time"];
		$ad_data['skippable'] = $post["skippable"];
		$ad_data['skip_time'] = $post["skip-time"];
		$ad_data['ad_time'] = $post["ad_time"];
		
		$update = $CbUads->update_ultimate_ad($ad_data);
      if (isset($_FILES['ad_image']) && $ad_data['ad_id']){
         $file = $_FILES['ad_image'];
         $banner = $CbUads->update_non_linear_banner($file,$ad_data['ad_id'],true);
      }

		if ($update){
			$reponse['msg'] = 'Your ad Has been updated succesfully !';
         if ($banner){
            $reponse['banner'] = $banner;
         }
		}else{
			$reponse['err'] = 'Something went wrong with updating Ad !';
		}

		echo json_encode($reponse);
	}
	break;

	case 'update_imp':{
		$ad_id = $post['ad_id'];
		$updated = $CbUads->update_ad_impression($ad_id);
		if ($updated){
			$response['msg'] = "Ad Impression updated";
		}else{
			$response['msg'] = "Something went wrong in updating Impression";
		}

		echo json_encode($response);
	}
	break;

   case 'remove-banner':{
      $ad_id = $post['ad_id'];
      $removed = $CbUads->removeBAnner($ad_id);
      if ($removed){
         $response['msg'] = "Banner Removed SuccessFully";
      }else{
         $response['msg'] = "Something went wrong in removing Banner";
      }

      echo json_encode($response);
   }
   break;

	default:{
		echo json_encode(array("err"=>"Go Home ! "));
	}
	break;
}
?>