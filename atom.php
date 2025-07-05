<?php
/**
 * Atom: The 8-year-old simple WordPress plugin builder.
 *
 * Usage examples:
 *   Atom::form('contact')->fields('name', 'email', 'message')->turnstile()->sendTo('me@example.com');
 *   Atom::postType('book')->fields('title', 'author')->public();
 *   Atom::settings('my_plugin')->field('API Key')->onlyFor('administrator');
 *   Atom::shortcode('hello', fn() => 'Hello!');
 *   Atom::ajax('say_hi')->fields('name')->onSubmit(fn($d) => 'Hi, '.$d['name'].'!');
 *   Atom::rest('/hello')->fields('name')->onSubmit(fn($d) => ['message'=>'Hello, '.$d['name']]);
 *   Atom::filter('book')->by('author')->by('year')->render();
 */

namespace AtomFramework;

class Atom {
    private static $deferred_forms = [];
    private static $deferred_post_types = [];
    private static $deferred_settings = [];
    private static $deferred_ajax = [];
    private static $deferred_rest = [];
    private static $deferred_filter = [];
    public static function form($slug) {
        if (!did_action('init')) {
            $builder = new DeferredFormBuilder($slug);
            self::$deferred_forms[] = $builder;
            return $builder->getProxy();
        }
        return new FormBuilder($slug);
    }
    public static function postType($slug) {
        if (!did_action('init')) {
            $builder = new DeferredPostTypeBuilder($slug);
            self::$deferred_post_types[] = $builder;
            return $builder->getProxy();
        }
        return new PostTypeBuilder($slug);
    }
    public static function settings($slug) {
        if (!did_action('init')) {
            $builder = new DeferredSettingsBuilder($slug);
            self::$deferred_settings[] = $builder;
            return $builder->getProxy();
        }
        return new SettingsBuilder($slug);
    }
    public static function ajax($action) {
        if (!did_action('init')) {
            $builder = new DeferredAjaxBuilder($action);
            self::$deferred_ajax[] = $builder;
            return $builder->getProxy();
        }
        return new AjaxBuilder($action);
    }
    public static function rest($route) {
        if (!did_action('init')) {
            $builder = new DeferredRestBuilder($route);
            self::$deferred_rest[] = $builder;
            return $builder->getProxy();
        }
        return new RestBuilder($route);
    }
    public static function filter($post_type) {
        if (!did_action('init')) {
            $builder = new DeferredFilterBuilder($post_type);
            self::$deferred_filter[] = $builder;
            return $builder->getProxy();
        }
        return new FilterBuilder($post_type);
    }
    public static function shortcode($tag, $cb) { add_shortcode($tag, $cb); }
    public static function setTurnstileKeys($site, $secret) { FormBuilder::$turnstile_site_key = $site; FormBuilder::$turnstile_secret_key = $secret; }
    /**
     * Atom::plugin - Minimalist plugin wrapper. Usage:
     *   Atom::plugin(function() { ... });
     * This ensures your code runs after Atom is loaded and on init.
     */
    public static function plugin($cb) {
        if (!class_exists(__CLASS__)) return;
        add_action('init', $cb);
    }
    public static function dashboardWidget($id) {
        return new DashboardWidgetBuilder($id);
    }
    /** Show an admin notice (success, error, info, warning) */
    public static function adminNotice($msg, $type = 'success') {
        add_action('admin_notices', function() use ($msg, $type) {
            echo '<div class="notice notice-' . esc_attr($type) . ' is-dismissible"><p>' . esc_html($msg) . '</p></div>';
        });
    }
    /** Add a menu separator at a given position */
    public static function adminMenuSeparator($pos) {
        add_action('admin_menu', function() use ($pos) {
            global $menu;
            $menu[$pos] = array('', 'read', 'separator' . $pos, '', 'wp-menu-separator');
            ksort($menu);
        });
    }
    /** Add a help tab to a screen */
    public static function helpTab($screen_id) {
        return new HelpTabBuilder($screen_id);
    }
    /** Show a frontend notice (success, error, info, warning) */
    public static function notice($msg, $type = 'success') {
        add_action('wp_footer', function() use ($msg, $type) {
            echo '<div class="atom-notice atom-notice-' . esc_attr($type) . '" style="position:fixed;bottom:20px;right:20px;z-index:9999;background:#fff;padding:12px 20px;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,0.1);color:#222;">' . esc_html($msg) . '</div>';
        });
    }
    /** Register a frontend widget for sidebars */
    public static function widget($id) {
        return new WidgetBuilder($id);
    }
    /** Tabbed settings/admin page */
    public static function tabbedSettings($slug) {
        return new TabbedSettingsBuilder($slug);
    }
    /** Custom admin table/list */
    public static function adminTable($id) {
        return new AdminTableBuilder($id);
    }
    /** Admin modal/popup */
    public static function adminModal($id) {
        return new AdminModalBuilder($id);
    }
    /** Frontend modal/popup */
    public static function modal($id) {
        return new ModalBuilder($id);
    }
    /** Frontend table/grid */
    public static function table($id) {
        return new TableBuilder($id);
    }
    /** Add a bulk action to a post type or table */
    public static function bulkAction($type, $action, $label, $cb) {
        add_filter('bulk_actions-edit-' . $type, function($actions) use ($action, $label) {
            $actions[$action] = $label; return $actions;
        });
        add_filter('handle_bulk_actions-edit-' . $type, function($redirect, $doaction, $ids) use ($action, $cb) {
            if ($doaction === $action) call_user_func($cb, $ids);
            return $redirect;
        }, 10, 3);
    }
    /**
     * Atom::load($callerFile)
     * Loads atom.php from the same directory as the calling file if not already loaded.
     * Returns true if Atom is loaded, false otherwise.
     * Usage: Atom::load(__FILE__);
     */
    public static function load($caller = null) {
        if (class_exists(__CLASS__, false)) return true;
        if (!$caller) {
            $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = isset($bt[1]['file']) ? $bt[1]['file'] : null;
        }
        if ($caller) {
            $dir = dirname($caller);
            $atom = $dir . '/atom.php';
            if (file_exists($atom)) require_once $atom;
        }
        if (!class_exists(__CLASS__, false)) return false;
        return true;
    }
    /**
     * Sanitize a field value by type (text, email, url, number, checkbox, textarea, etc.)
     */
    public static function sanitize_field($value, $type = 'text') {
        switch ($type) {
            case 'email': return sanitize_email($value);
            case 'url': return esc_url_raw($value);
            case 'number': return is_numeric($value) ? $value + 0 : 0;
            case 'checkbox': return $value ? '1' : '0';
            case 'textarea': return sanitize_textarea_field($value);
            default: return sanitize_text_field($value);
        }
    }
}

