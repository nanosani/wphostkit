<?php
/**
 * Template Name: WHM Add/Edit Product
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

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$editing = $product_id > 0; // Check if editing mode

// Fetch product data if editing
$product = null;
if ($editing) {
    $product = wc_get_product($product_id);
}

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
                <h1><?php echo $editing ? 'Edit Product' : 'Add New Product'; ?></h1>
                <div><?php echo $editing ? 'Edit the Product' : 'Add a New Product'; ?></div>
            </div>
            <div class="dashboard-action">
            </div>
        </div>
        
        <div class="form-container">
            <form id="product-form">
                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">

                <!-- Product Featured Image -->
                <label for="product_image">Product Image:</label>
                <input type="file" id="product_image" name="product_image">
                <div id="image-preview">
                    <?php if ($editing && $product->get_image_id()): ?>
                        <img src="<?php echo wp_get_attachment_url($product->get_image_id()); ?>" width="100">
                    <?php endif; ?>
                </div>

                <!-- Product Name -->
                <label for="product_name">Product Name:</label>
                <input type="text" id="product_name" name="product_name" value="<?php echo $editing ? esc_attr($product->get_name()) : ''; ?>" required>

                <!-- Product Price -->
                <label for="product_price">Product Price ($):</label>
                <input type="number" id="product_price" name="product_price" step="0.01" value="<?php echo $editing ? esc_attr($product->get_price()) : ''; ?>" required>

                <!-- Submit Button -->
                <button type="submit"><?php echo $editing ? 'Update Product' : 'Add Product'; ?></button>
            </form>
        </div>
    </main>
</div>

<?php
get_footer();