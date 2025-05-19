<?php
/**
 * Template Name: WHM Server Details
 */
defined( 'ABSPATH' ) || exit;

get_header();

// Login & permission checks
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

// Fetch & Validate Server ID
$server_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
if ( ! $server_id ) {
    echo '<p>' . esc_html__( 'Server ID not specified.', 'whm-dhm' ) . '</p>';
    get_footer();
    exit;
}

global $wpdb;

// Fetch Localized Accounts with WP Users
$localized_accounts = $wpdb->get_results($wpdb->prepare(
  "SELECT wa.*, u.user_login AS wp_user, pm.product_id
   FROM {$wpdb->prefix}whm_accounts wa
   LEFT JOIN {$wpdb->prefix}users u ON wa.user_id = u.ID
   LEFT JOIN {$wpdb->prefix}whm_plans_maps pm 
       ON wa.plan = pm.plan_name AND pm.server_id = %d
   WHERE wa.server_id = %d AND wa.user_id IS NOT NULL AND wa.on_whm = 'yes'",
  $server_id, $server_id
));


// Fetch orphan accounts (accounts with no user_id)
$orphan_accounts = $wpdb->get_results($wpdb->prepare(
  "SELECT wa.*, pm.product_id
   FROM {$wpdb->prefix}whm_accounts wa
   LEFT JOIN {$wpdb->prefix}whm_plans_maps pm 
       ON wa.plan = pm.plan_name AND pm.server_id = %d
   WHERE wa.server_id = %d AND wa.user_id IS NULL",
  $server_id, $server_id
));


$local_orphan_accounts = $wpdb->get_results($wpdb->prepare(
  "SELECT wa.*, pm.product_id
   FROM {$wpdb->prefix}whm_accounts wa
   LEFT JOIN {$wpdb->prefix}whm_plans_maps pm 
       ON wa.plan = pm.plan_name AND pm.server_id = %d
   WHERE wa.server_id = %d AND wa.on_whm = 'no'",
  $server_id, $server_id
));



$table_name = $wpdb->prefix . 'whm_servers';
$accounts_table = $wpdb->prefix . 'whm_accounts';

