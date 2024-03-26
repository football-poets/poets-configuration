<?php
/**
 * Football Poets Configuration
 *
 * Plugin Name: Football Poets Configuration
 * Description: Configures the Football Poets site.
 * Plugin URI:  https://github.com/football-poets/poets-configuration
 * Version:     0.2.2
 * Author:      Christian Wach
 * Author URI:  https://haystack.co.uk
 * Text Domain: poets-configuration
 * Domain Path: /languages
 *
 * @package Poets_Configuration
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Set our version here.
define( 'POETS_CONFIGURATION_VERSION', '0.2.2' );

// Store reference to this file.
if ( ! defined( 'POETS_CONFIGURATION_FILE' ) ) {
	define( 'POETS_CONFIGURATION_FILE', __FILE__ );
}

// Store URL to this plugin's directory.
if ( ! defined( 'POETS_CONFIGURATION_URL' ) ) {
	define( 'POETS_CONFIGURATION_URL', plugin_dir_url( POETS_CONFIGURATION_FILE ) );
}

// Store PATH to this plugin's directory.
if ( ! defined( 'POETS_CONFIGURATION_PATH' ) ) {
	define( 'POETS_CONFIGURATION_PATH', plugin_dir_path( POETS_CONFIGURATION_FILE ) );
}

/**
 * Football Poets Configuration Plugin Class.
 *
 * A class that encapsulates plugin functionality.
 *
 * @since 0.1
 */
class Poets_Configuration {

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	public function __construct() {

		// Include files.
		$this->include_files();

		// Setup globals.
		$this->setup_globals();

		// Register hooks.
		$this->register_hooks();

	}

	/**
	 * Include files.
	 *
	 * @since 0.1
	 */
	public function include_files() {

	}

	/**
	 * Set up objects.
	 *
	 * @since 0.1
	 */
	public function setup_globals() {

	}

	/**
	 * Register WordPress hooks.
	 *
	 * @since 0.1
	 */
	public function register_hooks() {

		// Always use translation.
		add_action( 'plugins_loaded', [ $this, 'translation' ] );

		/*
		// Grant 'edit_theme_options' capability to editor role.
		add_filter( 'user_has_cap', [ $this, 'grant_edit_theme_options' ] );
		*/

		/*
		 * Disable BuddyPress emails and use WordPress instead.
		 *
		 * We do this because the WP Better Emails plugin styles all emails, not
		 * just those originating from BuddyPress.
		 */
		add_filter( 'bp_email_use_wp_mail', '__return_true' );

		/*
		 * Amends the BuddyPress dropdown in the WordPress admin bar. The top-level
		 * items point to "Profile -> Edit" by default, but this seems kind of
		 * unintuitive, so point them to Member Home instead.
		 */
		add_action( 'wp_before_admin_bar_render', [ $this, 'admin_bar_buddypress' ], 1000 );

		// Remove BuddyPress Member Search.
		add_filter( 'bp_search_form_type_select_options', [ $this, 'search_form_options' ], 40, 1 );

		// Add link to password recovery page.
		add_action( 'bp_login_widget_form', [ $this, 'login_password_link' ], 20 );

		// Add hidden input to login form.
		add_action( 'bp_login_widget_form', [ $this, 'login_form' ], 100 );

		// Redirect to calling page after login.
		add_filter( 'login_redirect', [ $this, 'login_redirect' ], 20, 3 );

		// Tweak the admin bar for standard Users.
		add_action( 'wp_before_admin_bar_render', [ $this, 'admin_bar_tweaks' ], 1000 );

		// Add items to main menu.
		add_filter( 'wp_get_nav_menu_items', [ $this, 'menu_add_items' ], 10, 3 );

		// Filter menu based on membership.
		add_action( 'wp_nav_menu_objects', [ $this, 'menu_filter_items' ], 20, 2 );

		// Add User to main site when approved.
		add_action( 'bpro_hook_approved_user', [ $this, 'user_add_to_site' ], 10, 1 );

	}

	/**
	 * Load translation if present.
	 *
	 * @since 0.1
	 */
	public function translation() {

		// Allow translations to be added.
		// phpcs:ignore WordPress.WP.DeprecatedParameters.Load_plugin_textdomainParam2Found
		load_plugin_textdomain(
			'poets-configuration', // Unique name.
			false, // Deprecated argument.
			dirname( plugin_basename( POETS_CONFIGURATION_FILE ) ) . '/languages/'
		);

	}

