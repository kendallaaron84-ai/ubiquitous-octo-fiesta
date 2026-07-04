<?php
/**
 * Plugin Name: KOBA-I Audio - Jubilee Edition
 * Version: 5.0.0 (Agnostic SaaS Release)
 * Description: Tier-1 Audiobook & Video Player with E-Reader Cloud Studio. Universal Agnostic Embed.
 * Author: Kendall Aaron
 * Text Domain: Jubilee Works
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. CONSTANTS
define( 'KOBA_IA_PATH', plugin_dir_path( __FILE__ ) );
define( 'KOBA_IA_URL', plugin_dir_url( __FILE__ ) );

// 2. AUTO-UPDATER
require_once KOBA_IA_PATH . 'includes/updater.php';
if ( class_exists( 'KobaAudioUpdater' ) ) {
    $updater = new KobaAudioUpdater( __FILE__ );
    $updater->set_username( 'koba-i' );
    $updater->set_repository( 'https://audio.koba-i.com/updates/info.json' );
    $updater->initialize();
}

if ( file_exists( KOBA_IA_PATH . 'vendor/autoload.php' ) ) {
    require_once KOBA_IA_PATH . 'vendor/autoload.php';
}

// 3. LOAD MODULES (Stripped down to pure essentials)
$modules = [
    'includes/security.php',
    'includes/shortcodes-v2.php',
];
foreach ($modules as $module) {
    if ( file_exists( KOBA_IA_PATH . $module ) ) require_once KOBA_IA_PATH . $module;
}

// 4. JUBILEE STUDIO GATEWAY SETTINGS
add_action('admin_menu', 'koba_register_license_page');
function koba_register_license_page() {
    add_menu_page('Jubilee Studio Gateway', 'Jubilee Gateway', 'manage_options', 'koba-license', 'koba_render_license_page', 'dashicons-cloud', 2);
}

function koba_render_license_page() {
    $status = get_option('koba_license_status', 'inactive');
    $key = get_option('koba_license_key', '');
    $domain = $_SERVER['HTTP_HOST'];
    
    // Dynamically route to local Command Center during dev, or live dashboard in production
    $dashboard_url = (strpos($domain, 'localhost') !== false || strpos($domain, 'local') !== false) 
        ? 'http://localhost:3000' 
        : 'https://dashboard.koba-i.com';

    echo '<div class="wrap" style="max-width: 600px; margin-top: 40px; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border-top: 5px solid #f97316;">';
    
    // Header with Command Center Link
    echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">';
    echo '<h2 style="margin: 0;">KOBA-I SaaS Gateway</h2>';
    if ($status === 'active') {
        echo '<a href="' . esc_url($dashboard_url) . '" target="_blank" class="button button-primary" style="background: #0f172a; border-color: #0f172a;">Launch Command Center ↗</a>';
    }
    echo '</div>';
    
    if ($status === 'active') {
        echo '<div style="background: #ecfdf5; color: #065f46; padding: 15px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #a7f3d0;">✅ <strong>Connected to KOBA-I Cloud.</strong><br>Domain Locked: ' . esc_html($domain) . '</div>';
        
        // 🚀 The StudioKey Reveal & Copy UI
        echo '<div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 20px; border-radius: 8px; margin-bottom: 20px;">';
        echo '<h4 style="margin-top:0; font-size: 15px; color: #0f172a;">Your WPStudioKey (Tenant ID)</h4>';
        echo '<p style="font-size: 13px; color: #64748b;">This key uniquely identifies your product catalog. Use this key to embed your audiobooks on other platforms like Shopify, Wix, or custom HTML sites.</p>';
        echo '<div style="display:flex; gap: 10px;">';
        
        // Password field hides it by default
        echo '<input type="password" id="koba-studio-key-display" value="' . esc_attr($key) . '" readonly style="width: 100%; padding: 10px; background: #e2e8f0; border: 1px solid #cbd5e1; border-radius: 6px; color: #334155; font-family: monospace; font-size: 14px;">';
        
        // Toggle Visibility Script
        echo '<button type="button" class="button" style="padding: 0 15px;" onclick="const k = document.getElementById(\'koba-studio-key-display\'); if(k.type === \'password\'){ k.type = \'text\'; this.innerText = \'Hide\'; } else { k.type = \'password\'; this.innerText = \'Reveal\'; }">Reveal</button>';
        
        // Copy to Clipboard Script
        echo '<button type="button" class="button button-secondary" style="padding: 0 15px;" onclick="navigator.clipboard.writeText(document.getElementById(\'koba-studio-key-display\').value).then(() => alert(\'✅ StudioKey copied to clipboard! You can now paste this into your Shopify or Wix embed.\'));">Copy</button>';
        
        echo '</div>';
        echo '</div>';

        // Disconnect Option
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" onsubmit="return confirm(\'Are you sure you want to disconnect? Your media players will stop working on this domain.\');">';
        echo '<input type="hidden" name="action" value="koba_deactivate_license">';
        echo '<button type="submit" style="color: #ef4444; background: none; border: none; text-decoration: underline; cursor: pointer; font-size: 13px; padding: 0;">Disconnect Domain</button>';
        echo '</form>';

    } else {
        echo '<p style="color: #475569; font-size: 14px;">Please enter your Jubilee Studio <strong>WPStudioKey</strong> to connect this domain to your cloud dashboard. You can find this key in your Command Center.</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="koba_activate_license">';
        echo '<input type="text" name="koba_key" placeholder="JUBI-XXXX-XXXX-XXXX" style="width: 100%; padding: 12px; margin-bottom: 20px; font-family: monospace; border-radius: 6px; border: 1px solid #cbd5e1;" required>';
        echo '<button type="submit" class="button button-primary button-large" style="width: 100%; background: #f97316; border-color: #ea580c; font-weight: bold; font-size: 16px; padding: 10px 0; height: auto;">Connect & Authenticate</button>';
        echo '</form>';
    }
    echo '</div>';
}

// Add the deactivation handler just below your activation handler
add_action('admin_post_koba_deactivate_license', 'koba_process_license_deactivation');
function koba_process_license_deactivation() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized attempt.');
    delete_option('koba_license_key');
    update_option('koba_license_status', 'inactive');
    wp_redirect(admin_url('admin.php?page=koba-license&disconnected=true'));
    exit;
}

add_action('admin_post_koba_activate_license', 'koba_process_license_activation');
function koba_process_license_activation() {
    if (!current_user_can('manage_options') || empty($_POST['koba_key'])) {
        wp_die('Unauthorized attempt.');
    }
    $key = sanitize_text_field($_POST['koba_key']);
    update_option('koba_license_key', $key);
    update_option('koba_license_status', 'active');
    wp_redirect(admin_url('admin.php?page=koba-license&success=true'));
    exit;
}

add_action('admin_notices', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'koba-license' && isset($_GET['success']) && $_GET['success'] === 'true') {
        echo '<div class="notice notice-success is-dismissible"><p>🎉 Gateway Activated! You can now deploy the <strong>[koba_media_player asset="your_asset_id"]</strong> shortcode anywhere on your site.</p></div>';
    }
});