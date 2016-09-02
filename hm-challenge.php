<?php
/*
Plugin Name: Haagse Makers Challenges
Plugin URI: http://www.haagsemakers.nl
Description: Get into the challenge!
Author: Urbanlink
Author URI: http://urbabanlink.nl
Version: 0.1.0
*/

class HMChallenge {

  function __construct() {
    /* Runs when plugin is activated */
    register_activation_hook(__FILE__, array($this, 'hmchallenge_install') );
    /* Runs on plugin deactivation*/
    register_deactivation_hook( __FILE__, array($this, 'hmchallenge_remove' ) );

    add_action( 'init', array($this, 'hmchallenge_init' ));
    add_filter( 'query_vars', array($this, 'hmchallenge_rewrite_add_var') );
    add_action('wp_enqueue_scripts', array($this, 'hmchallenge_enqueue'));

    add_action( 'wp_ajax_hmchallenge_additem', array($this, 'hmchallenge_additem_callback' ));
    add_action( 'wp_ajax_hmchallenge_removeitem', array($this, 'hmchallenge_removeitem_callback' ));

    add_filter( 'template_include', array($this, 'hm_include_template_function'));

    add_action( 'wp', array($this, 'hmchallenge_cron_job' ));

    add_shortcode( 'hmchallenge-userlist', array($this, 'hmchallenge_shortcode_userlist' ));
    add_shortcode( 'hmchallenge-random-items', array($this, 'hmchallenge_shortcode_random_items_list' ));

  }



  // Install plugin hook
  function hmchallenge_install() {
    global $wpdb;

    $the_page_title = 'Maker Challenge';
    $the_page_name = 'maker-challenge';
    // the menu entry...
    delete_option("hmchallenge_page_title");
    add_option("hmchallenge_page_title", $the_page_title, '', 'yes');
    // the slug...
    delete_option("hmchallenge_page_name");
    add_option("hmchallenge_page_name", $the_page_name, '', 'yes');
    // the id...
    delete_option("hmchallenge_page_id");
    add_option("hmchallenge_page_id", '0', '', 'yes');

    $the_page = get_page_by_title( $the_page_title );

    if ( ! $the_page ) {
      // Create post object
      $_p = array();
      $_p['post_title'] = $the_page_title;
      $_p['post_content'] = "This text will be overridden by the plugin. You shouldn't edit it.";
      $_p['post_status'] = 'publish';
      $_p['post_type'] = 'page';
      $_p['comment_status'] = 'closed';
      $_p['ping_status'] = 'closed';
      $_p['post_category'] = array(1); // the default 'Uncatrgorised'
      // Insert the post into the database
      $the_page_id = wp_insert_post( $_p );
    }
    else {
      // the plugin may have been previously active and the page may just be trashed...
      $the_page_id = $the_page->ID;
      //make sure the page is not trashed...
      $the_page->post_status = 'publish';
      $the_page_id = wp_update_post( $the_page );
    }

    delete_option( 'hmchallenge_page_id' );
    add_option( 'hmchallenge_page_id', $the_page_id );
  }

  // Uninstall plugin hook
  function hmchallenge_remove() {
    global $wpdb;

    $the_page_title = get_option( "hmchallenge_page_title" );
    $the_page_name = get_option( "hmchallenge_page_name" );

    //  the id of our page...
    $the_page_id = get_option( 'hmchallenge_page_id' );
    if( $the_page_id ) {
      wp_delete_post( $the_page_id ); // this will trash, not delete
    }

    delete_option("hmchallenge_page_title");
    delete_option("hmchallenge_page_name");
    delete_option("hmchallenge_page_id");
  }


  // ---
  function hmchallenge_init() {

    // Register post type
    $labels = array(
  		'name' => _x('Challenge items', 'challenge items'),
  		'singular_name' => _x('Challenge item', 'challenge item'),
  		'add_new' => _x('Add New', 'challenge item'),
  		'add_new_item' => __('Add New Challenge Item'),
  		'edit_item' => __('Edit Challenge Item'),
  		'new_item' => __('New Challenge Item'),
  		'view_item' => __('View Challenge Item'),
  		'search_items' => __('Search Challenge Items'),
  		'not_found' =>  __('Nothing found'),
  		'not_found_in_trash' => __('Nothing found in Trash'),
  		'parent_item_colon' => ''
  	);

    $args = array(
  		'labels' => $labels,
  		'public' => true,
  		'publicly_queryable' => true,
  		'show_ui' => true,
  		'query_var' => true,
  		// 'menu_icon' => get_stylesheet_directory_uri() . '/article16.png',
  		'rewrite' => array('slug' => 'challenge/item'),
  		'capability_type' => 'post',
  		'hierarchical' => false,
  		'menu_position' => null,
  		'supports' => array('title','editor','author','custom-fields','comments')
  	);
  	register_post_type( 'challenge_item', $args);

    // Register categories
    register_taxonomy("Challenge Categories", array("challenge_item"), array("hierarchical" => true, "label" => "Challenge Categories", "singular_label" => "Category", "rewrite" => true));


    // Create challenge page for user /user/{user-slug}/challenge
    add_rewrite_tag( '%slug%', '([^&]+)' );
    add_rewrite_tag( '%challenge%', '([^&]+)' );
    add_rewrite_rule('^user/([^/]*)/([^/]*)/?','index.php?slug=$matches[1]&challenge=$matches[2]','top');
  }


