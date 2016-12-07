<?php
/**
 * Profile Controller
 *
 * @package EDD\FES_Rest_API\profile_Controller
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FES_Rest_API_Profile_Controller extends FES_Rest_API_Controller {
    public function __construct() {
        $this->id = 'profile';
        $this->fes_form = 'profile';
        $this->resource_name = 'profile';

        parent::__construct();
    }

    public function register_rest_routes( ) {
        register_rest_route( $this->namespace, '/' . $this->resource_name, array(
            array(
                'methods'         => WP_REST_Server::READABLE,
                'callback'        => array( $this, 'get_profile' ),
                'permission_callback' => array( $this, 'permissions_check' ),
                'args'            => array(

                ),
            ),
            array(
                'methods'         => WP_REST_Server::CREATABLE,
                'callback'        => array( $this, 'update_profile' ),
                'permission_callback' => array( $this, 'permissions_check' ),
                'args'            => $this->get_schema(),
            ),
        ) );
    }

    public function get_profile( $request ) {
        $data = array();

        $user_id = get_current_user_id();

        if( $user_id != 0 ) {
            $data[] = $this->prepare_item_for_response( get_userdata( $user_id ), $request );
        }

        $response = new WP_REST_Response( $data );

        return $response;
    }

    public function update_profile( $request ) {
        $data = array();

        $user_id = get_current_user_id();

        if( $user_id != 0 ) {
            $data = $this->save_item( $request );

            // If save item success then adds the product to the response
            if( isset($data['success']) && $data['success'] ) {
                $user = wp_get_current_user();

                unset( $data['save_id'] );

                $data['profile'] = $this->prepare_item_for_response( $user, $request );
            }
        }

        $response = new WP_REST_Response( $data );

        return $response;
    }

    public function get_masked_fields() {
        return apply_filters( 'edd_fes_rest_api_masked_' . $this->id . '_fields', array( 'user_url' => 'vendor_url' ) );
    }
}
