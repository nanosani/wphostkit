<?php
/**
 * Template Name: WHM All Servers
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
$servers_table  = $wpdb->prefix . 'whm_servers';
$accounts_table = $wpdb->prefix . 'whm_accounts';

// Fetch servers
$servers = $wpdb->get_results( "SELECT id, name, server_url, acc_limit, username FROM {$servers_table}" );
foreach ( $servers as $srv ) {
	$srv->used_accounts = (int) $wpdb->get_var(
		$wpdb->prepare( "SELECT COUNT(*) FROM {$accounts_table} WHERE server_id = %d", $srv->id )
	);
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

		<!-- Header with Add / Manage buttons -->
		<div class="dashboard-header">
			<div class="dashboard-info">
				<h1>Manage Servers</h1>
				<div>Manage your servers</div>
			</div>
			<div class="dashboard-action">
				<a href="<?php echo esc_url( site_url( '/whm-add-edit-server/' ) ); ?>">
					<button class="add-server">Add New Server</button>
				</a>
				<a href="<?php echo esc_url( site_url( '/whm-multiple-servers/' ) ); ?>">
					<button class="manage-all-servers">Manage All Servers</button>
				</a>
			</div>
		</div>

		<!-- Servers Table -->
		<table class="whm-servers-table">
			<thead>
				<tr>
					<th>Server Name</th>
					<th>Connection</th>
					<th>Accounts Quota</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( $servers ) : ?>
					<?php foreach ( $servers as $srv ) : 
						$percent = $srv->acc_limit > 0
							? round( ( $srv->used_accounts / $srv->acc_limit ) * 100 )
							: 0;
					?>
					<tr data-server-id="<?php echo esc_attr( $srv->id ); ?>">
						<td><?php echo esc_html( $srv->name ); ?></td>
						<td class="server-status">Checking…</td>
						<td>
							<div class="quota-bar">
								<div class="quota-fill" style="width:<?php echo esc_attr( $percent ); ?>%;"></div>
								<span class="quota-text"><?php echo esc_html( "{$srv->used_accounts} / {$srv->acc_limit}" ); ?></span>
							</div>
						</td>
						<td class="actions">
							<a href="<?php echo esc_url( site_url( "/whm-single-server-details/?id={$srv->id}" ) ); ?>">
								<button class="manage">Manage</button>
							</a>
							<a href="<?php echo esc_url( site_url( "/whm-add-edit-server/?id={$srv->id}" ) ); ?>">
								<button class="edit">Edit</button>
							</a>
							<a href="<?php echo esc_url( site_url( "/whm-map-server-plans/?id={$srv->id}" ) ); ?>">
								<button class="map">Map Plans</button>
							</a>
							<button class="delete-server" data-server-id="<?php echo esc_attr( $srv->id ); ?>">Delete</button>
							<button class="ping-server" data-server-id="<?php echo esc_attr( $srv->id ); ?>">Ping API</button>
						</td>
					</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="4">No servers found.</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>

	</main>
</div>

<?php
get_footer();