<?php
if(!defined('WPALM_VERSION')){
  die("Permission Denied");
}
/*
 * Main class
 */
/**
 * Class wpajaxLoadmore
 *
 * This class creates the option page and add the web app script
 */
 
class wpajaxLoadmore{
  /**
	* wpajaxLoadmore constructor.
	*
	* The main function call from here..
    */
  public function __construct() {
	$this->init();
  }
  /**
	* wpajaxLoadmore init. 
    */
  public function init(){
	add_action( 'init', array($this, 'create_posttype') );
	add_action( 'add_meta_boxes', array($this, 'add_meta_box') );
	add_action( 'save_post_ajaxloadmore', array($this, 'wpalm_save_meta_boxes_data'), 10, 2 );
	add_filter('user_can_richedit', array($this, 'disable_wyswyg') );
	add_filter( 'default_content', array($this, 'wpalm_editor_content'), 10, 2  );
	
	// Add the custom columns to the ajaxloadmore post type:
	add_filter( 'manage_ajaxloadmore_posts_columns', array($this, 'set_custom_edit_ajaxloadmore_columns') );
    // Add the data to the custom columns for the ajaxloadmore post type:
	add_action( 'manage_ajaxloadmore_posts_custom_column' , array($this, 'custom_ajaxloadmore_column'), 10, 2 );
	// Generate shortcode
	add_shortcode('ajaxLoamore', array($this, 'wpalm_shortcode') );
    add_action( 'wp_enqueue_scripts', array($this, 'ajaxLoadmore_scripts') ); // Ajax required script
	
	add_action('wp_ajax_wp_ajax_loadmore', array($this, 'wp_ajax_loadmore_ajax_handler')); // wp_ajax_{action}
	add_action('wp_ajax_nopriv_wp_ajax_loadmore', array($this, 'wp_ajax_loadmore_ajax_handler')); // wp_ajax_nopriv_{action} 
  }
   