	/**
	 * Perform plugin activation tasks.
	 *
	 * @since 0.1
	 */
	public function activate() {

	}

	/**
	 * Perform plugin deactivation tasks.
	 *
	 * @since 0.1
	 */
	public function deactivate() {

	}

	/**
	 * Grant "edit_theme_options" capability to Editors.
	 *
	 * This lifts the restriction on Editors such that they are able to access
	 * the Widgets and Menus admin pages.
	 *
	 * @since 0.1
	 *
	 * @param array $caps The existing capabilities.
	 * @return array $caps The modified capabilities.
	 */
	public function grant_edit_theme_options( $caps ) {

		// Check if User has "edit_pages" capability.
		if ( ! empty( $caps['edit_pages'] ) ) {

			// Grant User "edit_theme_options" capability.
			$caps['edit_theme_options'] = true;

		}

		// --<
		return $caps;

	}

	/**
	 * Tweak the BuddyPress dropdown in the WordPress admin bar.
	 *
	 * @return void
	 */
	public function admin_bar_buddypress() {

		// Access object.
		global $wp_admin_bar;

		// Bail if not logged in.
		if ( ! is_user_logged_in() ) {
			return;
		}

		// Remove the WordPress logo menu.
		$wp_admin_bar->remove_menu( 'wp-logo' );

		// Target BuddyPress dropdown parent.
		$args = [
			'id'   => 'my-account',
			'href' => trailingslashit( bp_loggedin_user_domain() ),
		];
		$wp_admin_bar->add_node( $args );

		// Target BuddyPress dropdown User Info.
		$args = [
			'id'   => 'user-info',
			'href' => trailingslashit( bp_loggedin_user_domain() ),
		];
		$wp_admin_bar->add_node( $args );

	}

	/**
	 * Filters the options available in the search dropdown.
	 *
	 * @since 0.2
	 *
	 * @param array $options Existing array of options to add to select field.
	 * @return array $options Modified array of options to add to select field.
	 */
	public function search_form_options( $options ) {

		// Allow all when logged in.
		if ( is_user_logged_in() ) {
			return $options;
		}

		// Do we have Members?
		if ( array_key_exists( 'members', $options ) ) {
			unset( $options['members'] );
		}

		// Do we have Groups?
		if ( array_key_exists( 'groups', $options ) ) {
			unset( $options['groups'] );
		}

		// Do we have Blogs?
		if ( array_key_exists( 'blogs', $options ) ) {
			unset( $options['blogs'] );
		}

		// --<
		return $options;

	}

