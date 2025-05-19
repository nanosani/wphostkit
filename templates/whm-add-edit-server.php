<?php
/**
 * Template Name: WHM Add/Edit Server
 */
defined( 'ABSPATH' ) || exit;

get_header();

// Login & permission checks
if ( ! is_user_logged_in() ) {
    echo '<center><div class="whm-login-message"><p>' . esc_html__( 'Please log in to WordPress to access this page.', 'whm-dhm' ) . '</p></div></center>';
    get_footer();
    exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'Unauthorized access', 'whm-dhm' ) );
}

global $wpdb;
$servers_table = $wpdb->prefix . 'whm_servers';

// Fetch existing server (if editing)
$server_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
$server    = null;
if ( $server_id ) {
    $server = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$servers_table} WHERE id = %d",
        $server_id
    ) );
    if ( $server ) {
        // decrypt for display
        $server->token = WHM_DHM_Encryption::decrypt( $server->token );
    } else {
        echo '<p>Server not found.</p>';
        get_footer();
        exit;
    }
}
?>

<div class="admin-container">
    <aside class="sidebar">
        <ul>
            <li><a href="<?php echo esc_url( site_url( '/whm-dashboard/' ) ); ?>"><?php esc_html_e( 'Dashboard', 'whm-dhm' ); ?></a></li>
            <li><a href="<?php echo esc_url( site_url( '/whm-all-servers/' ) ); ?>" class="active"><?php esc_html_e( 'Manage Servers', 'whm-dhm' ); ?></a></li>
            <li><a href="<?php echo esc_url( site_url( '/whm-manage-products/' ) ); ?>"><?php esc_html_e( 'Manage Products', 'whm-dhm' ); ?></a></li>
            <li><a href="<?php echo esc_url( site_url( '/whm-manage-orders/' ) ); ?>"><?php esc_html_e( 'Manage Orders', 'whm-dhm' ); ?></a></li>
            <li><a href="<?php echo esc_url( site_url( '/whm-manage-subscriptions/' ) ); ?>"><?php esc_html_e( 'Manage Subscriptions', 'whm-dhm' ); ?></a></li>
        </ul>
    </aside>

  <main class="dashboard-content">
    <div class="dashboard-header">
      <div class="dashboard-info">
        <h2>
          <?php echo $server_id
            ? esc_html__( 'Edit Server', 'whm-dhm' )
            : esc_html__( 'Add New Server', 'whm-dhm' ); ?>
        </h2>
        <div>
          <?php echo $server_id
            ? esc_html__( 'Edit the Server', 'whm-dhm' )
            : esc_html__( 'Add a New Server', 'whm-dhm' ); ?>
        </div>
      </div>
    </div>

    <section class="system-status">
      <div id="form-message"></div>
      <div class="form-container">
        <form id="server-form">
          <input type="hidden" name="action" value="whm_save_server">
          <input type="hidden" id="server-id" name="id" value="<?php echo esc_attr( $server_id ); ?>">

          <label for="server-name"><?php esc_html_e( 'Server Name:', 'whm-dhm' ); ?></label>
          <input type="text" id="server-name" name="name"
                 value="<?php echo esc_attr( $server->name ?? '' ); ?>" required>

          <label for="server-username"><?php esc_html_e( 'Username:', 'whm-dhm' ); ?></label>
          <input type="text" id="server-username" name="username"
                 value="<?php echo esc_attr( $server->username ?? '' ); ?>" required>

          <label for="server-url"><?php esc_html_e( 'Server URL:', 'whm-dhm' ); ?></label>
          <input type="url" id="server-url" name="server_url"
                 value="<?php echo esc_attr( $server->server_url ?? '' ); ?>" required>

          <label for="server-token"><?php esc_html_e( 'API Token:', 'whm-dhm' ); ?></label>
          <input type="text" id="server-token" name="token"
                 value="<?php echo esc_attr( $server->token ?? '' ); ?>" required>

          <label for="server-acc-limit"><?php esc_html_e( 'Accounts Limit:', 'whm-dhm' ); ?></label>
          <input type="number" id="server-acc-limit" name="acc_limit"
                 value="<?php echo esc_attr( $server->acc_limit ?? '' ); ?>" required>

          <label for="client-login"><?php esc_html_e( 'Client Login URL:', 'whm-dhm' ); ?></label>
          <input type="url" id="client-login" name="client_login"
                 value="<?php echo esc_attr( $server->client_login ?? '' ); ?>" required>

          <label for="nameserver1"><?php esc_html_e( 'Nameserver 1:', 'whm-dhm' ); ?></label>
          <input type="text" id="nameserver1" name="nameserver1"
                 value="<?php echo esc_attr( $server->nameserver1 ?? '' ); ?>" required>

          <label for="nameserver2"><?php esc_html_e( 'Nameserver 2:', 'whm-dhm' ); ?></label>
          <input type="text" id="nameserver2" name="nameserver2"
                 value="<?php echo esc_attr( $server->nameserver2 ?? '' ); ?>" required>

          <label for="server-wp-user"><?php esc_html_e( 'WP User:', 'whm-dhm' ); ?></label>
          <select id="server-wp-user" name="user_id" required>
            <option value=""><?php esc_html_e( 'Select User', 'whm-dhm' ); ?></option>
          </select>

          <button type="submit" class="button save">
            <?php esc_html_e( 'Save', 'whm-dhm' ); ?>
          </button>
        </form>
      </div>
    </section>
  </main>
</div>

<?php get_footer(); ?>