// Fetch all servers and calculate account usage
$servers = $wpdb->get_results("SELECT * FROM $table_name WHERE id = $server_id");
foreach ($servers as &$server) {
  $server->used_accounts = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM $accounts_table WHERE server_id = %d",
      $server->id
  ));
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
                <h1><?php echo htmlspecialchars($server->name); ?></h1>
                <div>System Status</div>
                <div class="server-details">
                    <div style="display: flex; align-items: center; width: 100%; max-width: 200px;">
                        <?php
                            $total = $server->acc_limit;
                            $used = $server->used_accounts;
                            $percent = $total > 0 ? ($used / $total) * 100 : 0;
                        ?>
                        <div style="flex: 1; margin-right: 10px; height: 10px; background-color: #e0e0e0; border-radius: 5px; overflow: hidden;">
                            <div style="width: <?php echo esc_attr($percent); ?>%; background-color: <?php echo $percent < 80 ? '#4caf50' : '#f44336'; ?>; height: 100%;"></div>
                        </div>
                        <span><?php echo esc_html("$used / $total"); ?></span>
                    </div>
                </div>
            </div>
            <div class="dashboard-action">
                <a href="<?php echo site_url('/whm-add-edit-client/?id=' . $server_id); ?>">
                    <button>Add New Account</button>
                </a>
                <button id="refresh-accounts" data-server-id="<?php echo $server_id; ?>">Refresh Accounts</button>
            </div>
        </div>
        
        


        <section class="accounts-list" style="margin-top:35px;">
            <div class="dashboard-header">
                <div class="dashboard-info">
                    <h2>Local Accounts</h2>
                    <div>System Status</div>
                </div>
                <div class="dashboard-action">
                    <div class="filters">
                        <div class="filter-item">
                        <select id="filter-product">
                            <option value="">All Products</option>
                            <?php
                            // Fetch distinct plan values from the accounts table
                            $plans = $wpdb->get_col("SELECT DISTINCT plan FROM {$wpdb->prefix}whm_accounts WHERE server_id = $server_id AND user_id IS NOT NULL");
                            foreach ($plans as $plan) {
                                // Default product name is the plan value.
                                $product_name = $plan;
                                // Look up the mapping for this plan.
                                $mapping = $wpdb->get_row($wpdb->prepare(
                                    "SELECT product_id FROM {$wpdb->prefix}whm_plans_maps WHERE plan_name = %s AND server_id = %d",
                                    $plan,
                                    $server_id
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
                            // Fetch WP Users
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
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($localized_accounts)): ?>
            <?php foreach ($localized_accounts as $account): ?>
            <?php
// Compute the product name (as in your cell output)
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
                        // Set a fallback value (e.g. the original plan name) if no mapping is found
                        $product_name = $account->plan; 
                        if (!empty($account->product_id)) {
                            $product = wc_get_product($account->product_id);
                            if ($product) {
                                $product_name = $product->get_name();
                            }
                        }
                        echo htmlspecialchars($product_name);
                        ?>
                    </td>
                    
                    <td>
                        <span class="account-status <?php echo strtolower($account->status); ?>">
                            <?php echo htmlspecialchars($account->status); ?>
                        </span>
                    </td>
                    <td><?php echo !empty($account->wp_user) ? htmlspecialchars($account->wp_user) : '-'; ?></td>
                    <td>
                        <a href="<?php echo site_url('/whm-view-client/?username=' . urlencode($account->username) . '&id=' . $server_id); ?>">
                            <button>View</button>
                        </a>
                        <a href="<?php echo site_url('/whm-add-edit-client/?username=' . urlencode($account->username) . '&id=' . $server_id); ?>">
                            <button>Edit</button>
                        </a>
                        <button class="login-to-cpanel" data-username="<?php echo htmlspecialchars($account->username); ?>" data-server-id="<?php echo $server_id; ?>">cPanel</button>
                        <?php if ($account->status === 'Suspended'): ?>
                            <button class="reactivate-account" data-username="<?php echo htmlspecialchars($account->username); ?>" data-server-id="<?php echo $server_id; ?>">Re-Activate</button>
                        <?php else: ?>
                            <button class="suspend-account" data-username="<?php echo htmlspecialchars($account->username); ?>" data-server-id="<?php echo $server_id; ?>">Suspend</button>
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

<div class="whmOrphans-table-container">
<!-- WHM Orphan Accounts Table -->
<h2>WHM Orphan Accounts</h2>
<table>
    <thead>
        <tr>
            <th>Username</th>
            <th>Domain</th>
            <th>Creation Date</th>
            <th>Email</th>
            <th>Product</th>
            <th>Status</th>
            <th>WP User</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody id="whm-orphan-accounts-tbody">
    <?php if (!empty($orphan_accounts)): ?>
        <?php foreach ($orphan_accounts as $oaccount): ?>
            <tr>
                <td><?php echo htmlspecialchars($oaccount->username); ?></td>
                <td><?php echo htmlspecialchars($oaccount->domain); ?></td>
                <td>
                    <input type="date" class="manual-creation-date" data-username="<?php echo htmlspecialchars($oaccount->username); ?>" value="<?php echo htmlspecialchars($oaccount->start_date); ?>">
                </td>
                <td><?php echo htmlspecialchars($oaccount->email); ?></td>
                <td>
                    <?php 
                    // Default to the plan name
                    $product_name = $oaccount->plan;
                    // If a product mapping exists, use the WooCommerce product name
                    if (!empty($oaccount->product_id)) {
                        $product = wc_get_product($oaccount->product_id);
                        if ($product) {
                            $product_name = $product->get_name();
                        }
                    }
                    echo htmlspecialchars($product_name);
                    ?>
                </td>
                <td>
                    <span class="account-status <?php echo strtolower($oaccount->status); ?>">
                        <?php echo htmlspecialchars($oaccount->status); ?>
                    </span>
                </td>
                <td>
                    <select class="wp-user-dropdown" data-username="<?php echo htmlspecialchars($oaccount->username); ?>">
                        <option value="">Select User</option>
                    </select>
                </td>
                <td>
                    <button class="localize-account" data-username="<?php echo htmlspecialchars($oaccount->username); ?>" data-server-id="<?php echo $server_id; ?>">
                        Localize
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="8">No WHM orphan accounts found.</td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>
</div>


<div class="localOrphans-table-container">
<!-- Local Orphan Accounts Table -->
<h2>Local Orphan Accounts</h2>
<table>
    <thead>
        <tr>
            <th>Username</th>
            <th>Domain</th>
            <th>Creation Date</th>
            <th>Email</th>
            <th>Product</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody id="local-orphan-accounts-tbody">
    <?php if (!empty($local_orphan_accounts)): ?>
        <?php foreach ($local_orphan_accounts as $loaccount): ?>
            <tr>
                <td><?php echo htmlspecialchars($loaccount->username); ?></td>
                <td><?php echo htmlspecialchars($loaccount->domain); ?></td>
                <td><?php echo htmlspecialchars($loaccount->start_date); ?></td>
                <td><?php echo htmlspecialchars($loaccount->email); ?></td>
                <td>
                    <?php 
                    // Default to the plan name
                    $product_name = $loaccount->plan;
                    // If a mapping exists, use the product name from WooCommerce
                    if (!empty($loaccount->product_id)) {
                        $product = wc_get_product($loaccount->product_id);
                        if ($product) {
                            $product_name = $product->get_name();
                        }
                    }
                    echo htmlspecialchars($product_name);
                    ?>
                </td>
                <td>
                    <span class="account-status <?php echo strtolower($loaccount->status); ?>">
                        <?php echo htmlspecialchars($loaccount->status); ?>
                    </span>
                </td>
                <td>
                    <button class="delete-local-orphan" data-username="<?php echo htmlspecialchars($loaccount->username); ?>" data-server-id="<?php echo $server_id; ?>">
                        Delete
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="7">No Local orphan accounts found.</td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>
</div>

        </section>
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

    /* ──────────────────────────────────────────────────────────
     *  Helpers
     * ────────────────────────────────────────────────────────── */
    const $ = sel => document.querySelector(sel);

    /** fireAjax now **returns** the fetch promise so callers can chain .finally() */
    function fireAjax(action, bodyObj, onSuccess) {
        bodyObj.action      = action;
        bodyObj._ajax_nonce = WHM_DHM.nonce;

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
        .catch(err => { console.error(err); alert('AJAX error.'); });
    }

    /* ──────────────────────────────────────────────────────────
     *  1.  COMMON BUTTON ACTIONS
     * ────────────────────────────────────────────────────────── */
    document.addEventListener('click', e => {
        const btn = e.target.closest('.suspend-account, .reactivate-account, .login-to-cpanel');
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

    /* ──────────────────────────────────────────────────────────
     *  2.  REFRESH ACCOUNTS
     * ────────────────────────────────────────────────────────── */
    const refreshBtn = $('#refresh-accounts');
    const whmBody    = $('#whm-orphan-accounts-tbody');
    const locBody    = $('#local-orphan-accounts-tbody');

    refreshBtn.addEventListener('click', () => {
        const serverId = refreshBtn.dataset.serverId;

        refreshBtn.disabled   = true;
        refreshBtn.textContent = 'Refreshing…';

        whmBody.innerHTML = '<tr><td colspan="8">Refreshing accounts …</td></tr>';
        locBody.innerHTML = '<tr><td colspan="7">Refreshing local orphan accounts …</td></tr>';

        fireAjax('whm_refresh_client_accounts', { server_id: serverId }, data => {
            renderWhmOrphans(data.accounts || [], serverId);
            renderLocalOrphans(data.local_orphan_accounts || [], serverId);
            loadWpUsers();                         // repopulate dropdowns
        })
        .finally(() => {
            refreshBtn.disabled   = false;
            refreshBtn.textContent = 'Refresh Accounts';
        });
    });

    /* ──────────────────────────────────────────────────────────
     *  3.  LOCALIZE & DELETE (delegated)
     * ────────────────────────────────────────────────────────── */
    document.addEventListener('click', e => {
        const btn = e.target.closest('.localize-account, .delete-local-orphan');
        if (!btn) return;

        const { username, serverId } = btn.dataset;

        if (btn.classList.contains('localize-account')) {
            const date  = $(`.manual-creation-date[data-username="${username}"]`).value;
            const wpUID = $(`.wp-user-dropdown[data-username="${username}"]`).value;
            if (!date || !wpUID) { alert('Pick a date and user first.'); return; }

            fireAjax('whm_localize_account',
                     { username, manual_date: date, wp_user_id: wpUID, server_id: serverId },
                     () => location.reload());
        }

        if (btn.classList.contains('delete-local-orphan')) {
            if (!confirm(`Delete "${username}" from the database?`)) return;
            fireAjax('delete_local_orphan_account',
                     { username, server_id: serverId },
                     () => btn.closest('tr').remove());
        }
    });

    /* ──────────────────────────────────────────────────────────
     *  4.  RENDERERS & UTILITIES
     * ────────────────────────────────────────────────────────── */
    function renderWhmOrphans(list, serverId) {
        if (!list.length) {
            whmBody.innerHTML = '<tr><td colspan="8">No orphan accounts found.</td></tr>';
            return;
        }
        whmBody.innerHTML = list.map(a => `
            <tr>
              <td>${a.username}</td>
              <td>${a.domain}</td>
              <td><input type="date" class="manual-creation-date" data-username="${a.username}"
                         value="${a.start_date || ''}"></td>
              <td>${a.email}</td>
              <td>${a.product_name || a.plan}</td>
              <td><span class="account-status ${a.status.toLowerCase()}">${a.status}</span></td>
              <td>
                 <select class="wp-user-dropdown" data-username="${a.username}">
                   <option value="">Select User</option>
                 </select>
              </td>
              <td><button class="localize-account" data-username="${a.username}"
                          data-server-id="${serverId}">Localize</button></td>
            </tr>`).join('');
    }

    function renderLocalOrphans(list, serverId) {
        if (!list.length) {
            locBody.innerHTML = '<tr><td colspan="7">No local orphan accounts found.</td></tr>';
            return;
        }
        locBody.innerHTML = list.map(a => `
            <tr>
              <td>${a.username}</td>
              <td>${a.domain}</td>
              <td>${a.start_date || 'N/A'}</td>
              <td>${a.email}</td>
              <td>${a.product_name || a.plan}</td>
              <td><span class="account-status ${a.status.toLowerCase()}">${a.status}</span></td>
              <td><button class="delete-local-orphan" data-username="${a.username}"
                          data-server-id="${serverId}">Delete</button></td>
            </tr>`).join('');
    }

    function loadWpUsers() {
        fireAjax('whm_fetch_wp_users', {}, data => {
            const users     = data.users || [];
            const dropdowns = document.querySelectorAll('.wp-user-dropdown');
            dropdowns.forEach(dd => {
                dd.length = 1;                     // keep “Select User”
                users.forEach(u => {
                    dd.insertAdjacentHTML('beforeend',
                        `<option value="${u.id}">${u.name}</option>`);
                });
            });
        });
    }

    /* initial bootstrap */
    loadWpUsers();
});
</script>
<?php
get_footer();
?>