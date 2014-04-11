<?php
if ( ! defined( 'ABSPATH' ) ) die();

require_once( "admin-permalink.php" );

class Cml4WoocommerceAdmin extends Cml4Woocommerce {
	public function __construct() {
      parent::__construct();

      global $pagenow;
      if( 'options-permalink.php' == $pagenow ) {
        new Cml4WoocommercePermalink();
      }

      add_action( 'admin_notices', array( & $this, 'admin_notices' ) );
      add_action( 'admin_init', array( & $this, 'add_meta_box' ) );
  
      //Add addon in "Addons page"
      add_action( 'cml_register_addons', array( & $this, 'register_addon' ), 10, 1 );

      //Tell to CML to ingnore woocommerce post types, so "Post data" box will not be displayed
      add_filter( 'cml_manage_post_types', array( & $this, 'remove_woocommerce_types' ) );
  
      //Post title e editors
      add_action( 'edit_form_after_title', array( & $this, 'insert_title_translations' ), 10, 1 );
  
      //Save translations in post_meta
      add_action( 'save_post', array( & $this, 'save_translations' ), 10, 2 );
      add_action( 'delete_post', array( & $this, 'delete_meta' ), 10, 1 );
      add_action( 'trash_post', array( & $this, 'delete_meta' ), 10, 1 );
      add_action( 'publish_my_custom_post_type', array( & $this, 'save_translations' ), 10, 2 );

      //add attributes in "My translations page"
      add_filter( 'cml_my_translations', array( & $this, 'custom_attributes' ), 10, 1 );
      add_filter( 'cml_my_translations_hide_default', array( & $this, 'hide_default_lang' ), 10, 1 );
      add_filter( 'cml_my_translations_label', array( & $this, 'change_label' ), 10, 2 );

      //Wp style & script
      add_action( 'admin_enqueue_scripts', array( & $this, 'enqueue_style' ), 10 );

      //show translate title in product page
      if( isset( $_GET[ 'post_type' ] ) &&
         'product' == $_GET[ 'post_type' ] ) {

        add_filter( 'the_title', array( & $this, 'get_translated_title' ), 0, 2 );
      }
	}

	function enqueue_style() {
      wp_enqueue_style( 'cmlwoocommerce-style', CML_WOOCOMMERCE_URL . 'css/admin.css' );
  
      wp_enqueue_script( 'cmlwoocommerce-admin', CML_WOOCOMMERCE_URL . 'js/admin.js' );
	}

	function register_addon( & $addons ) {
      $addon = array(
                                  'addon' => 'woocommerce',
                                  'title' => 'Woocommerce',
                                  );
      $addons[] = $addon;

      return $addons;
	}

