<?php
/*
Plugin Name: SuperSlider-Media-Pop
Description: Soda pop for your media. Adds numerous image related controls and options to your media admin panels. Adds image sizes to the image link buttons and insert image in the insert image screen.
Plugin URI: http://superslider.daivmowbray.com/superslider/superslider-media-pop/
Author: Daiv
Author URI: http://www.daivmowbray.com
Version: 2.2
*/

if (!class_exists("ssMediaPop")) {
	class ssMediaPop {
	
/*
Edit the following variables to set basic options
*/
	var $ss_set_jpeg_quality = 1; // set to 0 to turn jpeg quality control off

/*
edit to here
*/

    	/**
		* @var names used in this class.
		*/
	var $defaultAdminOptions;
	var $AdminOptionsName = 'ssMediaPop_options';
	var $ssMediaPopOpOut;
	var $plugin_name = 'superslider-media-pop';
	
    	/**
		*     PHP 4 Constructor
		*/
    function ssMediaPop() {
			
		ssMediaPop::mediaPop();

		}
				
		/**
		*     PHP 5 Constructor
		*/		
	function __construct(){
		
		self::mediaPop();
	
	    }
 		/**
		*     PHP 5 Constructor
		*/		
	function mediaPop(){  
			add_action ( 'admin_init', array(&$this,'init_media_pop'));
			add_action ( 'admin_init', array(&$this,'media_pop_create_media_page'));
			add_image_size( 'feature', 920, 240 );
			add_action ( 'admin_init', array(&$this,'Pop_create_media_page') );
	    }
    function init_media_pop() {
        $user_media = true;
        global $ss_set_jpeg_quality;
        
        if (strpos($_SERVER["REQUEST_URI"], "wp-admin/plugins.php") !== FALSE) {
        	add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array(&$this,'filter_plugin_link'), 10, 2 );
        }
      
        add_theme_support( 'post-thumbnails', array( 'post', 'page' ) ); 
		
		/* This is to load the meta box in the sidebar on media edit page only */
		
		if ( current_user_can('edit_posts') && current_user_can('edit_pages')){
				if( function_exists( 'add_meta_box' )) {
					add_thickbox();
					add_meta_box( 'ss_pop', __( 'Available sizes', $this->plugin_name ), array(&$this,'image_sizebox'), 'attachment', 'side', 'low');					
					
					add_action( 'add_meta_boxes', array(&$this,'attached_media_metabox') );

				}
		}
		
		add_action( 'load-post.php', array(&$this,'media_popup_init') );
		add_action( 'load-post-new.php', array(&$this,'media_popup_init') );
		
		add_filter( 'image_size_names_choose', array(&$this,'image_size_choose') );
		
  		if (strpos($_SERVER["REQUEST_URI"], "wp-admin/edit.php") !== FALSE) {
			add_filter( 'manage_posts_columns', array(&$this,'ss_add_post_page_columns') );
			add_filter( 'manage_pages_columns', array(&$this,'ss_add_post_page_columns') );
			
			add_action( 'manage_posts_custom_column', array(&$this,'ss_add_id_value'), 10, 2);
			add_action( 'manage_posts_custom_column', array(&$this,'ss_add_thumb_value'), 2, 2 );
			add_action( 'manage_posts_custom_column', array(&$this,'ss_add_attachment_value'), 10, 2);// creates the attachment list on the post list screen
	
			add_action( 'manage_pages_custom_column', array(&$this,'ss_add_id_value'), 10, 2);
			add_action( 'manage_pages_custom_column', array(&$this,'ss_add_thumb_value'), 2, 2 );	
			add_action( 'manage_pages_custom_column', array(&$this,'ss_add_attachment_value'), 10, 2);
        }
		if (strpos($_SERVER["REQUEST_URI"], "wp-admin/upload.php") !== FALSE) {
			add_filter( 'manage_media_columns', array(&$this,'ss_add_media_columns') );
			add_action( 'manage_media_custom_column', array(&$this,'ss_add_media_values'), 10, 2);
			
			add_filter( 'manage_upload_sortable_columns', array(&$this,'ss_column_sortable'), 10, 1 );
			add_action( 'admin_head', array(&$this,'ss_media_column_css'));
			add_filter( 'post_mime_types', array(&$this,'custom_mime_types' ));		
			add_filter( 'media_row_actions', array(&$this,'ss_pop_link'), 10, 2);		
		} 
       if (strpos($_SERVER["REQUEST_URI"], "wp-admin/options-media.php") !== FALSE) {
        add_action('admin_head', array(&$this,'ss_options_css'));
        
       }
      if (strpos($_SERVER["REQUEST_URI"], "wp-admin/edit.php") !== FALSE || strpos($_SERVER["REQUEST_URI"], "wp-admin/media-upload.php") !== FALSE || strpos($_SERVER["REQUEST_URI"], "wp-admin/edit-pages.php") !== FALSE)  {
         add_action('admin_head', array(&$this,'ss_media_column_css'));
         wp_enqueue_script( 'post' );
      }
     
      if ( $ss_set_jpeg_quality == 1) {
            add_filter( 'wp_editor_set_quality', array(&$this,'ss_pop_jpeg_quality'), 10, 2 );
      }
    
    }
 	/*
	* This is to set the default media panel image linkto option
	*/
	
	function media_popup_init() {
		wp_enqueue_script( 'ss-media-manager', plugins_url( 'js/set_default_media_panel.js', __FILE__ ), array( 'media-editor' ) );
		$poparams = array(
		  'align' => get_option ('ss_image_default_align'),//left, center, right, none
		  'link' => get_option ('ss_image_default_link_type'),// custom, file, post, none
		  'size' => get_option ('ss_image_default_size') //thumbnail, medium, large, full, post-thumbnail
		);
		wp_localize_script( 'ss-media-manager', 'poparams', $poparams );
	}
	/*
	* This is to create the attached metabox on the post screen
	*/
 	function attached_media_metabox() {
		$screens = array( 'post', 'page' ); //add more in here as you see fit
		foreach ($screens as $screen) {
			add_meta_box( 'attached_images_meta_box', __('Attached Files', $this->plugin_name), array(&$this,'attached_media_meta_content'), $screen, 'side' );
		}
	}
	/*
	* This is to create the attached metabox content
	*/

	function attached_media_meta_content($post){
		$attachments = get_children(array('post_parent'=>$post->ID));
		$count = count($attachments);
		add_thickbox();
		$html = '<a style="float:left;" href="#" class="button insert-media add_media" data-editor="content">';
		$html .= $count. __(' Files', $this->plugin_name).'</a>';
		
		foreach ($attachments as $att) {
				$html .= '<div style="float:left; padding: 2px; margin: 0 2px 5px; border: 1px solid #DFDFDF;">';
				$html .= '<a href="'.$att->guid.' " title="'.$att->post_title.'" rel="attached" class="thickbox">';// $att->guid &width=600&height=400
				$html .= wp_get_attachment_image( $att->ID, array(30, 30), true, array("class"=>"pinkynail") );
				$html .= '</a></div>' ;
		}             
		$html .= '<br style="clear:both;" />';
		echo $html;
	}
 	/*
 	* This adds the view pop over to the media page quick edit actions
 	*/
	function ss_pop_link($actions, $post) {
		add_thickbox();
		$the_file =  $post->guid;
		$type = $post->post_mime_type;
		if (strpos($type,'image') === false)
        	return $actions;	

		// adding the Action to the Quick Edit row
		$actions['ss_view_pop'] = '<a href="'.$the_file.'" class="thickbox" rel="pop-gallery">'.__('View Pop', $this->plugin_name ).'</a>';
			return $actions;    
	}
 
	 /*
	  *By default only three filters Ð Images, Video, and Audio Ð are supported in the WordPress Media Library. 
	  * this adds more media types
	  */
	  function custom_mime_types( $post_mime_types ) {
			$post_mime_types['application/msword'] = array( __( 'DOCs' ), __( 'Manage DOCs' ), _n_noop( 'DOC <span class="count">(%s)</span>', 'DOC <span class="count">(%s)</span>' ) );
			$post_mime_types['application/vnd.ms-excel'] = array( __( 'XLSs' ), __( 'Manage XLSs' ), _n_noop( 'XLS <span class="count">(%s)</span>', 'XLSs <span class="count">(%s)</span>' ) );
			$post_mime_types['application/pdf'] = array( __( 'PDFs' ), __( 'Manage PDFs' ), _n_noop( 'PDF <span class="count">(%s)</span>', 'PDFs <span class="count">(%s)</span>' ) );
			$post_mime_types['application/zip'] = array( __( 'ZIPs' ), __( 'Manage ZIPs' ), _n_noop( 'ZIP <span class="count">(%s)</span>', 'ZIPs <span class="count">(%s)</span>' ) );		
			$post_mime_types['application/x-shockwave-flash'] = array(__('Flash', 'swfobj'), __('Manage Flash', 'swfobj'), _n_noop('Flash <span class="count">(%s)</span>', 'Flash <span class="count">(%s)</span>' ));
	
			return $post_mime_types;
	}

  /* this can be set up to remove any of the media options
add_filter( 'media_view_strings', 'custom_media_uploader' );

function custom_media_uploader( $strings ) {
    unset( $strings['selected'] ); //Removes Upload Files & Media Library links in Insert Media tab
    unset( $strings['insertMediaTitle'] ); //Insert Media
    unset( $strings['uploadFilesTitle'] ); //Upload Files
    unset( $strings['mediaLibraryTitle'] ); //Media Library
    unset( $strings['createGalleryTitle'] ); //Create Gallery
    unset( $strings['setFeaturedImageTitle'] ); //Set Featured Image
    unset( $strings['insertFromUrlTitle'] ); //Insert from URL
    return $strings;
}    

    */
 	function newSizes($sizes) {
 		$size_names = $this->ss_all_image_sizes();
        $newimgsizes = array_merge($sizes, $size_names);
        return $newimgsizes;
 	}
 	
 	function image_size_choose($sizes) {
 	 
 	 	$size_names =  get_intermediate_image_sizes();// this only works with WP version 3+
     	foreach( $size_names as $key => $value) {
			$new_sizes[$value] = $value;
		}
     	$new_sizes['full'] = 'full';
     
     	$newimgsizes = array_merge($sizes, $new_sizes);

        return $newimgsizes;
 	}
 	
 	function image_sizebox($post) {	
 		$type = $post->post_mime_type;
		if (strpos($type,'image') === false){
			echo __("There are no size variations to this object", $this->plugin_name);
        	return ;	
		}
 		$size_names = $this->ss_all_image_sizes(); 		

 		foreach ( $size_names as $size ) {
			$downsize = image_downsize($post->ID, $size);
			
			$max_w = $downsize[1];
			$max_h = $downsize[2];	
			$enabled = ( $downsize[3] || 'full' == $size ) ; 
			$enabled == 1 ? $file = esc_attr($downsize[0]) : $file = '';
			$enabled == 1 ? $mclass = 'thickbox' : $mclass = 'button-disabled';

			$html = "<div  style='padding: 0px 5px;text-align: center;float:left;padding:5px;' ><a href= '" . $file . "' title=' Click to view file in popover: " . $file . "' class='button ".$mclass."' >";
			$html .= $size."</a><br />";
			if ( $enabled ) {
				$html .=  sprintf( "(%d&nbsp;&times;&nbsp;%d)", $max_w, $max_h );
			} else {
				$html .=  __('Not Available', $this->plugin_name);
			    //$html .=  "<a href=' ' title='Build this size : ".$max_w." x ". $max_h."' > Build </a>";
			    //<input type="radio" checked="checked" value="all" name="imgedit-target-53"> where 53 is the image ID
			    //<input class="button-primary imgedit-submit-btn" type="button" value="Save" disabled="disabled" onclick="imageEdit.save(53, '85ec627e6d')">
			}

			$html .= "</div>";
			$out[] = $html;
		}
         
        $out =  join("\n", $out). "<br style='clear:both;'>";
        echo $out;
 	}
 	/*
 	This will rebuild missing image versions
 	*/
 	function newImage($image){
 		
/* 
 		
WP_Image_Editor::resize($file, $max_w, $max_h, $crop, $suffix, $dest_path, $jpeg_quality );
 		
 		$image = wp_get_image_editor( 'cool_image.jpg' );
if ( ! is_wp_error( $image ) ) {
    $image->rotate( 90 );
    $image->resize( 300, 300, true );
    $image->save( 'new_image.jpg' );
}
 		
 		$file = '/path/to/image.png';
 
$editor = WP_Image_Editor::get_instance( $file );
$editor->resize( 100, 100, true );
$new_image_info = $editor->save();
 		 		
*/
 	}
 	 	
 	function Pop_create_media_page() {
  			    		
    		register_setting( 'media', 'feature_size_w' );
    		register_setting( 'media', 'feature_size_h' );
    		register_setting( 'media', 'feature_crop' );
			
			add_settings_field('feature_size', 'Featured image', array(&$this, 'feature_size'), 'media', 'default');			

    }
    
    function feature_size(){  
        echo '<label for="feature_size_w">'.__(' Max Width ', $this->plugin_name).'</label>
        <input name="feature_size_w" id="feature_size_w" type="text" value="'.get_option ('feature_size_w').'" class="small-text" />
        <label for="feature_size_h">'.__(' Max Height ', $this->plugin_name).'</label>
        <input name="feature_size_h" id="feature_size_h" type="text" value="'. get_option('feature_size_h').'" class="small-text" />
        <br /><input type="checkbox"'; 
            checked('1', get_option('feature_crop'));        
        echo ' value="1" id="feature_crop" name="feature_crop"><label for="feature_crop">'.__('Crop feature to exact dimensions', $this->plugin_name).'</label>';

	}
    
    function media_pop_create_media_page() {    			
       
        register_setting( 'media', 'ss_admin_thumb' );
        register_setting( 'media', 'jpeg_quality' );
        register_setting( 'media', 'ss_image_default_link_type' );
        register_setting( 'media', 'ss_image_default_align' );
        register_setting( 'media', 'ss_image_default_size' );
        
        add_settings_section( 'media-pop', 'SuperSlider-Media-Pop ', array(&$this,'media_pop_section'), 'media');			
        add_settings_field( 'jpeg_quality', 'Image compression', array(&$this,'ss_jpeg_quality'), 'media', 'media-pop');
        add_settings_field( 'ss_admin_thumb', 'Admin post list thumbnail', array(&$this,'ss_admin_thumb'), 'media', 'media-pop');
		
		add_settings_field( 'ss_image_default_link_type', 'Default attached links to ', array(&$this,'ss_attach_link'), 'media', 'media-pop');
        add_settings_field( 'ss_image_default_align', 'Default image alignment ', array(&$this,'ss_attach_align'), 'media', 'media-pop');
        add_settings_field( 'ss_image_default_size', 'Default image size ', array(&$this,'ss_attach_size'), 'media', 'media-pop');

       $this->set_default_settings();

    }
    function set_default_settings() {
        $isoption = get_option ('ss_admin_thumb');
        if ($isoption == NULL) {
            update_option( 'ss_admin_thumb', 60 );
            update_option( 'jpeg_quality', 70 );
            update_option( 'ss_image_default_link_type', 'large' );
            update_option( 'ss_image_default_align', 'none' );
            update_option( 'ss_image_default_size', 'medium' );
        }
    }
    function ss_attach_size() { 	    
        $dsize = get_option ('ss_image_default_size');
        $check = 'checked = "checked"';
        $size_names = $this->ss_all_image_sizes();        
        echo '<div >';
        
        foreach ( $size_names as $size ) {        
            $check = '';
            if($dsize == $size) $check = ' checked = "checked" ';
            echo '<input type="radio" '.$check.' value="'.$size.'" id="image-size-'.$size.'" name="ss_image_default_size"><label style="margin: 0px 8px 0px 4px;" class="image-size-'.$size.'-label" for="image-size-'.$size.'">'.$size.'</label>';  

         }
         echo "</div>";
	}
	
	function ss_attach_align() { 
        $alignOption = get_option ('ss_image_default_align');
        $check = 'checked = "checked"';
        $alignSet = array(
            0 => 'none',
            1 => 'left',
            2 => 'center',
            3 => 'right',
        );
        foreach ( $alignSet as $align ) {
            $check = '';
            if($alignOption == $align) $check = ' checked = "checked" ';
            echo '<input type="radio" '.$check.' value="'.$align.'" id="image-align-'.$align.'" name="ss_image_default_align"><label style="margin: 0px 8px 0px 4px;" class="align image-align-'.$align.'-label" for="image-align-'.$align.'">'.$align.'</label>';  
         }

	}
	function ss_attach_link() { 
        $attach_link = get_option('ss_image_default_link_type');        
        $links = array( 'custom', 'none', 'file', 'post' );
		$html = '<div>';
		foreach ($links as $link) {
			$check = '';
            if($attach_link == $link) $check = ' checked = "checked" ';         
	        $html .= '<input type="radio" name="ss_image_default_link_type" id="ss_attach_'.$link.'" value="'.$link.'" '.$check.' />';
	        $html .= '<label for="ss_attach_'.$link.'" class="radio" style="margin: 0px 8px 0px 4px;">'.$link.'</label>';
		}
		$html .= '</div>';
		echo $html;
	}
	
	
    function ss_options_css(){
       echo '<style type="text/css">
        .align {
        display:inline;
        font-weight:bold;
        margin:0 6px 0 2px;
        padding:0 0 0 22px;
        font-family:"Lucida Grande",Verdana,Arial,"Bitstream Vera Sans",sans-serif;
        font-size:13px;
        text-transform: capitalize;}
        .image-align-none-label {
            background: url(images/align-none.png) no-repeat center left;}        
        .image-align-left-label {
            background: url(images/align-left.png) no-repeat center left;}        
        .image-align-center-label {
            background: url(images/align-center.png) no-repeat center left;}       
        .image-align-right-label {
            background: url(images/align-right.png) no-repeat center left;}
        </style>';
    }
    
    function media_pop_section() { 
       $section = '<span class="description"> '.__('Set default options for various image properties.', $this->plugin_name ).'</span>';        
       echo $section;
    }
    
    function filter_plugin_link($links) {
			$settings_link = '<a href="options-media.php">'.__('Settings').'</a>';
			array_unshift( $links, $settings_link ); //  before other links
			return $links;
    }
	
	function ss_jpeg_quality() { 
        $jpeg = get_option ('jpeg_quality');
        echo '<label for="jpeg_quality">'.__('jpeg image quality', 'superslider-image-pop').'</label>
        <input name="jpeg_quality" id="jpeg_quality" type="text" value="'. $jpeg.'" class="small-text" />'; 
	}
    
    function ss_admin_thumb() { 
        $thumb = get_option ('ss_admin_thumb');
        echo '<label for="ss_admin_thumb">'.__('Post list thumbnail display size', 'superslider-image-pop').'</label>
        <input name="ss_admin_thumb" id="ss_admin_thumb" type="text" value="'. $thumb.'" class="small-text" /> px'; 

	}
	
    function ss_pop_jpeg_quality(  ) { 
        $image_quality = get_option('jpeg_quality');
        if (!$image_quality) $image_quality =  90;
        return $image_quality; // WP system Default is 90
    }
    
    function ss_insert($array = '', $position = '' , $elements= '') {                   
        $left = array_slice ($array, 0, $position-1);
        $right = array_slice ($array, $position-1);
        $array = array_merge ($left, $elements, $right);
        return $array;
    } 
    
    /*
    * Media Library columns
    * media-upload.php
    */
    function ss_media_column_css() {
        $style='<style type="text/css">
        .column-media { width:100px!important;}
        .column-author { width:70px!important;}
        .column-thumbnail { width:70px!important;}
        .column-thumbnail img, .media-icon img{ padding: 2px;border:1px solid #cdcdcd;}
        .column-title { width:100px!important;}
        .column-parent { width:100px!important;}
        .column-caption { width:80px!important;}
        .column-alt-text { width:70px!important;}
        .column-description { width:120px!important;}
        .column-attachments { width:170px!important;}
        .attach-thumb {float:left;margin:2px 0px 4px 0px;padding:2px;border:1px solid #cdcdcd;width: 30px;}
        .attach-text {border-bottom:1px solid silver;float:left; margin:4px 0px 0px 10px; width: 100px;}
        .column-attachments img { max-width:30px!important; max-height:30px!important;}
        .column-id { width:25px!important;}
        #mass-edit .title{border-bottom:1px solid #DADADA;
            clear:both;
            font-size:1.6em;
            padding:0 0 3px;
            color:#5A5A5A;
            font-family:Georgia,"Times New Roman",Times,serif;
            font-weight:normal;}
        .att_list_limit {padding: 2px;border:1px solid #cdcdcd;}
        .att_list_head {padding: 2px 4px;background: #eaeaea;margin:-2px -2px 2px -2px;}
        .ss-mini {
        	color: gray;
        	font-size: 8px;
        	font-weight: normal;}
        .check-column {width: 20px !important;}
        </style>';
               
        echo $style;        
    }

    /*
    * Media columns
    */
    function ss_add_media_columns($cols) {

        $capt = array('caption'=>__('Caption', $this->plugin_name ));
        $cols = $this->ss_insert($cols, '4', $capt);
        $media_id = array('id'=>__('ID', $this->plugin_name ));

        $cols['id'] = __('ID', $this->plugin_name );
        $alt = array('alt-text'=>__('Alternate', $this->plugin_name ));
        $cols = $this->ss_insert($cols, '5', $alt);
        $descript = array('description'=>__('Description', $this->plugin_name ));
     
        return $cols = $this->ss_insert($cols, '4', $descript); 
 
 	}    

    function ss_add_media_values($column_name, $post_id) {    
        switch($column_name):            
            case 'caption':
                $att = get_post($post_id);  
                echo $att->post_excerpt;
            break;
            case 'description':
                $desc = get_post($post_id);  
                echo $desc->post_content;
            break;
            case 'alt-text':
                $image_alt = get_post_meta($post_id, '_wp_attachment_image_alt', true);
                echo $image_alt;
            break;
            case 'id':
                echo $post_id;
            break;
        endswitch;
    }	
	// Register the column as sortable & sort by name
	function ss_column_sortable( $cols ) {
		$cols["id"] = "name";
		$cols["caption"] = "ID";
		$cols["description"] = "description";
		return $cols;
	}
    
    /*
    * Post - Page columns
    */
    function ss_add_post_page_columns($cols) {
        // plugin file gallery is here (http://wordpress.org/extend/plugins/file-gallery/)

        if (isset($cols['post_thumb'])) {
            unset($cols['post_thumb']);
            unset($cols['attachment_count']);
        }
        
        $thumb = array('thumbnail'=>__('Thumbnail', $this->plugin_name ));
        $cols = $this->ss_insert($cols, '2', $thumb);
        $cols['attachments'] = __('Attachments', $this->plugin_name );
        $id = array('id'=>__('ID', $this->plugin_name ));
        
		return $cols = $this->ss_insert($cols, '3', $id);
	}

	function ss_add_thumb_value($column_name, $post_id) {           
           $thumb_size = get_option ('ss_admin_thumb');
 
			if ( 'thumbnail' == $column_name ) {
				// thumbnail of WP 2.9
				$thumbnail_id = get_post_meta( $post_id, '_thumbnail_id', true );
								
				if ($thumbnail_id) {
					$thumb = "<a href='".get_edit_post_link($post_id)."' >";
					$thumb .= wp_get_attachment_image( $thumbnail_id, array($thumb_size, $thumb_size), true, 'style="width:'.$thumb_size.'px!important; height:'.$thumb_size.'px!important;"' );
				    $thumb .= "</a><br/><span class=''>".__('feature image', $this->plugin_name )."</span>";
				}
				else {	
				    $attachments = get_children( array('post_parent' => $post_id, 'post_type' => 'attachment', 'post_mime_type' => 'image', 'orderby' => 'menu_order', 'order' => 'DESC') );

					foreach ( $attachments as $attachment_id => $attachment ) {
					    $thumb = "<a href='".get_edit_post_link($post_id)."' >";
						$thumb .= wp_get_attachment_image( $attachment_id, array($thumb_size, $thumb_size), true, 'style="width:'.$thumb_size.'px!important; height:'.$thumb_size.'px!important;"' );
						$thumb .= "</a><br/><span class=''>".__('attached image', $this->plugin_name )."</span>";
					}
				}
				
				/*if(!isset($thumb)) {
					$thumb = "<a href='".get_edit_post_link($post_id)."' >";
					//$thumb = get_the_post_thumbnail($post_id, $size, $attr )
					$thumb = get_the_post_thumbnail( $thumbnail_id, array($thumb_size, $thumb_size), true, 'style="width:'.$thumb_size.'px!important; height:'.$thumb_size.'px!important;"' );
					$thumb .= "</a><br/><span class=''>".__('feature image thumbnail', $this->plugin_name )."</span>";
				}*/
				
                if ( isset($thumb) && $thumb ) {
                         
                    echo $thumb;
                } else {
                    echo "<a href='upload.php?detached=1' title='".__('First take note of the post ID and title', $this->plugin_name )."'>".__('Add image', $this->plugin_name )."</a>";
                }
			}
	}
	
    function ss_add_attachment_value($column_name, $post_id) {
        global $wpdb;

        if( $column_name == 'attachments' ) {
            $query = "SELECT post_title, ID FROM $wpdb->posts ".
                     "WHERE post_type='attachment' ".
                     "AND post_parent='$post_id' ".
                     "ORDER BY menu_order ASC LIMIT 31";
            $attachments = $wpdb->get_results($query);
                         
            $att_count = 0;
            $att_tag_open = '<div id="postatts_'.$post_id.'" class="postbox closed" style="min-width:160px!important;"><div class="handlediv" title="Click to toggle"><br /></div><h3 class="hndle" style="cursor:pointer;padding: 5px 10px;">';
            $att_tag_close =  __(' attachments', $this->plugin_name ) .'</h3><div class="inside">';
            $close = '</div><br style="clear:both;"/></div>';  
                              
            if( $attachments ) {              
                $att_count = count($attachments);

                $my_func = create_function('$att',
                    '$title =  substr($att->post_title, 0, 14); 
                    if ( strlen($att->post_title) > 14) $title .= " ... ";
                    $thumb = wp_get_attachment_image( $att->ID, array(30, 30), true, array("class"=>"pinkynail") );
                    return "<div class=\"attach-thumb\">".$thumb."</div><div class=\"attach-text\"><span>".$title.
                    " </span><br /><a href=\"".get_permalink($att->ID)."\" title=\"View\" ".$att->post_title."\">View</a> | <a href=\"media.php?attachment_id=".$att->ID."&action=edit\" title=\"Edit\" ".$att->post_title."\">
                    Edit</a></div>";');
                           
                    $all_attachments = array_map($my_func, $attachments);             
                    $att_list = $att_tag_open . $att_count . $att_tag_close . implode('<br style="clear:both;"/>',$all_attachments) . $close;
               
                    echo $att_list;
               
             } else {
                $att_count = __('None attached', $this->plugin_name );
                $link_to_atts = '<a href="upload.php" title="'.__('First take note of the post ID and title', $this->plugin_name ).'">'.__('Attach from Media Library', $this->plugin_name ).'</a>';              
                $att_list = $att_tag_open . '0' . $att_tag_close . $link_to_atts . $close;
                
                echo $att_list;
                // would be good to have a pop over link to the media lib
                //<br /><a class="hide-if-no-js" onclick="findPosts.open(\'media[]\',\''. $post_id .'\');return false;" href="#the-list">'. __('Attach', $this->plugin_name ).'</a>'
            }
        }
    }
	function ss_add_id_value($column_name, $post_id) { 
        if ( 'id' == $column_name ) {
                echo $post_id;                
        }
	}

    
    function ss_disabled( $disabled, $current = true, $echo = true ) {	   
	   return __checked_selected_helper( $disabled, $current, $echo, 'disabled' );
    }
    
    function ss_all_image_sizes() {
        // get a list of the actual pixel dimensions of each possible intermediate version of this image
        global $wp_version;    
        // is not version 3+
         if (version_compare($wp_version, "2.9.9", "<")) {
            $size_names = array('thumbnail' => 'thumbnail', 'medium' => 'medium', 'large' => 'large', 'full' => 'full',);
            if (function_exists('add_theme_support')) $size_names['post-thumbnail'] = 'post-thumbnail'; 
            if (class_exists("ssShow")) { $size_names['slideshow'] = 'slideshow'; $size_names['minithumb'] = 'minithumb';}
            if (class_exists("ssExcerpt")) $size_names['excerpt'] = 'excerpt'; 
            if (class_exists("ssPnext")) $size_names['prenext'] = 'prenext'; 
     
       } else {       
            $size_names =  get_intermediate_image_sizes();// this only works with WP version 3+
            $size_names[] = 'full'; // adds original / full sized image to list
       }
       return $size_names;
    }
    
}	//end class
} //End if Class ssMediaPop

/**
*instantiate the class
*/	
$myssMediaPop = new ssMediaPop();

?>