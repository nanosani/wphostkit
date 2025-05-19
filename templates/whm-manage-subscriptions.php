<?php
/**
 * Template Name: WHM Manage Subscriptions
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

$table_subs = $wpdb->prefix . 'whm_subscriptions';
$table_users = $wpdb->users;

$subscriptions = $wpdb->get_results(
    "SELECT s.*, u.display_name
     FROM {$table_subs} s
     JOIN {$table_users} u ON s.user_id = u.ID
     ORDER BY s.next_payment_date ASC"
);
?>

<div class="admin-container">
  <aside class="sidebar">
    <ul>
      <li><a href="<?php echo esc_url( site_url( '/whm-dashboard/' ) ); ?>"><?php esc_html_e( 'Dashboard', 'whm-dhm' ); ?></a></li>
      <li><a href="<?php echo esc_url( site_url( '/whm-all-servers/' ) ); ?>"><?php esc_html_e( 'Manage Servers', 'whm-dhm' ); ?></a></li>
      <li><a href="<?php echo esc_url( site_url( '/whm-manage-products/' ) ); ?>"><?php esc_html_e( 'Manage Products', 'whm-dhm' ); ?></a></li>
      <li><a href="<?php echo esc_url( site_url( '/whm-manage-orders/' ) ); ?>"><?php esc_html_e( 'Manage Orders', 'whm-dhm' ); ?></a></li>
      <li><a href="<?php echo esc_url( site_url( '/whm-manage-subscriptions/' ) ); ?>" class="active"><?php esc_html_e( 'Manage Subscriptions', 'whm-dhm' ); ?></a></li>
    </ul>
  </aside>

  <main class="dashboard-content">
    <div class="dashboard-header">
      <div class="dashboard-info">
        <h1><?php esc_html_e( 'Manage Subscriptions', 'whm-dhm' ); ?></h1>
        <div><?php esc_html_e( 'View and manage all active subscriptions.', 'whm-dhm' ); ?></div>
      </div>
    </div>

    <section class="subscriptions-list">
      <?php if ( ! empty( $subscriptions ) ) : ?>
        <?php foreach ( $subscriptions as $sub ) :
          // Calculate days until next payment
          $remaining_days = ceil( ( strtotime( $sub->next_payment_date ) - time() ) / DAY_IN_SECONDS );

          // Fetch all orders linked to this subscription
          $orders = wc_get_orders( [
            'limit'      => -1,
            'orderby'    => 'date',
            'order'      => 'DESC',
            'meta_key'   => 'subscription_id',
            'meta_value' => $sub->id,
            'customer_id' => $sub->user_id,
          ] );
        ?>
          <div class="subscription-box">
            <div class="subscription-header">
              <div class="subscription-name">
                <?php
                  /* translators: 1: subscription ID, 2: WP user display name */
                  printf(
                    esc_html__( 'Sub#%1$d (%2$s)', 'whm-dhm' ),
                    intval( $sub->id ),
                    esc_html( $sub->display_name )
                  );
                ?>
              </div>
              <a href="<?php echo esc_url( site_url( '/whm-subscription-details/?sub_id=' . intval( $sub->id ) ) ); ?>">
                <?php esc_html_e( 'View', 'whm-dhm' ); ?>
              </a>
            </div>

            <div class="subscription-meta">
              <span><?php echo esc_html( wc_price( $sub->amount ) ); ?></span>
              <span><?php printf( esc_html__( '- Every %s', 'whm-dhm' ), esc_html( ucfirst( $sub->billing_cycle ) ) ); ?></span>
              <span><?php printf( esc_html__( '- Status: %s', 'whm-dhm' ), '<strong>' . esc_html( ucfirst( $sub->subscription_status ) ) . '</strong>' ); ?></span>
              <span><?php printf( esc_html__( '- Renews in %d days', 'whm-dhm' ), intval( $remaining_days ) ); ?></span>
            </div>

            <div class="subscription-orders">
              <?php if ( ! empty( $orders ) ) : ?>
                foreach ( $subscriptions as $sub ) :

// derive some vars up front
$status       = strtolower( $sub->subscription_status );
$next_date    = strtotime( $sub->next_payment_date );
$updated_date = strtotime( $sub->updated_at );
$today        = time();

