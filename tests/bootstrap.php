<?php
/**
 * Minimal WordPress stand-ins so the plugin's pure logic (scoring, tokens,
 * deck selection, settings sanitising, captcha config) can be unit tested
 * without booting WordPress. Only what the classes under test call is
 * stubbed; anything else missing is a test failure on purpose.
 */

define('ABSPATH', __DIR__ . '/');
define('HOUR_IN_SECONDS', 3600);
define('MINUTE_IN_SECONDS', 60);
define('PAIRSMG_VERSION', 'test');
define('PAIRSMG_FILE', __DIR__ . '/../pairs-memory-game.php');
define('PAIRSMG_DIR', __DIR__ . '/../');
define('PAIRSMG_URL', 'https://example.test/wp-content/plugins/pairs-memory-game/');

$GLOBALS['pairsmg_test_options'] = array();
$GLOBALS['pairsmg_test_transients'] = array();
$GLOBALS['pairsmg_test_filters'] = array();
$GLOBALS['pairsmg_test_remote'] = null;
$GLOBALS['pairsmg_test_actions'] = array();

function pairsmg_test_reset() {
    $GLOBALS['pairsmg_test_cache'] = array();
    $GLOBALS['pairsmg_test_options'] = array();
    $GLOBALS['pairsmg_test_transients'] = array();
    $GLOBALS['pairsmg_test_filters'] = array();
    $GLOBALS['pairsmg_test_remote'] = null;
    $GLOBALS['pairsmg_test_actions'] = array();
}

class WP_Error {
    private $code;
    private $message;
    public function __construct($code = '', $message = '') { $this->code = $code; $this->message = $message; }
    public function get_error_code() { return $this->code; }
    public function get_error_message() { return $this->message; }
}
function is_wp_error($thing) { return $thing instanceof WP_Error; }

function get_option($key, $default = false) {
    return array_key_exists($key, $GLOBALS['pairsmg_test_options']) ? $GLOBALS['pairsmg_test_options'][$key] : $default;
}
function update_option($key, $value, $autoload = null) { $GLOBALS['pairsmg_test_options'][$key] = $value; return true; }
function delete_option($key) { unset($GLOBALS['pairsmg_test_options'][$key]); return true; }
$GLOBALS['pairsmg_test_cache'] = array();
function wp_cache_get($key, $group = '') { return $GLOBALS['pairsmg_test_cache'][$group . ':' . $key] ?? false; }
function wp_cache_set($key, $value, $group = '', $ttl = 0) { $GLOBALS['pairsmg_test_cache'][$group . ':' . $key] = $value; return true; }
function get_transient($key) {
    return array_key_exists($key, $GLOBALS['pairsmg_test_transients']) ? $GLOBALS['pairsmg_test_transients'][$key] : false;
}
function set_transient($key, $value, $ttl = 0) { $GLOBALS['pairsmg_test_transients'][$key] = $value; return true; }
function delete_transient($key) { unset($GLOBALS['pairsmg_test_transients'][$key]); return true; }

function add_filter($hook, $fn, $prio = 10, $args = 1) { $GLOBALS['pairsmg_test_filters'][$hook][] = $fn; }
function apply_filters($hook, $value) {
    $args = func_get_args();
    array_shift($args);
    foreach ((array) ($GLOBALS['pairsmg_test_filters'][$hook] ?? array()) as $fn) {
        $args[0] = call_user_func_array($fn, $args);
    }
    return $args[0];
}
function do_action($hook) { $GLOBALS['pairsmg_test_actions'][] = func_get_args(); }

function wp_parse_args($args, $defaults = array()) { return array_merge($defaults, (array) $args); }
function wp_json_encode($data) { return json_encode($data); }
function wp_generate_password($length = 12, $special = true, $extra = false) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $out = '';
    for ($i = 0; $i < $length; $i++) { $out .= $chars[random_int(0, strlen($chars) - 1)]; }
    return $out;
}
function __($text, $domain = null) { return $text; }
function _n($single, $plural, $n, $domain = null) { return $n == 1 ? $single : $plural; }
function get_bloginfo($what = '') { return 'Test Site'; }
function home_url($path = '') { return 'https://example.test' . $path; }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function sanitize_text_field($v) { return trim(strip_tags((string) $v)); }
function sanitize_textarea_field($v) { return trim(strip_tags((string) $v)); }
function sanitize_key($v) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $v)); }
function sanitize_title($v) { $v = strtolower(trim((string) $v)); $v = preg_replace('/[^a-z0-9\-]+/', '-', $v); return trim($v, '-'); }
function sanitize_hex_color($v) { return preg_match('/^#([0-9a-fA-F]{3}){1,2}$/', (string) $v) ? $v : ''; }
function esc_url_raw($v) { return filter_var($v, FILTER_VALIDATE_URL) ? $v : ''; }
function wp_strip_all_tags($v) { return trim(strip_tags((string) $v)); }
function wp_remote_post($url, $args = array()) { $GLOBALS['pairsmg_test_remote_last'] = array('url' => $url, 'args' => $args); return $GLOBALS['pairsmg_test_remote']; }
function wp_remote_retrieve_response_code($r) { return $r['response']['code'] ?? 0; }
function wp_remote_retrieve_body($r) { return $r['body'] ?? ''; }
function rawurlencode_wp($v) { return rawurlencode($v); }

// The classes under test. Post type is loaded only for get_active_pairs(),
// which reads the transient cache first - tests seed that cache.
require_once PAIRSMG_DIR . 'includes/class-pairsmg-settings.php';
require_once PAIRSMG_DIR . 'includes/class-pairsmg-token.php';
require_once PAIRSMG_DIR . 'includes/class-pairsmg-scoring.php';
require_once PAIRSMG_DIR . 'includes/class-pairsmg-captcha.php';
require_once PAIRSMG_DIR . 'includes/class-pairsmg-deck.php';
require_once PAIRSMG_DIR . 'includes/class-pairsmg-post-type.php';
require_once PAIRSMG_DIR . 'includes/class-pairsmg-game-page.php';
require_once PAIRSMG_DIR . 'includes/class-pairsmg-admin-settings.php';
require_once PAIRSMG_DIR . 'includes/class-pairsmg-admin-leaderboard.php';
require_once PAIRSMG_DIR . 'includes/class-pairsmg-rest.php';
