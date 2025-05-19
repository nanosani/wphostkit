<?php
/**
 * Template Name: WHM Map Server Plans
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

// Get server ID
$server_id = 0;
if ( get_query_var( 'id' ) ) {
    $server_id = intval( get_query_var( 'id' ) );
} elseif ( isset( $_GET['id'] ) ) {
    $server_id = intval( $_GET['id'] );
}
if ( ! $server_id ) {
    echo '<p>' . esc_html__( 'Server ID not provided.', 'whm-dhm' ) . '</p>';
    get_footer();
    exit;
}

// Fetch server record
global $wpdb;
$table = $wpdb->prefix . 'whm_servers';
$server = $wpdb->get_row( $wpdb->prepare(
    "SELECT * FROM {$table} WHERE id = %d",
    $server_id
) );
if ( ! $server ) {
    echo '<p>' . esc_html__( 'Server not found.', 'whm-dhm' ) . '</p>';
    get_footer();
    exit;
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
        <h1><?php esc_html_e( 'Products Mapping', 'whm-dhm' ); ?></h1>
        <div><?php esc_html_e( 'Proposed Maps for WHM Plans', 'whm-dhm' ); ?></div>
      </div>
    </div>

    <section class="maps-list">
      <div id="form-message"></div>

      <table id="plans-table">
        <thead>
          <tr>
            <th><?php esc_html_e( 'Plan', 'whm-dhm' ); ?></th>
            <th><?php esc_html_e( 'Product', 'whm-dhm' ); ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </section>
  </main>
</div>

<?php get_footer(); ?>