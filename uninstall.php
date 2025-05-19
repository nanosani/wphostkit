<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// 1) Drop our custom tables
foreach ( [ 'whm_accounts', 'whm_invoices', 'whm_plans_maps', 'whm_servers', 'whm_subscriptions' ] as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$table}" );
}

// 2) Delete any pages we created
foreach ( [
    'whm-dashboard',
    'whm-all-servers',
    'whm-single-server-details',
    'whm-multiple-servers',
    'whm-add-edit-server',
    'whm-map-server-plans',
    'whm-add-edit-client',
    'whm-manage-products',
    'whm-add-edit-product',
    'whm-manage-orders',
    'whm-manage-subscriptions',
    'whm-view-client',
] as $slug ) {
    if ( $page = get_page_by_path( $slug ) ) {
        wp_delete_post( $page->ID, true );
    }
}

// 3) Clear our scheduled cron
wp_clear_scheduled_hook( 'whm_dhm_check_subscriptions' );

// 4) Flush rewrite rules so those endpoints disappear
flush_rewrite_rules();
