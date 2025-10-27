<?php
/**
 * Plugin Name: Widget Company Directory
 * Description: A simple directory of companies + curated lists, with a Gutenberg block to display a selected list on the frontend.
 * Version: 1.0.0
 * Author: Silvia Chen
 * Text Domain: widget-company-directory
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 1) Define constants
 *    - PLUGIN_DIR: absolute path to this plugin
 *    - PLUGIN_URL: URL to assets (for enqueueing)
 */
define( 'WIDGET_COMPANY_DIRECTORY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WIDGET_COMPANY_DIRECTORY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * 2) Includes
 *    Keep the logic split into small classes by domain:
 *    - Company: registers CPT + meta, importer helpers
 *    - Admin: admin pages & sortable UI for company lists
 *    - Render: dynamic block server-side render
 */
require_once WIDGET_COMPANY_DIRECTORY_PLUGIN_DIR . 'includes/class-company.php';
require_once WIDGET_COMPANY_DIRECTORY_PLUGIN_DIR . 'admin/class-admin.php';
require_once WIDGET_COMPANY_DIRECTORY_PLUGIN_DIR . 'public/class-render.php';

/**
 * 3) Bootstrap on plugins_loaded
 *    Use singletons or simple static init methods to register hooks.
 */
add_action( 'plugins_loaded', function() {
    \WCD\Company::init();
    \WCD\Admin::init();
    \WCD\Render::init();
} );
