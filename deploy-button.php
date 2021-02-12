<?php
/**
 * Plugin Name: Deployment Button
 * Plugin URI: https://github.com/aahilario/deployment-button-plugin/
 * Description: A plugin that creates a configurable, one-line file in the base directory of a WordPress installation.
 * Version: 0.2
 * Author: Antonio VA Hilario
 * Author URI: https://github.com/aahilario/
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */
global $deployment_button_instance;

if ( !is_object($deployment_button_instance) || !is_a($deployment_button_instance, 'DeploymentTriggerUtility') ) {
  $deployment_button_instance = DeploymentTriggerUtility::get_singleton();
}

class DeploymentTriggerUtility {

  public static $singleton = NULL;
  public static $enable_debug = FALSE;

  var $deployment_trigger_file = NULL;

  static function recursive_dump( $a, $prefix = "-->", $depth = 0, $loglevel = LOG_INFO )
{/*{{{*/
  /*
   * Dump contents of array to syslog
   */

  global $skipkeys;
  $iterable = is_array($a) || is_object($a);
  if ( !$iterable ) {
    return 0;
  }
  foreach ( $a as $k => $v ) {
    $pad = str_pad(" ", 3 * $depth, " ", STR_PAD_LEFT);
    $iterable = is_array($v) || is_object($v);
    if ( $iterable ) {
      if ( is_array($skipkeys) && array_key_exists($k, $skipkeys) ) {
        syslog( $loglevel, $prefix . "{$pad}{$k} => [skipped]" );
      }
      else {
        syslog( $loglevel, $prefix . "{$pad}{$k} => ..." );
        self::recursive_dump( $v, $prefix, $depth + 1, $loglevel );
      }
    }
    else {
      if ( is_bool( $v ) ) {
        $v = $v ? 'true' : 'false';
      }
      syslog( $loglevel, $prefix . "{$pad}{$k} => {$v}" );
    }
  }
}/*}}}*/

  static function & get_singleton()
  {/*{{{*/
    // TODO: Implement RBAC here.
    // Instantiate a singleton, depending on the host URL. 
    static::$singleton = new DeploymentTriggerUtility; 
    return static::$singleton;
  }/*}}}*/

  static function deployment_button_activation_hook()
  {/*{{{*/
    add_option( 'deployment_button_active', 'yes' );
    // One of three states: Empty, "Pending", "Error"
    add_option( 'deployment_button_state', ''); 
    // A string placed in the database by deployment tools 
    add_option( 'deployment_button_stepinfo', ''); 
  }/*}}}*/

  static function deployment_button_deactivation_hook()
  {/*{{{*/
  }/*}}}*/

  static function deployment_button_init()
  {/*{{{*/
  }/*}}}*/

  static function load_plugin()
  {/*{{{*/
    closelog();
    openlog( basename(__FILE__), LOG_PID | LOG_NDELAY, LOG_LOCAL1 );
    if ( is_admin() && get_option( 'deployment_button_active' ) == 'yes' )
    {
      delete_option( 'deployment_button_active' );
      add_action( 'init', 'deployment_button_init' );
    }
  }/*}}}*/

  static function deployment_button_section_settings_cb( $args )
  {/*{{{*/
?>
<script type="text/javascript">
</script>
<?php
  }/*}}}*/

  static function deployment_button_field_targeturl_cb( $args )
  {/*{{{*/
    $options = get_option( 'deployment_button_options' );
    $deployment_button_field_targeturl = $options['deployment_button_field_targeturl'];
    $default_url = 'https://' . $_SERVER['SERVER_NAME'];
    if ( 0 == strlen(trim($deployment_button_field_targeturl)) )
      $deployment_button_field_targeturl = $default_url; 
?>
  <input id="<?php echo esc_attr( $args['label_for'] ); ?>"
    data-custom="<?php echo esc_attr( $args['deployment_button_custom_data'] ); ?>"
    name="deployment_button_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
    type="text"
    value="<?php echo esc_attr( $deployment_button_field_targeturl ); ?>"
  >
  <p class="description">
  <?php esc_html_e( "Deployment destination URL e.g. {$default_url}" , 'deployment_button' ); ?>
  </p>
<?php
  }/*}}}*/

