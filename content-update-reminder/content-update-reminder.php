<?php
/*
 * Plugin Name: Content Update Reminder
 * Description: Notifica al administrador sobre posts que necesitan actualización según un umbral de tiempo configurable.
 * Version: 1.0
 * Author: HugoNex
 * License: GPL2
 */

class ContentUpdateReminder {
    private $option_name = 'cur_settings';

    // Iniciar el plugin
    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_notices', array($this, 'show_update_notice'));
    }

    // Registrar las opciones del plugin
    public function register_settings() {
        register_setting('cur_options_group', $this->option_name, array(
            'sanitize_callback' => array($this, 'sanitize_settings')
        ));
        add_settings_section('cur_main_section', 'Main settings', null, 'content-update-reminder');
        add_settings_field('cur_threshold', 'Time threshold (months)', array($this, 'threshold_field_callback'), 'content-update-reminder', 'cur_main_section');
    }

    // Sanitizar las opciones
    public function sanitize_settings($input) {
        $input['threshold'] = absint($input['threshold']) ?: 6; // Por defecto 6 meses si no es válido
        return $input;
    }

    // Campo de configuración del umbral
    public function threshold_field_callback() {
        $options = get_option($this->option_name, array('threshold' => 6));
        $threshold = $options['threshold'];
        echo '<input type="number" name="' . $this->option_name . '[threshold]" value="' . esc_attr($threshold) . '" min="1" />';
        echo '<p class="description">Number of months after which a post is considered "old".</p>';
    }

    // Añadir página de configuración al menú
    public function add_settings_page() {
        add_options_page('Content Update Reminder', 'Update Reminder', 'manage_options', 'content-update-reminder', array($this, 'render_settings_page'));
    }

    // Renderizar la página de configuración
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('cur_options_group');
                do_settings_sections('content-update-reminder');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    // Mostrar notificación en el dashboard
    public function show_update_notice() {
        if (!current_user_can('manage_options')) return;

        $options = get_option($this->option_name, array('threshold' => 6));
        $threshold_months = $options['threshold'];
        $threshold_date = date('Y-m-d H:i:s', strtotime("-$threshold_months months"));

        $args = array(
            'post_type' => 'post',
            'posts_per_page' => 5, // Limitar a 5 posts en la notificación
            'date_query' => array(
                array(
                    'column' => 'post_modified',
                    'before' => $threshold_date,
                ),
            ),
            'post_status' => 'publish',
        );

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p><strong>Content Update Reminder:</strong> The following posts, are not updated The following posts have not been updated in more than <?php echo esc_html($threshold_months); ?> months:</p>
                <ul>
                    <?php
                    while ($query->have_posts()) {
                        $query->the_post();
                        $edit_link = get_edit_post_link();
                        echo '<li><a href="' . esc_url($edit_link) . '">' . esc_html(get_the_title()) . '</a> (Última actualización: ' . esc_html(get_the_modified_date()) . ')</li>';
                    }
                    ?>
                </ul>
                <p><a href="<?php echo admin_url('edit.php?post_status=publish&post_type=post'); ?>">All posts</a></p>
            </div>
            <?php
            wp_reset_postdata();
        }
    }
}

// Iniciar el plugin
new ContentUpdateReminder();