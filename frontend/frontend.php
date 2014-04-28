<?php
if ( ! defined( 'ABSPATH' ) ) die();

class Cml4WoocommerceFrontend extends Cml4Woocommerce {
	protected $_metas = array();

	public function __construct() {
    parent::__construct();

    //Translate product link
    if( get_option( 'cmlwoo_translate_permalink', 1 ) ) {
      add_filter( 'post_type_link', array( & $this, 'translate_product_link' ), 10, 4 );

      //Tell to wp the original product name :)
      add_filter( 'pre_get_posts', array( & $this, 'change_product_name' ), 0, 1 );
    }

    //Product translations
    add_filter( 'the_title', array( & $this, 'get_translated_title' ), 0, 2 );
    add_filter( 'woocommerce_cart_item_name', array( & $this, 'get_translated_title' ), 10, 3 );
    add_filter( 'the_content', array( & $this, 'get_translated_content' ), 0, 1 );
    add_filter( 'woocommerce_short_description', array( & $this, 'get_translated_description' ), 0, 1 );

    /*
     * When I translate category url I have to inform wordpress which is "original" category.
     * I can't use is_category for translated url of custom categories, so I have to use
     * cml_is_custom_category filter.
     *
     * is_woocommerce_tag detect if current url is a woocommerce category
     */
    add_filter( 'cml_is_custom_category', array( & $this, 'is_woocommerce_category' ), 10, 2 );
    add_filter( 'cml_custom_category_name', array( & $this, 'get_category_name' ), 10, 2 );
    add_filter( 'cml_change_wp_query_values', array( & $this, 'change_wp_query_values' ), 10, 2 );

    //Translate cart product title
    add_filter( 'woocommerce_cart_item_product', array( & $this, 'translate_product' ), 10, 3 );
    add_filter( 'woocommerce_order_get_items', array( & $this, 'translate_items_name' ), 10, 2 );

    //Add language to form, so error will be displayed in current language
    add_action( 'woocommerce_before_checkout_billing_form', array( & $this, 'checkout_form' ), 10 );
    
    //Is shop page?
    add_action( 'cml_is_single_page', array( & $this, 'is_shop_page' ), 10, 2 );
    add_action( 'cml_get_custom_page_id', array( & $this, 'get_shop_page_id' ), 10, 2 );
    
    if( get_option( "cmlwoo_translate_slugs" ) ) {
      //translate category url
      add_filter( 'woocommerce_taxonomy_args_product_cat', array( & $this, 'get_translated_cat_slug' ), 10, 1 );
      add_filter( 'woocommerce_taxonomy_args_product_tag', array( & $this, 'get_translated_tag_slug' ), 10, 1 );
  
      //translate category title in flag link
      add_filter( 'cml_get_the_link', array( & $this, 'translate_category_link' ), 10, 4 );
    }
  }

  function get_translated_content( $content ) {
    if( ! defined( 'CECEPPA_DB_VERSION' ) ) return $content;
    if( ! in_array( get_the_ID(), $this->_indexes ) ) return $content;
    if( ! is_product() ) return $content;

    $meta = $this->get_meta();

    $c = isset( $meta[ 'content' ] ) ? $meta[ 'content' ] : "";
    if( !empty( $c ) ) $content = $c;

    $content = str_replace( ']]>', ']]&gt;', $content );

    return $content;
  }

  function get_translated_description( $excerpt ) {
      if( ! defined( 'CECEPPA_DB_VERSION' ) ) return $excerpt;
      if( ! in_array( get_the_ID(), $this->_indexes ) ) return $excerpt;

      $meta = $this->get_meta();

      $c = isset( $meta[ 'short' ] ) ? $meta[ 'short' ] : "";
      if( !empty( $c ) ) $excerpt = $c;

      return $excerpt;
  }