  static function deployment_button_field_filename_cb( $args )
  {/*{{{*/
    $options = get_option( 'deployment_button_options' );
    $deployment_button_field_filename = $options['deployment_button_field_filename'];
    if ( 0 == strlen(trim($deployment_button_field_filename)) )
      $deployment_button_field_filename = "deploy.txt";
?>
  <input id="<?php echo esc_attr( $args['label_for'] ); ?>"
    data-custom="<?php echo esc_attr( $args['deployment_button_custom_data'] ); ?>"
    name="deployment_button_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
    type="text"
    value="<?php echo esc_attr( $deployment_button_field_filename ); ?>"
  >
  <p class="description">
  <?php esc_html_e( 'Name of file to place in '. ABSPATH, 'deployment_button' ); ?>
  </p>
<?php
  }/*}}}*/

  static function deployment_button_field_branchinfo_cb( $args )
  {/*{{{*/
    $options = get_option( 'deployment_button_options' );
    $deployment_button_field_branchinfo = $options['deployment_button_field_branchinfo'];
    if ( 0 == strlen(trim($deployment_button_field_branchinfo)) )
      $deployment_button_field_branchinfo = "branch.txt";

?>
  <input id="<?php echo esc_attr( $args['label_for'] ); ?>"
    data-custom="<?php echo esc_attr( $args['deployment_button_custom_data'] ); ?>"
    name="deployment_button_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
    type="text"
    value="<?php echo esc_attr( $deployment_button_field_branchinfo ); ?>"
  >
  <p class="description">
  <?php esc_html_e( 'File in '. ABSPATH . ' containing branch info', 'deployment_button' ); ?>
  </p>
<?php
  }/*}}}*/

  static function deployment_button_settings_init()
  {/*{{{*/
    global $deployment_button_instance;
    register_setting( 'deployment_button', 'deployment_button_options' );
    add_settings_section(
      'deployment_button_section_settings',
      __( 'Action taken on selecting "Deploy"', 'deployment_button' ),
      array($deployment_button_instance,'deployment_button_section_settings_cb'),
      'deployment_button'
    );

    add_settings_field(
      'deployment_button_field_filename',
      __('Trigger filename', 'deployment_button'),
      array($deployment_button_instance,'deployment_button_field_filename_cb'),
      'deployment_button',
      'deployment_button_section_settings',
      [
        'label_for' => 'deployment_button_field_filename',
        'class' => 'deployment_button_row',
        'deployment_button_custom_data' => 'custom',
      ]
    );

    add_settings_field(
      'deployment_button_field_branchinfo',
      __('Branch name file', 'deployment_button'),
      array($deployment_button_instance,'deployment_button_field_branchinfo_cb'),
      'deployment_button',
      'deployment_button_section_settings',
      [
        'label_for' => 'deployment_button_field_branchinfo',
        'class' => 'deployment_button_row',
        'deployment_button_custom_data' => 'custom',
      ]
    );

    add_settings_field(
      'deployment_button_field_targeturl',
      __('Full Destination URL', 'deployment_button'),
      array($deployment_button_instance,'deployment_button_field_targeturl_cb'),
      'deployment_button',
      'deployment_button_section_settings',
      [
        'label_for' => 'deployment_button_field_targeturl',
        'class' => 'deployment_button_row',
        'deployment_button_custom_data' => 'custom',
      ]
    );


  }/*}}}*/