/**
 * AtomFramework\FormBuilder
 *
 * Usage:
 *   Atom::form('contact')->fields('name', 'email', 'message')->turnstile()->sendTo('me@example.com');
 */
class FormBuilder {
    public static $turnstile_site_key = null;
    public static $turnstile_secret_key = null;
    protected $slug;
    protected $fields = [];
    protected $field_options = [];
    protected $use_turnstile = false;
    protected $use_captcha = false;
    protected $email = null;
    protected $custom_handler = null;
    protected $form_id;
    protected $success_message = 'Thank you!';
    protected $error_message = 'Failed to send.';
    public function __construct($slug) {
        $this->slug = sanitize_key($slug);
        $this->form_id = 'atom_form_' . $this->slug;
    }
    public function fields(...$fields) {
        foreach ($fields as $f) $this->field($f);
        return $this;
    }
    public function field($name, $type = null) {
        $opts = [];
        if (is_array($name)) {
            $field = $name;
            $name = $field[0] ?? '';
            $type = $field[1] ?? null;
            $opts = $field[2] ?? [];
        }
        $type = $type ?: (stripos($name, 'email') !== false ? 'email' : (stripos($name, 'message') !== false ? 'textarea' : 'text'));
        $this->fields[$name] = $type;
        if (!empty($opts)) $this->field_options[$name] = $opts;
        return $this;
    }
    public function success($msg) { $this->success_message = $msg; return $this; }
    public function error($msg) { $this->error_message = $msg; return $this; }
    public function turnstile() { $this->use_turnstile = true; return $this; }
    public function captcha() { $this->use_captcha = true; return $this; }
    public function sendTo($email) { $this->email = $email; $this->registerHandler(); return $this; }
    public function onSubmit($cb) { $this->custom_handler = $cb; $this->registerHandler(); return $this; }
    public function shortcode($tag) {
        $builder = $this;
        add_shortcode($tag, function() use ($builder) { return $builder->render(); });
        return $this;
    }
    protected function registerHandler() {
        $fields = $this->fields;
        if ($this->use_turnstile) $fields['turnstile'] = 'turnstile';
        if ($this->use_captcha) $fields['captcha'] = 'captcha';
        $action = $this->form_id . '_submit';
        $handler = $this->custom_handler;
        $success_message = $this->success_message;
        $error_message = $this->error_message;
        if (!$handler && $this->email) {
            $handler = function($data) use ($success_message, $error_message) {
                $to = $this->email;
                $subject = 'Contact Form Submission';
                $body = '';
                foreach ($this->fields as $k => $t) $body .= ucfirst($k) . ': ' . ($data[$k] ?? '') . "\n";
                $headers = [];
                if (!empty($data['email']) && is_email($data['email'])) $headers[] = 'Reply-To: ' . $data['email'];
                $sent = wp_mail($to, $subject, $body, $headers);
                return $sent ? ['success' => true, 'message' => $success_message] : ['success' => false, 'message' => $error_message];
            };
        }
        if ($handler) {
            foreach (['wp_ajax_', 'wp_ajax_nopriv_'] as $prefix) {
                add_action($prefix . $action, function() use ($fields, $handler, $success_message, $error_message) {
                    $atom = new Atom();
                    $data = [];
                    foreach ($fields as $key => $type) $data[$key] = Atom::sanitize_field($_REQUEST[$key] ?? '', $type);
                    try {
                        $result = call_user_func($handler, $data);
                        if (is_array($result) && isset($result['success']) && $result['success']) wp_send_json_success($result);
                        else wp_send_json_error($result);
                    } catch (\Exception $e) { wp_send_json_error(['message' => $error_message]); }
                    wp_die();
                });
            }
        }
    }
    public function render() {
        $fields = $this->fields;
        $field_options = $this->field_options;
        if ($this->use_turnstile) $fields['turnstile'] = 'turnstile';
        if ($this->use_captcha) $fields['captcha'] = 'captcha';
        $action = $this->form_id . '_submit';
        static $turnstile_enqueued = false;
        $html = '<form class="atom-form" method="post" action="#" data-atom-action="' . esc_attr($action) . '">';
        foreach ($fields as $name => $type) {
            $opts = $field_options[$name] ?? [];
            $label = $opts['label'] ?? preg_replace('/[_-]+/', ' ', $name);
            $label = ucwords($label);
            $required = !empty($opts['required']) ? 'required' : '';
            $desc = $opts['desc'] ?? '';
            $val = '';
            $html .= '<div class="atom-form-field">';
            if ($type === 'checkbox') {
                $html .= '<label><input type="checkbox" name="' . esc_attr($name) . '" value="1" ' . $required . '> ' . esc_html($label) . '</label>';
            } elseif ($type === 'textarea') {
                $html .= '<label>' . esc_html($label) . '<br><textarea name="' . esc_attr($name) . '" rows="' . ($opts['rows'] ?? 4) . '" ' . $required . '></textarea></label>';
            } elseif ($type === 'turnstile') {
                $sitekey = self::$turnstile_site_key ?: '';
                $html .= '<div class="cf-turnstile" data-sitekey="' . esc_attr($sitekey) . '"></div>';
                if (!$turnstile_enqueued) {
                    add_action('wp_footer', function() {
                        echo '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>';
                    });
                    $turnstile_enqueued = true;
                }
            } elseif ($type === 'captcha') {
                $html .= '<label>' . esc_html($label) . '<br><input type="text" name="' . esc_attr($name) . '" ' . $required . '></label> <span class="atom-captcha">[Captcha here]</span>';
            } else {
                $html .= '<label>' . esc_html($label) . '<br><input type="' . esc_attr($type) . '" name="' . esc_attr($name) . '" value="' . esc_attr($val) . '" ' . $required . '></label>';
            }
            if ($desc) $html .= '<br><small>' . esc_html($desc) . '</small>';
            $html .= '</div>';
        }
        $html .= '<button type="submit">Send</button>';
        $html .= '</form>';
        return $html;
    }
    /**
     * Allow safe string conversion (e.g., echo $form) to always output the form HTML.
     */
    public function __toString() {
        return $this->render();
    }
}