  function translate_product_link( $permalink, $post, $leavename, $sample ) {
    if( ! defined( 'CECEPPA_DB_VERSION' ) ) return $permalink;

    $ps = CMLUtils::get_permalink_structure();
    if( ! in_array( get_post_type( $post ), $this->_post_types ) ||
        empty( $ps ) ||
        is_preview() )  {
      return $permalink;
    }

    global $wp_rewrite;

    //Current slug
    $lang = CMLUtils::_get( "_forced_language_id", CMLLanguage::get_current_id() );

    //Current language id
    if( CMLLanguage::is_default( $lang ) ) return $permalink;

    $url = explode( "/", untrailingslashit( $permalink ) );
    unset( $url[ count( $url ) - 1 ] );

    //Get translated title
    $title = CMLTranslations::get( $lang, "_{$post->post_type}_" . $post->ID, "_woo_", true );
    if( empty( $title ) ) {
      $title = $this->get_translated_title( $post->post_title, $post->ID );
    }

    $url[] = strtolower( sanitize_title( $title ) );

    return join( "/", $url );
  }

  /*
   * tell to wp the original name of produt to avoid 404 error
   */
  function change_product_name( $wp_query ) {
    global $wpdb;

    if( ! defined( 'CECEPPA_DB_VERSION' ) ) return;

    if( cml_is_homepage() ||
      CMLLanguage::is_default() ||
      ! isset( $wp_query->query[ 'product' ] ) ) {
      return;
    }

    $product = strtolower( $wp_query->query[ 'product' ] );
    $key = CMLTranslations::search( CMLLanguage::get_current_id(), $product, "_woo_" );

    //Nothing found
    if( empty( $key ) ) return;

    if( ! preg_match( "/\d*$/", $key, $out ) ) return;

    $id = end( $out );
    $post = get_post( $id );

    $wp_query->query[ 'product' ] = $post->post_title; 
    $wp_query->query[ 'name' ] = $post->post_title; 

    $wp_query->query_vars[ 'product' ] = $post->post_title; 
    $wp_query->query_vars[ 'name' ] = $post->post_title;
  }

  /*
   * detect if current url is a woocommerce category
   */
  function is_woocommerce_category( $is_custom, $wp_query ) {
      if( isset( $wp_query->query[ 'product_cat' ] ) ) {
          return true;
      }

      return $is_custom;
  }

  /*
   * return woocommerce category name
   */
  function get_category_name( $cat, $wp_query ) {
      if( ! $this->is_woocommerce_category( false, $wp_query ) ) {
          return $cat;
      }

      return $wp_query->query[ 'product_cat' ];
  }

  /*
   * add missing values to wp_query, so wordpress doesn't show 404 error
   */
  function change_wp_query_values( $wp_query, $cat ) {
    $cat = end ( $cat );

    if( empty( $cat ) ) return $wp_query;

    $wp_query->query[ 'product_cat' ] = $cat;
    $wp_query->query_vars[ 'product_cat' ] = $cat;

    return $wp_query;
  }
    
  function get_translated_cat_slug( $args ) {
    if( ! defined( 'CECEPPA_DB_VERSION' ) ) return $args;

    $lang = CMLUtils::_get( "_forced_language_id", CMLLanguage::get_current_id() );
    if( CMLLanguage::is_default( $lang ) ) return $args;

    $permalinks = CMLUtils::_get( "_cmlwoo_permalinks", null );
    if( null == $permalinks ) {
      $permalinks = get_option( "cmlwoo_permalinks", array() );

      CMLUtils::_set( "cmlwoo_permalinks", $permalinks );
    }

    if( isset( $permalinks[ $lang ][ 'category_base' ] ) ) {
      $args[ 'rewrite' ][ 'slug' ] = $permalinks[ $lang ][ 'category_base' ];
    }

    return $args;
  }
  
