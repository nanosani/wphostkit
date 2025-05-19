<?php
/**
 * Template Name: WHM Manage Products
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

// Fetch Hosting Plans Category
$hosting_cat = get_term_by( 'name', 'Hosting Plans', 'product_cat' );
$cat_id      = $hosting_cat ? intval( $hosting_cat->term_id ) : 0;

// Query Products in Hosting Plans
$args     = [
    'post_type'      => 'product',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'tax_query'      => [
        [
            'taxonomy' => 'product_cat',
            'field'    => 'term_id',
            'terms'    => $cat_id,
        ],
    ],
];
$query    = new WP_Query( $args );
?>

<div class="admin-container">
  <aside class="sidebar">
    <ul>
      <li><a href="<?php echo esc_url( site_url( '/whm-dashboard/' ) ); ?>"><?php esc_html_e( 'Dashboard', 'whm-dhm' ); ?></a></li>
      <li><a href="<?php echo esc_url( site_url( '/whm-all-servers/' ) ); ?>"><?php esc_html_e( 'Manage Servers', 'whm-dhm' ); ?></a></li>
      <li><a href="<?php echo esc_url( site_url( '/whm-manage-products/' ) ); ?>" class="active"><?php esc_html_e( 'Manage Products', 'whm-dhm' ); ?></a></li>
      <li><a href="<?php echo esc_url( site_url( '/whm-manage-orders/' ) ); ?>"><?php esc_html_e( 'Manage Orders', 'whm-dhm' ); ?></a></li>
      <li><a href="<?php echo esc_url( site_url( '/whm-manage-subscriptions/' ) ); ?>"><?php esc_html_e( 'Manage Subscriptions', 'whm-dhm' ); ?></a></li>
    </ul>
  </aside>

  <main class="dashboard-content">
    <div class="dashboard-header">
      <div class="dashboard-info">
        <h1><?php esc_html_e( 'Manage Products', 'whm-dhm' ); ?></h1>
        <div><?php esc_html_e( 'Manage your products', 'whm-dhm' ); ?></div>
      </div>
      <div class="dashboard-action">
        <a href="<?php echo esc_url( site_url( '/whm-add-edit-product/' ) ); ?>" class="button"><?php esc_html_e( 'Add New Product', 'whm-dhm' ); ?></a>
      </div>
    </div>

    <section class="products-list">
      <table class="products-table">
        <thead>
          <tr>
            <th></th>
            <th><?php esc_html_e( 'Product Name', 'whm-dhm' ); ?></th>
            <th><?php esc_html_e( 'Price', 'whm-dhm' ); ?></th>
            <th><?php esc_html_e( 'Publish Date', 'whm-dhm' ); ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if ( $query->have_posts() ) : ?>
            <?php while ( $query->have_posts() ) : $query->the_post(); ?>
              <?php
              $product    = wc_get_product( get_the_ID() );
              $product_id = get_the_ID();
              $edit_url   = add_query_arg( 'id', $product_id, site_url( '/whm-add-edit-product/' ) );
              $delete_url = get_delete_post_link( $product_id, '', true );
              ?>
              <tr>
                <td class="product-image">
                  <?php echo get_the_post_thumbnail( $product_id, 'thumbnail' ); ?>
                </td>
                <td><?php the_title(); ?></td>
                <td><?php echo $product ? wp_kses_post( $product->get_price_html() ) : '-'; ?></td>
                <td><?php echo esc_html( get_the_date() ); ?></td>
                <td class="actions">
                  <a href="<?php echo esc_url( $edit_url ); ?>" class="button edit"><?php esc_html_e( 'Edit', 'whm-dhm' ); ?></a>
                  <a href="<?php echo esc_url( $delete_url ); ?>" class="button delete" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this product?', 'whm-dhm' ); ?>');">
                    <?php esc_html_e( 'Delete', 'whm-dhm' ); ?>
                  </a>
                </td>
              </tr>
            <?php endwhile; wp_reset_postdata(); ?>
          <?php else : ?>
            <tr>
              <td colspan="5"><?php esc_html_e( 'No products found in "Hosting Plans" category.', 'whm-dhm' ); ?></td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </section>
  </main>
</div>

<?php
// Load footer
get_footer();