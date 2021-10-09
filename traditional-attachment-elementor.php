<?php

use ElementorPro\Modules\Forms\Classes\Ajax_Handler;
use ElementorPro\Modules\Forms\Classes\Form_Record;

/**
 * Send Elementor Form upload field as attachments to email
 */
class Elementor_Form_Email_Attachments {

	/** 
	 * Set to true if you want the files to be removed from
	 * the server after they are sent by email
	 */ 
	const DELETE_ATTACHMENT_FROM_SERVER = false;

	public $attachments_array = [];

	public function __construct() {
		add_action( 'elementor_pro/forms/process', [$this, 'init_form_email_attachments'], 11, 2 );
	}

	/**
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record
	 * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
	 */
	public function init_form_email_attachments( $record, $ajax_handler ) {
		// check if we have attachments
		$files = $record->get( 'files' );
		if ( empty( $files ) ):
			return;
		endif;

		// Store attachment in local var
		foreach ( $files as $id => $files_array ):
			
			/* New Fix For multiple files */
			$len = 20;

			for($i = 0; $i < $len; $i++) :
  				$this->attachments_array[] = $files_array['path'][$i];
   			endfor;
		endforeach;

		// if local var has attachments setup filter hook
		if ( 0 < count( $this->attachments_array ) ):
			add_filter( 'wp_mail', [ $this, 'wp_mail' ] );
			add_action( 'elementor_pro/forms/new_record', [ $this, 'remove_wp_mail_filter' ], 5 );
		endif;
	}

	public function remove_wp_mail_filter() {

		if ( self::DELETE_ATTACHMENT_FROM_SERVER ) :
			foreach ( $this->attachments_array as $uploaded_file ):
				unlink( $uploaded_file );
			endforeach;
		endif;

		$this->attachments_array = [];
		remove_filter( 'wp_mail', [ $this, 'wp_mail' ] );
	}

	public function wp_mail( $args ) {
		// check for no attachment flag
		$no_attachments = strpos($args['message'], '[elementor_form_no_attachment]');
		if ( FALSE !== $no_attachments ) :
			// remove the flag from the message
			$args['message'] = str_replace('[elementor_form_no_attachment]', '', $args['message']);
		else:
			// attach files instead
			$args['attachments'] = $this->attachments_array;
		endif;
			
		return $args;
	}
}

new Elementor_Form_Email_Attachments();