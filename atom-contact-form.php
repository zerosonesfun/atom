<?php
/*
Plugin Name: Atom Contact Form Example
Description: Contact form with customizable settings and Turnstile support, built with Atom.
Version: 1.0
*/

require_once __DIR__ . '/atom.php';
if (!\AtomFramework\Atom::load()) return;

// Settings page in main Settings menu
\AtomFramework\Atom::settings('atom_contact_form')
    ->field('recipient_email', 'email')
    ->field('success_message', 'text')
    ->field('error_message', 'text')
    ->field('enable_turnstile', 'checkbox')
    ->field('turnstile_site_key', 'text')
    ->field('turnstile_secret_key', 'text')
    ->menu([
        'parent' => 'options-general.php',
        'title' => 'Contact Form Settings',
        'menu' => 'Contact Form',
        'capability' => 'manage_options'
    ]);

add_action('init', function() {
    $recipient = get_option('recipient_email', get_option('admin_email'));
    $success = get_option('success_message', 'Thank you for contacting us!');
    $error = get_option('error_message', 'Sorry, there was a problem.');
    $turnstile = get_option('enable_turnstile');
    $site_key = get_option('turnstile_site_key');
    $secret_key = get_option('turnstile_secret_key');

    if ($turnstile && $site_key && $secret_key) {
        \AtomFramework\Atom::setTurnstileKeys($site_key, $secret_key);
    }

    \AtomFramework\Atom::form('contact')
        ->fields(
            ['name', 'text', ['label' => 'Your Name', 'required' => true]],
            ['email', 'email', ['label' => 'Your Email', 'required' => true]],
            ['message', 'textarea', ['label' => 'Message', 'required' => true, 'rows' => 5]]
        )
        ->shortcode('atom_contact_form')
        ->sendTo($recipient)
        ->success($success)
        ->error($error)
        ->{($turnstile && $site_key && $secret_key) ? 'turnstile' : 'captcha'}();
}); 