  function get_translated_tag_slug( $args ) {
    if( ! defined( 'CECEPPA_DB_VERSION' ) ) return $args;

    $lang = CMLUtils::_get( "_forced_language_id", CMLLanguage::get_current_id() );
    if( CMLLanguage::is_default( $lang ) ) return $args;

    $permalinks = CMLUtils::_get( "_cmlwoo_permalinks", null );
    if( null == $permalinks ) {
      $permalinks = get_option( "cmlwoo_permalinks", array() );

      CMLUtils::_set( "cmlwoo_permalinks", $permalinks );
    }

    if( isset( $permalinks[ $lang ][ 'tag_base' ] ) ) {
      $args[ 'rewrite' ][ 'slug' ] = $permalinks[ $lang ][ 'tag_base' ];
    }

    return $args;
  }

  function translate_items_name( $items ) {
    if( CMLLanguage::is_default() ) return $items;

    foreach( $items as $key => $item ) {
      if( isset( $item[ 'product_id' ] ) ) {
        $items[ $key ][ 'name' ] = $this->get_translated_title( $item[ 'name' ], $item[ 'product_id' ] );
      }
    }
    
    return $items;
  }

  function translate_product( $post, $item, $key ) {
    $post->post->post_title = $this->get_translated_title( $post->post->post_title, $post->id );

    return $post;
  }
  
  function checkout_form( $checkout ) {
    echo '<input type="hidden" name="lang" value="' . CMLLanguage::get_current()->cml_locale . '" />';

    return $checkout;
  }
  
  //is shop page?
  function is_shop_page( $is_single, $queried ) {
    if( isset( $queried->query_var ) &&
        $queried->query_var == 'product' ) {
      return true;
    }

    return $is_single;
  }

  //tell to cml the shop page id
  function get_shop_page_id( $id, $queried ) {
    if( ! $this->is_shop_page( false, $queried ) ) {
      return $id;
    }
    
    return wc_get_page_id( 'shop' );
  }
  
  function translate_category_link( $link, $attrs, $queried, $lang ) {
    $ps = CMLUtils::get_permalink_structure();
    if( null == $queried || empty( $ps ) ) return $link;
    if( ! isset( $queried->taxonomy ) || ! in_array( $queried->taxonomy, array( "product_cat", "product_tag" ) ) ) return $link;

    $field = ( $queried->taxonomy == 'product_cat' ) ? 'category_base' : 'tag_base';

    $permalinks = get_option( "woocommerce_permalinks", array() );
    if( empty( $permalinks ) ) return $link;

    $c_base = $permalinks[ $field ];
    if( empty( $c_base ) ) return $link;

    $home = trailingslashit( CMLUtils::get_home_url( CMLLanguage::get_slug( $lang ) ) );
    $l = str_replace( $home, "", $link );
    $l = explode( "/", $l );

    $p = CMLUtils::_get( "_cmlwoo_permalinks", null );
    if( null == $p ) {
      $p = get_option( "cmlwoo_permalinks", array() );

      CMLUtils::_set( "cmlwoo_permalinks", $permalinks );
    }

    $base = @$p[ $lang ][ $field ];
    if( empty( $base ) ) $base = $c_base;

    $l[ 0 ] = $base;

    return $home . join( "/", $l );
  }
}

/*
 * I can't use this function in my class or I cant translate
 * checkout page id when order is confirmed ( the class isn't initialized )
 */
//Translate cart and checkout id
function cmlwoo_get_translated_page_id( $id ) {
  if( ! defined( 'CECEPPA_DB_VERSION' ) ) return $id;

  if( CMLLanguage::is_default() ) return $id;

  $linked = CMLPost::get_translation( CMLLanguage::get_current_id(), $id );

  if( $linked == 0 ) $linked = $id;

  return $linked;
}

$pages = array( 'cart', 'product', 'myaccount', 'shop', 'change_password', 'checkout' );
foreach( $pages as $page ) {
  add_filter( 'woocommerce_get_' . $page . '_page_id', 'cmlwoo_get_translated_page_id', 10, 1 );
}
?>