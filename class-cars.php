<?php
/**
* Main cars class file
*
* @package Cars
* @author Mandela Effiong
* @license GPL2
* @copyright 2017 Mandela Effiong
*/
class SomeSpiderCars {
	protected static $version = '0.0.1';
	
	protected static $plugin_slug = 'cars';
	
	protected static $instance = null;


	// Class Methods	
	private function __construct() {
		add_action( 'init', array( $this, 'register_car_post_types' ), 100 );
		add_action( 'add_meta_boxes', array( $this, 'ss_create_meta_boxes' ), 100 );
		
		add_action('init', array( $this, 'ss_register_taxonomies'), 100 );
		add_action ('save_post', array( $this, 'ss_save_post_meta'), 100 );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts'), 100 );

		add_action('wp_ajax_do_filter_posts', array( $this, 'vb_filter_posts'), 100 );
		add_action('wp_ajax_nopriv_do_filter_posts', array( $this, 'vb_filter_posts'), 100 );

	}

	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
		
	}



	function register_scripts(){
		wp_enqueue_script( 'main', plugin_dir_url( __FILE__ ) . 'js/main.js', ['jquery'], null, true );

		wp_localize_script( 'main', 'bobz', array(
	        'nonce'    => wp_create_nonce( 'bobz' ),
	        'ajax_url' => admin_url( 'admin-ajax.php' )
	    ));
	}



	/**
	 * AJAC filter posts by taxonomy term
	 */
	function vb_filter_posts() {

	    /**
	     * Default response
	     */
	    $response = [
	        'status'  => 500,
	        'message' => 'Something is wrong, please try again later ...',
	        'content' => false,
	        'found'   => 0
	    ];

	    $tax  = sanitize_text_field($_POST['params']['tax']);
	    $term = sanitize_text_field($_POST['params']['term']);
	    $page = intval($_POST['params']['page']);
	    $qty  = intval($_POST['params']['qty']);

	    /**
	     * Check if term exists
	     */
	    if (!term_exists( $term, $tax) && $term != 'all-terms') :
	        $response = [
	            'status'  => 501,
	            'message' => 'Term doesn\'t exist',
	            'content' => 0
	        ];

	        die(json_encode($response));
	    endif;

	    if ($term == 'all-terms') : 

	        $tax_qry[] = [
	            'taxonomy' => $tax,
	            'field'    => 'slug',
	            'terms'    => $term,
	            'operator' => 'NOT IN'
	        ];

	    else :

	        $tax_qry[] = [
	            'taxonomy' => $tax,
	            'field'    => 'slug',
	            'terms'    => $term,
	        ];

	    endif;

	    /**
	     * Setup query
	     */
	    $args = [
	        'paged'          => $page,
	        'post_type'      => 'cars',
	        'post_status'    => 'publish',
	        'posts_per_page' => $qty,
	        'tax_query'      => $tax_qry,
	        'order'               => 'DESC',
			'orderby'             => 'date',
	    ];

	    $qry = new WP_Query($args);

	    ob_start();
	        if ($qry->have_posts()) :
	            while ($qry->have_posts()) : $qry->the_post(); ?>

	                <article class="loop-item">
	                    <header>
	                        <h2 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
	                    </header>
	                    <div>
							<img src="<?php echo get_the_post_thumbnail_url(); ?>" alt="Car Image" style="width: 600px;">
							<p>Year:
								<?php echo get_post_meta( get_the_ID(), 'year', true ); ?>
							</p>
							<p>Location:
								<?php echo get_post_meta( get_the_ID(), 'location', true ); ?>
							</p>

							<p>Color
								<?php the_terms( get_the_ID(), 'color'); ?>
							</p>

							<?php setlocale(LC_MONETARY, 'en_US'); ?>
							<p>Price
								<?php echo money_format('%(#10n', get_post_meta( get_the_ID(), 'price', true ) ); ?>
							</p>
						</div>

	                    <div class="entry-summary">
	                        <?php the_excerpt(); ?>
	                    </div>
	                </article>

	            <?php endwhile;

	            /**
	             * Pagination
	             */
	            $this->vb_ajax_pager($qry,$page);

	            $response = [
	                'status'=> 200,
	                'found' => $qry->found_posts
	            ];

	            
	        else :

	            $response = [
	                'status'  => 201,
	                'message' => 'No posts found'
	            ];

	        endif;

	    $response['content'] = ob_get_clean();

	    die(json_encode($response));

	}



	/**
	 * Pagination
	 */
	function vb_ajax_pager( $query = null, $paged = 1 ) {

	    if (!$query)
	        return;

	    $paginate = paginate_links([
	        'base'      => '%_%',
	        'type'      => 'array',
	        'total'     => $query->max_num_pages,
	        'format'    => '#page=%#%',
	        'current'   => max( 1, $paged ),
	        'prev_text' => 'Prev',
	        'next_text' => 'Next'
	    ]);

	    if ($query->max_num_pages > 1) : ?>
	        <ul class="pagination">
	            <?php foreach ( $paginate as $page ) :?>
	                <li><?php echo $page; ?></li>
	            <?php endforeach; ?>
	        </ul>
	    <?php endif;
	}



	/**
	 * Registers a new post type
	 * @uses $wp_post_types Inserts new post type object into the list
	 *
	 * @param string  Post type key, must not exceed 20 characters
	 * @param array|string  See optional args description above.
	 * @return object|WP_Error the registered post type object, or an error object
	 */
	public function register_car_post_types() {
	
		$labels = array(
			'name'               => __( 'Cars', 'some_spider_cars' ),
			'singular_name'      => __( 'Car', 'some_spider_cars' ),
			'add_new'            => _x( 'Add New Car', 'some_spider_cars', 'some_spider_cars' ),
			'add_new_item'       => __( 'Add New Car', 'some_spider_cars' ),
			'edit_item'          => __( 'Edit Car', 'some_spider_cars' ),
			'new_item'           => __( 'New Car', 'some_spider_cars' ),
			'view_item'          => __( 'View Car', 'some_spider_cars' ),
			'search_items'       => __( 'Search Cars', 'some_spider_cars' ),
			'not_found'          => __( 'No Cars found', 'some_spider_cars' ),
			'not_found_in_trash' => __( 'No Cars found in Trash', 'some_spider_cars' ),
			'parent_item_colon'  => __( 'Parent Car:', 'some_spider_cars' ),
			'menu_name'          => __( 'Cars', 'some_spider_cars' ),
		);
	
		$args = array(
			'labels'              => $labels,
			'hierarchical'        => false,
			'description'         => 'A post type for creating and listing some spider car types',
			'taxonomies'          => array('location','color','price'),
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => null,
			'menu_icon'           => esc_url(  plugin_dir_url( __FILE__ ) . 'ss.png'  ),
			'show_in_nav_menus'   => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'has_archive'         => true,
			'query_var'           => true,
			'can_export'          => true,
			'rewrite'             => true,
			'capability_type'     => 'post',
			'supports'            => array(
				'title',
				'editor',
				'author',
				'thumbnail',
				'excerpt',
				'custom-fields',
				'trackbacks',
				'comments',
				'revisions',
				'page-attributes',
				'post-formats',
			),
		);
	
		register_post_type( 'cars', $args );
	}



	// Taxonomies

	/**
	 * Create a taxonomy
	 *
	 * @uses  Inserts new taxonomy object into the list
	 * @uses  Adds query vars
	 *
	 * @param string  Name of taxonomy object
	 * @param array|string  Name of the object type for the taxonomy object.
	 * @param array|string  Taxonomy arguments
	 * @return null|WP_Error WP_Error if errors, otherwise null.
	 */

	function ss_register_taxonomies() {
	    $taxs = array(
	            'color' => array(
	            'menu_title' => 'Colors',
	            'plural' => 'Colors',
	            'singular' => 'Color',
	            'hierarchical' => true,
	            'slug' => 'color',
	            'post_type' => 'cars'
	            ),
	    );

	    foreach( $taxs as $tax => $args ) {
	        $labels = array(
	            'name' => _x( $args['plural'], 'taxonomy general name' ),
	            'singular_name' => _x( $args['singular'], 'taxonomy singular name' ),
	            'search_items' => __( 'Search '.$args['plural'] ),
	            'all_items' => __( 'All '.$args['plural'] ),
	            'parent_item' => __( 'Parent '.$args['plural'] ),
	            'parent_item_colon' => __( 'Parent '.$args['singular'].':' ),
	            'edit_item' => __( 'Edit '.$args['singular'] ),
	            'update_item' => __( 'Update '.$args['singular'] ),
	            'add_new_item' => __( 'Add New '.$args['singular'] ),
	            'new_item_name' => __( 'New '.$args['singular'].' Name' ),
	            'menu_name' => __( $args['menu_title'] )
	        );

	        $tax_args = array(
	            'hierarchical' => $args['hierarchical'],
	            'labels' => $labels,
	            'public' => true,
	            'rewrite' => array( 'slug' => $args['slug'] ),
	        );

	        register_taxonomy( $tax, $args['post_type'], $tax_args );
	    }
	}


	/**
	 * Add meta boxes for cars type
	 *
	 */
	function ss_create_meta_boxes(){
		add_meta_box( 'cars', 'Some Spider Car Listings', array($this, 'ss_create_meta_box_content'), $screen = 'cars', $context = 'side', $priority = 'high', $callback_args = null );
	}


	/**
	 * Content for cars meta box
	 * 
	 */
	function ss_create_meta_box_content($post){
		wp_nonce_field( basename( __FILE__ ), 'cars_meta_box_nonce' );
		?>

		<form action="" method="post">
			<div class="ss_cars_listings">
				<label for="location">Location</label><input type="text" id="location" name="location" value="<?php echo get_post_meta($post->ID, 'location', true); ?>"><br />
				<label for="price">Price</label><input type="number" id="price" name="price" value="<?php echo get_post_meta($post->ID, 'price', true); ?>"><br />
				<label for="year">Year</label><input type="text" pattern="\d*" maxlength="4" id="year" name="year" value="<?php echo get_post_meta($post->ID, 'year', true); ?>" ><br />
			</div>
		</form>
		
	<?php }



	/**
	 * Save post meta for cars
	 * 
	 */
	function ss_save_post_meta( $post_id ) {
		// verify if this is an auto save routine.
		// If it is the post has not been updated, so we don't want to do anything
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		if ( !isset( $_POST['cars_meta_box_nonce'] ) || !wp_verify_nonce( $_POST['cars_meta_box_nonce'], basename( __FILE__ ) ) ) {
			return $post_id;
		}

		global $post;
		$post_type = get_post_type_object( $post->post_type );

		if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
			return $post_id;
		}

		// Get the posted data and pass it into an associative array for ease of entry
		$metadata['location'] = ( isset( $_POST['location'] ) ? $_POST['location'] : '');
		$metadata['year'] = ( isset( $_POST['year'] ) ? $_POST['year'] : '');
		$metadata['price'] = ( isset( $_POST['price'] ) ? $_POST['price'] : '');

		// add/update record (both are taken care of by update_post_meta)
		foreach( $metadata as $key => $value ) {
		// get current meta value
			$current_value = get_post_meta( $post_id, $key, true);
			if ( $value && '' == $current_value ) {
				add_post_meta( $post_id, $key, $value, true );
			} elseif ( $value && $value != $current_value ) {
				update_post_meta( $post_id, $key, $value );
			} elseif ( '' == $value && $current_value ) {
				delete_post_meta( $post_id, $key, $current_value );
			}
		}
	}


}