<?php
    // If this file is called directly, abort.
    if ( ! defined( 'WPINC' ) ) {
        die;
    }

    class WP_PrimaryCategory
    {
        protected $_slug = 'wp_primarycat';
    
        protected $_version = PRIMARYCATEGORY_VERSION;
        
        protected $_debug = false;
    
        protected $_script_debug = false;
        
        protected static $instance;
    
        /**
         * Return Instance
         * @return mixed
         */
        public static function get_instance()
        {
            if(!is_a(static::$instance, __CLASS__))
            {
                static::$instance = true;
                static::$instance = new self();
            }
            return self::$instance;
        }
    
        /**
         * Init
         */
        public function init()
        {
            $this->pc_debug();
            if ( is_admin() )
            {
                add_action('add_meta_boxes', [$this, 'pc_add_custom_box']);
                add_action( 'save_post', [$this, 'pc_save'] );
            }else{
                add_shortcode( 'pc-shortcode', [$this, 'pc_display' ] );
            }
        }
    
        /**
         * Define Debug constants.
         */
        protected function pc_debug()
        {
            if (defined('WP_DEBUG') && true === WP_DEBUG)
            {
                $this->_debug = WP_DEBUG;
            }
            if (defined('SCRIPT_DEBUG') && true === SCRIPT_DEBUG)
            {
                $this->_script_debug = SCRIPT_DEBUG;
            }
        }
    
        /**
         * Post Meta Box
         * @param $post_type
         */
        public function add_meta_box( $post_type )
        {
            // Limit meta box to certain post types.
            $post_types = array( 'post', 'page' );
        
            if ( in_array( $post_type, $post_types ) )
            {
                add_meta_box(
                    'some_meta_box_name',
                    __( 'Some Meta Box Headline', 'primary-cat' ),
                    array( $this, 'render_meta_box_content' ),
                    $post_type,
                    'advanced',
                    'high'
                );
            }
        }
    
        /**
         * Metabox Callback
         *
         * @param $post_type
         */
        function pc_add_custom_box($post_type)
        {
            // Retrieve the taxonomy name
            $screen = get_current_screen();
    
            //Check if custom post type hasn't taxonomies
            $mytax = get_post_type_object($screen->post_type);
            
            if ($screen->post_type != 'post' && empty($mytax->taxonomies)) return;
            
            $post_types = ['post'];
            if ( in_array( $post_type, $post_types ) )
            {
                add_meta_box(
                    'some_meta_box_name',
                    __( 'Primary Category', 'primary-cat' ),
                    array( $this, 'pc_custom_box_html' ),
                    $post_type,
                    'advanced',
                    'high'
                );
            }
        }
    
        /**
         * HTML Custom box
         * @param $post
         */
        function pc_custom_box_html($post)
        {
            global $post;

            // Retrieve data from primary_category custom field
            $current_selected = get_post_meta( $post->ID, '_pc_primary_category', true );
    
            // Retrieve the taxonomy name
            $screen = get_current_screen();
            
            $taxarray = get_object_taxonomies( $screen->post_type );
            
            $taxonomy_name=$taxarray[0];
            
            $terms = get_terms( $taxonomy_name );
            
            // Add an nonce field so we can check for it later.
            wp_nonce_field( 'pc_inner_custom_box', 'pc_inner_custom_box_nonce' );
    
            ?>
            <label for="pc_field">Category:</label>
            <select name="pc_field" id="pc_field" class="postbox">
                <option value="">Select something...</option>
                <?php
                    foreach ($terms as $t_k => $t_v)
                    {
                        $sel = ($t_v->slug === $current_selected)? "selected": "";
                        echo "<option value=\"".$t_v->slug."\" $sel >".$t_v->name."</option>";
                    }
                ?>
            </select>
            <?php
        }
    
        /**
         * Save the meta when the post is saved.
         *
         * @param int $post_id The ID of the post being saved.
         */
        public function pc_save( $post_id )
        {
            /*
             * We need to verify this came from the our screen and with proper authorization,
             * because save_post can be triggered at other times.
             */
        
            // Check if our nonce is set.
            if ( ! isset( $_POST['pc_inner_custom_box_nonce'] ) )
            {
                return $post_id;
            }
        
            $nonce = $_POST['pc_inner_custom_box_nonce'];
        
            // Verify that the nonce is valid.
            if ( ! wp_verify_nonce( $nonce, 'pc_inner_custom_box' ) )
            {
                return $post_id;
            }
        
            /*
             * If this is an autosave, our form has not been submitted,
             * so we don't want to do anything.
             */
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
            {
                return $post_id;
            }
        
            // Check the user's permissions.
            if ( 'page' == $_POST['post_type'] ) {
                if ( ! current_user_can( 'edit_page', $post_id ) )
                {
                    return $post_id;
                }
            } else {
                if ( ! current_user_can( 'edit_post', $post_id ) )
                {
                    return $post_id;
                }
            }
        
            /* OK, it's safe for us to save the data now. */
        
            // Sanitize the user input.
            $mydata = sanitize_text_field( $_POST['pc_field'] );
        
            // Update the meta field.
            update_post_meta( $post_id, '_pc_primary_category', $mydata );
        }
    
        /**
         * Shortcode display
         *
         * @param $atts
         *
         * @return string
         */
        public function pc_display( $atts ) {
        
            // Set valid shortcode attributes
            $a = shortcode_atts( array(), $atts );
            $postype = 'post';
            
            global $post;
            $ret = "";
    
            // Retrieve data from primary_category custom field
            $current_selected = get_post_meta( $post->ID, '_pc_primary_category', true );
    
            $idObj = get_category_by_slug( $current_selected);
    
            if ( $idObj instanceof WP_Term )
            {
    
                // Set query args to display any post type with a primary category set to name attribute from shortcode
                $pc_query_args = array(
                    'post_type'     => $postype,
                    'meta_key'      => '_pc_primary_category',
                    'meta_value'    => $current_selected
                );
    
                $ret = '';
    
                // Create custom query
                $pc_query = new WP_Query( $pc_query_args );
    
                // Loop through posts returned by query
                if( $pc_query->have_posts() )
                {
                    $ret .= '<ul>';
                    while ( $pc_query->have_posts() )
                    {
                        $pc_query->the_post();
                        $ret .= '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
                    }
                    $ret .= '</ul>';
                } else {
                    $ret .= __("Sorry, there are no posts or custom posts with that primary category.",'primary-cat');
                }
            }
        
            return $ret;
        
            // reset postdata
            wp_reset_postdata();
        
        }
        
    }