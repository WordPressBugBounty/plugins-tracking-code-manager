<?php
if ( ! defined( 'ABSPATH' ) ) exit;
add_filter( 'wp_head', 'tcmp_head', get_option( 'TCM_HookPriority', TCMP_HOOK_PRIORITY_DEFAULT ) );
function tcmp_head() {
	global $post, $tcmp;

	$tcmp->options->setPostShown( null );
	if ( $post && isset( $post->ID ) && $post->ID > 0 ) {
		$tcmp->options->setPostShown( $post );
		$tcmp->log->info( 'POST ID=%s IS SHOWN', $post->ID );
	}

	$tcmp->body_written = false;

	//future development
	//is_archive();
	//is_post_type_archive();
	//is_post_type_hierarchical();
	//is_attachment();
	$tcmp->manager->write_codes( TCMP_POSITION_HEAD );
}

add_action( 'wp_body_open', 'tcmp_body', get_option( 'TCM_HookPriority', TCMP_HOOK_PRIORITY_DEFAULT ) );
function tcmp_body() {
	global $tcmp;

	$tcmp->manager->write_codes( TCMP_POSITION_BODY );
	$tcmp->body_written = true;
}

add_action( 'wp_footer', 'tcmp_footer', get_option( 'TCM_HookPriority', TCMP_HOOK_PRIORITY_DEFAULT ) );
function tcmp_footer() {
	global $tcmp;

	if ( ! $tcmp->body_written ) {
		// this is a fallback if wp_body_open() is not called by the theme
		$tcmp->manager->write_codes( TCMP_POSITION_BODY );
	}

	$tcmp->manager->write_codes( TCMP_POSITION_CONVERSION );
	$tcmp->manager->write_codes( TCMP_POSITION_FOOTER );

	if ( $tcmp->options->getModifySuperglobalVariable() ) {
		if ( function_exists( 'wp_cache_set_home' ) ) {
			unset( $_POST );
		}
	}
}

//volendo funziona anche con gli shortcode
add_shortcode( 'tcmp', 'tcmp_shortcode' );
add_shortcode( 'tcm', 'tcmp_shortcode' );
function tcmp_shortcode( $atts, $content = '' ) {
	global $tcmp;
	// Assign explicitly instead of extract() on external input (see F-13).
	$atts = shortcode_atts( array( 'id' => false ), $atts );
	$id   = $atts['id'];

	if ( ! $id ) {
		return '';
	}

	$snippet = $tcmp->manager->get( $id, true );
	if ( ! is_array( $snippet ) || ! isset( $snippet['code'] ) ) {
		return '';
	}

	// Run the snippet through the same output path as write_codes() instead of
	// returning it raw, so the shortcode and the normal injection path cannot
	// diverge (F-05). Note that this equalises the two paths; it does not make
	// the shortcode safe to expose to lower-privileged authors, because the
	// whitelist esc_js_code() applies permits <script> by design.
	return $tcmp->manager->esc_js_code( $snippet['code'] );
}

function tcmp_ui_first_time() {
	global $tcmp;
	if ( $tcmp->options->isShowActivationNotice() ) {
		//$tcmp->options->pushSuccessMessage('FirstTimeActivation');
		//$tcmp->options->writeMessages();
		$tcmp->options->setShowActivationNotice( false );
	}
}
function tcmp_admin_footer() {
	global $tcmp;
	if ( $tcmp->lang->bundle->autoPush && TCMP_AUTOSAVE_LANG ) {
		$tcmp->lang->bundle->store( TCMP_PLUGIN_DIR . 'languages/Lang.txt' );
	}
}
add_filter( 'admin_footer', 'tcmp_admin_footer' );
