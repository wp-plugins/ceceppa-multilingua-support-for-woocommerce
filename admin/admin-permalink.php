<?php

class Cml4WoocommercePermalink {
  function __construct() {
    add_action( 'admin_init', array( $this, 'settings_init' ) );
    add_action( 'admin_init', array( $this, 'settings_save' ) );
  }
  
  function settings_init() {
    if( ! defined( 'CECEPPA_DB_VERSION' ) ) return;

    foreach( CMLLanguage::get_no_default() as $lang ) {
      // Add our settings
      add_settings_field(
          'cmlwoo_product_category_slug_' . $lang->id,      	// id
          '<div class="cml-remove"></div>', 	      // setting title
          array( $this, 'product_category_slug_input' ),  // display callback
          'permalink',                 				// settings page
          'optional',                  				// settings section
          array( "lang" => $lang->id )
      );

      add_settings_field(
          'cmlwoo_product_tag_slug_' . $lang->id,      		// id
          '<div class="cml-remove"></div>', 	      // setting title
          array( $this, 'product_tag_slug_input' ),  // display callback
          'permalink',                 				// settings page
          'optional',                  				// settings section
          array( "lang" => $lang->id )
      );
      
      //add_settings_field(
      //    'cmlwoo_product_attribute_slug_' . $lang->id,      	// id
      //    '<div class="cml-remove"></div>', 	      // setting title
      //    array( $this, 'product_attribute_slug_input' ),  // display callback
      //    'permalink',                 				// settings page
      //    'optional',                  				// settings section
      //    array( "lang" => $lang->id )
      //);
    }
  }
  
  function settings_save() {
    if( ! defined( 'CECEPPA_DB_VERSION' ) ) return;

    if ( isset( $_POST['permalink_structure'] ) ||
         isset( $_POST['category_base'] ) &&
         isset( $_POST['product_permalink'] ) ) {
      
      $permalinks = get_option( "cmlwoo_permalinks", array() );
      
      foreach( CMLLanguage::get_no_default() as $lang ) {
        $category_base = wc_clean( @$_POST[ 'cmlwoo_product_category_slug_' . $lang->id ] );
        $tag_base = wc_clean( @$_POST[ 'cmlwoo_product_tag_slug_' . $lang->id ] );
        $attribute_base = wc_clean( @$_POST[ 'cmlwoo_product_attribute_slug_' . $lang->id ] );
        
        $permalinks[ $lang->id ][ 'category_base' ] = untrailingslashit( $category_base );
        $permalinks[ $lang->id ][ 'tag_base' ] = untrailingslashit( $tag_base );
        $permalinks[ $lang->id ][ 'attribute_base' ] = untrailingslashit( $attribute_base );
      }
      
      update_option( "cmlwoo_permalinks", $permalinks );
    }
  }

  function product_category_slug_input( $args ) {
    $lang = $args[ 'lang' ];

    $permalinks = get_option( "cmlwoo_permalinks", array() );
    
    if( empty( $permalinks ) ) {
      $permalinks = get_option( 'woocommerce_permalinks' );
    } else {
      $permalinks = $permalinks[ $args[ 'lang' ] ];
    }
?>
    <div class="cmlwoo_category_slug cml-hidden">
      <?php echo CMLLanguage::get_flag_img( $lang ) ?>
      <input name="cmlwoo_product_category_slug_<?php echo $lang ?>" type="text" class="regular-text code" value="<?php if ( isset( $permalinks['category_base'] ) ) echo esc_attr( $permalinks['category_base'] ); ?>" placeholder="<?php echo _x('product-category', 'slug', 'woocommerce') ?>" />
    </div>
<?php
  }

  /**
   * Show a slug input box.
   */
  public function product_tag_slug_input( $args ) {
    $lang = $args[ 'lang' ];

    $permalinks = get_option( "cmlwoo_permalinks", array() );
    
    if( empty( $permalinks ) ) {
      $permalinks = get_option( 'woocommerce_permalinks' );
    } else {
      $permalinks = $permalinks[ $args[ 'lang' ] ];
    }
?>
    <div class="cmlwoo_tag_slug cml-hidden">
      <?php echo CMLLanguage::get_flag_img( $lang ) ?>
      <input name="cmlwoo_product_tag_slug_<?php echo $lang ?>" type="text" class="regular-text code" value="<?php if ( isset( $permalinks['tag_base'] ) ) echo esc_attr( $permalinks['tag_base'] ); ?>" placeholder="<?php echo _x('product-tag', 'slug', 'woocommerce') ?>" />
    </div>
<?php
  }


  /**
   * Show a slug input box.
   */
  public function product_attribute_slug_input( $args ) {
    $lang = $args[ 'lang' ];

    $permalinks = get_option( 'woocommerce_permalinks' );
?>
    <div class="cmlwoo_attribute_slug cml-hidden">
      <?php echo CMLLanguage::get_flag_img( $lang ) ?>
      <input name="cmlwoo_product_attribute_slug_<?php echo $lang ?>" type="text" class="regular-text code" value="<?php if ( isset( $permalinks['attribute_base'] ) ) echo esc_attr( $permalinks['attribute_base'] ); ?>" /><code>/attribute-name/attribute/</code>
    </div>
<?php
  }
}

?>