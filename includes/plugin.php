<?php
namespace LSDDonation\Wafvel;

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
        $wafvel = new self();

        // Bind to Init
        add_action('plugins_loaded', [$wafvel, 'loaded']);

        register_activation_hook(LSDD_WAFVEL_BASE, [$wafvel, 'activation']);
        register_deactivation_hook(LSDD_WAFVEL_BASE, [$wafvel, 'uninstall']);
    }

    /**
     * Plugin Loaded
     *
     * @return void
     */
    public function loaded()
    {
        load_plugin_textdomain('lsddonation-wafvel', false, LSDD_WAFVEL_PATH . '/languages/');

        // Includes Notification Method
        require_once LSDD_WAFVEL_PATH . 'includes/class-notification-wafvel.php';
    }

    /**
     * Load Class Activator on Plugin Active
     *
     * @return void
     * @since 1.0.3
     */
    public function activation()
    {
        require_once LSDD_WAFVEL_PATH . 'core/common/class-activator.php';
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
        require_once LSDD_WAFVEL_PATH . 'core/common/class-deactivator.php';
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
        _doing_it_wrong(__FUNCTION__, esc_html__('Something went wrong.', 'lsddonation-wafvel'), LSDD_WAFVEL_VERSION);
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
        _doing_it_wrong(__FUNCTION__, esc_html__('Something went wrong.', 'lsddonation-wafvel'), LSDD_WAFVEL_VERSION);
    }
}

Plugin::load();