	function admin_notices() {
      global $pagenow;

      if( ! defined( 'CECEPPA_DB_VERSION' ) ) {
echo <<< EOT
	<div class="error">
		<p>
			<strong>Ceceppa Multilingua for Woocommerce</strong>
			<br /><br />
			Hi there!	I'm just an addon for <a href="http://wordpress.org/plugins/ceceppa-multilingua/">Ceceppa Multilingua</a>, I can't work alone :(
		</p>
	</div>
EOT;
        return;
      }
      
      if( 'edit.php' == $pagenow &&
         'product_attributes' == @$_GET[ 'page' ] ) {
?>
      <div class="updated">
        <p>
<?php
        printf( __( 'Click <%s>here</a> to translate attributes name', 'cml4woo' ),
                       'a href="' . admin_url() . 'admin.php?page=ceceppaml-translations-page&tab=_cml4woo_attr" class="button"' );
?>
        </p>
      </div>
<?php
      }
	}

	function add_meta_box() {
      add_meta_box( 'cml-box-cml4woo-addons', 
                                  __( 'Woocommerce', 'woocommerce' ), 
                                  array( & $this, 'meta_box' ), 
                                  'cml_box_addons_woocommerce' );
      
      add_meta_box( 'cml-box-cml4woo-settings-addons', 
                                  __( 'Settings', 'cml4woo' ), 
                                  array( & $this, 'meta_box_settings' ), 
                                  'cml_box_addons_woocommerce' );
	}

	function meta_box() {
      if( isset( $_GET[ 'update' ] ) ) {
        $this->update_product_language();
      }
?>
	  <div id="minor-publishing">
          <?php _e( 'This addon provide support to Woocommerce', 'cml4woo' ); ?>

        <br /><br /><br />
        <a href="<?php echo CMLUtils::_get( '_woocommerce_addon_page' ) ?>&update=1">
          <?php _e( 'Update product language', 'cml4woo' ); ?>
        </a>
        <br />
      </div>
<?php
    }
    
    function meta_box_settings() {
      if( isset( $_POST[ 'update' ] ) ) {
        update_option( "cmlwoo_translate_slugs", intval( @$_POST[ 'translate-slugs' ] ) );
      }
?>
	  <div id="minor-publishing">
        <form method="post">
          <input type="hidden" name="update" value="1" />
          <div class="cml-checkbox">
            <input type="checkbox" id="translate-slugs" name="translate-slugs" value="1" <?php checked( get_option( 'cmlwoo_translate_slugs', 1 ) ) ?> />
            <label for="translate-slugs"><span>||</span></label>
          </div>
          <label for="translate-slugs"><?php _e( 'Translate product category and tag base', 'cml4woo' ) ?>&nbsp;</label>
          
          <div class="cml-submit-button">
            <?php submit_button() ?>
          </div>
          <br /><br /><br />
        </form>
      </div>
<?php
    }

	//for cml
	function remove_woocommerce_types( $types ) {
		foreach( $this->_post_types as $key ) {
			unset( $types[ $key ] );
		}

		return $types;
	}

	/*
	 * Add extra <input> field for each language
	 */
	function insert_title_translations( $post ) {
		if( ! defined( 'CECEPPA_DB_VERSION' ) ) return;

		if( ! in_array( $post->post_type, $this->_post_types ) ) {
			return;
		}

		$titles = "";
		$tabs = "";
		$short_tabs = "";
		$editors = "";

		foreach( CMLLanguage::get_all() as $lang ) {
			$label = sprintf( __( 'Product name in %s', 'cml4woo' ), $lang->cml_language );

			$img = CMLLanguage::get_flag_src( $lang->id );

			$meta = get_post_meta( $post->ID, "_cml_woo_" . $lang->id, true );

			$title = isset( $meta[ 'title' ] ) ? $meta[ 'title' ] : "";
			$content = isset( $meta[ 'content' ] ) ? $meta[ 'content' ] : "";
			if( empty( $content ) ) $content = $post->post_content;

			$short = isset( $meta[ 'short' ] ) ? $meta[ 'short' ] : "";
			if( empty( $short ) ) $short = $post->post_excerpt;

			if( ! $lang->cml_default ) {
$titles .= <<< EOT
<div id="titlewrap" class="cml-hidden cmlwoo-titlewrap">
	<img class="tipsy-s" title="$label" src="$img" />
	<label class="" id="title-prompt-text" for="title_$lang->id">$label</label>
	<input type="text" class="cmlwoo-title" name="cml_post_title_$lang->id" size="30" id="title_$lang->id" autocomplete="off" value="$title"/>
</div>
EOT;
			}

			$active = ( $lang->cml_default ) ? "nav-tab-active" : "";

			if( ! $lang->cml_default )  {	
				echo '<div id="cmlwoo-editor" class="cmlwoo-editor-' . $lang->id . ' cmlwoo-editor-wrapper cml-hidden postarea edit-form-section">';
					wp_editor( htmlspecialchars_decode( $content ), "cml_content_" . $lang->id );
				echo '</div>';

				$settings = array(
					'textarea_name'	=> 'cml_short_content_' . $lang->id,
					'quicktags' 	=> array( 'buttons' => 'em,strong,link' ),
					'tinymce' 	=> array(
						'theme_advanced_buttons1' => 'bold,italic,strikethrough,separator,bullist,numlist,separator,blockquote,separator,justifyleft,justifycenter,justifyright,separator,link,unlink,separator,undo,redo,separator',
						'theme_advanced_buttons2' => '',
					),
					'editor_css'	=> '<style>#wp-excerpt-editor-container .wp-editor-area{height:175px; width:100%;}</style>'
				);

				echo '<div id="cmlwoo-short-editor" class="cmlwoo-short-editor-' . $lang->id . ' cmlwoo-short-editor-wrapper cml-hidden wp-core-ui wp-editor-wrap tmce-active">';
					wp_editor( htmlspecialchars_decode( $short ), "cml_short_content_" . $lang->id, $settings );
				echo '</div>';
			}

			$img = CMLLanguage::get_flag_img( $lang->id );

$tabs .= <<< EOT
	<a id="cmlwoo-editor-$lang->id" class="nav-tab $active cmlwoo-switch" onclick="CmlWoo.switchTo( $lang->id, '' );">
		$img
		$lang->cml_language
	</a>
EOT;

//short description
$short_tabs .= <<< EOT
	<a id="cmlwoo-short-editor-$lang->id" class="nav-tab $active cmlwoo-short-switch" onclick="CmlWoo.switchTo( $lang->id, 'short-' )">
		$img
		$lang->cml_language
	</a>
EOT;
		}

		echo $titles;

		echo '<h2 class="nav-tab-wrapper cmlwoo-nav-tab">&nbsp;&nbsp;';
		echo $tabs;
		echo '</h2>';

		echo '<h2 class="nav-tab-wrapper cmlwoo-short-nav-tab cmlwoo-nav-tab cml-hidden">&nbsp;&nbsp;';
		echo $short_tabs;
		echo '</h2>';

	}

	/*
	 * save translations in db
	 */
	function save_translations( $post_id, $post ) {
		global $wpdb;

		//Nothing to do for other post types
		if( ! in_array( $post->post_type, $this->_post_types ) ) return;

		foreach( CMLLanguage::get_no_default() as $lang ) {
			if( ! isset( $_POST[ 'cml_post_title_' . $lang->id ] ) ) continue;

			$title = esc_attr( $_POST[ 'cml_post_title_' . $lang->id ] );
			$content = @$_POST[ 'cml_content_' . $lang->id ];
			$short = @$_POST[ 'cml_short_content_' . $lang->id ];

			/*
			 * Store titles in ceceppa ml tables, and I'll use them
			 * in change_product_name function
			 */
			$product = strtolower( sanitize_title( $title ) );
			CMLTranslations::set( $lang->id, "_" . $post->post_type . "_" . $post->ID, $product, "_woo_" );

			$meta = array( 'title' => $title,
											'content' => $content,
											'short' => $short );

			//Store translations in meta field
			update_post_meta( $post_id, "_cml_woo_" . $lang->id, $meta );

			//Tell to CML that post exists in all languages
			CMLPost::set_as_unique( $post_id );
		}

		$query = sprintf( "SELECT ID FROM $wpdb->posts WHERE post_type IN ( '%s' ) AND post_status = 'publish'",
												join( "', '", $this->_post_types ) );

		$posts = $wpdb->get_results( $query, ARRAY_N );
		$ids = array();

		foreach( $posts as $post ) {
			$ids[] = $post[ 0 ];
		}

		update_option( "cml_woo_indexes", $ids );
	}

	function delete_meta( $id ) {
		delete_post_meta( $id, "_cml_woo" );

		if( ! defined( 'CECEPPA_DB_VERSION' ) ) return;

		$post = get_post( $id );
		CMLTranslations::delete_text( "_" . $post->post_type . "_" . $post->ID, "_woo_" );
	}
    
    function update_product_language() {
      $args=array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => -1,
       );

      $posts = get_posts( $args );
      foreach( $posts as $post ) {
        $id = $post->ID;

        CMLPost::set_language( 0, $id );
      }
      
      cml_generate_mo_from_translations();
    }
    
    function custom_attributes( $types ) {
      global $wpdb;

      $attributes = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies" );

      foreach ( $attributes as $attr ) {
        CMLTranslations::add( "_cml4woo_attr_" . $attr->attribute_id . "_" . $attr->attribute_name, $attr->attribute_name, "_cml4woo_attr", true );
      }

      $types[ "_cml4woo_attr" ] = "Woocommerce: Custom attributes";
      
      return $types;
    }
    
    function hide_default_lang( $array ) {
      $array[] = '_cml4woo_attr';
      
      return $array;
    }

    //remove id from label
    function change_label( $label, $type ) {
      if( '_cml4woo_attr' !== $type ) return $label;
      
      preg_match( '/(\d*)_(.*)/', $label, $match );
      if( count( $match ) == 3 )  {
        return $match[ 2 ];
      }
      
      return $label;
    }
}