  function hmchallenge_rewrite_add_var( $vars ) {
    $vars[] = 'username';
    return $vars;
  }


  // Schedule Cron Job Event to send mailings to challenge users.
  function hmchallenge_cron_job() {
  	if ( ! wp_next_scheduled( 'hmchallenge_send_mailing' ) ) {
  		wp_schedule_event( current_time( 'timestamp' ), 'daily', 'hmchallenge_send_mailing' );
  	}
  }

  // Scheduled Action Hook
  // send the necessary mailings to user registered in the challenge
  function hmchallenge_send_mailing() {

    $mailing_steps     = ['1'   , '2'   , '3'   , '4'   , '5'   , '6'    , '7'   , '8'    ];
    $mailing_templates = ['init', 'day1', 'day2', 'day3', 'day4', 'day5', 'day10', '8-day15' ];
    $mailing_intervals = [0,      1*24  , 2*24  , 3*24  , 4*24  , 5*24  , 10*24  , 15*24     ];

    // find users with active challenge step1, send mail and update step to 2
    $current_time = time();

    for ($i=0; $i<count($mailing_steps); $i++) {
      $args = array(
        'meta_query' => array(
          'relation' => 'AND',
          array(
            'key' => 'hm_challenge_active',
        	  'value' => '1'
          ),
          array(
            'key' => 'hm_challenge_mailingstep',
        	  'value' => (string)$mailing_steps[$i]
          ),
          array(
            'key' => 'hm_challenge_startdate',
        	  'value' => $current_time - ($mailing_intervals[ $i]*60*60),
            'compare' => '<'
          )
        )
      );
      $user_query = new WP_User_Query( $args );
      $user_result = array();
      foreach ( $user_query->results as $user ) {
        $user_result[] = $user->ID;
        // send mail to the user
        $title = 'Haagse Makers Challenge';
        $content = file_get_contents(dirname(__FILE__) . "/mailing/" . $mailing_templates[ $i] . ".php");

        $headers = array();
        $headers[] = 'template: 0125e543-4026-46d7-b22c-95dda1550004';

        $status = wp_mail($user->user_email, $title, $content, $headers);

        if ($status) {
          update_user_meta( $user->ID, 'hm_challenge_mailingstep', $mailing_steps[ $i] +1 );
        }
      }
    }
  }