/**
 * PostTypeBuilder: For Atom::postType('slug')
 */
class PostTypeBuilder {
    protected $slug;
    protected $args = [];
    protected $fields = [];
    private $registered = false;
    private $admin_label = null;
    private $menu_position = null;
    private $sortable = [];
    private $not_sortable = [];
    private $custom_columns = [];
    public function __construct($slug) { $this->slug = sanitize_key($slug); }
    public function fields(...$fields) { foreach ($fields as $f) $this->field($f); return $this; }
    public function field($name, $type = 'text') { $this->fields[$name] = $type; $this->register(); return $this; }
    public function public($bool = true) { $this->args['public'] = $bool; $this->register(); return $this; }
    public function icon($icon) { $this->args['menu_icon'] = $icon; $this->register(); return $this; }
    public function onlyFor($role) { $this->args['capability_type'] = $role; $this->register(); return $this; }
    /** Set admin label */
    public function label($label) { $this->admin_label = $label; $this->register(); return $this; }
    /** Set menu position */
    public function menuPosition($pos) { $this->menu_position = $pos; $this->register(); return $this; }
    /** Set sortable columns (array or false) */
    public function sortable($cols) { $this->sortable = is_array($cols) ? $cols : []; $this->register(); return $this; }
    /** Set not-sortable columns (array) */
    public function notSortable($cols) { $this->not_sortable = is_array($cols) ? $cols : [$cols]; $this->register(); return $this; }
    /** Add custom column */
    public function column($key, $label, $cb) { $this->custom_columns[$key] = ['label'=>$label, 'cb'=>$cb]; $this->register(); return $this; }
    private function register() {
        if ($this->registered) return;
        $args = array_merge([
            'label' => $this->admin_label ?: ucfirst($this->slug),
            'supports' => array_keys($this->fields),
        ], $this->args);
        if ($this->menu_position) $args['menu_position'] = $this->menu_position;
        add_action('init', function() use ($args) { register_post_type($this->slug, $args); });
        // Custom columns
        if ($this->custom_columns) {
            add_filter('manage_' . $this->slug . '_posts_columns', function($cols) {
                foreach ($this->custom_columns as $k => $v) $cols[$k] = $v['label'];
                return $cols;
            });
            add_action('manage_' . $this->slug . '_posts_custom_column', function($col, $post_id) {
                if (isset($this->custom_columns[$col])) echo call_user_func($this->custom_columns[$col]['cb'], get_post($post_id));
            }, 10, 2);
        }
        // Sortable columns
        if ($this->sortable) {
            add_filter('manage_edit-' . $this->slug . '_sortable_columns', function($cols) {
                foreach ($this->sortable as $col) $cols[$col] = $col;
                return $cols;
            });
        }
        if ($this->not_sortable) {
            add_filter('manage_edit-' . $this->slug . '_sortable_columns', function($cols) {
                foreach ($this->not_sortable as $col) unset($cols[$col]);
                return $cols;
            }, 20);
        }
        $this->registered = true;
    }
}

