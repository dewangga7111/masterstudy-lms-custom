<?php
/**
 * Plugin Name: MasterStudy LMS Custom
 * Description: Custom additions for MasterStudy LMS — safe from plugin updates.
 * Version: 1.0.0
 * Author: Custom
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'MSLC_PLUGIN_FILE', __FILE__ );
define( 'MSLC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Core
require_once MSLC_PLUGIN_DIR . 'includes/core/activation.php';
require_once MSLC_PLUGIN_DIR . 'includes/core/course-field.php';

// Admin
require_once MSLC_PLUGIN_DIR . 'includes/admin/careers-menu.php';
require_once MSLC_PLUGIN_DIR . 'includes/admin/careers.php';
require_once MSLC_PLUGIN_DIR . 'includes/admin/careers-form.php';
require_once MSLC_PLUGIN_DIR . 'includes/admin/careers-categories.php';

// Frontend
require_once MSLC_PLUGIN_DIR . 'includes/frontend/careers-page.php';
require_once MSLC_PLUGIN_DIR . 'includes/frontend/careers-detail-page.php';

// Open edX Integration
require_once MSLC_PLUGIN_DIR . 'includes/api/courses-client.php';
require_once MSLC_PLUGIN_DIR . 'includes/admin/courses-menu.php';

require_once MSLC_PLUGIN_DIR . 'includes/admin/courses.php';
require_once MSLC_PLUGIN_DIR . 'includes/frontend/courses-catalog.php';
