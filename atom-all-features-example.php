<?php
/*
Plugin Name: Atom All Features Example
Description: Demonstrates all Atom features in one plugin.
Version: 1.0
*/

require_once __DIR__ . '/atom.php';
if (!\AtomFramework\Atom::load()) return;

// Admin notice
\AtomFramework\Atom::adminNotice('Atom All Features Example loaded!', 'success');

// Settings page (main menu)
\AtomFramework\Atom::settings('atom_all_settings')
    ->field('site_api_key', 'text')
    ->field('enable_feature', 'checkbox')
    ->menu([
        'parent' => 'options-general.php',
        'title' => 'All Features Settings',
        'menu' => 'All Features',
        'capability' => 'manage_options'
    ]);
// Settings page (top-level)
\AtomFramework\Atom::settings('atom_all_top')
    ->field('top_setting', 'text')
    ->menu([
        'parent' => null,
        'title' => 'Top Level Settings',
        'menu' => 'Top Level',
        'icon' => 'dashicons-admin-tools'
    ]);

// Tabbed settings
\AtomFramework\Atom::tabbedSettings('atom_tabbed')
    ->tab('General', function($tab) { $tab->field('General Setting'); })
    ->tab('Advanced', function($tab) { $tab->field('Advanced Setting'); });

// Custom post type with columns, sorting, etc.
\AtomFramework\Atom::postType('book')
    ->label('Books')
    ->menuPosition(5)
    ->fields('title', 'author')
    ->sortable(['title', 'author'])
    ->notSortable(['date'])
    ->column('author', 'Author', function($post) { return get_post_meta($post->ID, 'author', true); })
    ->public();

// Bulk action for books
\AtomFramework\Atom::bulkAction('book', 'mark_read', 'Mark as Read', function($ids) {
    foreach ($ids as $id) update_post_meta($id, 'read', 1);
});

// Dashboard widget
\AtomFramework\Atom::dashboardWidget('atom_widget')
    ->title('Atom Widget')
    ->content(function() { echo 'This is a dashboard widget from Atom!'; });

// Help tab for settings page
\AtomFramework\Atom::helpTab('settings_page_atom_all_settings')
    ->title('How to use')
    ->content('This page demonstrates Atom settings.');

// Admin menu separator
\AtomFramework\Atom::adminMenuSeparator(60);

// Admin table
\AtomFramework\Atom::adminTable('sample_table')
    ->columns(['Name', 'Email'])
    ->rows([
        ['Alice', 'alice@example.com'],
        ['Bob', 'bob@example.com']
    ]);
// To render: call ->render() in a settings page or custom admin page callback

// Admin modal
\AtomFramework\Atom::adminModal('sample_modal')
    ->title('Sample Modal')
    ->content('This is a sample admin modal. Click OK to close.')
    ->onConfirm(function() { /* do something */ });
// To render: call ->render() in a settings page or custom admin page callback

// Frontend notice
\AtomFramework\Atom::notice('Welcome to the Atom All Features Example!', 'info');

// Frontend modal
\AtomFramework\Atom::modal('welcome_modal')
    ->title('Welcome!')
    ->content('Thanks for visiting this site.')
    ->showOnLoad();

// Frontend table
\AtomFramework\Atom::table('frontend_table')
    ->columns(['Product', 'Price'])
    ->rows([
        ['Book', '$10'],
        ['Pen', '$2']
    ]);
// To render: call ->render() in a shortcode or template

// Widget
\AtomFramework\Atom::widget('atom_sidebar_widget')
    ->title('Atom Widget')
    ->content(function() { echo 'This is a sidebar widget from Atom!'; });

// AJAX endpoint
\AtomFramework\Atom::ajax('say_hi')
    ->fields('name')
    ->onSubmit(function($d) { return ['success'=>true, 'message'=>'Hi, '.$d['name'].'!']; });

// REST endpoint
\AtomFramework\Atom::rest('/hello')
    ->fields('name')
    ->onSubmit(function($d) { return ['message'=>'Hello, '.$d['name']]; });

// Filter UI for books
\AtomFramework\Atom::filter('book')
    ->by('author')
    ->by('year')
    ->render();

// Contact form with Turnstile/captcha, using settings
$recipient = get_option('site_api_key', get_option('admin_email'));
$success = 'Thank you for contacting us!';
$error = 'Sorry, there was a problem.';
$turnstile = get_option('enable_feature');
$site_key = get_option('turnstile_site_key');
$secret_key = get_option('turnstile_secret_key');
if ($turnstile && $site_key && $secret_key) {
    \AtomFramework\Atom::setTurnstileKeys($site_key, $secret_key);
}
\AtomFramework\Atom::form('all_features_contact')
    ->fields(
        ['name', 'text', ['label' => 'Your Name', 'required' => true]],
        ['email', 'email', ['label' => 'Your Email', 'required' => true]],
        ['message', 'textarea', ['label' => 'Message', 'required' => true, 'rows' => 5]]
    )
    ->shortcode('atom_all_contact_form')
    ->sendTo($recipient)
    ->success($success)
    ->error($error)
    ->{($turnstile && $site_key && $secret_key) ? 'turnstile' : 'captcha'}(); 