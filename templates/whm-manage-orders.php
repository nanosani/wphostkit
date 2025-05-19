<?php
/**
 * Template Name: WHM Manage Orders
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

// Get the “Hosting Plans” category ID.
$hosting_cat = get_term_by( 'name', 'Hosting Plans', 'product_cat' );
$hosting_cat_id = $hosting_cat ? $hosting_cat->term_id : 0;

// Retrieve all orders, then filter down to those containing Hosting Plans.
$all_orders      = wc_get_orders( [
    'limit'   => -1,
    'orderby' => 'date',
    'order'   => 'DESC',
] );
$filtered_orders = [];

foreach ( $all_orders as $order ) {
    foreach ( $order->get_items() as $item ) {
        $pid   = $item->get_product_id();
        $terms = get_the_terms( $pid, 'product_cat' ) ?: [];
        foreach ( $terms as $term ) {
            if ( (int) $term->term_id === $hosting_cat_id ) {
                $filtered_orders[] = $order;
                break 2;
            }
        }
    }
}
?>

<div class="admin-container">
  <aside class="sidebar">
    <ul>
      <li><a href="<?php echo esc_url( site_url( '/whm-dashboard/' ) ); ?>"><?php esc_html_e( 'Dashboard', 'whm-dhm' ); ?></a></li>
      <li><a href="<?php echo esc_url( site_url( '/whm-all-servers/' ) ); ?>"><?php esc_html_e( 'Manage Servers', 'whm-dhm' ); ?></a></li>
      <li><a href="<?php echo esc_url( site_url( '/whm-manage-products/' ) ); ?>"><?php esc_html_e( 'Manage Products', 'whm-dhm' ); ?></a></li>
      <li><a href="<?php echo esc_url( site_url( '/whm-manage-orders/' ) ); ?>" class="active"><?php esc_html_e( 'Manage Orders', 'whm-dhm' ); ?></a></li>
      <li><a href="<?php echo esc_url( site_url( '/whm-manage-subscriptions/' ) ); ?>"><?php esc_html_e( 'Manage Subscriptions', 'whm-dhm' ); ?></a></li>
    </ul>
  </aside>

  <main class="dashboard-content">
    <div class="dashboard-header">
      <div class="dashboard-info">
        <h1><?php esc_html_e( 'Manage Orders', 'whm-dhm' ); ?></h1>
        <div><?php esc_html_e( 'View and update your Hosting Plan orders.', 'whm-dhm' ); ?></div>
      </div>
    </div>

    <section class="orders-list">
      <table class="orders-table">
        <thead>
          <tr>
            <th><?php esc_html_e( 'Order #', 'whm-dhm' ); ?></th>
            <th><?php esc_html_e( 'Status', 'whm-dhm' ); ?></th>
            <th><?php esc_html_e( 'Payment', 'whm-dhm' ); ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php if ( ! empty( $filtered_orders ) ) : ?>
          <?php foreach ( $filtered_orders as $order ) :
            $order_id      = $order->get_id();
            $order_status  = wc_get_order_status_name( $order->get_status() );
            $is_paid       = $order->is_paid();
            $payment_label = $is_paid ? __( 'Paid', 'whm-dhm' ) : __( 'Unpaid', 'whm-dhm' );
          ?>
            <tr data-order-id="<?php echo esc_attr( $order_id ); ?>">
              <td><?php printf( esc_html__( 'Order #%d', 'whm-dhm' ), $order_id ); ?></td>
              <td>
                <span class="status <?php echo esc_attr( $order->get_status() ); ?>">
                  <?php echo esc_html( $order_status ); ?>
                </span>
              </td>
              <td>
                <label class="toggle-switch">
                  <input
                    type="checkbox"
                    class="payment-toggle"
                    data-order-id="<?php echo esc_attr( $order_id ); ?>"
                    <?php checked( $is_paid ); ?>
                  >
                  <span class="slider"></span>
                </label>
                <span class="payment-status"><?php echo esc_html( $payment_label ); ?></span>
              </td>
              <td>
                <button class="button delete-order-btn" data-order-id="<?php echo esc_attr( $order_id ); ?>">
                  <?php esc_html_e( 'Delete', 'whm-dhm' ); ?>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else : ?>
          <tr>
            <td colspan="4"><?php esc_html_e( 'No orders found in "Hosting Plans" category.', 'whm-dhm' ); ?></td>
          </tr>
        <?php endif; ?>
        </tbody>
      </table>
    </section>
  </main>
</div>

<?php
get_footer();