	/**
	 * Add a link to the password recovery page to the BuddyPress login widget.
	 *
	 * @since 0.2
	 */
	public function login_password_link() {

		// Get current URL.
		$url = wp_lostpassword_url();

		// Add link to password recovery page.
		echo '<span class="bp-login-widget-password-link">';
		echo '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Lost Password?', 'poets-configuration' ) . '</a>';
		echo '</span>';

	}

	/**
	 * Add hidden input to BuddyPress login widget.
	 *
	 * @since 0.2
	 */
	public function login_form() {

		// Get current URL.
		$url = esc_attr( home_url( add_query_arg( null, null ) ) );

		// Add hidden input with value of current page URL.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<input type="hidden" name="fp-current-page" id="fp-current-page" value="' . $url . '" />' . "\n\n";

		// Add a nonce field.
		wp_nonce_field( 'fp_current_page_action', 'fp_current_page_nonce' );

	}

	/**
	 * Redirect User after successful login.
	 *
	 * @since 0.2
	 *
	 * @param string $redirect_to URL to redirect to.
	 * @param string $request URL the User is coming from.
	 * @param object $user Logged-in User's data.
	 * @return string
	 */
	public function login_redirect( $redirect_to, $request, $user ) {

		// Bail if no User.
		if ( ! $user instanceof WP_User ) {
			return $redirect_to;
		}

		/*
		// Bail if super admin.
		if ( is_super_admin( $user->ID ) ) {
			return $redirect_to;
		}

		// Bail if not main site and User is site administrator.
		if ( ! is_main_site() && user_can( $user, 'manage_options' ) ) {
			return $redirect_to;
		}
		*/

		// Bail if no hidden input.
		if ( empty( $_REQUEST['fp-current-page'] ) ) {
			return $redirect_to;
		}

		// Do we trust the source of the data?
		check_admin_referer( 'fp_current_page_action', 'fp_current_page_nonce' );

		// Get the value of the hidden input.
		$redirect_to = sanitize_text_field( wp_unslash( $_REQUEST['fp-current-page'] ) );

		// Return to request URL.
		return $redirect_to;

	}

	/**
	 * Make changes to the WordPress admin bar.
	 *
	 * For Users other than editors and above, we remove all items except for
	 * the BuddyPress User dropdown.
	 *
	 * We also add quick links to:
	 *
	 * - Create a Primary Poet Profile for those who don't have one.
	 * - Publish a poem for those with a Primary Poet Profile.
	 *
	 * @since 0.1
	 */
	public function admin_bar_tweaks() {

		// Bail if not logged in.
		if ( ! is_user_logged_in() ) {
			return;
		}

		// Access admin bar and BuddyForms.
		global $wp_admin_bar, $buddyforms;

		// Don't show in WordPress admin.
		if ( ! is_admin() ) {

			// Get User object.
			$user = wp_get_current_user();

			// If the User is not held in the moderation queue.
			if ( ! $this->is_held_in_moderation( $user ) ) {

				// Get connections plugin.
				$pc = poets_connections();

				// Does this User have a Primary Poet?
				$primary_poet = $pc->config->get_primary_poet( $user->ID );

				// If they do have a Primary Poet.
				if ( $primary_poet instanceof WP_Post ) {

					// Get parent tab.
					$parent_tab = buddyforms_members_parent_tab( $buddyforms['poems'] );

					// Add "Publish a Poem" for those who don't have one.
					$args = [
						'id'     => 'fp-poem-new',
						'title'  => __( 'Publish a Poem', 'poets-configuration' ),
						'href'   => trailingslashit( bp_loggedin_user_domain() . $parent_tab . '/poems-create' ),
						'parent' => 'top-secondary',
					];
					$wp_admin_bar->add_node( $args );

				} else {

					// Add "Create Poet Profile" for those who don't have one.
					$args = [
						'id'     => 'fp-primary',
						'title'  => __( 'Create Your Poet Profile', 'poets-configuration' ),
						'href'   => trailingslashit( bp_loggedin_user_domain() . buddypress()->profile->slug . '/edit/group/2' ),
						'parent' => 'top-secondary',
					];
					$wp_admin_bar->add_node( $args );

				}

			}

		}

		// Allow super admins to see all menu items.
		if ( is_super_admin() ) {
			return;
		}

		// Allow editors to see all.
		if ( current_user_can( 'edit_others_posts' ) ) {
			return;
		}

		// Remove temporarily.
		$wp_admin_bar->remove_node( 'my-sites' );
		$wp_admin_bar->remove_node( 'blog-1' );
		$wp_admin_bar->remove_node( 'blog-1-d' );
		$wp_admin_bar->remove_node( 'site-name' );
		$wp_admin_bar->remove_node( 'new-content' );
		$wp_admin_bar->remove_node( 'new-media' );

		// Remove "Dashboard".
		$wp_admin_bar->remove_node( 'dashboard' );

	}

	/**
	 * Add items to the WordPress Menus backend.
	 *
	 * @since 0.1.3
	 *
	 * @param array  $items The array of menu items.
	 * @param object $menu The WordPress menu object.
	 * @param array  $args The additional arguments.
	 * @return array $items The modified array of menu items.
	 */
	public function menu_add_items( $items, $menu, $args ) {

		// Bail if not logged in.
		if ( ! is_user_logged_in() ) {
			return $items;
		}

		// Don't show in WordPress admin.
		if ( is_admin() ) {
			return $items;
		}

		// Bail if not main menu.
		if ( 'main-menu' !== $menu->slug ) {
			return $items;
		}

		// Get User object.
		$user = wp_get_current_user();

		// Bail if the User is held in the moderation queue.
		if ( $this->is_held_in_moderation( $user ) ) {
			return $items;
		}

		// Get connections plugin.
		$pc = poets_connections();

		// Does this User have a Primary Poet?
		$primary_poet = $pc->config->get_primary_poet( $user->ID );

		// Bail if they don't have a Primary Poet.
		if ( ! ( $primary_poet instanceof WP_Post ) ) {
			return $items;
		}

		// Access BuddyForms plugin.
		global $buddyforms;

		// Get parent tab.
		$parent_tab = buddyforms_members_parent_tab( $buddyforms['poems'] );

		// Init menu item.
		$placeholder = new stdClass();

		// Make child of "Poems".
		$placeholder->menu_item_parent = 132;

		// Add enough properties to satify WordPress and CommentPress.
		$placeholder->menu_order    = count( $items ) + 1;
		$placeholder->classes       = [ '' ];
		$placeholder->type          = 'custom';
		$placeholder->object        = 'custom';
		$placeholder->object_id     = 0;
		$placeholder->db_id         = 0;
		$placeholder->ID            = 0;
		$placeholder->comment_count = 0;
		$placeholder->post_parent   = 0;
		$placeholder->target        = '';
		$placeholder->xfn           = '';

		// Add title and link to "Add Poem".
		$placeholder->post_title = __( 'Publish a Poem', 'poets-configuration' );
		$placeholder->title      = $placeholder->post_title;
		$placeholder->url        = trailingslashit( bp_loggedin_user_domain() . $parent_tab . '/poems-create' );

		// Add to menu.
		$items[] = $placeholder;

		/*
		// Logging.
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'items' => $items,
			'menu' => $menu,
			'args' => $args,
			//'user_domain' => $user_domain,
			//'backtrace' => $trace,
		], true ) );
		*/

		// --<
		return $items;

	}

	/**
	 * Filter the main menu on the root site.
	 *
	 * @since 0.2
	 *
	 * @param array $sorted_menu_items The menu items, sorted by each menu item's menu order.
	 * @param array $args Array of wp_nav_menu() arguments.
	 * @return $sorted_menu_items The filtered menu items.
	 */
	public function menu_filter_items( $sorted_menu_items, $args ) {

		// Only on front end.
		if ( is_admin() ) {
			return $sorted_menu_items;
		}

		// Only on main blog.
		if ( ! is_main_site() ) {
			return $sorted_menu_items;
		}

		// Allow network admins.
		if ( is_super_admin() ) {
			return $sorted_menu_items;
		}

		// Allow logged-in folks.
		if ( is_user_logged_in() ) {
			return $sorted_menu_items;
		}

		// Remove items from array.
		$this->remove_item( $sorted_menu_items, 'post_type', '/members/' );
		$this->remove_item( $sorted_menu_items, 'post_type', '/members/' );
		$this->remove_item( $sorted_menu_items, 'post_type', '/activity/' );

		// --<
		return $sorted_menu_items;

	}

	/**
	 * Filter the main menu on the root site.
	 *
	 * @since 0.2
	 *
	 * @param array $sorted_menu_items The menu items, sorted by each menu item's menu order.
	 * @param str   $type The type of menu item we're looking for.
	 * @param array $url_snippet The slug we're looking for in the menu item's target URL.
	 */
	private function remove_item( &$sorted_menu_items, $type, $url_snippet ) {

		// Loop through them and get the menu item's key.
		foreach ( $sorted_menu_items as $key => $item ) {

			// Is it the item we're looking for?
			if ( $item->type === $type && false !== strpos( $item->url, $url_snippet ) ) {

				// Store found key.
				$found = $key;
				break;

			}

		}

		// Remove it if we find it.
		if ( isset( $found ) ) {
			unset( $sorted_menu_items[ $found ] );
		}

	}

	/**
	 * Add a User to the main site when they are approved.
	 *
	 * @since 0.2
	 *
	 * @param int $user_id The approved User ID.
	 */
	public function user_add_to_site( $user_id ) {

		// If not already a Member.
		if ( ! is_user_member_of_blog( $user_id, 1 ) ) {

			// Add User to main site as subscriber.
			add_user_to_blog( 1, $user_id, 'subscriber' );

		}

	}

	/**
	 * Checks if a logged-in User has been approved.
	 *
	 * @since 0.2.1
	 *
	 * @param WP_User $user The logged-in User record to check.
	 * @return bool True if held in moderation queue, false otherwise.
	 */
	public function is_held_in_moderation( $user ) {

		// Bail if not moderating Members.
		$moderate = get_option( 'bprwg_moderate' );
		if ( ! $moderate ) {
			return false;
		}

		// Bail if this User is not held in moderation.
		if ( ! bp_registration_get_moderation_status( $user->ID ) ) {
			return false;
		}

		// --<
		return true;

	}

}

/**
 * Plugin reference getter.
 *
 * @since 0.1
 *
 * @return obj $poets_configuration The plugin object.
 */
function poets_configuration() {
	global $poets_configuration;
	return $poets_configuration;
}

// Instantiate the class.
global $poets_configuration;
$poets_configuration = new Poets_Configuration();

// Activation.
register_activation_hook( __FILE__, [ $poets_configuration, 'activate' ] );

// Deactivation.
register_deactivation_hook( __FILE__, [ $poets_configuration, 'deactivate' ] );
