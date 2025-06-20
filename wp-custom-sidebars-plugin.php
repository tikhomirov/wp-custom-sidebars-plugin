<?php
/**
 * Plugin Name: Custom Classic Sidebars
 * Plugin URI:  https://rwsite.ru
 * Description: Create custom dynamic sidebars and use anywhere with shortcodes. How to use: <code>[custom_sidebars id=""]</code>
 * Version:     1.0.2
 * Author:      Alex T
 * Author URI:  https://rwsite.ru
 *
 * Requires PHP: 7.4
 * Recommend PHP: 8.3
 *
 * Text Domain: wp-custom-sidebars-plugin
 * Domain Path: /languages
 *
 */

if (!defined('ABSPATH')) {
    exit;
}

final class CustomSidebars
{

    /**
     * @var CustomSidebars The single instance of the class
     */
    protected static $_instance = null;

    public string $version = '1.0';

    public ?string $plugin_url = null;

    public $stored;

    public array $sidebars = [];

    protected string $title;

    protected function __construct()
    {
        $this->stored = 'custom_sidebars';

        // Load plugin text domain
        add_action('init', [$this, 'load_plugin_textdomain']);

        add_action('admin_footer', [$this, 'template_custom_widget_area'], 200);
        add_action('load-widgets.php', [$this, 'load_scripts_styles'], 5);

        add_action('widgets_init', [$this, 'register_custom_sidebars'], 1000);
        add_action('wp_ajax_stag_ajax_delete_custom_sidebar', [$this, 'delete_sidebar_area'], 1000);

        add_shortcode($this->stored, [$this, 'sidebar_shortcode']);

        add_filter('wie_unencoded_export_data', [$this, 'export_data']);
        add_filter('wie_import_results', [$this, 'reset_custom_key']);
        add_action('wie_import_data', [$this, 'before_wie_import']);

        add_action('customize_controls_print_scripts', [$this, 'customize_controls_print_scripts']);
    }

    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Internationalization.
     *
     * @return void
     */
    public function load_plugin_textdomain()
    {
        load_plugin_textdomain('wp-custom-sidebars-plugin', false, dirname(plugin_basename(__FILE__)).'/languages/');
        $this->title = __('Custom Widget Area', 'wp-custom-sidebars-plugin');
    }

    /**
     * Register/queue scripts.
     *
     * @access public
     * @return void
     */
    public function load_scripts_styles()
    {

        add_action('load-widgets.php', [$this, 'add_sidebar_area'], 100);

        wp_enqueue_script('jquery');
        wp_enqueue_script('custom-sidebars', $this->plugin_url().'/custom-sidebars.js', ['jquery'], $this->version,
            true);

        wp_localize_script(
            'custom-sidebars',
            'objectL10n',
            [
                'shortcode'           => __('Shortcode', 'wp-custom-sidebars-plugin'),
                'delete_sidebar_area' => __('Are you sure you want to delete this sidebar?',
                    'wp-custom-sidebars-plugin'),
            ]
        );

        wp_enqueue_style('custom-sidebars', $this->plugin_url().'/custom-sidebars.css', '', $this->version, 'screen');
    }

    /**
     * Get the plugin url.
     *
     * @access public
     * @return string
     */
    public function plugin_url()
    {
        if (!empty($this->plugin_url)) {
            return $this->plugin_url;
        }
        return $this->plugin_url = untrailingslashit(plugins_url('/', __FILE__));
    }

