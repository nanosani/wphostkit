<?php
/**
 * Template Name: WHM Add/Edit Client
 */
defined( 'ABSPATH' ) || exit;

get_header();

//  — Login & permission checks —
if ( ! is_user_logged_in() ) {
    echo '<center><div class="whm-login-message"><p>'
       . esc_html__( 'Please log in to WordPress to access this page.', 'whm-dhm' )
       . '</p></div></center>';
    get_footer();
    exit;
}
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'Unauthorized access', 'whm-dhm' ) );
}

global $wpdb;

// Fetch query params
$server_id = intval( $_GET['id'] ?? 0 );
$username  = sanitize_text_field( $_GET['username'] ?? '' );

// Table names
$servers_table   = $wpdb->prefix . 'whm_servers';
$accounts_table  = $wpdb->prefix . 'whm_accounts';
$plans_map_table = $wpdb->prefix . 'whm_plans_maps';

// 1) Load server record
$server = $wpdb->get_row(
    $wpdb->prepare( "SELECT * FROM {$servers_table} WHERE id = %d", $server_id )
);
if ( ! $server ) {
    echo '<p>' . esc_html__( 'Server not found.', 'whm-dhm' ) . '</p>';
    get_footer();
    exit;
}

// Decrypt token & get API user
$token    = WHM_DHM_Encryption::decrypt( $server->token );
$api_user = $server->username;

// 2) If editing an account, fetch its summary from WHM
$account_summary = null;
if ( $username ) {
    $resp = WHM_DHM_WHM_API::get_account_summary( $username, $server_id );
    if ( ! is_wp_error( $resp )
         && isset( $resp['data']['acct'][0] )
         && is_array( $resp['data']['acct'][0] )
    ) {
        $account_summary = $resp['data']['acct'][0];
    }
}

// 3) Fetch local DB record (if any)
$local_account = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * FROM {$accounts_table} WHERE username = %s AND server_id = %d",
        $username,
        $server_id
    )
);

// Prepare form defaults safely
$email_value = '';
if ( $account_summary && ! empty( $account_summary['email'] ) ) {
    $email_value = $account_summary['email'];
} elseif ( $local_account && isset( $local_account->email ) ) {
    $email_value = $local_account->email;
}

$current_plan = $local_account->plan ?? '';

// 4) Fetch mapped WooCommerce→WHM plans
$mapped_plans = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT plan_name, product_id FROM {$plans_map_table} WHERE server_id = %d",
        $server_id
    ),
    ARRAY_A
);
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
    <h1>
      <?php echo $username
        ? esc_html__( 'Edit Client Account', 'whm-dhm' )
        : esc_html__( 'Add New Account', 'whm-dhm' ); ?>
    </h1>

    <section class="add-edit-user">
      <div id="form-message"></div>
      <form id="client-form">
        <input type="hidden" name="action"    value="whm_save_client">
        <input type="hidden" name="server_id" value="<?php echo esc_attr( $server_id ); ?>">
        <input type="hidden" name="username"  value="<?php echo esc_attr( $username ); ?>">

        <?php if ( ! $username ): ?>
          <label for="new_username"><?php esc_html_e( 'Username:', 'whm-dhm' ); ?></label>
          <input type="text" id="new_username" name="new_username" required>

          <label for="password"><?php esc_html_e( 'Password:', 'whm-dhm' ); ?></label>
          <input type="password" id="password" name="password" required>

          <label for="domain"><?php esc_html_e( 'Domain:', 'whm-dhm' ); ?></label>
          <input type="text" id="domain" name="domain" required>
        <?php else: ?>
          <p><strong><?php esc_html_e( 'Username:', 'whm-dhm' ); ?></strong> <?php echo esc_html( $username ); ?></p>
          <label for="password"><?php esc_html_e( 'New Password (optional):', 'whm-dhm' ); ?></label>
          <input type="password" id="password" name="password">
        <?php endif; ?>

        <label for="email"><?php esc_html_e( 'Email:', 'whm-dhm' ); ?></label>
        <input
          type="email"
          id="email"
          name="email"
          value="<?php echo esc_attr( $email_value ); ?>"
          required
        >

        <label for="plan"><?php esc_html_e( 'Plan:', 'whm-dhm' ); ?></label>
        <select id="plan" name="plan" required>
          <?php if ( $mapped_plans ): ?>
            <?php foreach ( $mapped_plans as $map ) :
              $val   = $map['plan_name'];
              $pid   = intval( $map['product_id'] );
              $prod  = wc_get_product( $pid );
              $label = $prod ? $prod->get_name() : $val;
            ?>
              <option
                value="<?php echo esc_attr( $val ); ?>"
                <?php selected( $current_plan, $val ); ?>
              >
                <?php echo esc_html( $label ); ?>
              </option>
            <?php endforeach; ?>
          <?php else: ?>
            <option value="">
              <?php esc_html_e( 'No plans mapped for this server', 'whm-dhm' ); ?>
            </option>
          <?php endif; ?>
        </select>

        <div class="wp-user-manage">
          <label for="wp-user-dropdown"><?php esc_html_e( 'Assign to WP User:', 'whm-dhm' ); ?></label>
          <select id="wp-user-dropdown" name="wp_user_id" required>
            <option value=""><?php esc_html_e( 'Select User', 'whm-dhm' ); ?></option>
          </select>

          <button type="button" id="add-new-wp-user" class="button">
            <?php esc_html_e( 'Add New WP User', 'whm-dhm' ); ?>
          </button>
        </div>

        <button type="submit" class="button save">
          <?php esc_html_e( 'Save', 'whm-dhm' ); ?>
        </button>
      </form>

      <!-- Pop-up UI omitted for brevity… -->
    </section>
  </main>
</div>

<?php get_footer(); ?>