  static function custom_toolbar_link($wp_admin_bar)
  {/*{{{*/
    if ( !is_admin() ) return;
    $siteurl = get_option('siteurl');
    $options = get_option( 'deployment_button_options' );
    $deployment_button_field_branchinfo = $options['deployment_button_field_branchinfo'];
    $deployment_button_field_targeturl = $options['deployment_button_field_targeturl'];
    $branchfile = ABSPATH . '/' . $deployment_button_field_branchinfo;
    $gitbranch = "-";
    if ( 0 < strlen( $deployment_button_field_targeturl ) )
      $gitbranch = $deployment_button_field_targeturl;
    else if ( file_exists( $branchfile ) && is_file( $branchfile ) )
      $gitbranch = file_get_contents( $branchfile );
    $args = [
      array(
        'id' => 'deployment-button-trigger',
        'title' => "Deploy {$gitbranch}",
        'meta' => array(
          'class' => 'deployment-button-trigger',
          'title' => "Trigger a deployment from branch '{$gitbranch}' on {$_SERVER['SERVER_NAME']}",
          'id' => 'deployment-button-trigger'
        )
      )
    ];
    foreach ( $args as $arg ) $wp_admin_bar->add_node($arg);
  }/*}}}*/

  static function deployment_button_admin_settings_page()
  {/*{{{*/
    if ( !current_user_can('manage_options') ) {
      return;
    }

    if ( isset( $_GET['settings-updated'] ) ) {
      add_settings_error( 'deployment_button_messages', 'deployment_button_message', __( 'Settings Saved', 'deployment_button' ), 'updated' );
    }
?>
    <div class="wrap">
      <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
      <form action="options.php" method="post">
<?php
    // output security fields for the registered setting "wporg_options"
    settings_fields( 'deployment_button' );
    // output setting sections and their fields
    // (sections are registered for "wporg", each field is registered to a specific section)
    do_settings_sections( 'deployment_button' );
    // output save settings button
    submit_button( __( 'Save Settings', 'textdomain' ) );
?>
      </form>
    </div>
<?php
  }/*}}}*/

  static function deployment_button_add_admin_submenu()
  {/*{{{*/
    global $deployment_button_instance;
    add_submenu_page(
      'options-general.php',
      'Deployment Settings',
      'Deployment',
      'manage_options',
      'deploy-trigger',
      array($deployment_button_instance,'deployment_button_admin_settings_page')
    );
  }/*}}}*/

  static function get_sessioninfo()
  {/*{{{*/
    # Get session identifier for the currently logged-in user
    $sessioninfo = NULL;
    foreach ( $_COOKIE as $k => $v ) {
      if ( 1 == preg_match( '/^wordpress_logged_in_(.*)$/', $k ) ) {
        $sessioninfo = explode('|', $v);
        if ( is_array($sessioninfo) && array_key_exists(2, $sessioninfo) ) {
          $sessioninfo = $sessioninfo[2];
        }
        else {
          $sessioninfo = NULL;
        }
        break;
      }
    }
    return $sessioninfo;
  }/*}}}*/

  static function enqueue_scripts( $hook )
  {/*{{{*/
    wp_enqueue_script( 
      'deploy-button-ajax', 
      plugins_url( '/js/deploy-button.js', __FILE__ ), 
      array('jquery')
    );
    $deploy_css = plugins_url( basename( plugin_dir_path(__FILE__) ) ) . '/css/deploy-button.css';
    wp_enqueue_style('deploy_button_stylesheet', $deploy_css);
    $trigger_nonce = wp_create_nonce( 'trigger' );
    $queryid = self::get_sessioninfo();
    $admin_url = admin_url('admin-ajax.php');
    syslog( LOG_INFO, "Enqueueing $hook with ajax_url = {$admin_url}, queryid {$queryid}, nonce {$trigger_nonce}" );
    wp_localize_script( 'deploy-button-ajax', 'deploy_button_ajax_obj', array(
      'ajax_url' => $admin_url,
      'nonce'    => $trigger_nonce,
      'queryid'  => $queryid,
    ) );
  }/*}}}*/

  static function unparse_url($parsed_url) {
    $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
    $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
    $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
    $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
    $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
    return "{$scheme}{$host}{$port}{$path}{$query}";
  }

