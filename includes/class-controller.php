<?php
/**
 * Controller
 *
 * @package EDD\FES_Rest_API\Controller
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FES_Rest_API_Controller {

    // For filters
    protected $id;
    // FES form
    protected $fes_form;
    //
    protected $namespace_base;

    protected $version;

    protected $namespace;

    protected $resource_name;

    public function __construct() {
        $this->namespace_base = 'fes';
        $this->version = '1';
        $this->namespace = apply_filters( 'edd_fes_rest_api_namespace', ( ( !empty($this->namespace_base) ) ? $this->namespace_base . '/' : '' ) . 'v' . $this->version );

        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
    }

    public function register_rest_routes( ) {

    }

    /**
     * Check if logged user is vendor
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function permissions_check( $request ) {
        return EDD_FES()->vendors->user_is_vendor();
    }

    public function save_item( $request, $save_id = -2 ) {
        $data = array();
        $form_id = EDD_FES()->helper->get_option( 'fes-' . $this->fes_form . '-form', false );
        $values = $_POST;
        $form_class = 'FES_' . ucfirst($this->fes_form) . '_Form';

        // Pass masked fields
        foreach( $this->get_masked_fields() as $original_field => $masked_field ) {
            if( isset($values[$masked_field]) ) {
                $values[$original_field] = $values[$masked_field];
            }
        }


        $values = $this->prepare_fields_for_save($values);

        // FES needs a nonce to process the form
        // Form is never rendered so we need to add it manually
        $_REQUEST['fes-' . $this->fes_form . '-form'] = wp_create_nonce( 'fes-' . $this->fes_form . '-form' );

        // Make the FES Form
        $form = new $form_class( $form_id, 'id', $save_id );

        // Save the FES Form
        $response = $form->save_form_frontend( $values );

        if( isset( $response['success'] ) ) {
            $data['message'] = $response['message'];
            $data['success'] = $response['success'];

            if( $response['success'] == true ) {
                // Success
                $data['code'] = 'rest_save_form_' . $this->id . '_success';
                $data['save_id'] = $form->save_id;
            } else {
                // Error on submit
                $data['code'] = 'rest_save_form_' . $this->id . '_failure';
                $data['errors'] = $response['errors'];
            }
        } else {
            // Something wrong happens
            $data['code'] = 'rest_save_form_' . $this->id . '_failure';
            $data['message'] = 'Unknown error!';
            $data['success'] = false;
        }

        return $data;
    }

    public function prepare_fields_for_save($values) {
        return $values;
    }

    /**
     * Prepare the item for the REST response.
     *
     * @param mixed $item WordPress representation of the item.
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response $response
     */
    public function prepare_item_for_response( $item, $request ) {
        $response = array();

        foreach($this->get_form_fields( $item->ID ) as $field) {
            // Pass the masked field name if exists
            $masked_fields = $this->get_masked_fields();
            $field_name = ( ( isset($masked_fields[$field->name()]) ) ? $masked_fields[$field->name()] : $field->name() );

            if( $field->template() == 'action_hook' ) {
                // Action hook fields needs define their own response
                $response = apply_filters( 'edd_fes_rest_api_' . $field->name() . '_prepare_for_response', $response, $field_name );
            } else if( $field->template() == 'featured_image' ) {
                // Featured image aka post thumbnail
                if(has_post_thumbnail( $item->ID )) {
                    $response[$field_name] = wp_get_attachment_url( get_post_thumbnail_id( $item->ID ) );
                } else {
                    $response[$field_name] = '';
                }
            } else {
                $response[$field_name] = $field->get_field_value_frontend( $item->ID, get_current_user_id(), true );
            }
        }

        return apply_filters( 'edd_fes_rest_api_' . $this->id . '_prepare_for_response', $response );
    }

    public function get_schema() {
        $schema = array();

        foreach( $this->get_form_fields() as $field ) {
            // Pass the masked field name if exists
            $masked_fields = $this->get_masked_fields();
            $field_name = ( ( isset($masked_fields[$field->name()]) ) ? $masked_fields[$field->name()] : $field->name() );

            if( $field->template() == 'action_hook' ) {
                // Action hook fields needs define their own schema
                $schema = apply_filters( 'edd_fes_rest_api_' . $field->name() . '_schema', $schema, $field, $field_name );
            } else {
                $schema = $this->get_field_schema($schema, $field, $field_name);
            }
        }

        return apply_filters( 'edd_fes_rest_api_' . $this->id . '_schema', $schema );
    }

    /**
     * Function to mask fields to use masked fields instead of Wordpress/EDD/FES fields (for example category instead of download_category)
     *
     * @return array
     */
    public function get_field_schema($schema, $field, $field_name) {
        $schema[$field_name] = array(
            'description' => $field->characteristics['help'],
            'type' => $this->parse_template_to_type( $field->template() ),
            'required' => $field->required(),
            'sanitize_callback' => 'sanitize_text_field',
        );

        // Custom schema params based on field type

        // Select
        if ($field->template() == 'select') {
            $schema[$field_name]['enum'] = $field->characteristics['options'];
        }

        return $schema;
    }

    public function get_form_fields( $item_id = false ) {
        $form_id = EDD_FES()->helper->get_option( 'fes-' . $this->fes_form . '-form', false );

        $form = EDD_FES()->helper->get_form_by_id( $form_id, $item_id );

        return apply_filters( 'edd_fes_rest_api_' . $this->id . '_fields', $form->fields );
    }

    /**
     * Function to mask fields to use masked fields instead of Wordpress/EDD/FES fields (for example category instead of download_category)
     *
     * @return array
     */
    public function get_masked_fields() {
        return apply_filters( 'edd_fes_rest_api_masked_' . $this->id . '_fields', array() );
    }

    /**
     * Parses FES form field template to Rest API type
     *
     * @return array
     */
    public function parse_template_to_type( $template ) {
        switch( $template ) {
            case 'featured_image':
            case 'user_avatar':
                return 'file';
            case 'download_category':
                return 'integer';
            default:
                return 'string';
        }
    }
}
