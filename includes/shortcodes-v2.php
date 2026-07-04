<?php
/**
 * Jubilee Works: Universal Agnostic Shortcode Injector
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// Command Center routing logic (Helper)
if (!function_exists('koba_get_command_center_url')) {
    function koba_get_command_center_url() {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        // If testing locally, route back to the Next.js Bug-Free-Robot on port 3000
        if (strpos($host, 'localhost') !== false || strpos($host, 'local') !== false) {
            return "http://localhost:3000"; 
        }
        return 'https://dashboard.koba-i.com';
    }
}

function koba_universal_media_player_shortcode($atts) {
    // 1. SAFETY GATE: Editor View (Divi, Gutenberg, Elementor preview)
    if (is_admin() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return '<div style="padding: 15px; border: 2px dashed #f97316; background: #fff7ed; color: #c2410c; text-align: center; border-radius: 8px; font-weight: bold;">🎧 KOBA-I Cloud Player Mount Point<br><small>[koba_media_player]</small></div>';
    }

    // 2. TENANT AUTHENTICATION LOCK
    $status = get_option('koba_license_status');
    $studio_key = get_option('koba_license_key');
    
    if ($status !== 'active' || empty($studio_key)) {
        return '<div style="padding: 20px; background: #fee2e2; color: #991b1b; text-align: center; border-radius: 8px;"><strong>Cloud Engine Error:</strong> Domain not authenticated with a WPStudioKey.</div>';
    }

    // 3. PARSE ATTRIBUTES
    $args = shortcode_atts(array(
        'asset' => '', // e.g., abk_kendall_123
    ), $atts);

    if (empty($args['asset'])) {
        return '<div style="padding: 20px; background: #fee2e2; color: #991b1b; text-align: center; border-radius: 8px;"><strong>Configuration Error:</strong> Missing "asset" attribute. Example: [koba_media_player asset="abk_this_is_my_book"]</div>';
    }

    // 4. ENQUEUE REACT ASSETS (For 4GB device optimized performance)
    wp_enqueue_style('koba-bloom-css', KOBA_IA_URL . 'assets/bloom-style.css', [], time());
    wp_enqueue_script('jubilee-core-js', KOBA_IA_URL . 'assets/jubilee-core.js', [], time(), true);

    // 5. INJECT ROUTING CONFIGURATION FOR NEXT.JS BACKEND
    $dashboard_url = koba_get_command_center_url();
    wp_localize_script('jubilee-core-js', 'JubileeConfig', array(
        'commandCenterUrl' => $dashboard_url,
        'endpoints' => array(
            'publicProduct' => $dashboard_url . '/api/products/public',
            'checkout'      => $dashboard_url . '/api/checkout',
            'verifyAccess'  => $dashboard_url . '/api/verify-entitlement',
            'authSmsSend'   => $dashboard_url . '/api/auth/sms-send',
            'authSmsVerify' => $dashboard_url . '/api/auth/sms-verify'
        )
    ));

    // 6. RENDER THE AGNOSTIC DOM NODE
    // (This identical div structure can be pasted raw into Shopify Liquid or Wix Custom HTML)
    $asset_key = esc_attr($args['asset']);
    $safe_key = esc_attr($studio_key);
    
    $output = sprintf(
        '<div id="jubilee-bloom-root" 
              class="koba-agnostic-embed"
              data-asset="%s" 
              data-studio-key="%s" 
              style="min-height: 400px; width: 100%%; position: relative;">
            <div style="color: #64748b; text-align:center; padding:80px 20px; font-family: system-ui;">
                <div class="jubilee-spinner" style="width:30px; height:30px; border:3px solid #e2e8f0; border-top-color:#f97316; border-radius:50%%; display:inline-block; animation: spin 1s linear infinite;"></div>
                <div style="margin-top: 15px; font-weight: 500;">Connecting to Secure Cloud Engine...</div>
                <style>@keyframes spin { to { transform: rotate(360deg); } }</style>
            </div>
        </div>',
        $asset_key,
        $safe_key
    );

    return $output;
}

// Register the new Universal Shortcode
add_shortcode('koba_media_player', 'koba_universal_media_player_shortcode');