<?php
/*
Plugin Name: It's Wednesday! Beer Me!
Author: Aaron Brazell
Author URI: aaron@technosailor.com
Description: A demonstration of <a href="http://wp-cli.org">WP-CLI</a> for Baltimore PHP using the <a href="http://brewerydb.org">BreweryDB</a> API
*/

if( defined( 'WP_CLI' ) && WP_CLI ) {
	class Beer_Me extends WP_CLI_Command {

		public $apikey;
		public $apiurl;

		public function __construct() {
			$this->apikey = BREWERYDB_APIKEY;
			$this->apiurl = 'http://api.brewerydb.com/v2/';
		}

		/**
		 * @synopsis post <brewery_info> --abv=<abv> [--ibu=<ibu>]
		 */
		public function post( $args, $assoc_args ) {

			global $current_user;

			// Base API endpoint
			$url = $this->apiurl . 'beers/?key=' . $this->apikey;

			// Add positional ($args) variables
			$url .= '&withBreweries=' . strtoupper( $args[0] );

			// Add other arguments (optional and required)
			$url .= '&abv=+' . (int) round( $assoc_args['abv'] );
			$url .= ( isset( $assoc_args['ibu'] ) ) ? '&ibu=+' . (int) round( $assoc_args['ibu'] ) : '';

			$url = esc_url_raw( $url );

			$request = wp_remote_get( $url );
			$json = wp_remote_retrieve_body( $request );
			$data = json_decode( $json );
			$one_beer = $data->data[array_rand( $data->data )];

			// If either of our fields are empty, get another one
			if( !$one_beer->name || !$one_beer->description ) {
				$one_beer = $data->data[array_rand( $data->data )];
			}

			// Assemble a post
			$post = array(
				'post_title' => $one_beer->name,
				'post_content' => $one_beer->description,
				'post_status' => 'publish',
				'post_author' => $current_user->ID
			);

			if( $post_id = wp_insert_post( $post ) ) {
				add_post_meta( $post_id, 'abv', $one_beer->abv );
				add_post_meta( $post_id, 'ibu', $one_beer->ibu );
				add_post_meta( $post_id, 'organic', $one_beer->isOrganic );

				WP_CLI::line( sprintf( "Post %d: Inserted successfully!", $post_id ) );
			}
			else {
				WP_CLI::line( 'Failed to insert post! You clearly are a dumbass.');
			}
			
		}
	}

	WP_CLI::add_command( 'beer', 'Beer_Me' );
}