/**
 * SettingsBuilder: For Atom::settings('slug')
 */
class SettingsBuilder {
    protected $slug;
    protected $fields = [];
    protected $role = null;
    private $registered = false;
    private $menu_opts = [];
    protected $field_options = [];
    public function __construct($slug) { $this->slug = sanitize_key($slug); }
    public function field($name, $type = 'text', $opts = []) {
        $this->fields[$name] = $type;
        if (!empty($opts)) $this->field_options[$name] = $opts;
        $this->register();
        return $this;
    }
    public function onlyFor($role) { $this->role = $role; $this->register(); return $this; }
    /**
     * Set menu options for the settings page.
     * @param array $opts parent, title, menu, position, icon, capability
     */
    public function menu($opts) { $this->menu_opts = $opts; $this->register(); return $this; }
    private function register() {
        if ($this->registered) return;
        add_action('admin_menu', function() {
            $opts = array_merge([
                'parent' => 'options-general.php',
                'title' => ucfirst($this->slug),
                'menu' => ucfirst($this->slug),
                'position' => null,
                'icon' => null,
                'capability' => $this->role ?: 'manage_options',
            ], $this->menu_opts);
            if ($opts['parent']) {
                add_submenu_page($opts['parent'], $opts['title'], $opts['menu'], $opts['capability'], $this->slug, [$this, 'render'], $opts['position']);
            } else {
                add_menu_page($opts['title'], $opts['menu'], $opts['capability'], $this->slug, [$this, 'render'], $opts['icon'], $opts['position']);
            }
        });
        add_action('admin_init', function() {
            // Register a section for the settings page
            add_settings_section('main', '', function() {}, $this->slug);
            foreach ($this->fields as $key => $type) {
                register_setting($this->slug, $key, ['sanitize_callback' => function($v) use ($type) { return Atom::sanitize_field($v, $type); }]);
                $opts = $this->field_options[$key] ?? [];
                $label = $opts['label'] ?? preg_replace('/[_-]+/', ' ', $key);
                $label = ucwords($label);
                $required = !empty($opts['required']) ? 'required' : '';
                $desc = $opts['desc'] ?? '';
                $is_checkbox = $type === 'checkbox';
                $asterisk = !empty($opts['required']) ? ' <span style="color:red">*</span>' : '';
                add_settings_field($key, $label . $asterisk, function() use ($key, $type, $required, $desc, $is_checkbox) {
                    $val = get_option($key, '');
                    if ($is_checkbox) {
                        echo '<input type="checkbox" name="' . esc_attr($key) . '" value="1" ' . checked($val, '1', false) . ' ' . $required . ' style="width:auto;">';
                    } else {
                        echo '<input type="' . esc_attr($type) . '" name="' . esc_attr($key) . '" value="' . esc_attr($val) . '" ' . $required . ' style="min-width:250px">';
                    }
                    if ($desc) echo '<br><small>' . esc_html($desc) . '</small>';
                }, $this->slug, 'main');
            }
        });
        $this->registered = true;
    }
    public function render() {
        echo '<form method="post" action="options.php">';
        settings_fields($this->slug); do_settings_sections($this->slug); submit_button();
        echo '</form>';
    }
}