  // Add javascript file
  function hmchallenge_enqueue($hook) {
    // Challenge CSS
    wp_register_style( 'hmchallenge', plugins_url( '/css/challenge.min.css', __FILE__) );
    wp_enqueue_style( 'hmchallenge' );
    // Challenge JS script
    wp_enqueue_script( 'ajax-script', plugins_url( '/js/hm-challenge.js', __FILE__ ), array('jquery') );
    // in JavaScript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
  	wp_localize_script( 'ajax-script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'we_value' => 1234 ) );
  }


  // Respond to a ajax call to add a challenge item
  function hmchallenge_additem_callback() {

    $title = sanitize_text_field($_POST['item']);

    global $current_user;
    get_currentuserinfo();
    $user_id = $current_user->ID;
    $response = array();

    // Create the new challenge item
    // Check the number of posts (max is 100);
    $args = array(
      'numberposts' => 150,
      'post_type' => 'challenge_item',
      'author' => $user_id
    );
    $posts = get_posts( $args );
    $count = count($posts);
    wp_reset_postdata();
    if ($count>99){
      $response.push(array(
        'status' => 'error',
        'statusMsg' => 'Maximum number of posts received: ' . $count
      ));
    } else {

      // Create the new post
      $post_id = wp_insert_post(array(
        'post_author'   => $user_id,
        'post_title'    => $title,
        'post_status'   => 'publish',
        'post_type'   => 'challenge_item'
        )
      );

      // Check if this is the start of the challenge for this user
      $started = get_the_author_meta('hm_challenge_active', $user_id);
      if (!$started) {
        // Start the challenge for this user.
        update_user_meta( $user_id, 'hm_challenge_active', '1' );
        $mailing_step = '1';
        update_user_meta( $user_id, 'hm_challenge_mailingstep', $mailing_step );
        $time = time();
        update_user_meta( $user_id, 'hm_challenge_startdate', $time );
        update_user_meta( $user_id, 'hm_challenge_lastmailing_date', $time );

        // Send the start mail to this user
        $to = $current_user->user_email;
        $subject = "Thanks for starting the challenge! ";
        $content = file_get_contents(dirname(__FILE__)."/mailing/init.php");
        $status = wp_mail($to, $subject, $content);
        $mailing_step = '1';
        if ($status) {
          update_user_meta( $user_id, 'hm_challenge_mailingstep', $mailing_step );
        }
      }

      // generate the response
      $response = array(
        'status' => 'success',
        'count' => $count,
        'item' => array(
          'title' => $title,
          'user_id' => $user_id,
          'post_id' => $post_id
        ),
        'user_meta' => array(
          'started' => get_the_author_meta('hm_challenge_active', $user_id),
          'time' => get_the_author_meta('hm_challenge_startdate', $user_id),
          'step' => get_the_author_meta('hm_challenge_mailingstep', $user_id),
        ),
        'mail' => array(
          'to' => $to,
          'status' => $status
        ),
        'current_user' => $current_user->user_email
      );
    }

    $response = json_encode($response);

    // response output
    header( "Content-Type: application/json" );
    echo $response;

    wp_die();
  }


  function hmchallenge_removeitem_callback() {
    // echo 'some';
    $post_id = $_POST['item'];
    $result = wp_delete_post($post_id);
    echo 'success';
    wp_die();
  }

  // Connect pages and posts with the right template.
  // Reference: https://github.com/tommcfarlin/page-template-example/blob/master/class-page-template-example.php
  function hm_include_template_function( $template_path ) {

    global $wp_query;
    global $post;

    // check for /user/{slug}/challenge
    if ($wp_query->query_vars['slug'] && ($wp_query->query_vars['challenge'] == 'challenge')) {
      $template_path = plugin_dir_path( __FILE__ ) . 'templates/page-user-challenge.php';
      return $template_path;
    } else if (get_option( 'hmchallenge_page_id' ) == $post->ID) {
    // /maker-challenge page
      $template_path = plugin_dir_path( __FILE__ ) . 'templates/page-maker-challenge.php';
      return $template_path;
    } else if ( get_post_type() == 'challenge_item' ) {
    // maker-challenge-item post type template
      if ( is_single() ) {
        // checks if the file exists in the theme first,
        // otherwise serve the file from the plugin
        if ( $theme_file = locate_template( array ( 'single-challenge-item.php' ) ) ) {
          $template_path = $theme_file;
        } else {
          $template_path = plugin_dir_path( __FILE__ ) . 'templates/single-challenge-item.php';
        }
        return $template_path;
      }
    } else {
      return $template_path;
    }
  }


  /****** SHORTCODES ******/
  // List of users who participate in the challenge
  function hmchallenge_shortcode_userlist( $atts ){
    // fetch a list of users with the meta 'hmchallenge_active'=true;
    $args = array(
      'number' => 10,
      'meta_key' => 'hm_challenge_active',
  	  'meta_value' => '1',
    );
    $user_query = new WP_User_Query( $args );
    $html='';
    // User Loop
    if ( ! empty( $user_query->results ) ) {
      $html .='<div class="challenge-userlist">';
      $html .= '<h3>Deze makers doen mee aan de challenge! </h3>';
      $html .= '<div class="row">';
    	foreach ( $user_query->results as $user ) {
        // $userdata = get_userdata($user->ID);
        $html .= '<div class="col-xs-3 user-container">';
        $html .= '<a href="/user/'. $user->user_login .'/challenge">' . get_avatar( $user->ID, 64, null, null, array('class'=>'img-responsive img-circle') ) . '</a>';
    		$html .= '<span class="username">' . $user->display_name . '</span>';
        $html .= '</div>';
    	}
      $html .= '</div>';
      $html .= '</div>';
    } else {
    	// $html = '<div class="empty">No users found.</div>';
    }

    return $html;
  }


  // List of random challenge items
  function hmchallenge_shortcode_random_items_list($atts) {
    echo '<h3>Challenges van anderen</h3>';
    $args = array(
      'numberposts' => 5,
      'post_type' => 'challenge_item',
      'orderby' => 'rand'
    );
    $the_query = new WP_Query( $args );

    if ( $the_query->have_posts() ) {
      echo '<ol id="challenge-randomlist">';
      while ( $the_query->have_posts() ) {
        $the_query->the_post();
        $status = get_field('challenge_status');
        if ($status) { $status = 'checked'; }
        echo
          '<li class="challenge-listitem small ' . $status . '">
            <span class="avatar inline">' . get_avatar(get_the_author_id(), 24, null, null, array('class'=>'img-circle')) . '</span> ' .
            '<span class="author">' . get_the_author() . '</span>' .
            '<span class="title">' . get_the_title() . '</span>' .
          '</li>';
      }
      echo '</ol>';
    }
    /* Restore original Post Data */
    wp_reset_postdata();
  }
}

new HMChallenge();
