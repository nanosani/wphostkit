<?php
/**
 * Template Name: WHM Manage All Servers
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

// Fetch all servers associated with the logged-in user
$servers = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}whm_servers");

if (empty($servers)) {
    echo '<p>No servers found for your account.</p>';
    get_footer();
    exit;
}

$accounts_table = $wpdb->prefix . 'whm_accounts';

// Fetch Localized Accounts across all servers (with WP user info)
$localized_accounts = $wpdb->get_results(
    "SELECT wa.*, u.user_login AS wp_user
     FROM $accounts_table wa
     LEFT JOIN {$wpdb->prefix}users u ON wa.user_id = u.ID
     WHERE wa.server_id IN (SELECT id FROM {$wpdb->prefix}whm_servers)
     AND wa.user_id IS NOT NULL AND wa.on_whm = 'yes'"
);

// Fetch WHM Orphan Accounts across all servers
$orphan_accounts = $wpdb->get_results(
    "SELECT * FROM $accounts_table 
     WHERE server_id IN (SELECT id FROM {$wpdb->prefix}whm_servers)
     AND user_id IS NULL AND on_whm = 'yes'"
);

// Fetch Local Orphan Accounts across all servers
$local_orphan_accounts = $wpdb->get_results(
    "SELECT * FROM $accounts_table 
     WHERE server_id IN (SELECT id FROM {$wpdb->prefix}whm_servers)
     AND on_whm = 'no'"
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
        <div class="dashboard-header">
            <div class="dashboard-info">
                <h1>Manage All Servers</h1>
                <div>Manage all your servers</div>
            </div>
            <div class="dashboard-action">
                <button id="refresh-accounts" data-server-id="<?php echo isset($server_id) ? $server_id : ''; ?>" class="button">Refresh Accounts</button>
                <div class="server-actions">
                    <!-- Server Selection Dropdown -->
                    <select id="server-dropdown">
                        <option value="">Select a Server</option>
                        <?php foreach ($servers as $server): ?>
                            <option value="<?php echo htmlspecialchars($server->id); ?>">
                                <?php echo htmlspecialchars($server->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
        
                    <!-- Add New Account Button -->
                    <a id="add-new-account-link" href="#">
                        <button class="button">Add New Account</button>
                    </a>
                </div>
            </div>
        </div>

        <section class="accounts-tables">
            <!-- Localized Accounts Table -->
            <h2>Local Accounts</h2>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Domain</th>
                        <th>Creation Date</th>
                        <th>Server</th>
                        <th>Product</th>
                        <th>Status</th>
                        <th>WP Users</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($localized_accounts)): ?>
                    <?php foreach ($localized_accounts as $account): ?>
                        <?php
                        $server_name = $wpdb->get_var($wpdb->prepare(
                            "SELECT name FROM {$wpdb->prefix}whm_servers WHERE id = %d",
                            $account->server_id
                        ));
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($account->username); ?></td>
                            <td><?php echo htmlspecialchars($account->domain); ?></td>
                            <td><?php echo htmlspecialchars($account->start_date); ?></td>
                            <td><?php echo htmlspecialchars($server_name); ?></td>
                            <td>
                                <?php 
                                    $product_name = $account->plan;
                                    $mapping = $wpdb->get_row($wpdb->prepare(
                                        "SELECT product_id FROM {$wpdb->prefix}whm_plans_maps WHERE plan_name = %s",
                                        $account->plan
                                    ));
                                    if ($mapping && !empty($mapping->product_id)) {
                                        $product = wc_get_product($mapping->product_id);
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
                            <td>
                                <?php echo !empty($account->wp_user) ? htmlspecialchars($account->wp_user) : '-'; ?>
                            </td>
                            <td>
                                <a href="<?php echo site_url('/whm-view-client/?username=' . urlencode($account->username) . '&id=' . urlencode($account->server_id)); ?>">
                                    <button class="button">View</button>
                                </a>
                                <a href="<?php echo site_url('/whm-add-edit-client/?username=' . urlencode($account->username) . '&id=' . urlencode($account->server_id)); ?>">
                                    <button class="button">Edit</button>
                                </a>
                                <button class="login-to-cpanel" data-username="<?php echo htmlspecialchars($account->username); ?>" data-server-id="<?php echo htmlspecialchars($account->server_id); ?>">cPanel</button>
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

            <!-- WHM Orphan Accounts Table -->
            <h2>WHM Orphan Accounts</h2>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Domain</th>
                        <th>Creation Date</th>
                        <th>Server</th>
                        <th>Product</th>
                        <th>Status</th>
                        <th>WP User</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="client-accounts-table-body">
                <?php if (!empty($orphan_accounts)): ?>
                    <?php foreach ($orphan_accounts as $oaccount): ?>
                        <?php
                        $server_name = $wpdb->get_var($wpdb->prepare(
                            "SELECT name FROM {$wpdb->prefix}whm_servers WHERE id = %d",
                            $oaccount->server_id
                        ));
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($oaccount->username); ?></td>
                            <td><?php echo htmlspecialchars($oaccount->domain); ?></td>
                            <td>
                                <input type="date" class="manual-creation-date" data-username="<?php echo htmlspecialchars($oaccount->username); ?>" value="<?php echo htmlspecialchars($oaccount->start_date); ?>">
                            </td>
                            <td><?php echo htmlspecialchars($server_name); ?></td>
                            <td>
                                <?php 
                                    $product_name = $oaccount->plan;
                                    $mapping = $wpdb->get_row($wpdb->prepare(
                                        "SELECT product_id FROM {$wpdb->prefix}whm_plans_maps WHERE plan_name = %s",
                                        $oaccount->plan
                                    ));
                                    if ($mapping && !empty($mapping->product_id)) {
                                        $product = wc_get_product($mapping->product_id);
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
                                <button class="localize-account" data-username="<?php echo htmlspecialchars($oaccount->username); ?>" data-server-id="<?php echo htmlspecialchars($oaccount->server_id); ?>">
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

            <!-- Local Orphan Accounts Table -->
            <h2>Local Orphan Accounts</h2>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Domain</th>
                        <th>Creation Date</th>
                        <th>Server</th>
                        <th>Product</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="local-orphan-accounts-table-body">
                <?php if (!empty($local_orphan_accounts)): ?>
                    <?php foreach ($local_orphan_accounts as $loaccount): ?>
                        <?php
                        $server_name = $wpdb->get_var($wpdb->prepare(
                            "SELECT name FROM {$wpdb->prefix}whm_servers WHERE id = %d",
                            $loaccount->server_id
                        ));
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($loaccount->username); ?></td>
                            <td><?php echo htmlspecialchars($loaccount->domain); ?></td>
                            <td><?php echo htmlspecialchars($loaccount->start_date); ?></td>
                            <td><?php echo htmlspecialchars($server_name); ?></td>
                            <td>
                                <?php 
                                    $product_name = $loaccount->plan;
                                    $mapping = $wpdb->get_row($wpdb->prepare(
                                        "SELECT product_id FROM {$wpdb->prefix}whm_plans_maps WHERE plan_name = %s",
                                        $loaccount->plan
                                    ));
                                    if ($mapping && !empty($mapping->product_id)) {
                                        $product = wc_get_product($mapping->product_id);
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
                                <button class="delete-local-orphan" data-username="<?php echo htmlspecialchars($loaccount->username); ?>" data-server-id="<?php echo htmlspecialchars($loaccount->server_id); ?>">
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

    /* ───────────────────────────────
     *  Helpers
     * ─────────────────────────────── */
    const $ = sel => document.querySelector(sel);

    function fireAjax(action, bodyObj, onSuccess) {
        bodyObj.action      = action;
        bodyObj._ajax_nonce = WHM_DHM.nonce;

        /* return the promise so callers can chain .finally() */
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
     *  Static DOM refs
     * ─────────────────────────────── */
    const whmBody  = $('#client-accounts-table-body');
    const locBody  = $('#local-orphan-accounts-table-body');
    const refresh  = $('#refresh-accounts');
    const serverDD = $('#server-dropdown');
    const addLink  = $('#add-new-account-link');

    /* ───────────────────────────────
     *  Delegated button actions
     * ─────────────────────────────── */
    document.addEventListener('click', e => {
        const b = e.target.closest(
            '.suspend-account, .reactivate-account, .login-to-cpanel,' +
            '.localize-account, .delete-local-orphan'
        );
        if (!b) return;

        const { username, serverId } = b.dataset;

        if (b.classList.contains('suspend-account')) {
            if (!confirm(`Suspend ${username}?`)) return;
            fireAjax('whm_suspend_account',
                     { username, server_id: serverId },
                     () => location.reload());
        }

        if (b.classList.contains('reactivate-account')) {
            if (!confirm(`Re-activate ${username}?`)) return;
            fireAjax('whm_reactivate_account',
                     { username, server_id: serverId },
                     () => location.reload());
        }

        if (b.classList.contains('login-to-cpanel')) {
            fireAjax('whm_login_to_cpanel',
                     { username, server_id: serverId },
                     d => window.open(d.url, '_blank'));
        }

        if (b.classList.contains('localize-account')) {
            const date  = $(`.manual-creation-date[data-username="${username}"]`).value;
            const wpUID = $(`.wp-user-dropdown[data-username="${username}"]`).value;
            if (!date || !wpUID) { alert('Pick a date and user first.'); return; }

            fireAjax('whm_localize_account',
                     { username, manual_date: date, wp_user_id: wpUID, server_id: serverId },
                     () => location.reload());
        }

        if (b.classList.contains('delete-local-orphan')) {
            if (!confirm(`Delete "${username}" from DB?`)) return;
            fireAjax('delete_local_orphan_account',
                     { username, server_id: serverId },
                     () => b.closest('tr').remove());
        }
    });

    /* ───────────────────────────────
     *  Refresh All Accounts
     * ─────────────────────────────── */
    refresh.addEventListener('click', () => {
        const sid = refresh.dataset.serverId || '';

        refresh.disabled = true;
        refresh.textContent = 'Refreshing…';

        whmBody.innerHTML = '<tr><td colspan="8">Refreshing …</td></tr>';
        locBody.innerHTML = '<tr><td colspan="7">Refreshing …</td></tr>';

        fireAjax('whm_refresh_client_accounts', { server_id: sid }, data => {
            renderWhmOrphans(data.accounts || []);
            renderLocalOrphans(data.local_orphan_accounts || []);
            loadWpUsers();                       // (re)populate dropdowns
        })
        .finally(() => {
            refresh.disabled = false;
            refresh.textContent = 'Refresh Accounts';
        });
    });

    /* ───────────────────────────────
     *  Server dropdown → update “Add New Account” link
     * ─────────────────────────────── */
    serverDD.addEventListener('change', () => {
        addLink.href = serverDD.value
            ? `<?php echo esc_js( site_url( '/whm-add-edit-client/?id=' ) ); ?>${serverDD.value}`
            : '#';
    });

    /* ───────────────────────────────
     *  Rendering helpers
     * ─────────────────────────────── */
    function renderWhmOrphans(list) {
        if (!list.length) {
            whmBody.innerHTML = '<tr><td colspan="8">No WHM orphan accounts found.</td></tr>';
            return;
        }
        whmBody.innerHTML = list.map(a => `
            <tr>
              <td>${a.username}</td>
              <td>${a.domain}</td>
              <td><input type="date" class="manual-creation-date" data-username="${a.username}"
                         value="${a.start_date || ''}"></td>
              <td>${a.server_name || ''}</td>
              <td>${a.product_name || a.plan}</td>
              <td><span class="account-status ${a.status.toLowerCase()}">${a.status}</span></td>
              <td>
                  <select class="wp-user-dropdown" data-username="${a.username}">
                      <option value="">Select User</option>
                  </select>
              </td>
              <td><button class="localize-account" data-username="${a.username}"
                          data-server-id="${a.server_id}">Localize</button></td>
            </tr>`).join('');
    }

    function renderLocalOrphans(list) {
        if (!list.length) {
            locBody.innerHTML = '<tr><td colspan="7">No local orphan accounts found.</td></tr>';
            return;
        }
        locBody.innerHTML = list.map(a => `
            <tr>
              <td>${a.username}</td>
              <td>${a.domain}</td>
              <td>${a.start_date || 'N/A'}</td>
              <td>${a.server_name || ''}</td>
              <td>${a.product_name || a.plan}</td>
              <td><span class="account-status ${a.status.toLowerCase()}">${a.status}</span></td>
              <td><button class="delete-local-orphan" data-username="${a.username}"
                          data-server-id="${a.server_id}">Delete</button></td>
            </tr>`).join('');
    }

    /* ───────────────────────────────
     *  Load WP users into every dropdown
     * ─────────────────────────────── */
    function loadWpUsers() {
        fireAjax('whm_fetch_wp_users', {}, data => {
            const users     = data.users || [];
            const dropdowns = document.querySelectorAll('.wp-user-dropdown');
            dropdowns.forEach(dd => {
                dd.length = 1;                       // keep “Select User”
                users.forEach(u => {
                    dd.insertAdjacentHTML('beforeend',
                        `<option value="${u.id}">${u.name}</option>`);
                });
            });
        });
    }

    /* page-load bootstrap */
    loadWpUsers();
});
</script>
<?php
get_footer();