/**
 * AjaxBuilder: For Atom::ajax('action')
 */
class AjaxBuilder {
    protected $action;
    protected $fields = [];
    protected $cb = null;
    public function __construct($action) { $this->action = $action; }
    public function fields(...$fields) { foreach ($fields as $f) $this->field($f); return $this; }
    public function field($name, $type = 'text') { $this->fields[$name] = $type; return $this; }
    public function onSubmit($cb) {
        $this->cb = $cb;
        foreach (['wp_ajax_', 'wp_ajax_nopriv_'] as $prefix) {
            add_action($prefix . $this->action, function() {
                $atom = new Atom();
                $data = [];
                foreach ($this->fields as $key => $type) $data[$key] = Atom::sanitize_field($_REQUEST[$key] ?? '', $type);
                try {
                    $result = call_user_func($this->cb, $data);
                    if (is_array($result) && isset($result['success']) && $result['success']) wp_send_json_success($result);
                    else wp_send_json_error($result);
                } catch (\Exception $e) { wp_send_json_error(['message' => $e->getMessage()]); }
                wp_die();
            });
        }
        return $this;
    }
}

/**
 * RestBuilder: For Atom::rest('/route')
 */
class RestBuilder {
    protected $route;
    protected $fields = [];
    protected $cb = null;
    public function __construct($route) { $this->route = $route; }
    public function fields(...$fields) { foreach ($fields as $f) $this->field($f); return $this; }
    public function field($name, $type = 'text') { $this->fields[$name] = $type; return $this; }
    public function onSubmit($cb) {
        $this->cb = $cb;
        add_action('rest_api_init', function() {
            register_rest_route('atom/v1', $this->route, [
                'methods' => 'POST',
                'callback' => function($request) {
                    $atom = new Atom();
                    $data = [];
                    foreach ($this->fields as $key => $type) $data[$key] = Atom::sanitize_field($request->get_param($key), $type);
                    try {
                        $result = call_user_func($this->cb, $data, $request);
                        if (is_array($result) && isset($result['success']) && $result['success']) return $result;
                        else return new \WP_Error('rest_error', $result['message'] ?? 'Error', ['status' => 400]);
                    } catch (\Exception $e) { return new \WP_Error('rest_exception', $e->getMessage(), ['status' => 500]); }
                }
            ]);
        });
        return $this;
    }
}