  /** 
   * Generate shortcode:
   */
  public function wpalm_shortcode($atts){
	$id = isset($atts['id'])?$atts['id']:'';
	if($id == ''){
	  exit("Required parameter id is missing");
	}
	$post = get_post($id); 
	$post_content = $post->post_content;
	
	// Get Meta info
	$paged = 1;
	$meta = get_post_meta( $post->ID, 'wpalm_post_type', true );	
	$btn_label = (isset($meta['btn_label']))?$meta['btn_label']:'Load More';
	$btn_class = (isset($meta['btn_class']))?$meta['btn_class']:'ajax-btn'; 
	$wrapper_class = (isset($meta['wrapper_class']))?$meta['wrapper_class']:'ajax_holder';
	$per_page = (isset($meta['per_page']))?$meta['per_page']:6;
	$orderby = (isset($meta['orderby']))?$meta['orderby']:'post_date';
	$order = (isset($meta['order']))?$meta['order']:'DESC';
	$noPost = (isset($meta['no_post']))?$meta['no_post']:'<div class="ajax-empty-post">No Post Found</div>';
	
	$post_type = (isset($meta['post_types']))?$meta['post_types']:'post';
	$post_types = explode(',', $post_type);
	
	$output = "<div class='ajaxLoadmore' data-page='$paged' data-id='$id'>";
	  $output .= "<div class='ajaxLoadmore-inner $wrapper_class' id='ajaxLoadmore-inner' >";
	  $args = array(
        'post_type'       => $post_types,
        'status'          => 'published',
        'posts_per_page'  => $per_page,
        'orderby'         => $orderby,
        'order'           => $order,
        'paged'           => $paged
      );
	  $posts = get_posts( $args );
	  if(count($posts) > 0):
	    foreach($posts as $post){ 
	      ob_start();
          eval('?' . '>' . $post_content);
          $post_content = ob_get_clean();
          $output .= do_shortcode($post_content);
	    } 
	  else:
	    $output .= $noPost;
	  endif;
	  
	  $output .= "</div>";
	  if(count($posts) > 0):
	    $output .= "<div class='ajax-loading-div' style='display: none;'>Loading</div><a href='javascript:;' class='ajaxLoadmoreBtn $btn_class'>$btn_label</a>";
	  endif;
	$output .= "</div>";
	
	return $output;
  } 
  /** 
   * Generate Ajax option:
   */
  function wp_ajax_loadmore_ajax_handler(){  
	if ( !wp_verify_nonce( $_REQUEST['nonce'], "wp_ajax_loadmore")) {
      exit("No naughty business please");
    }
    $id = isset($_POST['id'])?$_POST['id']:'';  
	if($id == ''){
	  exit("Required parameter id is missing");
	} 
	$post = get_post($id); 
	$post_content = $post->post_content;
	
	// Get Meta info 
	$meta = get_post_meta( $post->ID, 'wpalm_post_type', true );
	$post_type = (isset($meta['post_types']))?$meta['post_types']:'post';
	$post_types = explode(',', $post_type);
	
	$per_page = (isset($meta['per_page']))?$meta['per_page']:6;
	$orderby = (isset($meta['orderby']))?$meta['orderby']:'post_date';
	$order = (isset($meta['order']))?$meta['order']:'DESC';
	$paged = (isset($_POST['paged']))?$_POST['paged']:1;
	$output = '';
	$args = array(
      'post_type'       => $post_types,
      'status'          => 'published',
      'posts_per_page'  => $per_page,
      'orderby'         => $orderby,
      'order'           => $order,
      'paged'           => $paged
    );
	$posts = get_posts( $args ); 
	foreach($posts as $post){ 
	  ob_start();
      eval('?' . '>' . $post_content);
      $post_content = ob_get_clean();
      $output .= do_shortcode($post_content);
	} 
	echo $output;
	die();	
  }
  /** 
   * Generate Default script:
   */
  function ajaxLoadmore_scripts() { 
    wp_register_script( 'ajaxLoadmore', WPALM_URL . '/js/ajax-loadmore.js', array('jquery') , '1.0', true);	
	wp_localize_script( 'ajaxLoadmore', 'ajaxLoadmore_params', array(
      'ajaxurl' => site_url() . '/wp-admin/admin-ajax.php',
	  'wp_ajax_loadmore' => wp_create_nonce("wp_ajax_loadmore")
    ) ); 

 	wp_enqueue_script( 'ajaxLoadmore');
  }
  
  
  /** 
   * Add the custom columns to the ajaxloadmore post type:
   */
  public  function set_custom_edit_ajaxloadmore_columns($columns){
	$columns['shortcode'] = __( 'Shortcode', 'wp-ajax-loadmore' );
	return $columns; 
  }
  /** 
   * Add the data to the custom columns for the ajaxloadmore post type:
   */
  public function custom_ajaxloadmore_column($column, $post_id){
	switch ( $column ) {
      case 'shortcode' :
	    printf("<code style='background: #9e9e9e; color: #fff;'>[ajaxLoamore id='%s']</code><br /><br /><code style='background: #9e9e9e; color: #fff;'>&lt;?php echo do_shortcode(&quot;[ajaxLoamore id='%s']&quot;); ?&gt;</code>", $post_id, $post_id);
	  break;
	}	
  }
  
  
  /**
   * disable wyswyg for custom post type, using get_post_type() function
   */
  public function disable_wyswyg($default){
    if( get_post_type() === 'ajaxloadmore')  return false;
	return $default;
  } 
  /**
   * disable wyswyg for custom post type, using get_post_type() function
   */
  public function wpalm_editor_content( $content, $post){ 
    switch( $post->post_type ) {
        case 'ajaxloadmore':
            $content = '&lt;?php print $post->post_title; ?>';
        break; 
        default:
            $content = '';
        break;
    } 
    return $content; 
  } 
  
  /**
	* create post type. 
    */
  public function create_posttype(){
    $supports = array(
	  'title', // post title
	  'author', // post author
	  'editor', // editor 
	);
	$labels = array(
	  'name' => _x('Ajax Loadmore', 'plural'),
	  'singular_name' => _x('Ajax Loadmore', 'singular'),
	  'menu_name' => _x('Ajax Loadmore', 'admin menu'),
	  'name_admin_bar' => _x('Ajax Loadmore', 'admin bar'),
	  'add_new' => _x('Add New', 'add new'),
	  'add_new_item' => __('Add New Loadmore'),
	  'new_item' => __('New loadmore'),
	  'edit_item' => __('Edit loadmore'),
	  'view_item' => __('View loadmore'),
	  'all_items' => __('All loadmore'),
	 'search_items' => __('Search loadmore'),
	  'not_found' => __('No loadmore found.'),
	);
	$args = array(
	  'supports' => $supports,
	  'labels' => $labels,
	  'public' => false,
	  'query_var' => false,
	  'has_archive' => false,
	  'hierarchical' => false,
	  'show_ui' => true, 
	);
	register_post_type('ajaxloadmore', $args);
  }  
  
