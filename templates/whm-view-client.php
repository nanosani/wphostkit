<?php
/**
 * Template Name: WHM View Client
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

// Check for required URL parameters
if (!isset($_GET['id']) || !isset($_GET['username'])) {
    echo "<p>Server ID or username not specified.</p>";
    get_footer();
    exit;
}

$server_id = intval($_GET['id']);
$username = sanitize_text_field($_GET['username']);

global $wpdb;

$server = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}whm_servers WHERE id = %d",
        $server_id
    )
);

if ( ! $server ) {
    echo '<p>' . esc_html__( 'Server not found.', 'whm-dhm' ) . '</p>';
    get_footer();
    exit;
}

// Decrypt the API token
$token = WHM_DHM_Encryption::decrypt( $server->token );

// Function to fetch account summary
$account_summary = WHM_DHM_WHM_API::get_account_summary( $username, $server_id );

if ( ! $account_summary || empty( $account_summary['data']['acct'][0] ) ) {
    /* translators: %s: username */
    echo '<p>' . sprintf( esc_html__( 'Unable to retrieve account details for username: %s', 'whm-dhm' ), esc_html( $username ) ) . '</p>';
    get_footer();
    exit;
}

$acct = $account_summary['data']['acct'][0];
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
    <div class="dashboard-action">
      <button onclick="history.back()">&lt; <?php esc_html_e( 'Back', 'whm-dhm' ); ?></button>
    </div>

    <h2><?php esc_html_e( 'Account Details', 'whm-dhm' ); ?></h2>

    <div class="whm-account-header" style="border:1px solid #ccc;padding:15px;margin-bottom:20px;">
      <p><strong><?php esc_html_e( 'Username:', 'whm-dhm' ); ?></strong> <?php echo esc_html( $acct['user'] ); ?></p>
      <p><strong><?php esc_html_e( 'Email:', 'whm-dhm' ); ?></strong> <?php echo esc_html( $acct['email'] ); ?></p>
      <p><strong><?php esc_html_e( 'Domain:', 'whm-dhm' ); ?></strong> <?php echo esc_html( $acct['domain'] ); ?></p>
    </div>

    <table class="whm-account-table" style="width:100%;border-collapse:collapse;">
      <thead>
        <tr>
          <th><?php esc_html_e( 'Field', 'whm-dhm' ); ?></th>
          <th><?php esc_html_e( 'Value', 'whm-dhm' ); ?></th>
        </tr>
      </thead>
      <tbody>
        <tr><td><?php esc_html_e( 'IP', 'whm-dhm' ); ?></td><td><?php echo esc_html( $acct['ip'] ); ?></td></tr>
        <tr>
          <td><?php esc_html_e( 'Status', 'whm-dhm' ); ?></td>
          <td>
            <?php echo $acct['suspended'] ? esc_html__( 'Suspended', 'whm-dhm' ) : esc_html__( 'Active', 'whm-dhm' ); ?>
          </td>
        </tr>
        <tr><td><?php esc_html_e( 'Plan', 'whm-dhm' ); ?></td><td><?php echo esc_html( $acct['plan'] ); ?></td></tr>
        <tr><td><?php esc_html_e( 'Creation Date', 'whm-dhm' ); ?></td><td><?php echo esc_html( $acct['startdate'] ); ?></td></tr>
        <tr>
          <td><?php esc_html_e( 'Disk Used / Limit', 'whm-dhm' ); ?></td>
          <td><?php echo esc_html( "{$acct['diskused']} / {$acct['disklimit']} MB" ); ?></td>
        </tr>
        <tr>
          <td><?php esc_html_e( 'Inodes Used / Limit', 'whm-dhm' ); ?></td>
          <td><?php echo esc_html( "{$acct['inodesused']} / {$acct['inodeslimit']}" ); ?></td>
        </tr>
      </tbody>
    </table>
  </main>
</div>

<?php
get_footer();