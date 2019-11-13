<?php

class PageTemplater {
	/**
	 * A reference to an instance of this class.
	 */
	private static $instance = null;
	/**
	 * The array of templates that this plugin tracks.
	 */
	protected $templates;
	/**
	 * Returns an instance of this class.
	 */
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new PageTemplater();
		}
		return self::$instance;
	}
	/**
	 * Initializes the plugin by setting filters and administration functions.
	 */
	private function __construct() {
		add_action ('init', array( $this, 'ss_add_shortcodes'), 90 );

		$this->templates = array();
		// Add a filter to the attributes metabox to inject template into the cache.
		if ( version_compare( floatval( get_bloginfo( 'version' ) ), '4.7', '<' ) ) {
			// 4.6 and older
			add_filter(
				'page_attributes_dropdown_pages_args',
				array( $this, 'register_project_templates' )
			);
		} else {
			// Add a filter to the wp 4.7 version attributes metabox
			add_filter(
				'theme_page_templates', array( $this, 'add_new_template' )
			);
		}
		// Add a filter to the save post to inject out template into the page cache
		add_filter(
			'wp_insert_post_data',
			array( $this, 'register_project_templates' )
		);
		// Add a filter to the template include to determine if the page has our
		// template assigned and return it's path
		add_filter(
			'template_include',
			array( $this, 'view_project_template')
		);
		// Add your templates to this array.
		$this->templates = array(
			'cars-template.php' => 'Some Spider Cars Listing Page',
		);



	}
	/**
	 * Adds our template to the page dropdown for v4.7+
	 *
	 */
	public function add_new_template( $posts_templates ) {
		$posts_templates = array_merge( $posts_templates, $this->templates );
		return $posts_templates;
	}
	/**
	 * Adds our template to the pages cache in order to trick WordPress
	 * into thinking the template file exists where it doens't really exist.
	 */
	public function register_project_templates( $atts ) {
		// Create the key used for the themes cache
		$cache_key = 'page_templates-' . md5( get_theme_root() . '/' . get_stylesheet() );
		// Retrieve the cache list.
		// If it doesn't exist, or it's empty prepare an array
		$templates = wp_get_theme()->get_page_templates();
		if ( empty( $templates ) ) {
			$templates = array();
		}
		// New cache, therefore remove the old one
		wp_cache_delete( $cache_key , 'themes');
		// Now add our template to the list of templates by merging our templates
		// with the existing templates array from the cache.
		$templates = array_merge( $templates, $this->templates );
		// Add the modified cache to allow WordPress to pick it up for listing
		// available templates
		wp_cache_add( $cache_key, $templates, 'themes', 1800 );
		return $atts;
	}
	/**
	 * Checks if the template is assigned to the page
	 */
	public function view_project_template( $template ) {
		// Return the search template if we're searching (instead of the template for the first result)
		if ( is_search() ) {
			return $template;
		}
		// Get global post
		global $post;
		// Return template if post is empty
		if ( ! $post ) {
			return $template;
		}
		// Return default template if we don't have a custom one defined
		if ( ! isset( $this->templates[get_post_meta(
			$post->ID, '_wp_page_template', true
		)] ) ) {
			return $template;
		}
		// Allows filtering of file path
		$filepath = apply_filters( 'page_templater_plugin_dir_path', plugin_dir_path( __FILE__ ) );
		$file =  $filepath . get_post_meta(
			$post->ID, '_wp_page_template', true
		);
		// Just to be safe, we check if the file exist first
		if ( file_exists( $file ) ) {
			return $file;
		} else {
			echo $file;
		}
		// Return template
		return $template;
	}


	// function ss_create_page(){
	// 	$title ='Some Spider Cars Listing Page';

	// 	if (!get_page_by_title($title, OBJECT, 'page')) :
	// 		$postarr = array(
	// 			'post_title'			=> $title,
	// 			'post_status'			=> 'publish',
	// 			'post_type'				=> 'page',
	// 		);

	// 		wp_insert_post( $postarr, $wp_error = false );
	// 	endif;
	// }


	/**
	 * Shortocde for displaying terms filter and results on page
	 */
	function vb_filter_posts_sc($atts) {

	    $a = shortcode_atts( array(
	        'tax'      => 'color', // Taxonomy
	        'terms'    => false, // Get specific taxonomy terms only
	        'active'   => false, // Set active term by ID
	        'per_page' => 12, // How many posts per page,
	        'pager'    => 'pager' // 'pager' to use numbered pagination || 'infscr' to use infinite scroll
	    ), $atts );

	    $result = NULL;
	    $terms  = get_terms(array('taxonomy' => $a['tax'], 'hide_empty' => false,) );

	    if (count($terms)) :
		        ob_start(); ?>
		            <div id="container-async" data-paged="<?php echo $a['per_page']; ?>" class="sc-ajax-filter">
		                <ul class="nav-filter">
		                    <li>
		                        <a href="#" data-filter="<?= $terms[0]->taxonomy; ?>" data-term="all-terms" data-page="1">
		                            Show All
		                        </a>
		                    </li>
		                    <?php foreach ($terms as $term) : ?>
		                        <li<?php if ($term->term_id == $a['active']) :?> class="active"<?php endif; ?>>
		                            <a href="<?php echo get_term_link( $term, $term->taxonomy ); ?>" data-filter="<?php echo $term->taxonomy; ?>" data-term="<?php echo $term->slug; ?>" data-page="1">
		                                <?php echo $term->name; ?>
		                            </a>
		                        </li>
		                    <?php endforeach; ?>
		                </ul>

		                <div class="status"></div>
		                <div class="content"></div>
		                
		                <?php if ( $a['pager'] == 'infscr' ) : ?>
					<nav class="pagination infscr-pager">
						<a href="#page-2" class="btn btn-primary">Load More</a>
					</nav>
				<?php endif; ?>
		            </div>
		        
		        <?php $result = ob_get_clean();
		    endif;

	    return $result;
	}

	function ss_add_shortcodes($atts){
		add_shortcode( 'cars_sc', array($this, 'vb_filter_posts_sc') );
	}

}
add_action( 'plugins_loaded', array( 'PageTemplater', 'get_instance' ) );
