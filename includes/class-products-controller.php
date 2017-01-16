<?php
/**
 * Products Controller
 *
 * Manages products endpoints on rest API
 *
 * @package EDD\FES_Rest_API\Products_Controller
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FES_Rest_API_Products_Controller extends FES_Rest_API_Controller {

    protected $product_singular;
    protected $product_plural;

    public function __construct() {
        $this->id = 'product';
        $this->fes_form = 'submission';

        $this->product_singular = EDD_FES()->helper->get_product_constant_name( false, false );
        $this->product_plural = EDD_FES()->helper->get_product_constant_name( true, false );

        $this->resource_name = str_replace( ' ', '-', $this->product_plural );

        parent::__construct();
    }

    public function register_rest_routes( ) {
        // List and create
        register_rest_route( $this->namespace, '/' . $this->resource_name, array(
            array(
                'methods'         => WP_REST_Server::READABLE,
                'callback'        => array( $this, 'get_products' ),
                'permission_callback' => array( $this, 'permissions_check' ),
                'args'            => array(

                ),
            ),
            array(
                'methods'         => WP_REST_Server::CREATABLE,
                'callback'        => array( $this, 'create_product' ),
                'permission_callback' => array( $this, 'permissions_check' ),
                'args'            => $this->get_schema(),
            ),
        ) );

        // Get, update and delete (endpoints with id)
        register_rest_route( $this->namespace, '/' . $this->resource_name . '/(?P<id>[\d]+)', array(
            array(
                'methods'         => WP_REST_Server::READABLE,
                'callback'        => array( $this, 'get_product' ),
                'permission_callback' => array( $this, 'permissions_check' ),
                'args'            => array(

                ),
            ),
            array(
                'methods'         => WP_REST_Server::EDITABLE,
                'callback'        => array( $this, 'update_product' ),
                'permission_callback' => array( $this, 'update_permissions_check' ),
                'args'            => $this->get_schema(),
            ),
            array(
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => array( $this, 'delete_product' ),
                'permission_callback' => array( $this, 'delete_permissions_check' ),
                'args'     => array(

                ),
            ),
        ) );
    }

    public function get_product( WP_REST_Request $request ) {
        $data = array();

        $user_id = get_current_user_id();

        if( $user_id != 0 ) {
            $download = get_post( $request->get_param( 'id' ) );

            if( !is_wp_error( $download ) && $download->post_type == 'download' && $download->post_author == $user_id ) {
                $data = $this->prepare_item_for_response($download, $request);
            } else {
                // Something wrong
            }
        }

        $response = new WP_REST_Response( $data );

        return $response;
    }

    public function get_products( WP_REST_Request $request ) {
        $data = array();

        $user_id = get_current_user_id();

        if( $user_id != 0 ) {
            $page = $request->get_param( 'page' );

            $query = new WP_Query(array(
                'post_type' => 'download',
                'post_status' => array( 'publish', 'pending', 'draft' ),
                'author' => $user_id,
                'posts_per_page' => 10,
                'paged' => ( ( $page != null ) ? $page : 1 )
            ));

            $downloads = $query->get_posts();

            foreach( $downloads as $download ) {
                $data[] = $this->prepare_item_for_response( $download, $request );
            }
        }

        $response = new WP_REST_Response( $data );

        return $response;
    }

    public function create_product( $request ) {
        $data = array();

        $user_id = get_current_user_id();

        if( $user_id != 0 ) {
            $data = $this->save_item( $request );

            // If save item success then adds the product to the response
            if( isset($data['success']) && $data['success'] ) {
                $download = get_post( $data['save_id'] );

                unset( $data['save_id'] );

                /**
                 * Fires after a single post is created or updated via the REST API.
                 *
                 * @param WP_Post         $download  Inserted download object.
                 * @param WP_REST_Request $request   Request object.
                 * @param boolean         $creating  True when creating download, false when updating.
                 */
                do_action( "rest_insert_download", $download, $request, true );

                // Adds a meta to meet when a download comes from API
                update_post_meta( $download->ID, 'edd_fes_rest_api_created_from_api', true );

                $data[$this->product_singular] = $this->prepare_item_for_response( $download, $request );
            }
        }

        $response = new WP_REST_Response( $data );

        return $response;
    }

    public function update_product( $request ) {
        $data = array();

        $user_id = get_current_user_id();

        if( $user_id != 0 ) {
            $data = $this->save_item( $request, $request->get_param( 'id' ) );

            // If save item success then adds the product to the response
            if( isset($data['success']) && $data['success'] ) {
                $download = get_post( $request->get_param( 'id' ) );

                unset( $data['save_id'] );

                /**
                 * Fires after a single post is created or updated via the REST API.
                 *
                 * @param WP_Post         $download  Inserted download object.
                 * @param WP_REST_Request $request   Request object.
                 * @param boolean         $creating  True when creating download, false when updating.
                 */
                do_action( "rest_insert_download", $download, $request, false );

                // Adds a meta to meet when a download comes from API
                update_post_meta( $download->ID, 'edd_fes_rest_api_updated_from_api', true );

                $data[$this->product_singular] = $this->prepare_item_for_response( $download, $request );
            }
        }

        $response = new WP_REST_Response( $data );

        return $response;
    }

    public function delete_product( $request ) {
        $data = array();

        $user_id = get_current_user_id();

        if( $user_id != 0 ) {
            wp_delete_post( $request->get_param( 'id' ) );
        }

        $response = new WP_REST_Response( $data );

        return $response;
    }

    public function update_permissions_check( $request ) {
        if( ! EDD_FES()->helper->get_option( 'fes-allow-vendors-to-edit-products', false ) ) {
            return false;
        }

        if ( ! EDD_FES()->vendors->vendor_can_edit_product( $request->get_param( 'id' ) ) ) {
            return false;
        }

        return parent::permissions_check( $request );
    }

    public function delete_permissions_check( $request ) {
        if( ! EDD_FES()->helper->get_option( 'fes-allow-vendors-to-delete-products', false ) ) {
            return false;
        }

        if ( ! EDD_FES()->vendors->vendor_can_delete_product( $request->get_param( 'id' ) ) ) {
            return false;
        }

        return parent::permissions_check( $request );
    }

    public function prepare_item_for_response( $item, $request ) {
        $response = parent::prepare_item_for_response( $item, $request );

        // Adds the post_ID on products for response
        $response['id'] = $item->ID;

        return $response;
    }

    public function prepare_fields_for_save($values)
    {
        $values = parent::prepare_fields_for_save($values);

        // Pass pricing fields
        if(isset($values['multiple_pricing'])) {
            // Multiple pricing

            foreach($values['multiple_pricing'] as $key => $price_option) {
                // TODO: Turn file ID into url
            }
        } else if(isset($values[$this->product_singular . '_price']) && isset($values[$this->product_singular . '_file'])) {
            // Single pricing

            // TODO: Turn file ID into url
        }

        return $values;
    }

    public function get_field_schema($schema, $field, $field_name) {
        $schema = parent::get_field_schema($schema, $field, $field_name);

        // Custom schema params for download specific fields

        // Download category
        if ($field->template() == 'download_category') {
            $terms = get_terms(array(
                'taxonomy' => 'download_category',
                'hide_empty' => false,
                'parent' => null,
                'fields' => 'id=>name'
            ));

            $options = array();

            foreach($terms as $term_id => $term_name) {
                $options[$term_id] = $term_name;

                $children = get_term_children( $term_id, 'download_category' );

                if( ! is_wp_error( $children ) ) {
                    foreach($children as $child_id) {
                        $child_term = get_term_by( 'id', $child_id, 'download_category' );

                        $options[$child_id] = '    ' . $child_term->name;
                    }
                }
            }

            $schema[$field_name]['enum'] = $options;
        }

        // Download tags
        // TODO

        // Price
        if ($field->template() == 'multiple_pricing') {
            $names_disabled     = isset( $field->characteristics['names'] )    &&  $field->characteristics['names']    !== 'no'    ? false : true;
            $prices_disabled    = isset( $field->characteristics['prices'] )   &&  $field->characteristics['prices']   !== 'no'    ? false : true;
            $files_disabled     = isset( $field->characteristics['files'] )    &&  $field->characteristics['files']    !== 'no'    ? false : true;
            $predefined_on      = isset( $field->characteristics['multiple'] ) &&  $field->characteristics['multiple'] !== 'false' ? true  : false;
            $predefined_options = !empty( $field->characteristics['files'] )   &&  $field->characteristics['files']    ? esc_attr( $field->characteristics['files'] ) : false;

            if($field->characteristics['single'] !== 'yes') {
                // Multiple pricing
                $schema[$field_name]['type'] = 'array';
                unset($schema[$field_name]['sanitize_callback']);

                $schema[$field_name]['default'] = array();

                if( !$names_disabled || $predefined_on ) {
                    $schema[$field_name]['default']['description'] = array(
                        'description' => __('Name of price option'),
                        'type' => 'string',
                        'required' => $field->required(),
                    );
                }

                if ( !$prices_disabled || $predefined_on ) {
                    $schema[$field_name]['default']['price'] = array(
                        'description' => sprintf( __( 'Amount (%s)', 'edd-fes-rest-api' ), edd_get_currency() ),
                        'type' => 'number',
                        'required' => $field->required(),
                    );
                }

                if ( !$files_disabled ) {
                    $schema[$field_name]['default']['file'] = array(
                        'description' => __('File'),
                        'type' => 'file',
                        'required' => $field->required(),
                    );
                }
            } else {
                // Single pricing
                unset($schema[$field_name]);

                $schema[$this->product_singular . '_price'] = array(
                    'description' => sprintf( __( 'Amount (%s)', 'edd-fes-rest-api' ), edd_get_currency() ),
                    'type' => 'number',
                    'required' => $field->required(),
                );

                $schema[$this->product_singular . '_file'] = array(
                    'description' => __('File'),
                    'type' => 'file',
                    'required' => $field->required(),
                );
            }
        }

        return $schema;
    }

    public function get_masked_fields() {
        return apply_filters( 'edd_fes_rest_api_masked_' . $this->id . '_fields', array(
            'post_title' => 'title',
            'post_content' => 'description',
            'download_category' => 'category',
            'multiple_pricing' => $this->product_singular . '_prices',
        ) );
    }
}