/**
 * FilterBuilder: For Atom::filter('post_type')
 */
class FilterBuilder {
    protected $post_type;
    protected $fields = [];
    public function __construct($post_type) { $this->post_type = $post_type; }
    public function by($field) { $this->fields[] = $field; return $this; }
    public function render() {
        echo '<form method="get">';
        foreach ($this->fields as $f) {
            echo '<input type="text" name="' . esc_attr($f) . '" placeholder="Filter by ' . esc_attr($f) . '"> ';
        }
        echo '<button type="submit">Filter</button></form>';
    }
}

// Proxy/deferred builder for Atom::form
class DeferredFormBuilder {
    private $slug;
    public $calls = [];
    private $proxy;
    public function __construct($slug) {
        $this->slug = $slug;
        $this->proxy = new class($this) {
            private $parent;
            public function __construct($parent) { $this->parent = $parent; }
            public function __call($name, $args) {
                $this->parent->calls[] = [$name, $args];
                return $this;
            }
        };
        add_action('init', [$this, 'replay']);
    }
    public function getProxy() { return $this->proxy; }
    public function replay() {
        $builder = new FormBuilder($this->slug);
        foreach ($this->calls as [$name, $args]) {
            // Special handling for shortcode: always call on the real builder
            if ($name === 'shortcode' && method_exists($builder, 'shortcode')) {
                call_user_func_array([$builder, $name], $args);
            } elseif (method_exists($builder, $name)) {
                call_user_func_array([$builder, $name], $args);
            }
        }
    }
}

class DeferredPostTypeBuilder {
    private $slug;
    public $calls = [];
    private $proxy;
    public function __construct($slug) {
        $this->slug = $slug;
        $this->proxy = new class($this) {
            private $parent;
            public function __construct($parent) { $this->parent = $parent; }
            public function __call($name, $args) {
                $this->parent->calls[] = [$name, $args];
                return $this;
            }
        };
        add_action('init', [$this, 'replay']);
    }
    public function getProxy() { return $this->proxy; }
    public function replay() {
        $builder = new PostTypeBuilder($this->slug);
        foreach ($this->calls as [$name, $args]) {
            if (method_exists($builder, $name)) {
                call_user_func_array([$builder, $name], $args);
            }
        }
    }
}

class DeferredSettingsBuilder {
    private $slug;
    public $calls = [];
    private $proxy;
    public function __construct($slug) {
        $this->slug = $slug;
        $this->proxy = new class($this) {
            private $parent;
            public function __construct($parent) { $this->parent = $parent; }
            public function __call($name, $args) {
                $this->parent->calls[] = [$name, $args];
                return $this;
            }
        };
        add_action('init', [$this, 'replay']);
    }
    public function getProxy() { return $this->proxy; }
    public function replay() {
        $builder = new SettingsBuilder($this->slug);
        foreach ($this->calls as [$name, $args]) {
            if (method_exists($builder, $name)) {
                call_user_func_array([$builder, $name], $args);
            }
        }
    }
}

class DeferredAjaxBuilder {
    private $action;
    public $calls = [];
    private $proxy;
    public function __construct($action) {
        $this->action = $action;
        $this->proxy = new class($this) {
            private $parent;
            public function __construct($parent) { $this->parent = $parent; }
            public function __call($name, $args) {
                $this->parent->calls[] = [$name, $args];
                return $this;
            }
        };
        add_action('init', [$this, 'replay']);
    }
    public function getProxy() { return $this->proxy; }
    public function replay() {
        $builder = new AjaxBuilder($this->action);
        foreach ($this->calls as [$name, $args]) {
            if (method_exists($builder, $name)) {
                call_user_func_array([$builder, $name], $args);
            }
        }
    }
}

