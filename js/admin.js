var _cml_is_switching = false;

jQuery( document ).ready( function( $ ) {
	$( '.cmlwoo-titlewrap' ).insertAfter( $( '#titlediv > #titlewrap' ) );
	$( '.cmlwoo-titlewrap' ).removeClass( 'cml-hidden' );

	//Hide label if value is not empty
	$( '.cmlwoo-title' ).each( function() {
		if( $( this ).val() != "" ) {
			$( this ).prev().fadeOut( 0 );
		}
	});

	$( '.cmlwoo-title' ).focus( function() {
		$( this ).prev().fadeOut( 'fast' );
	});

	$( '.cmlwoo-titlewrap input' ).focusout( function() {
		$this = $( this );

		if( $this.val() != "" ) return;

		$this.prev().fadeIn( 'fast' );
	});

	//Move switch tab inside ".wp-editor-tabs" div
	$to = $( '#postdivrich #wp-content-editor-tools .wp-editor-tabs' )

	//move my textarea above #postdivrich
	$( '.cmlwoo-editor-wrapper' ).insertAfter( $( '#postdivrich' ) );

	//move short description after #wp-excerpt-wrap
	$( '.cmlwoo-short-editor-wrapper' ).insertAfter( $( '#wp-excerpt-wrap' ) );
	$( 'h2.cmlwoo-short-nav-tab' ).insertBefore( $( '#wp-excerpt-wrap' ) ).removeClass( 'cml-hidden' );

    //Move fields in permalink options
    $( '.cmlwoo_category_slug' ).insertAfter( $( 'input[name="woocommerce_product_category_slug"]' ) );
    $( '.cmlwoo_category_slug' ).removeClass( 'cml-hidden' );

    $( '.cmlwoo_tag_slug' ).insertAfter( $( 'input[name="woocommerce_product_tag_slug"]' ) );
    $( '.cmlwoo_tag_slug' ).removeClass( 'cml-hidden' );

    $( '.cmlwoo_attribute_slug' ).insertAfter( $( 'input[name="woocommerce_product_attribute_slug"]' ) );
    $( '.cmlwoo_attribute_slug' ).removeClass( 'cml-hidden' );

    $( 'th .cml-remove' ).each( function() {
      $( this ).parents( 'tr' ).remove();
    });

    //Edit permalink
    $( 'body' ).on( 'click', '.cml-permalink #edit-slug-buttons .edit-slug, .cml-permalink #editable-post-name', function() {
      $sample = $( this ).parents( '.cml-permalink' ).find( '#editable-post-name' );
      if ( $sample.find( 'input' ).length > 0 ) {
        return;
      }

      $sample.attr( 'original-slug', $sample.html() );

      $input = '<input type="text" id="new-post-slug" value="' + $sample.html() + '" />';
      $sample.html( $input );

      $( this ).parents( '.cml-permalink' ).find( '.cml-view-product' ).hide();
      $( this ).parents( '.cml-permalink' ).find( '#edit-slug-buttons *' ).addClass( 'cml-hidden' );
      $( this ).parents( '.cml-permalink' ).find( '#edit-slug-buttons .save, #edit-slug-buttons .cancel' ).removeClass( 'cml-hidden' );
    });

    //Cancel edit permalink
    $( 'body' ).on( 'click', '.cml-permalink #edit-slug-buttons .cancel', function() {
      $sample = $( this ).parents( '.cml-permalink' ).find( '#editable-post-name' );

      $sample.html( $sample.attr( 'original-slug' ) );

      $parent = $( this ).parent();
      $( this ).parent().find( '*' ).addClass( 'cml-hidden' );
      $( this ).parent().find( '.edit-slug, .original' ).removeClass( 'cml-hidden' );
      $( this ).parents( '.cml-permalink' ).find( '.cml-view-product' ).show();

      $parent.find( '.custom-permalink' ).val( $sample.attr( 'original-slug' ) );
      $parent.find( '.spinner' ).css( 'display', 'none' );
    });

    // $( 'body' ).on( 'click', '.cml-permalink #edit-slug-buttons .cancel', function() {
		//
    // });

    //use default permalink
    $( 'body' ).on( 'click', '.cml-permalink #edit-slug-buttons .original', function() {
      $sample = $( this ).parents( '.cml-permalink' ).find( '#editable-post-name' );

      $span = $( '.inside > #edit-slug-box' ).first().find( '#editable-post-name' );
      var permalink = $span.html();
      if ( $span.find( 'input' ).length > 0 ) {
        permalink = $span.find( 'input' ).val();
      }

      $( this ).parents( '.cml-permalink' ).find( '.custom-permalink' ).val( permalink );
      $sample.html( permalink );
    });

    //confirm permalink
    $( 'body' ).on( 'click', '.cml-permalink #edit-slug-buttons .save', function() {
      $parent = $( this ).parents( '.cml-permalink' );

      $( this ).parent().find( '*' ).addClass( 'cml-hidden' );
      $parent.find( '.spinner' ).css( 'display', 'inline-block' );

      var lang = $parent.attr( 'cml-lang' );
      permalink = $parent.find( '#new-post-slug' ).val();
      if ( permalink.trim() == '' ) {
        permalink = $( 'input[name="cml_post_title_' + lang  + '"]' ).val();
      }

      if ( permalink.trim() == '' ) {
        permalink = $( 'input[name="post_title"]' ).val();
      }

      var data = {
				action: 'cmlwoo_save_permalink',
				secret: ceceppaml_admin.secret,
        lang: lang,
        permalink: permalink,
        post_type: $( 'form[name="post"] input#post_type' ).val(),
        post_ID: $( 'form[name="post"] input#post_ID' ).val()
      };

      $.post( ajaxurl, data, function(response) {
        if ( response == -1 ) {
          alert( 'Something went wrong' );

          $parent.find( '.cancel' ).trigger( 'click' );
          return;
        }

        $parent.find( '#editable-post-name' ).attr( 'original-slug', response );
        $( 'input[name="custom_permalink_' + lang + '"]' ).val( response );

        $parent.find( '.cancel' ).trigger( 'click' );
      });
    });

    //view product
    $( 'body' ).on( 'click', '.cml-permalink .cml-view-product', function() {
      var link = $( this ).parents( '.cml-permalink' ).find( '#sample-permalink' ).html();
      var pattern = /[^<]*/

      link = link.match( pattern );
      link += $( this ).parents( '.cml-permalink' ).find( '#sample-permalink' ).find( 'span' ).html();

      //Open product in new tab
      window.open( link,'_blank' );
    });
});

