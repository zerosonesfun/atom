# Atom: The fastest way to build modern WordPress plugins.

Atom is a modern, minimal, and robust WordPress plugin framework that lets you build powerful plugins with just a few lines of code. Atom handles settings pages, forms (with Turnstile/captcha), custom post types, AJAX, REST, widgets, admin UI, and more—all with a chainable, intuitive API.

## Features
- **Settings pages** (main menu, top-level, tabbed, submenus)
- **Frontend forms** (with Turnstile/captcha, AJAX, validation)
- **Custom post types** (fields, columns, sorting, admin table control)
- **AJAX and REST endpoints** (with sanitization and security)
- **Admin and frontend UI helpers** (notices, modals, tables, widgets, help tabs)
- **Dashboard widgets**
- **Bulk actions**
- **Filter UI for post types**
- **Automatic sanitization and best practices**
- **Deferred builder pattern for true top-level minimalism**

## Installation
1. Copy `atom.php` into your plugin directory.
2. In your plugin file, add:
   ```php
   require_once __DIR__ . '/atom.php';
   if (!\AtomFramework\Atom::load()) return;
   ```
3. Use Atom's API to build your plugin features.

## Quickstart Example
```php
require_once __DIR__ . '/atom.php';
if (!\AtomFramework\Atom::load()) return;

// Settings page
\AtomFramework\Atom::settings('my_plugin')
    ->field('api_key', 'text', ['label' => 'API Key', 'required' => true])
    ->menu(['parent' => 'options-general.php', 'title' => 'My Plugin Settings', 'menu' => 'My Plugin']);

// Contact form with Turnstile
add_action('init', function() {
    $recipient = get_option('api_key', get_option('admin_email'));
    \AtomFramework\Atom::form('contact')
        ->fields(
            ['name', 'text', ['label' => 'Your Name', 'required' => true]],
            ['email', 'email', ['label' => 'Your Email', 'required' => true]],
            ['message', 'textarea', ['label' => 'Message', 'required' => true, 'rows' => 5]]
        )
        ->shortcode('atom_contact_form')
        ->sendTo($recipient)
        ->success('Thank you!')
        ->error('Sorry, there was a problem.')
        ->turnstile();
});
```

## API Reference & Developer Guide

### Settings Pages
```php
\AtomFramework\Atom::settings('my_plugin')
    ->field('api_key', 'text', ['label' => 'API Key', 'required' => true])
    ->field('enable_feature', 'checkbox', ['label' => 'Enable Feature'])
    ->menu([
        'parent' => 'options-general.php', // or null for top-level
        'title' => 'My Plugin Settings',
        'menu' => 'My Plugin',
        'icon' => 'dashicons-admin-generic',
        'position' => 80,
        'capability' => 'manage_options'
    ]);
```

### Forms
```php
\AtomFramework\Atom::form('contact')
    ->fields(
        ['name', 'text', ['label' => 'Your Name', 'required' => true]],
        ['email', 'email', ['label' => 'Your Email', 'required' => true]],
        ['message', 'textarea', ['label' => 'Message', 'required' => true, 'rows' => 5]]
    )
    ->shortcode('atom_contact_form')
    ->sendTo('me@example.com')
    ->success('Thank you!')
    ->error('Sorry, there was a problem.')
    ->turnstile();
```

### Custom Post Types
```php
\AtomFramework\Atom::postType('book')
    ->label('Books')
    ->fields('title', 'author')
    ->sortable(['title', 'author'])
    ->column('author', 'Author', function($post) { return get_post_meta($post->ID, 'author', true); })
    ->public();
```

### AJAX & REST
```php
\AtomFramework\Atom::ajax('say_hi')
    ->fields('name')
    ->onSubmit(function($d) { return ['success'=>true, 'message'=>'Hi, '.$d['name'].'!']; });

\AtomFramework\Atom::rest('/hello')
    ->fields('name')
    ->onSubmit(function($d) { return ['message'=>'Hello, '.$d['name']]; });
```

### Admin & Frontend UI Helpers
```php
\AtomFramework\Atom::adminNotice('Settings saved!', 'success');
\AtomFramework\Atom::notice('Welcome!', 'info');
\AtomFramework\Atom::dashboardWidget('my_widget')->title('My Widget')->content(function() { echo 'Hello!'; });
\AtomFramework\Atom::helpTab('settings_page_my_plugin')->title('Help')->content('Instructions...');
```

### Bulk Actions, Filters, Tables, Modals, Widgets
```php
\AtomFramework\Atom::bulkAction('book', 'mark_read', 'Mark as Read', function($ids) { /* ... */ });
\AtomFramework\Atom::filter('book')->by('author')->by('year')->render();
\AtomFramework\Atom::adminTable('sample')->columns(['Name','Email'])->rows([['A','a@a.com']]);
\AtomFramework\Atom::adminModal('modal')->title('Confirm')->content('Are you sure?');
\AtomFramework\Atom::widget('my_widget')->title('Widget')->content(function() { echo 'Widget!'; });
```

## Best Practices
- Always `require_once` atom.php before using Atom in your plugin.
- Use `add_action('init', ...)` for any code that fetches options or outputs forms.
- Use the chainable API for all features.
- Use the settings API for all plugin options.
- Sanitize all user input (Atom does this for you by default).
- Register shortcodes for frontend forms.
- Use the namespace: `\AtomFramework\Atom`.

## Advanced
- You can extend Atom's builders for custom field types, validation, or UI.
- All builder methods are chainable and return `$this`.
- Deferred builder pattern allows top-level minimalism in your plugin files.

## License
MIT

---

**Atom: The fastest way to build modern WordPress plugins.** 