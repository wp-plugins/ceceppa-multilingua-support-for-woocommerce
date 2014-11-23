jQuery(document).bind("ajaxSend", function( e, xhr, settings ){
   if (settings.data == null) {
    settings.data = { };
  }
  settings.data[ 'lang' ] = ceceppa_ml.slug;
});