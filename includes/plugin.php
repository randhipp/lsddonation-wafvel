<?php
namespace LSDDonation\Wablas;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class Plugin
{
    /**
     * Loads the plugin into WordPress.
     */
    public static function load()
    {
        $wablas = new self();

        // Bind to Init
        add_action('plugins_loaded', [$wablas, 'loaded']);

        register_activation_hook(LSDD_WABLAS_BASE, [$wablas, 'activation']);
        register_deactivation_hook(LSDD_WABLAS_BASE, [$wablas, 'uninstall']);
    }

    /**
     * Plugin Loaded
     *
     * @return void
     */
    public function loaded()
    {
        load_plugin_textdomain('lsddonation-wablas', false, LSDD_WABLAS_PATH . '/languages/');

        // Includes Notification Method
        require_once LSDD_WABLAS_PATH . 'includes/class-notification-wablas.php';
    }

    /**
     * Load Class Activator on Plugin Active
     *
     * @return void
     * @since 1.0.3
     */
    public function activation()
    {
        require_once LSDD_WABLAS_PATH . 'core/common/class-activator.php';
        Activator::activate();
    }

    /**
     * Load Class Deactivator on Plugin Deactivate
     *
     * @return void
     * @since 1.0.3
     */
    public function uninstall()
    {
        require_once LSDD_WABLAS_PATH . 'core/common/class-deactivator.php';
        Deactivator::deactivate();
    }

    /**
     * Clone.
     *
     * Disable class cloning and throw an error on object clone.
     *
     * The whole idea of the singleton design pattern is that there is a single
     * object. Therefore, we don't want the object to be cloned.
     *
     * @access public
     * @since 1.0.0
     */
    public function __clone()
    {
        // Cloning instances of the class is forbidden.
        _doing_it_wrong(__FUNCTION__, esc_html__('Something went wrong.', 'lsddonation-wablas'), LSDD_WABLAS_VERSION);
    }

    /**
     * Wakeup.
     *
     * Disable unserializing of the class.
     *
     * @access public
     * @since 1.0.0
     */
    public function __wakeup()
    {
        // Unserializing instances of the class is forbidden.
        _doing_it_wrong(__FUNCTION__, esc_html__('Something went wrong.', 'lsddonation-wablas'), LSDD_WABLAS_VERSION);
    }
}

Plugin::load();