  static function deploy_query()
  {
    $reply = [];

    global $skipkeys;
    $skipkeys = NULL;
    $options = get_option( 'deployment_button_options' );
    $deploystate = get_option( 'deployment_button_state' );
    $stepinfo = get_option( 'deployment_button_stepinfo' );
    $current_user = wp_get_current_user();
    $deployment_button_field_targeturl = $options['deployment_button_field_targeturl'];

    $sessioninfo = self::get_sessioninfo();

    $reply = [
      'target' => $deployment_button_field_targeturl,
      'trackby' => $sessioninfo,
      'requester' => $current_user->data->user_login,
      'state' => $deploystate,
      'info' => $stepinfo,
      'interval' => 10000,
    ];

    if ( $stepinfo == "Done" )
      $deploystate = "Done";

    switch ( $deploystate ) {
      case "Pending":
        $reply['interval'] = 2000;
        break;
      case "Error":
        $reply['interval'] = 5000;
        break;
      case "Done":
        $reply['interval'] = 10000;
        $parsed_target = parse_url($deployment_button_field_targeturl);
        $parsed_target['query'] = "ver=" . bin2hex(random_bytes(32));
        $reply['target'] = self::unparse_url($parsed_target);
        $parsed_target = parse_url($reply['target']);
        update_option( 'deployment_button_state', '' );
        update_option( 'deployment_button_stepinfo', '' );
        break;
    }

    return $reply;
  }
  static function deploy_trigger()
  {/*{{{*/

    global $skipkeys;
    $skipkeys = NULL;
    $options = get_option( 'deployment_button_options' );
    $deploystate = get_option( 'deployment_button_state' );
    $current_user = wp_get_current_user();
    $deployment_button_field_filename = $options['deployment_button_field_filename'];
    $deployment_button_field_targeturl = $options['deployment_button_field_targeturl'];

    $trigger_file = ABSPATH . '/' . $deployment_button_field_filename;

    $sessioninfo = self::get_sessioninfo();

    $reply = [
      'trackby' => $sessioninfo,
      'requester' => $current_user->data->user_login,
    ];

    if ( 0 == strlen($deploystate) ) {

      syslog( LOG_INFO, "Dropping trigger file into {$trigger_file}" );
      $dropped = "Yes";

      if ( file_exists($trigger_file) )
        unlink($trigger_file);
      $trigger_data = [
        'requester' => $current_user->data->user_login,
        'targeturl' => [ 
          'full_url' => $deployment_button_field_targeturl,
          'parts' => parse_url($deployment_button_field_targeturl)
        ],
      ];
      if ( !file_put_contents( $trigger_file, json_encode(array_merge(
        $_SERVER,
        $trigger_data 
      ))) ) {
        $dropped = "No";
      }
      else {
        update_option('deployment_button_state', 'Pending');
        update_option('deployment_button_stepinfo', 'Pending');
      }
    }
    else {
      $dropped = "No";
    }

    $reply['dropped'] = $dropped;
    $reply['stepinfo'] = get_option('deployment_button_stepinfo');

    return $reply;

  }/*}}}*/

  static function deploy_handler()
  {/*{{{*/
    $reply = [];
    switch( substr($_REQUEST['action'],0,10) ) {
      case 'query':
        $reply = self::deploy_query();
        break;
      case 'trigger':
        $reply = self::deploy_trigger();
        break;
      default:
        break;
    }

    // static::recursive_dump($reply,'-#');    
    $response = json_encode($reply);
    header('Content-Length: ' . strlen($response));
    header('Content-Type: application/json');
    echo($response);
    exit(0);

  }/*}}}*/

}

register_activation_hook( __FILE__, array($deployment_button_instance, 'deployment_button_activation_hook'));
register_activation_hook( __FILE__, array($deployment_button_instance, 'deployment_button_deactivation_hook'));

add_action('admin_init'           , array($deployment_button_instance, 'load_plugin'));
add_action('admin_init'           , array($deployment_button_instance, 'deployment_button_settings_init'));
add_action('admin_bar_menu'       , array($deployment_button_instance, 'custom_toolbar_link'), 999);
add_action('admin_menu'           , array($deployment_button_instance, 'deployment_button_add_admin_submenu'));
add_action('admin_enqueue_scripts', array($deployment_button_instance, 'enqueue_scripts') );

add_action('wp_ajax_trigger', array($deployment_button_instance, 'deploy_handler') );
add_action('wp_ajax_query', array($deployment_button_instance, 'deploy_handler') );