  /**
	* add custom meta box. 
    */
  public function add_meta_box(){
	add_meta_box( 'ajax-template', __( 'Custom Attribute', 'wp-ajax-loadmore' ), array($this, 'add_meta_box_add_field'), 'ajaxloadmore', 'normal', 'high' );  
  }
  /**
	* add custom meta box fields. 
    */
  public function add_meta_box_add_field(){
	global $post;
    $meta = get_post_meta( $post->ID, 'wpalm_post_type', true ); 
	wp_nonce_field( basename( __FILE__ ), 'wpalm_meta_box_nonce' );
	?>	
	<p class="post-attributes-label-wrapper">
	  <label class="post-attributes-label" for="wpalm_post_type[text]"><?php echo __('Post types - add multiple post types by comma ', 'wp-ajax-loadmore'); ?></label>
	</p> 
	<input type="text" name="wpalm_post_type[post_types]" id="wpalm_post_type[post_types]" class="regular-text" value="<?php echo esc_attr((isset($meta['post_types']))?$meta['post_types']:''); ?>">
	
	<p class="post-attributes-label-wrapper">
	  <label class="post-attributes-label" for="wpalm_post_type[btn_label]"><?php echo __('Button Label', 'wp-ajax-loadmore'); ?></label>
	</p> 
	<input type="text" name="wpalm_post_type[btn_label]" id="wpalm_post_type[btn_label]" class="regular-text" value="<?php echo esc_attr((isset($meta['btn_label']))?$meta['btn_label']:''); ?>">
	
	<p class="post-attributes-label-wrapper">
	  <label class="post-attributes-label" for="wpalm_post_type[btn_class]"><?php echo __('Button Class', 'wp-ajax-loadmore'); ?></label>
	</p> 
	<input type="text" name="wpalm_post_type[btn_class]" id="wpalm_post_type[btn_class]" class="regular-text" value="<?php echo esc_attr((isset($meta['btn_class']))?$meta['btn_class']:''); ?>">
	
	<p class="post-attributes-label-wrapper">
	  <label class="post-attributes-label" for="wpalm_post_type[per_page]"><?php echo __('Post per page', 'wp-ajax-loadmore'); ?></label>
	</p> 
	<input type="number" name="wpalm_post_type[per_page]" id="wpalm_post_type[per_page]" class="regular-text" value="<?php echo esc_attr((isset($meta['per_page']))?$meta['per_page']:6); ?>">
	
	<p class="post-attributes-label-wrapper">
	  <label class="post-attributes-label" for="wpalm_post_type[wrapper_class]"><?php echo __('Wrapper Class', 'wp-ajax-loadmore'); ?></label>
	</p> 
	<input type="text" name="wpalm_post_type[wrapper_class]" id="wpalm_post_type[wrapper_class]" class="regular-text" value="<?php echo esc_attr((isset($meta['wrapper_class']))?$meta['wrapper_class']:''); ?>">
	
	<p class="post-attributes-label-wrapper">
	  <label class="post-attributes-label" for="wpalm_post_type[orderby]"><?php echo __('Order By', 'wp-ajax-loadmore'); ?></label>
	</p> 
	<input type="text" name="wpalm_post_type[orderby]" id="wpalm_post_type[orderby]" class="regular-text" value="<?php echo esc_attr((isset($meta['orderby']))?$meta['orderby']:'post_date'); ?>">
	
	<p class="post-attributes-label-wrapper">
	  <label class="post-attributes-label" for="wpalm_post_type[order]"><?php echo __('Order', 'wp-ajax-loadmore'); ?></label>
	</p> 
	<input type="text" name="wpalm_post_type[order]" id="wpalm_post_type[order]" class="regular-text" value="<?php echo esc_attr((isset($meta['order']))?$meta['order']:'DESC'); ?>">
	
	<?php 
  }
  
  /**
	* save meta box fields. 
    */
  public function wpalm_save_meta_boxes_data($post_id){
	// verify taxonomies meta box nonce
	if ( !isset( $_POST['wpalm_meta_box_nonce'] ) || !wp_verify_nonce( $_POST['wpalm_meta_box_nonce'], basename( __FILE__ ) ) ){
		return;
	}
	// return if autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
		return;
	}
	// Check the user's permissions.
	if ( ! current_user_can( 'edit_post', $post_id ) ){
		return;
	} 
	 
	// cholesterol string
	if ( isset( $_REQUEST['wpalm_post_type'] ) ) {
	  $meta = $_REQUEST['wpalm_post_type'];	 
	  foreach($meta as $key => $value){
		$meta[$key] = sanitize_text_field($value);
	  }
	  update_post_meta( $post_id, "wpalm_post_type", $meta);  
	}
	
  }
  
  
}
new wpajaxLoadmore();
