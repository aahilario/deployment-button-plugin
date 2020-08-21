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

  static function deployment_button_field_filename_cb( $args )
  {/*{{{*/
    $options = get_option( 'deployment_button_options' );
    $deployment_button_field_filename = $options['deployment_button_field_filename'];
?>
  <input id="<?php echo esc_attr( $args['label_for'] ); ?>"
    data-custom="<?php echo esc_attr( $args['deployment_button_custom_data'] ); ?>"
    name="deployment_button_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
    type="text"
    value="<?php echo esc_attr( $deployment_button_field_filename ); ?>"
  >
  <p class="description">
  <?php esc_html_e( 'Name of file to place in '. get_home_path(), 'deployment_button' ); ?>
  </p>
<?php
  }/*}}}*/

  static function deployment_button_field_branchinfo_cb( $args )
  {/*{{{*/
    $options = get_option( 'deployment_button_options' );
    $deployment_button_field_branchinfo = $options['deployment_button_field_branchinfo'];
?>
  <input id="<?php echo esc_attr( $args['label_for'] ); ?>"
    data-custom="<?php echo esc_attr( $args['deployment_button_custom_data'] ); ?>"
    name="deployment_button_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
    type="text"
    value="<?php echo esc_attr( $deployment_button_field_branchinfo ); ?>"
  >
  <p class="description">
  <?php esc_html_e( 'File in '. get_home_path() . 'containing branch info', 'deployment_button' ); ?>
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


  }/*}}}*/

  static function custom_toolbar_link($wp_admin_bar)
  {/*{{{*/
    if ( !is_admin() ) return;
    $siteurl = get_option('siteurl');
    $options = get_option( 'deployment_button_options' );
    $deployment_button_field_branchinfo = $options['deployment_button_field_branchinfo'];
    $branchfile = get_home_path() . $deployment_button_field_branchinfo;
    $gitbranch = "-";
    if ( file_exists( $branchfile ) )
      $gitbranch = file_get_contents( $branchfile );
    $args = [
      array(
        'id' => 'deployment-button-trigger',
        'title' => 'Deploy ' . $gitbranch,
        'meta' => array(
          'class' => 'deployment-button-trigger',
          'title' => "Trigger a deployment from branch '{$gitbranch}' on " . get_home_path(),
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
      // add settings saved message with the class of "updated"
      add_settings_error( 'deployment_button_messages', 'deployment_button_message', __( 'Settings Saved', 'deployment_button' ), 'updated' );
    }

    // settings_errors( 'deployment_button_messages' );

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

  static function enqueue_scripts( $hook )
  {/*{{{*/
    wp_enqueue_script( 
      'deploy-button-ajax', 
      plugins_url( '/js/deploy-button.js', __FILE__ ), 
      array('jquery')
    );
    $trigger_nonce = wp_create_nonce( 'trigger' );
    syslog( LOG_INFO, "Enqueueing $hook with ajax_url = " . admin_url('admin-ajax.php') );
    wp_localize_script( 'deploy-button-ajax', 'deploy_button_ajax_obj', array(
      'ajax_url' => admin_url( 'admin-ajax.php' ),
      'nonce'    => $trigger_nonce,
    ) );
  }/*}}}*/

  static function deploy_trigger()
  {/*{{{*/

    $options = get_option( 'deployment_button_options' );
    $current_user = wp_get_current_user();
    $deployment_button_field_filename = $options['deployment_button_field_filename'];

    syslog( LOG_INFO, "Received trigger" );
    $trigger_file = get_home_path() . $deployment_button_field_filename;
    $dropped = "Yes";
    if ( !file_exists($trigger_file) )
      file_put_contents( $trigger_file, json_encode(array_merge($_SERVER,['requester' => $current_user->data->user_login])));
    else
      $dropped = "No";
    $reply = [
      'dropped' => $dropped,
      'requester' => $current_user->data->user_login
    ];
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

add_action('wp_ajax_trigger', array($deployment_button_instance, 'deploy_trigger') );