var CmlWoo = {
	switchTo: function( index, type ) {
		jQuery( '.cmlwoo-' + type + 'nav-tab > a' ).removeClass( 'nav-tab-active' );
		jQuery( '.cmlwoo-' + type + 'nav-tab > a#cmlwoo-' + type + 'editor-' + index ).addClass( 'nav-tab-active' );

		jQuery( '.cmlwoo-' + type + 'editor-wrapper' ).addClass( 'cml-hidden' );
		jQuery( '.cmlwoo-' + type + 'editor-' + index ).removeClass( 'cml-hidden' );

		//Show default editor
		if( index == ceceppaml_admin.default_id ) {
			jQuery( '#cmlwoo-' + type + 'editor' ).addClass( 'cml-hidden' );

			if( type == "" ) {
				jQuery( '#postdivrich' ).removeClass( 'cml-hidden' );
			} else {
				jQuery( '#wp-excerpt-wrap' ).removeClass( 'cml-hidden' );
			}
		} else {
			//My editor
			if( type == "" ) {
				jQuery( '#postdivrich' ).addClass( 'cml-hidden' );
			} else {
				jQuery( '#wp-excerpt-wrap' ).addClass( 'cml-hidden' );
			}
			jQuery( '#cmlwoo-' + type + 'editor.cmlwoo-editor-' + index ).removeClass( 'cml-hidden' );
		}

		//Resize iframe
		jQuery( '#cmlwoo-editor iframe' ).height( '433' );
	}
}
