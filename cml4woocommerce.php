<?php
/*
Plugin Name: Ceceppa Multilingua support for Woocommerce
Plugin URI: http://www.ceceppa.eu/portfolio/ceceppa-multilingua/
Description: Plugin to make Ceceppa Multilingua work with Woocommerce.\nThis plugin required Ceceppa Multilingua 1.4.10.
Version: 0.8
Author: Alessandro Senese aka Ceceppa
Author URI: http://www.alessandrosenese.eu/
License: GPL3
Tags: multilingual, multi, language, admin, tinymce, qTranslate, Polyglot, bilingual, widget, switcher, professional, human, translation, service, multilingua, woocommerce
*/
// Make sure we don't expose any info if called directly
if ( ! defined( 'ABSPATH' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

define( 'CML_WOOCOMMERCE_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'CML_WOOCOMMERCE_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );

require_once( CML_WOOCOMMERCE_PATH . "admin/admin.php" );
require_once( CML_WOOCOMMERCE_PATH . "frontend/frontend.php" );

class Cml4Woocommerce {
  public function __construct() {
    //Woocommerce post types
    $this->_post_types = array( 'product', 'product_variation', 'shop_order', 'shop_coupon' );

    $this->_indexes = get_option( "cml_woo_indexes", array() );

    add_action( 'init', array( & $this, 'rewrite_rules' ), 99 );
  }

  /*
   * allow category slug translation in url
   */
  function rewrite_rules() {
    if( ! defined( 'CECEPPA_DB_VERSION' ) ) return;

    $permalinks = get_option( "cmlwoo_permalinks", array() );
	$woo = get_option( 'woocommerce_permalinks' );

    $slugs = array( 'category_base', 'tag_base' );
    $type = array(
                  'category_base' => 'product_cat',
                  'tag_base' => 'product_tag',
                 );

    foreach( CMLLanguage::get_no_default() as $lang ) {
      if( ! isset( $permalinks[ $lang->id ] ) ) continue;

      foreach( $slugs as $slug ) {
        if( empty( $permalinks[ $lang->id ][ $slug ] ) ) continue;

        $category = $permalinks[ $lang->id ][ $slug ];
        if( $category == $woo[ $slug ] ) continue;

        add_rewrite_rule( $category . '/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?$','index.php?' . $type[ $slug ] . '=$matches[1]&feed=$matches[2]', 'top' );
        add_rewrite_rule( $category . '/(.+?)/(feed|rdf|rss|rss2|atom)/?$','index.php?' . $type[ $slug ] . '=$matches[1]&feed=$matches[2]', 'top' );
        add_rewrite_rule( $category . '/(.+?)/page/?([0-9]{1,})/?$','index.php?' . $type[ $slug ] . '=$matches[1]&paged=$matches[2]', 'top' );
        add_rewrite_rule( $category . '/(.+?)/?$','index.php?' . $type[ $slug ] . '=$matches[1]', 'top' );
      }
    }

    flush_rewrite_rules();
  }

  function get_translated_title( $title, $id ) {
    if( ! defined( 'CECEPPA_DB_VERSION' ) ) return $title;

    //Get request language
    $lang = CMLUtils::_get( "_forced_language_id", CMLLanguage::get_current_id() );
    if( CMLLanguage::is_default( $lang ) ) return $title;

    //In the loop in can't use is_singular so I check if current $id exists
    //in "woocommerce" post types
    if( ! in_array( $id, $this->_indexes ) ) return $title;

    $meta = $this->get_meta( $id );
    if( empty( $meta ) ) return $title;

    $c = isset( $meta[ 'title' ] ) ? $meta[ 'title' ] : "";
    if( !empty( $c ) ) $title = $c;

    return $title;
  }


  function get_meta( $id = null ) {
    if( null == $id ) {
      $id = get_the_ID();
    }

    $lang = CMLUtils::_get( "_forced_language_id", CMLLanguage::get_current_id() );

    if( ! isset( $this->_metas[ $id ][ $lang ] ) ) {
      $meta = get_post_meta( $id, "_cml_woo_" . $lang, true );

      $this->_metas[ $id ][ $lang ] = $meta;
    } else {
      $meta = $this->_metas[ $id ][ $lang ];
    }

    return $meta;
  }
}

if( is_admin() ) {
	$cml4woocommerce = new Cml4WoocommerceAdmin();
} else {
	$cml4woocommerce = new Cml4WoocommerceFrontend();
}