    /**
     * Template for displaying the custom widget area add interface.
     *
     * @return void custom widget area field
     */
    public function template_custom_widget_area()
    {
        global $wp_version;

        ?>
        <script type="text/html" id="tmpl-add-widget">
            <div class="custom-sidebar-add wrap">
                <?php
                if (false === version_compare($wp_version, '3.7.9', '>')) : ?>
                    <div class="sidebar-name">
                        <h3><?php
                            echo $this->title; ?></h3>
                    </div>
                <?php
                endif; ?>

                <form class="add-widget" method="post">

                    <?php
                    if (true === version_compare($wp_version, '3.7.9', '>')) : ?>
                        <div class="sidebar-name">
                            <h3><?php echo $this->title; ?></h3>
                        </div>
                    <?php
                    endif; ?>

                    <input type="text" name="add-widget" value=""
                           placeholder="<?php _e('Enter name of the new widget area here',
                               'wp-custom-sidebars-plugin'); ?>" required/>

                    <?php submit_button(__('Add Widget Area', 'wp-custom-sidebars-plugin'), 'secondary large',
                        'custom-sidebar-submit'); ?>

                    <input type='hidden' name='scs-delete-nonce'
                           value="<?php echo wp_create_nonce('scs-delete-nonce'); ?>">
                </form>
            </div>
        </script>
        <?php
    }

    /**
     * Add Sidebar area.
     *
     * @return void
     */
    public function add_sidebar_area()
    {
        if (!empty($_POST['add-widget'])) {
            $this->sidebars = get_option($this->stored, []);
            $name = $this->get_name($_POST['add-widget']);

            $this->sidebars[sanitize_title_with_dashes($name)] = $name;
            update_option($this->stored, $this->sidebars);
            wp_redirect(admin_url('widgets.php'));
            die();
        }
    }

    /**
     * Check user entered widget area name and manage conflicts.
     *
     * @param  string  $name  User entered name
     * @return string Processed name
     */
    public function get_name($name)
    {

        if (empty($GLOBALS['wp_registered_sidebars'])) {
            return $name;
        }

        $taken = array();

        foreach ($GLOBALS['wp_registered_sidebars'] as $sidebar) {
            $taken[] = $sidebar['name'];
        }

        if (empty($this->sidebars)) {
            $this->sidebars = array();
        }
        $taken = array_merge($taken, $this->sidebars);

        if (in_array($name, $taken)) {
            $counter = substr($name, -1);
            $new_name = '';

            if (!is_numeric($counter)) {
                $new_name = $name.' 1';
            } else {
                $new_name = substr($name, 0, -1).((int) $counter + 1);
            }

            $name = $this->get_name($new_name);
        }

        return $name;
    }

    /**
     * Delete Sidebar area.
     *
     * @return void
     */
    public function delete_sidebar_area()
    {
        check_ajax_referer('scs-delete-nonce');

        if (!empty($_POST['name'])) {
            $name = sanitize_title_with_dashes(stripslashes($_POST['name']));
            $this->sidebars = get_option($this->stored, []);

            if (array_key_exists($name, $this->sidebars)) {
                unset($this->sidebars[$name]);
                update_option($this->stored, $this->sidebars);
                unregister_sidebar($name);
                echo 'sidebar-deleted';
            }
        }
        die();
    }

    /**
     * Shortcode handler.
     *
     * @param  array  $atts  Array of attributes
     * @return string $output returns the modified html string
     */
    public function sidebar_shortcode($atts)
    {

        $atts = shortcode_atts([
            'id'    => '1',
            'class' => '',
        ], $atts);

        $output = '';

        if (is_active_sidebar($atts['id']) && !is_admin()) {
            ob_start();

            do_action('custom_sidebars_before', $atts['id']);

            echo "<section id='".esc_attr($atts['id'])."' class='stag-custom-widget-area ".esc_attr($atts['class'])."'>";
            dynamic_sidebar($atts['id']);
            echo '</section>';

            do_action('custom_sidebars_after');

            $output = ob_get_clean();
        }

        return $output;
    }

    /**
     * Set a custom array key in export data.
     *
     * Inject all custom sidebar areas created on site under export data of "Widget Importer and Exporter".
     *
     * @param  array  $sidebars  An array containing sidebars' widget data.
     * @return array $sidebars Modified array, adds custom array key set during export.
     * @since 1.0.6
     * @uses Widget_Importer_Exporter
     * @link https://wordpress.org/plugins/widget-importer-exporter
     *
     */
    public function export_data($sidebars)
    {

        if (empty($this->sidebars)) {
            $this->sidebars = get_option($this->stored);
        }

        $sidebars['stag-custom-sidebars-areas'] = $this->sidebars;

        return $sidebars;
    }

    /**
     * Delete custom array key before 'Widget Importer & Exporter' import.
     *
     * @param  array  $results  An array containing sidebars' widget data.
     * @return array $results Modified array, deletes custom array key set during export.
     * @since 1.0.6
     * @uses Widget_Importer_Exporter
     * @link https://wordpress.org/plugins/widget-importer-exporter
     *
     */
    public function reset_custom_key($results)
    {
        unset($results['stag-custom-sidebars-areas']);
        return $results;
    }

    /**
     * Create new sidebar areas.
     *
     * Filter widget data before widgets import. Deletes the custom key set during widget file export.
     * Also register new custom widgets areas.
     *
     * @param  object  $data  Contains widget import data.
     * @return array  $data Modified widget import data.
     * @global $wp_registered_sidebars
     *
     */
    public function before_wie_import($data)
    {
        global $wp_registered_sidebars;

        $data = (array) $data;

        $key = 'stag-custom-sidebars-areas';
        $sidebars = get_option('custom_sidebars');
        $custom_sidebars = (array) $data[$key];

        unset($data[$key]);

        // Loop through each imported custom sidebar area and prepare it
        // to be added in new custom sidebar areas.
        foreach ($custom_sidebars as $sidebar_id => $title) {
            if (!isset($wp_registered_sidebars[$sidebar_id])) {
                $sidebars[$sidebar_id] = $title;
            }
        }

        update_option('custom_sidebars', $sidebars);

        $this->register_custom_sidebars();

        return $data;
    }

    /**
     * Register sidebars.
     *
     * @access public
     * @return void
     */
    public function register_custom_sidebars()
    {

        $sidebars = get_option($this->stored);

        $args = apply_filters(
            'custom_sidebars_widget_args',
            array(
                'before_widget' => '<aside id="%1$s" class="widget %2$s">',
                'after_widget'  => '</aside>',
                'before_title'  => '<h3 class="widgettitle">',
                'after_title'   => '</h3>',
            )
        );

        if (is_array($sidebars)) {
            foreach ($sidebars as $sidebar) {
                $args['name'] = $sidebar;

                $sidebar = sanitize_title_with_dashes($sidebar);

                $args['id'] = $sidebar;
                $args['class'] = 'stag-custom';

                register_sidebar(apply_filters('scs_widget_args_'.$sidebar, $args));
            }
        }
    }

    /**
     * Tweak style for Widget customizer.
     *
     * @return void
     * @since 1.0.7.
     */
    public function customize_controls_print_scripts()
    {
        $sidebars = get_option('custom_sidebars');

        if (false === ($sidebars)) {
            return;
        }

        // Get custom sidebar keys.
        $sidebars = array_keys($sidebars);

        if (!is_array($sidebars)) {
            return;
        }

        echo "<style>\n";
        foreach ($sidebars as $sidebar_id) :
            echo '#accordion-section-sidebar-widgets-'.esc_attr($sidebar_id)." { display: list-item !important; height: auto !important; }\n";
            echo '#accordion-section-sidebar-widgets-'.esc_attr($sidebar_id)." .widget-top { opacity: 1 !important; }\n";
        endforeach;
        echo "</style>\n";
    }
}

/**
 * Returns the main instance of SCS to prevent the need to use globals.
 * @return CustomSidebars|null
 */
function custom_sidebars(): ?CustomSidebars
{
    return $GLOBALS['custom_sidebars'] = CustomSidebars::instance();
}

custom_sidebars();