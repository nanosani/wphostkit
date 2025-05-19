<?php
/**
 * Template Name: WHM Dashboard
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

// Fetch all servers
$servers = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}whm_servers" );

// Fetch localized accounts from all servers with the extra joins for WP user and plan mapping.
$localized_accounts = $wpdb->get_results(
    "SELECT wa.*, u.user_login AS wp_user, pm.product_id
     FROM {$wpdb->prefix}whm_accounts wa
     LEFT JOIN {$wpdb->prefix}users u ON wa.user_id = u.ID
     LEFT JOIN {$wpdb->prefix}whm_plans_maps pm ON wa.plan = pm.plan_name
     WHERE wa.user_id IS NOT NULL AND wa.on_whm = 'yes'"
);
?>
<div class="admin-container">
	<aside class="sidebar">
		<ul>
			<li><a href="<?php echo esc_url( site_url( '/whm-dashboard/' ) ); ?>" class="active"><?php esc_html_e( 'Dashboard', 'whm-dhm' ); ?></a></li>
			<li><a href="<?php echo esc_url( site_url( '/whm-all-servers/' ) ); ?>"><?php esc_html_e( 'Manage Servers', 'whm-dhm' ); ?></a></li>
			<li><a href="<?php echo esc_url( site_url( '/whm-manage-products/' ) ); ?>"><?php esc_html_e( 'Manage Products', 'whm-dhm' ); ?></a></li>
			<li><a href="<?php echo esc_url( site_url( '/whm-manage-orders/' ) ); ?>"><?php esc_html_e( 'Manage Orders', 'whm-dhm' ); ?></a></li>
			<li><a href="<?php echo esc_url( site_url( '/whm-manage-subscriptions/' ) ); ?>"><?php esc_html_e( 'Manage Subscriptions', 'whm-dhm' ); ?></a></li>
		</ul>
	</aside>

	<main class="dashboard-content">
        <div class="dashboard-header">
            <div class="dashboard-info">
                <h1>Admin Dashboard</h1>
                <div>System Status</div>
            </div>
            <div class="dashboard-action">
                <button id="refresh-dashboard" class="refresh-btn">Refresh</button>
            </div>
        </div>
        
        <section class="system-status">
            <div class="status-grid">
                <a href="<?php echo site_url('/whm-all-servers/'); ?>">
                    <div class="status-card servers">
                        <div class="card-info">
                            <p>0</p>
                            <h3>Servers</h3>
                        </div>
                        <div class="card-action">
                            <span>View Details</span>
                        </div>
                    </div>
                </a>
                <a href="<?php echo site_url('/whm-manage-products/'); ?>">
                    <div class="status-card products">
                        <div class="card-info">
                            <p>0</p>
                            <h3>Products</h3>
                        </div>
                        <div class="card-action">
                            <span>View Details</span>
                        </div>
                    </div>
                </a>
                <a href="<?php echo site_url('/whm-manage-orders/'); ?>">
                    <div class="status-card total-orders">
                        <div class="card-info">
                            <p>0</p>
                            <h3>Total Orders</h3>
                        </div>
                        <div class="card-action">
                            <span>View Details</span>
                        </div>
                    </div>
                </a>
                <a href="<?php echo site_url('/whm-manage-orders/'); ?>">
                    <div class="status-card unpaid-orders">
                        <div class="card-info">
                            <p>0</p>
                            <h3>Unpaid Orders</h3>
                        </div>
                        <div class="card-action">
                            <span>View Details</span>
                        </div>
                    </div>
                </a>
            </div>
        </section>
<br><br>

<div class="dashboard-header">
    <div class="dashboard-info">
        <h2>Local Accounts</h2>
        <div>Local Accounts on all servers</div>
    </div>
    <div class="dashboard-action">
        <a href="<?php echo site_url('/whm-multiple-servers/'); ?>">
            <button class="manage-all-servers">Manage All Servers</button>
        </a>
        <div class="filters">
            <div class="filter-item">
                <select id="filter-product">
                    <option value="">All Products</option>
                    <?php
                    // Fetch distinct plan values from the accounts table without filtering by a specific server.
                    $plans = $wpdb->get_col("SELECT DISTINCT plan FROM {$wpdb->prefix}whm_accounts WHERE user_id IS NOT NULL");
                    foreach ($plans as $plan) {
                        // Default product name is the plan value.
                        $product_name = $plan;
                        // Look up the mapping for this plan.
                        $mapping = $wpdb->get_row($wpdb->prepare(
                            "SELECT product_id FROM {$wpdb->prefix}whm_plans_maps WHERE plan_name = %s",
                            $plan
                        ));
                        if ($mapping && !empty($mapping->product_id)) {
                            $product = wc_get_product($mapping->product_id);
                            if ($product) {
                                $product_name = $product->get_name();
                            }
                        }
                        // Output the product name in lowercase as the option value for easier comparison.
                        echo "<option value='" . esc_attr(strtolower($product_name)) . "'>" . esc_html($product_name) . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="filter-item">
                <select id="filter-status">
                    <option value="">All Status</option>
                    <option value="Active">Active</option>
                    <option value="Suspended">Suspended</option>
                </select>
            </div>
            <div class="filter-item">
                <select id="filter-user">
                    <option value="">All Users</option>
                    <?php
                    // Fetch WP Users.
                    $users = $wpdb->get_results("SELECT ID, user_login FROM {$wpdb->prefix}users");
                    foreach ($users as $user) {
                        echo "<option value='" . esc_attr($user->user_login) . "'>" . esc_html($user->user_login) . "</option>";
                    }
                    ?>
                </select>
            </div>
        </div>
    </div>
</div>
        
<div class="local-table-container">
    <!-- Localized Accounts Table -->
    <table id="local-accounts-table">
        <thead>
            <tr>
                <th>Username</th>
                <th>Domain</th>
                <th>Creation Date</th>
                <th>Email</th>
                <th>Product</th>
                <th>Status</th>
                <th>WP User</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($localized_accounts)): ?>
                <?php foreach ($localized_accounts as $account): ?>
                    <?php
                        // Compute the product name (as in your cell output).
                        $product_name = $account->plan;
                        if (!empty($account->product_id)) {
                            $product = wc_get_product($account->product_id);
                            if ($product) {
                                $product_name = $product->get_name();
                            }
                        }
                    ?>
                    <tr class="account-row" 
                        data-product="<?php echo esc_attr($product_name); ?>" 
                        data-status="<?php echo esc_attr(strtolower($account->status)); ?>" 
                        data-user="<?php echo esc_attr($account->wp_user); ?>">
                    
                        <td><?php echo htmlspecialchars($account->username); ?></td>
                        <td><?php echo htmlspecialchars($account->domain); ?></td>
                        <td><?php echo htmlspecialchars($account->start_date); ?></td>
                        <td><?php echo htmlspecialchars($account->email); ?></td>
                        <td>
                            <?php 
                                // Set a fallback value (e.g. the original plan name) if no mapping is found.
                                $display_product = $account->plan; 
                                if (!empty($account->product_id)) {
                                    $product = wc_get_product($account->product_id);
                                    if ($product) {
                                        $display_product = $product->get_name();
                                    }
                                }
                                echo htmlspecialchars($display_product);
                            ?>
                        </td>
                        
                        <td>
                            <span class="account-status <?php echo strtolower($account->status); ?>">
                                <?php echo htmlspecialchars($account->status); ?>
                            </span>
                        </td>
                        <td><?php echo !empty($account->wp_user) ? htmlspecialchars($account->wp_user) : '-'; ?></td>
                        <td>
                            <a href="<?php echo site_url('/whm-view-client/?username=' . urlencode($account->username) . '&id=' . $account->server_id); ?>">
                                <button>View</button>
                            </a>
                            <a href="<?php echo site_url('/whm-add-edit-client/?username=' . urlencode($account->username) . '&id=' . $account->server_id); ?>">
                                <button>Edit</button>
                            </a>
                            <button class="login-to-cpanel" data-username="<?php echo htmlspecialchars($account->username); ?>" data-server-id="<?php echo $account->server_id; ?>">cPanel</button>
                            <?php if ($account->status === 'Suspended'): ?>
                                <button class="reactivate-account" data-username="<?php echo htmlspecialchars($account->username); ?>" data-server-id="<?php echo $account->server_id; ?>">Re-Activate</button>
                            <?php else: ?>
                                <button class="suspend-account" data-username="<?php echo htmlspecialchars($account->username); ?>" data-server-id="<?php echo $account->server_id; ?>">Suspend</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8">No localized accounts found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
    </main>
</div>
<script>
var WHM_DHM = {
    ajax_url : "<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>",
    nonce    : "<?php echo wp_create_nonce( 'whm_dhm_nonce' ); ?>"
};
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {

    /* ───────────────────────────────
     *  0.  helpers
     * ─────────────────────────────── */
    const $ = sel => document.querySelector(sel);

    function fireAjax(action, bodyObj, onSuccess) {
        bodyObj.action      = action;
        bodyObj._ajax_nonce = WHM_DHM.nonce;

        /* RETURN the fetch-promise so the caller can chain .then/.finally */
        return fetch(WHM_DHM.ajax_url, {
            method : 'POST',
            headers: { 'Content-Type':'application/x-www-form-urlencoded' },
            body   : new URLSearchParams(bodyObj),
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) { onSuccess && onSuccess(res.data); }
            else             { alert(res.data.message || 'Operation failed.'); }
        })
        .catch(e => { console.error(e); alert('AJAX error'); });
    }

    /* ───────────────────────────────
     *  1.  Refresh top four stats
     * ─────────────────────────────── */
    const refreshBtn = $('#refresh-dashboard');

    function refreshDashboard() {
        refreshBtn.disabled = true;
        refreshBtn.textContent = 'Refreshing…';

        fireAjax('refresh_whm_dashboard', {}, data => {
            $('.status-card.servers p').textContent       = data.servers;
            $('.status-card.products p').textContent      = data.products;
            $('.status-card.total-orders p').textContent  = data.total_orders;
            $('.status-card.unpaid-orders p').textContent = data.unpaid_orders;
        })
        .finally(() => {
            refreshBtn.disabled = false;
            refreshBtn.textContent = 'Refresh';
        });
    }

    /* run once & bind click */
    refreshDashboard();
    refreshBtn.addEventListener('click', refreshDashboard);

    /* ───────────────────────────────
     *  2.  Delegated button actions
     * ─────────────────────────────── */
    document.addEventListener('click', e => {
        const btn = e.target.closest(
            '.suspend-account, .reactivate-account, .login-to-cpanel'
        );
        if (!btn) return;

        const { username, serverId } = btn.dataset;

        if (btn.classList.contains('suspend-account')) {
            if (!confirm(`Suspend the account "${username}"?`)) return;
            fireAjax('whm_suspend_account',
                     { username, server_id: serverId },
                     () => location.reload());
        }

        if (btn.classList.contains('reactivate-account')) {
            if (!confirm(`Re-activate the account "${username}"?`)) return;
            fireAjax('whm_reactivate_account',
                     { username, server_id: serverId },
                     () => location.reload());
        }

        if (btn.classList.contains('login-to-cpanel')) {
            fireAjax('whm_login_to_cpanel',
                     { username, server_id: serverId },
                     d => window.open(d.url, '_blank'));
        }
    });

    /* ───────────────────────────────
     *  3.  Row-filters
     * ─────────────────────────────── */
    const productFilter = $('#filter-product');
    const statusFilter  = $('#filter-status');
    const userFilter    = $('#filter-user');
    const rows          = document.querySelectorAll('.account-row');

    function applyFilters() {
        const prod = productFilter.value.toLowerCase();
        const stat = statusFilter.value.toLowerCase();
        const usr  = userFilter.value.toLowerCase();

        rows.forEach(r => {
            const rp = r.dataset.product.toLowerCase();
            const rs = r.dataset.status.toLowerCase();
            const ru = r.dataset.user.toLowerCase();

            r.style.display =
                (!prod || rp === prod) &&
                (!stat || rs === stat) &&
                (!usr  || ru === usr) ? '' : 'none';
        });
    }

    productFilter.addEventListener('change', applyFilters);
    statusFilter .addEventListener('change', applyFilters);
    userFilter   .addEventListener('change', applyFilters);
});
</script>
<?php
get_footer();