<?php
/**
 * It fires on the frontend.

 * @package Hide Link PRO
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

add_action( 'the_content','eos_hide_links_in_content' );
add_filter( 'render_block','eos_hide_links_in_content' );
// add_filter( 'block_editor_content','hide_links_by_css_class' );

/**
 * Hide the links.
 *
 * @param string $content Content
 * @since  1.0.0
 */
function eos_hide_links_in_content( $content ){
    return eos_hide_links_in_element( $content, true );
}

/**
 * Hide all the links in the element.
 *
 * @param string $content Content
 * @since  1.0.0
 */
function eos_hide_links_in_element( $content, $by_href_suffix = false, $context = '' ) {
    $processor = new WP_HTML_Tag_Processor( $content );
    $opts = get_site_option( 'hide-link-pro' );
    $class = apply_filters( 'hide_link_cache_css_class', isset( $opts['hidden_class'] ) && ! empty( $opts['hidden_class'] ) ? sanitize_key( $opts['hidden_class'] ) : 'hide-links' );
    do {
        $href = $processor->get_attribute( 'href' );
        $target = $processor->get_attribute( 'target' );
        if( $href && ! empty( $href ) && ( ( ! $by_href_suffix || false !== strpos( $href, '#hide-this-link' ) ) || $processor->has_class( esc_attr( $class ) ) ) ) {             
            $target = ! $target ? '\',\'_self' : '\',\'' . $target;
            $processor->add_class( 'eos-hl' );
            $processor->remove_attribute( 'href' );
            $processor->set_attribute( 'onclick', 'window.open(\'' . esc_url( str_replace( '#hide-this-link', '', $href ) ) . $target . '\');' );
        }
    }
    while ( $processor->next_tag( 'a' ) );
    $content = $processor->get_updated_html();
    return $content;
  }


add_action( 'wp_footer',function(){
    /**
     * Add inline style and scripts.
     *
     * @since  1.0.0
     */
    ?>
    <style id="hide-links-css">.eos-hl{cursor:pointer}</style>
	<script id="hide-links-js">
		function eos_hl_set_colors(){
			var hl_style = document.getElementById('hide-links-css'), a = document.createElement("a");
			a.style.display = 'hidden';
			document.body.appendChild(a);
			col = window.getComputedStyle(a,null).getPropertyValue("color");
			if(col && '' !== col){
				hl_style.innerHTML = hl_style.innerHTML.replace('{cursor:pointer}','{cursor:pointer;color:' + col);
			}
			a.remove();
		}
		eos_hl_set_colors();
		document.getElementById('hide-links-js').remove();
	</script>
    <?php
} );

add_filter( 'walker_nav_menu_start_el',function( $item_output, $menu_item, $depth, $args ) {
    $hide_link = false;
    $options = get_site_option( 'hide-link-pro', array() );
    if( isset( $options['main_navigation'] ) && 'true' === sanitize_text_field( $options['main_navigation'] ) ) {
        $hide_link = true;
    }
    else{
        $hide_link = get_post_meta( absint( $menu_item->ID ), '_hide_link', true );
    }
    if(
        $hide_link
        || ( false !== strpos( $menu_item->url,'#hide-this-link') && false !== strpos( $item_output,'<a' ) )
        || ( defined( 'HIDE_LINK_FOR_ALL_MENU_ITEMS' ) && HIDE_LINK_FOR_ALL_MENU_ITEMS )
    ) {
        if( false !== strpos( $item_output,'class="' ) ){
            $item_output = str_replace( 'class="','class="eos-hl ', $item_output );
        }
        else{
            $item_output = str_replace( '<a ','<a class="eos-hl" ', $item_output );
        }
        $item_output = str_replace( 'href="'.$menu_item->url.'"','onclick="window.location.href=\''.esc_url( str_replace( '#hide-this-link','',$menu_item->url ) ).'\';"',$item_output );
    }
  return $item_output;
}, 999999, 4 );

add_action( 'init', function() {
    add_filter( 'wp_nav_menu', function( $nav_menu, $args ) {
        /**
         * Hide links in navigation according to the settings.
         *
         * @since  1.0.1
         */
        if( isset( $args->menu ) && isset( $args->menu->slug ) ) {
            $opts = get_site_option( 'hide-link-pro' );
            if( 'all' === $opts['hidden_menus'] || ( $opts && isset( $opts['hidden_menus'] ) && $args->menu->slug === $opts['hidden_menus'] ) ) {
                return eos_hide_links_in_element( $nav_menu, false, 'menu_' );
            }
        }
        return $nav_menu;
    }, 999999 , 2 );
} );