class DeferredRestBuilder {
    private $route;
    public $calls = [];
    private $proxy;
    public function __construct($route) {
        $this->route = $route;
        $this->proxy = new class($this) {
            private $parent;
            public function __construct($parent) { $this->parent = $parent; }
            public function __call($name, $args) {
                $this->parent->calls[] = [$name, $args];
                return $this;
            }
        };
        add_action('init', [$this, 'replay']);
    }
    public function getProxy() { return $this->proxy; }
    public function replay() {
        $builder = new RestBuilder($this->route);
        foreach ($this->calls as [$name, $args]) {
            if (method_exists($builder, $name)) {
                call_user_func_array([$builder, $name], $args);
            }
        }
    }
}

class DeferredFilterBuilder {
    private $post_type;
    public $calls = [];
    private $proxy;
    public function __construct($post_type) {
        $this->post_type = $post_type;
        $this->proxy = new class($this) {
            private $parent;
            public function __construct($parent) { $this->parent = $parent; }
            public function __call($name, $args) {
                $this->parent->calls[] = [$name, $args];
                return $this;
            }
        };
        add_action('init', [$this, 'replay']);
    }
    public function getProxy() { return $this->proxy; }
    public function replay() {
        $builder = new FilterBuilder($this->post_type);
        foreach ($this->calls as [$name, $args]) {
            if (method_exists($builder, $name)) {
                call_user_func_array([$builder, $name], $args);
            }
        }
    }
}

class DashboardWidgetBuilder {
    protected $id;
    protected $title = 'Dashboard Widget';
    protected $content = null;
    protected $context = 'normal';
    protected $priority = 'core';
    public function __construct($id) { $this->id = sanitize_key($id); }
    public function title($title) { $this->title = $title; return $this; }
    public function content($cb) { $this->content = $cb; $this->register(); return $this; }
    public function context($context) { $this->context = $context; return $this; }
    public function priority($priority) { $this->priority = $priority; return $this; }
    private function register() {
        add_action('wp_dashboard_setup', function() {
            wp_add_dashboard_widget($this->id, $this->title, $this->content, null, null, $this->context, $this->priority);
        });
    }
}

class HelpTabBuilder {
    protected $screen_id;
    protected $title = 'Help';
    protected $content = '';
    public function __construct($screen_id) { $this->screen_id = $screen_id; }
    public function title($title) { $this->title = $title; return $this; }
    public function content($content) { $this->content = $content; $this->register(); return $this; }
    private function register() {
        add_action('current_screen', function() {
            $screen = get_current_screen();
            if ($screen && $screen->id === $this->screen_id) {
                $screen->add_help_tab([
                    'id'      => $this->screen_id . '_help',
                    'title'   => $this->title,
                    'content' => '<p>' . $this->content . '</p>',
                ]);
            }
        });
    }
}

class WidgetBuilder {
    protected $id;
    protected $title = 'Widget';
    protected $content = null;
    public function __construct($id) { $this->id = sanitize_key($id); }
    public function title($title) { $this->title = $title; return $this; }
    public function content($cb) { $this->content = $cb; $this->register(); return $this; }
    private function register() {
        add_action('widgets_init', function() {
            $id = $this->id;
            $title = $this->title;
            $cb = $this->content;
            class_exists('WP_Widget') && register_widget(new class($id, $title, $cb) extends \WP_Widget {
                private $cb;
                public function __construct($id, $title, $cb) {
                    parent::__construct($id, $title);
                    $this->cb = $cb;
                }
                public function widget($args, $instance) {
                    echo $args['before_widget'];
                    if (!empty($this->name)) echo $args['before_title'] . $this->name . $args['after_title'];
                    if (is_callable($this->cb)) call_user_func($this->cb, $args, $instance);
                    echo $args['after_widget'];
                }
            });
        });
    }
}

