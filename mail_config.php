<?php
/**
 * mail_config.php — SMTP settings for the newsletter confirmation email.
 * ---------------------------------------------------------------------------
 * THIS IS THE ONLY FILE YOU NEED TO EDIT to turn on email sending.
 *
 * Recommended: Gmail with an "App Password" (NOT your normal Gmail password).
 *   1. Your Google account must have 2-Step Verification turned on.
 *   2. Go to:  https://myaccount.google.com/apppasswords
 *   3. Create an app password (pick "Mail" / "Other") — you'll get 16 letters.
 *   4. Paste your Gmail address and that 16-letter password below.
 *
 * Leave MAIL_ENABLED as false (or leave the password as the placeholder) and
 * the site still works — subscribers are saved, the confirmation email is just
 * skipped until you finish this setup.
 * ---------------------------------------------------------------------------
 */

return [
    // Master on/off switch. Set to true once you've filled in the details below.
    'enabled' => true,

    // --- Gmail SMTP defaults (change host/port only if you use another provider) ---
    'host' => 'smtp.gmail.com',
    'port' => 587,              // 587 = STARTTLS (recommended). Use 465 for SMTPS.
    'encryption' => 'tls',            // 'tls' for 587, 'ssl' for 465

    // --- YOUR credentials ---
    'username' => 'parixitc9@gmail.com',   // the Gmail you send FROM
    'password' => 'gtmoagrmgpgymsrs',  // 16-char Google App Password (no spaces)

    // --- What the email looks like ---
    'from_email' => 'parixitc9@gmail.com',    // usually same as username
    'from_name' => 'CMS — Daily News Hub',
    'subject' => 'You are subscribed to CMS News',
];
