<?php

/*
 * On the Advanced Settings page enable "Enable dynamic caching" and clear
 * the cache.
 *
 * Plugin authors: NEVER define the template tag for your users. Make them 
 * choose one so it will be unique to their site.
 *
 * There are two examples in this file. Both use template tags that must be
 * kept secret.
 *
 * GLOSSARY:
 *
 * Dynamic content: the text or widget you want to show visitors to your site
 * that changes every time it's viewed.
 * Placeholder/template tag: the string of random characters placed in your 
 * theme file or printed in an action where the dynamic content will go.
 * Output buffer (ob): any text that is printed by PHP to be sent to the browser 
 * but captured by PHP for further manipulation.
 * OB Callback function: A function that is called when the output buffer is
 * filled with a html page. The contents of the page are passed to the function
 * for processing.
 *
 * **** MAKE SURE YOU KEEP THE TEMPLATE TAG SECRET ****
 */

/*
 * EXAMPLE 1
 * http://ocaoimh.ie/2013/10/21/shiny-new-dynamic-content-wp-super-cache/
 * Replace a string in your theme with the dynamic content.
 *
 * dynamic_cache_test_init()
 * This function is the first one to be called. This function hooks 
 * dynamic_cache_test_template() to the WordPress action, wp_footer.
 * This script is loaded before WordPress is and the add_action() 
 * function isn't defined at this time. 
 * This init function hooks onto the cache action "add_cacheaction"
 * that fires after WordPress (and add_action) is loaded.
 *
 *
 * dynamic_cache_test_template_tag()
 * This function hooks on to wp_footer and displays the secret template 
 * tag that will be replaced by our dynamic content on each page view.
 *
 *
 * dynamic_cache_test_filter()
 * This function hooks on to the filter through which all the cached data
 * sent to visitors is sent.
 * In this simple example the template tag is replaced by a html comment 
 * containing the text "Hello world at " and the current server time. 
 * If you want to use the output of a WordPress plugin or command you 
 * must enable "late init" on the settings page. Each time you reload 
 * the cached page this time will change. View the page source to examine 
 * this text.
 *
 */
define( 'DYNAMIC_CACHE_TEST_TAG', '' ); // Change this to a secret placeholder tag
if ( DYNAMIC_CACHE_TEST_TAG != '' ) {
	function dynamic_cache_test_safety( $safety ) {
		return 1;
	}
	add_cacheaction( 'wpsc_cachedata_safety', 'dynamic_output_buffer_test_safety' );


	function dynamic_cache_test_filter( &$cachedata) {
		return str_replace( DYNAMIC_CACHE_TEST_TAG, "<!-- Hello world at " . date( 'H:i:s' ) . " -->", $cachedata );
	}
	add_cacheaction( 'wpsc_cachedata', 'dynamic_cache_test_filter' );

	function dynamic_cache_test_template_tag() {
		echo DYNAMIC_CACHE_TEST_TAG; // This is the template tag
	}

	function dynamic_cache_test_init() {
		add_action( 'wp_footer', 'dynamic_cache_test_template_tag' );
	}
	add_cacheaction( 'add_cacheaction', 'dynamic_cache_test_init' );
}

/*
 * EXAMPLE 2
 *
 * This is going to be complicated. Hang on!
 *
 * When the cache file for a new page is generated the plugin uses an output
 * buffer to capture the page. A callback function processes the buffer and 
 * writes to the cache file. The placeholder tag for any dynamic content has
 * to be written to that cache file but also, it has to be replaced with 
 * dynamic content before the page is shown to the user.
 * More on output buffers here: http://php.net/ob_start
 *
 * Unfortunately an extra output buffer is often required when capturing dynamic
 * content such as sidebar widgets. Due to a quirk of the way PHP works it's 
 * not possible to have an output buffer run in an output buffer callback. That
 * dynamic content has to be generated before the callback function is reached.
 * The following error occurs when an output buffer is created in the
 * callback function of another output buffer:
 * "PHP Fatal error:  ob_start(): Cannot use output buffering in output buffering display handlers in..."
 *
 * In this example the function add_action() isn't available when this file is
 * loaded so dynamic_output_buffer_init() is hooked on to the "add_cacheaction"
 * cacheaction. That function then hooks dynamic_output_buffer_test() on to the 
 * familiar wp_footer action.
 *
 * The first time dynamic_output_buffer_test() runs it generates the dynamic
 * content and captures it with ob_start() in the DYNAMIC_OB_TEXT constant.
 *
 * When the main WP Super Cache output buffer is ready the callback is called.
 * This fires the wpsc_cachedata_safety filter. If the DYNAMIC_OB_TEXT constant
 * is set, which means dynamic content is ready, then it returns 1, a signal
 * that everything is ok.
 * Finally, the wpsc_cachedata filter is run. The function 
 * dynamic_output_buffer_test() is hooked on to it. Since DYNAMIC_OB_TEXT is
 * set it replaces the placeholder text with that constant.
 * The resulting html is then sent to the browser.
 *
 * Already cached pages call the safety filter, and then the wpsc_cachedata
 * filter so any hooked function must be ready to generate dynamic content. The
 * very last line of dynamic_output_buffer_test() replaces the placeholder tag
 * with the dynamic content in the cache file.
 *
 *
 * Use an output buffer to capture dynamic content while the page is generated
 * and insert into the right place:
 * Remember to add the DYNAMIC_OUTPUT_BUFFER_TAG text (as defined below) to 
 * your theme where the dynamic content should be.
 *
 * dynamic_output_buffer_test() is a function that uses the wpsc_cachedata 
 * filter to add a small message and the current server time to every web 
 * page. The time increments on every reload.
 *
 */

define( 'DYNAMIC_OUTPUT_BUFFER_TAG', '' ); // Change this to a secret placeholder tag

if ( DYNAMIC_OUTPUT_BUFFER_TAG != '' ) {
	function dynamic_output_buffer_test( &$cachedata = 0 ) {
		if ( defined( 'DYNAMIC_OB_TEXT' ) )
			return str_replace( DYNAMIC_OUTPUT_BUFFER_TAG, DYNAMIC_OB_TEXT, $cachedata );

		ob_start();
		// call the sidebar function, do something dynamic
		echo "<p>This is a test. The current time on the server is: " . date( 'H:i:s' ) . "</p>";
		$text = ob_get_contents();
		ob_end_clean();

		if ( $cachedata === 0 ) { // called directly from the theme so store the output
			define( 'DYNAMIC_OB_TEXT', $text );
		} else // called via the wpsc_cachedata filter. We only get here in cached pages in wp-cache-phase1.php
			return str_replace( DYNAMIC_OUTPUT_BUFFER_TAG, $text, $cachedata );

	}
	add_cacheaction( 'wpsc_cachedata', 'dynamic_output_buffer_test' );

	function dynamic_output_buffer_init() {
		add_action( 'wp_footer', 'dynamic_output_buffer_test' );
	}
	add_cacheaction( 'add_cacheaction', 'dynamic_output_buffer_init' );

	function dynamic_output_buffer_test_safety( $safety ) {
		if ( defined( 'DYNAMIC_OB_TEXT' ) ) // this is set when you call dynamic_output_buffer_test() from the theme
			return 1; // ready to replace tag with dynamic content.
		else
			return 0; // tag cannot be replaced.
	}
	add_cacheaction( 'wpsc_cachedata_safety', 'dynamic_output_buffer_test_safety' );
}
?>
