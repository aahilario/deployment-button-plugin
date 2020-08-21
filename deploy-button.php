<?php
/**
 * Plugin Name: Deployment Button
 * Plugin URI: https://github.com/aahilario/deployment-button-plugin/
 * Description: A plugin that creates a configurable, one-line file in the base directory of a WordPress installation.
 * Version: 0.1
 * Author: Antonio VA Hilario
 * Author URI: https://github.com/aahilario/
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( !function_exists('deployment_button_activation_hook') )
{
  function deployment_button_activation_hook()
  {/*{{{*/
    add_option( 'deployment_button_active', 'yes' );
  }/*}}}*/
}

register_activation_hook( __FILE__, 'deployment_button_activation_hook' );

if ( !function_exists('deployment_button_deactivation_hook') )
{
  function deployment_button_deactivation_hook()
  {/*{{{*/
  }/*}}}*/
}

register_activation_hook( __FILE__, 'deployment_button_deactivation_hook' );

if ( !function_exists('deployment_button_init') )
{
  function deployment_button_init()
  {/*{{{*/
  }/*}}}*/
}

if ( !function_exists('load_plugin') )
{
  function load_plugin()
  {/*{{{*/
    if ( is_admin() && get_option( 'deployment_button_active' ) == 'yes' )
    {
      delete_option( 'deployment_button_active' );
      add_action( 'init', 'deployment_button_init' );
    }
  }/*}}}*/
}

add_action( 'admin_init', 'load_plugin' );

/**
 * custom option and settings:
 * callback functions
 */
function deployment_button_section_settings_cb( $args )
{/*{{{*/
  // developers section cb
  // section callbacks can accept an $args parameter, which is an array.
  // $args have the following keys defined: title, id, callback.
  // the values are defined at the add_settings_section() function.
?>
<p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Follow the white rabbit.', 'deployment_button' ); ?></p>
<?php
}/*}}}*/

function deployment_button_field_filename_cb( $args )
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

function deployment_button_settings_init()
{/*{{{*/
  register_setting( 'deployment_button', 'deployment_button_options' );
  add_settings_section(
    'deployment_button_section_settings',
    __( 'Action taken on selecting "Deploy"', 'deployment_button' ),
    'deployment_button_section_settings_cb',
    'deployment_button'
  );

  add_settings_field(
    'deployment_button_field_filename',
    __('Filename', 'deployment_button'),
    'deployment_button_field_filename_cb',
    'deployment_button',
    'deployment_button_section_settings',
    [
      'label_for' => 'deployment_button_field_filename',
      'class' => 'deployment_button_row',
      'deployment_button_custom_data' => 'custom',
    ]
  );

}/*}}}*/

add_action( 'admin_init', 'deployment_button_settings_init' );

if ( !function_exists('custom_toolbar_link') )
{
  function custom_toolbar_link($wp_admin_bar)
  {/*{{{*/
    if ( !is_admin() ) return;
    $siteurl = get_option('siteurl');
    $gitbranch = "-";
    $branchfile = get_home_path() . '/branch.txt';
    if ( file_exists( $branchfile ) )
      $gitbranch = file_get_contents( $branchfile );
    $args = [
      array(
        'id' => 'deployment-button-trigger',
        'title' => 'Deploy ' . $gitbranch,
        // 'href' => plugins_url() . '/execute.php',
        'meta' => array(
          'class' => 'deployment-button-trigger',
          'title' => "Trigger a deployment from branch '{$gitbranch}' on " . get_home_path()
        )
      )
    ];
    foreach ( $args as $arg ) $wp_admin_bar->add_node($arg);
  }/*}}}*/
}

add_action('admin_bar_menu', 'custom_toolbar_link', 999);

if ( !function_exists('deployment_button_admin_settings_page') )
{
  function deployment_button_admin_settings_page()
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
}

if ( !function_exists('deployment_button_add_admin_submenu') )
{
  function deployment_button_add_admin_submenu()
  {/*{{{*/
    add_submenu_page(
      'options-general.php',
      'Deployment Settings',
      'Deployment',
      'manage_options',
      'deploy-trigger',
      'deployment_button_admin_settings_page'
    );
  }/*}}}*/
}

add_action('admin_menu', 'deployment_button_add_admin_submenu');

if ( !function_exists('deployment_button_foo') )
{
  function deployment_button_foo()
  {
  }
}