class TabbedSettingsBuilder {
    protected $slug;
    protected $tabs = [];
    public function __construct($slug) { $this->slug = sanitize_key($slug); }
    public function tab($label, $cb) { $this->tabs[] = [$label, $cb]; $this->register(); return $this; }
    private function register() {
        add_action('admin_menu', function() {
            add_options_page(ucfirst($this->slug), ucfirst($this->slug), 'manage_options', $this->slug, [$this, 'render']);
        });
    }
    public function render() {
        echo '<h2 class="nav-tab-wrapper">';
        $active = isset($_GET['tab']) ? $_GET['tab'] : ($this->tabs[0][0] ?? '');
        foreach ($this->tabs as [$label, $cb]) {
            $url = add_query_arg('tab', urlencode($label));
            $class = ($active === $label) ? ' nav-tab-active' : '';
            echo '<a href="' . esc_url($url) . '" class="nav-tab' . $class . '">' . esc_html($label) . '</a>';
        }
        echo '</h2><div style="margin-top:2em">';
        foreach ($this->tabs as [$label, $cb]) {
            if ($active === $label && is_callable($cb)) call_user_func($cb, $this);
        }
        echo '</div>';
    }
    public function field($name, $type = 'text') {
        // Minimal: just render a field
        echo '<p><label>' . esc_html($name) . ': <input type="' . esc_attr($type) . '" name="' . esc_attr($name) . '"></label></p>';
    }
}

class AdminTableBuilder {
    protected $id;
    protected $columns = [];
    protected $rows = [];
    protected $actions = [];
    public function __construct($id) { $this->id = sanitize_key($id); }
    public function columns($cols) { $this->columns = $cols; return $this; }
    public function rows($rows) { $this->rows = $rows; return $this; }
    public function actions($actions) { $this->actions = $actions; return $this; }
    public function render() {
        echo '<table class="widefat"><thead><tr>';
        foreach ($this->columns as $col) echo '<th>' . esc_html($col) . '</th>';
        echo '</tr></thead><tbody>';
        foreach ($this->rows as $row) {
            echo '<tr>';
            foreach ($row as $cell) echo '<td>' . esc_html($cell) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
}

class AdminModalBuilder {
    protected $id;
    protected $title = 'Modal';
    protected $content = '';
    protected $on_confirm = null;
    public function __construct($id) { $this->id = sanitize_key($id); }
    public function title($title) { $this->title = $title; return $this; }
    public function content($content) { $this->content = $content; return $this; }
    public function onConfirm($cb) { $this->on_confirm = $cb; return $this; }
    public function render() {
        echo '<div id="' . esc_attr($this->id) . '" class="atom-admin-modal" style="display:none"><h2>' . esc_html($this->title) . '</h2><div>' . $this->content . '</div><button class="button-primary" onclick="if(window.atomModalConfirm)atomModalConfirm(\'' . esc_js($this->id) . '\')">OK</button></div>';
        // JS for modal (minimal)
        echo '<script>window.atomModalConfirm=function(id){jQuery("#"+id).hide();};</script>';
    }
}

class ModalBuilder {
    protected $id;
    protected $title = 'Modal';
    protected $content = '';
    protected $show_on_load = false;
    public function __construct($id) { $this->id = sanitize_key($id); }
    public function title($title) { $this->title = $title; return $this; }
    public function content($content) { $this->content = $content; return $this; }
    public function showOnLoad($bool = true) { $this->show_on_load = $bool; $this->register(); return $this; }
    private function register() {
        add_action('wp_footer', function() {
            echo '<div id="' . esc_attr($this->id) . '" class="atom-modal" style="display:' . ($this->show_on_load ? 'block' : 'none') . ';position:fixed;top:20%;left:50%;transform:translateX(-50%);background:#fff;padding:2em;z-index:9999;border-radius:8px;box-shadow:0 2px 16px rgba(0,0,0,0.2);"><h2>' . esc_html($this->title) . '</h2><div>' . $this->content . '</div><button onclick="this.parentNode.style.display=\'none\'">Close</button></div>';
        });
    }
}

class TableBuilder {
    protected $id;
    protected $columns = [];
    protected $rows = [];
    public function __construct($id) { $this->id = sanitize_key($id); }
    public function columns($cols) { $this->columns = $cols; return $this; }
    public function rows($rows) { $this->rows = $rows; return $this; }
    public function render() {
        echo '<table class="atom-table" style="width:100%;border-collapse:collapse;"><thead><tr>';
        foreach ($this->columns as $col) echo '<th style="border-bottom:1px solid #ccc;padding:8px;text-align:left;">' . esc_html($col) . '</th>';
        echo '</tr></thead><tbody>';
        foreach ($this->rows as $row) {
            echo '<tr>';
            foreach ($row as $cell) echo '<td style="padding:8px;border-bottom:1px solid #eee;">' . esc_html($cell) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
}
?>