// future: days until renewal
if ( $next_date > $today ) {
    $interval_text = sprintf(
        esc_html__( 'Renews in %s', 'whm-dhm' ),
        _n( '%s day', '%s days', ceil( ( $next_date - $today ) / DAY_IN_SECONDS ), 'whm-dhm' )
    );
}
// past: days since updated_at, only for certain statuses
else {
    $days_ago = ceil( ( $today - $updated_date ) / DAY_IN_SECONDS );
    switch ( $status ) {
        case 'suspended':
            $interval_text = sprintf(
                esc_html__( 'Suspended %s ago', 'whm-dhm' ),
                _n( '%s day', '%s days', $days_ago, 'whm-dhm' )
            );
            break;
        case 'cancelled':
            $interval_text = sprintf(
                esc_html__( 'Cancelled %s ago', 'whm-dhm' ),
                _n( '%s day', '%s days', $days_ago, 'whm-dhm' )
            );
            break;
        case 'terminated':
            $interval_text = sprintf(
                esc_html__( 'Terminated %s ago', 'whm-dhm' ),
                _n( '%s day', '%s days', $days_ago, 'whm-dhm' )
            );
            break;
        default:
            $interval_text = ''; // e.g. pending/reactivation_manual
    }
}
?>

<div class="subscription-box">
  <div class="subscription-header">
    <div class="subscriptionName"><?php echo esc_html( 'Sub#' . $sub->id ); ?></div>

    <div class="subscription-actions">
      <!-- VIEW button (existing) -->
      <a href="<?php echo esc_url( $view_url ); ?>" class="button view-subscription">
        <?php esc_html_e( 'View', 'whm-dhm' ); ?>
      </a>

      <?php if ( in_array( $status, [ 'pending', 'reactivation_manual', 'suspended', 'suspension_manual' ], true ) ) : ?>
        <button class="button activate-subscription" data-id="<?php echo esc_attr( $sub->id ); ?>">
          <?php esc_html_e( 'Activate', 'whm-dhm' ); ?>
        </button>
      <?php elseif ( 'active' === $status ) : ?>
        <button class="button suspend-subscription" data-id="<?php echo esc_attr( $sub->id ); ?>">
          <?php esc_html_e( 'Suspend', 'whm-dhm' ); ?>
        </button>
      <?php endif; ?>
    </div>
  </div>

  <div class="subscription-meta">
    <!-- PRICE: use wc_price() to avoid raw HTML issues -->
    <div class="subscription-field">
      <label><?php esc_html_e( 'Price:', 'whm-dhm' ); ?></label>
      <span class="subscription-price"><?php echo wp_kses_post( wc_price( $sub->amount ) ); ?></span>
    </div>

    <!-- BILLING CYCLE -->
    <div class="subscription-field">
      <label><?php esc_html_e( 'Cycle:', 'whm-dhm' ); ?></label>
      <span class="subscription-cycle"><?php echo esc_html( ucfirst( $sub->billing_cycle ) ); ?></span>
    </div>

    <!-- STATUS: add `status-{status}` class -->
    <div class="subscription-field">
      <label><?php esc_html_e( 'Status:', 'whm-dhm' ); ?></label>
      <span class="subscription-status status-<?php echo esc_attr( $status ); ?>">
        <?php echo esc_html( ucfirst( $status ) ); ?>
      </span>
    </div>

    <!-- INTERVAL TEXT -->
    <?php if ( $interval_text ) : ?>
      <div class="subscription-field">
        <span class="subscription-interval"><?php echo esc_html( $interval_text ); ?></span>
      </div>
    <?php endif; ?>
  </div>

  <!-- ORDERS -->
  <div class="subscription-orders">
    <?php if ( ! empty( $orders ) ) : ?>
      <?php foreach ( $orders as $order ) :
        $o_status = strtolower( $order->get_status() );
      ?>
        <div class="order-box">
          <div class="orderHeader">
            <div class="orderId">
              <strong><?php printf( esc_html__( 'Order #%d', 'whm-dhm' ), $order->get_id() ); ?></strong>
              <span>(<?php echo $order->get_meta('original_order_id') ? esc_html__( 'Renewal', 'whm-dhm' ) : esc_html__( 'Original', 'whm-dhm' ); ?>)</span>
            </div>
            <div class="orderPrice"><?php echo wp_kses_post( wc_price( $order->get_total() ) ); ?></div>
          </div>

          <!-- ORDER STATUS with class -->
          <div class="orderStatus status-<?php echo esc_attr( $o_status ); ?>">
            <?php echo esc_html( ucfirst( $o_status ) ); ?>
          </div>

          <div class="orderItems">
            <?php esc_html_e( 'Items:', 'whm-dhm' ); ?>
            <?php 
              $names = wp_list_pluck( $order->get_items(), 'name' );
              echo esc_html( implode( ', ', $names ) );
            ?>
          </div>

          <div class="orderDate">
            <?php printf(
              /* translators: 1: date */
              esc_html__( 'Issued: %s', 'whm-dhm' ),
              esc_html( $order->get_date_created()->date_i18n( 'Y-m-d' ) )
            ); ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else : ?>
      <p><?php esc_html_e( 'No orders found for this subscription.', 'whm-dhm' ); ?></p>
    <?php endif; ?>
  </div>
</div>

<?php endforeach; ?>

<?php get_footer(); ?>