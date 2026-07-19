<?php
/**
 * subscribe.php — Handles a newsletter sign-up from the home page.
 * Reached via: index.php?page=subscribe   (POST from the home newsletter widget)
 *
 * Flow:
 *   1. Validate the email.
 *   2. Save it to the `subscribers` table (duplicates handled gracefully).
 *   3. If email sending is configured, send a confirmation via PHPMailer.
 *   4. Redirect back to the home page with a status flag for the banner.
 *
 * Designed to degrade safely: if SMTP isn't set up yet, the address is still
 * saved and the visitor still sees a success message — only the email is skipped.
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Ensure a DB connection (index.php normally provides $conn).
if (!isset($conn)) { require_once 'db.php'; }

/** Small helper: send the visitor back home with a status code for the banner. */
function newsletter_redirect($status, $email = '') {
    // Remember the status in the session so the home page can show one banner,
    // then clear it (so a refresh doesn't show the message again).
    $_SESSION['newsletter_status'] = $status;      // subscribed | already | invalid | error
    if ($email !== '') { $_SESSION['newsletter_email'] = $email; }
    header("Location: index.php?page=home#newsletter");
    exit();
}

// Only accept POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    newsletter_redirect('error');
}

$email = trim($_POST['email'] ?? '');

// 1. Validate.
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
    newsletter_redirect('invalid', $email);
}
$email = strtolower($email);

// 2. Save. The UNIQUE index on `email` lets us detect duplicates cleanly.
$already = false;
$stmt = $conn->prepare("INSERT INTO subscribers (email) VALUES (?)");
if ($stmt === false) {
    // Most likely cause: the table doesn't exist yet.
    newsletter_redirect('error', $email);
}
$stmt->bind_param("s", $email);

if (!$stmt->execute()) {
    if ($conn->errno === 1062) {   // 1062 = duplicate entry for UNIQUE key
        $already = true;
    } else {
        $stmt->close();
        newsletter_redirect('error', $email);
    }
}
$stmt->close();

// 3. Try to send a confirmation email (only for brand-new subscribers).
if (!$already) {
    // send_confirmation_email() returns true/false; failure never blocks the signup.
    @send_confirmation_email($email, cms_site_base_url());
}

// 4. Done.
newsletter_redirect($already ? 'already' : 'subscribed', $email);


/* ==========================================================================
   Email sending — uses PHPMailer if it's been dropped in AND mail_config.php
   is enabled. Otherwise it quietly returns false (signup still succeeds).
   ========================================================================== */
/**
 * Work out the public base URL of the site from the current request, e.g.
 * "http://localhost:8080/de6". Used so email links point at wherever the site
 * is actually running instead of a hardcoded address.
 */
function cms_site_base_url() {
    $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
           || (($_SERVER['SERVER_PORT'] ?? '') == 443);
    $scheme = $https ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';           // includes port if non-standard
    // Directory the app lives in (e.g. /de6), derived from this script's path.
    $dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return $scheme . '://' . $host . $dir;
}

function send_confirmation_email($to_email, $site_url = '') {
    $config_path = __DIR__ . '/mail_config.php';
    if (!is_file($config_path)) { return false; }

    $cfg = require $config_path;
    if (empty($cfg['enabled'])) { return false; }                 // sending switched off
    if (empty($cfg['password']) || $cfg['password'] === 'PASTE_APP_PASSWORD_HERE') { return false; } // not configured

    // Locate the vendored PHPMailer files.
    $base = __DIR__ . '/assets/lib/PHPMailer/';
    $need = ['Exception.php', 'PHPMailer.php', 'SMTP.php'];
    foreach ($need as $f) {
        if (!is_file($base . $f)) { return false; }               // library not dropped in yet
    }
    require_once $base . 'Exception.php';
    require_once $base . 'PHPMailer.php';
    require_once $base . 'SMTP.php';

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $cfg['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $cfg['username'];
        $mail->Password   = $cfg['password'];
        $mail->Port       = (int) $cfg['port'];
        // 'ssl' => implicit TLS (port 465); anything else => STARTTLS (port 587)
        $mail->SMTPSecure = ($cfg['encryption'] === 'ssl')
            ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
            : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;

        $mail->setFrom($cfg['from_email'], $cfg['from_name']);
        $mail->addAddress($to_email);

        $mail->isHTML(true);
        $mail->Subject = $cfg['subject'];
        $mail->Body    = newsletter_email_html($site_url);
        $mail->AltBody = "Thanks for subscribing to CMS — Daily News Hub!\n\n"
                       . "You'll receive the latest breaking news in your inbox.\n"
                       . "Read today's stories: " . rtrim($site_url, '/') . "/index.php?page=home\n\n"
                       . "If this wasn't you, you can safely ignore this email.";

        $mail->send();
        return true;
    } catch (\Throwable $e) {
        // Swallow errors: a failed send must never break the sign-up experience.
        // (To debug locally you could log $mail->ErrorInfo here.)
        return false;
    }
}

/**
 * The HTML body of the confirmation email — matches the site's editorial look.
 * @param string $site_url Base URL of the site (e.g. http://localhost:8080/de6).
 *                         Falls back to a relative-safe default if empty.
 */
function newsletter_email_html($site_url = '') {
    $site_url = rtrim($site_url, '/');
    if ($site_url === '') { $site_url = 'http://localhost/de6'; } // sensible fallback
    $home_url = htmlspecialchars($site_url . '/index.php?page=home', ENT_QUOTES);

    return '
    <div style="margin:0;padding:24px 12px;background:#faf8f5;font-family:Arial,Helvetica,sans-serif;">
      <div style="max-width:540px;margin:0 auto;background:#ffffff;border:1px solid #e7e1d8;border-radius:10px;overflow:hidden;">
        <div style="height:4px;background:#c0362c;"></div>

        <!-- Masthead -->
        <div style="background:#111014;padding:24px 32px;text-align:center;">
          <span style="color:#ffffff;font-size:24px;font-weight:800;font-family:Georgia,\'Times New Roman\',serif;letter-spacing:.5px;">CM<span style="color:#c0362c;">S</span></span>
          <div style="color:#9b968f;font-size:10px;letter-spacing:.28em;text-transform:uppercase;margin-top:6px;">Daily News Hub</div>
        </div>

        <!-- Body -->
        <div style="padding:36px 32px 8px;">
          <div style="font-size:11px;letter-spacing:.22em;text-transform:uppercase;color:#c0362c;font-weight:700;margin-bottom:10px;">Subscription confirmed</div>
          <h1 style="margin:0 0 14px;font-family:Georgia,\'Times New Roman\',serif;font-size:26px;line-height:1.2;color:#17161b;">You&rsquo;re on the list.</h1>
          <p style="margin:0 0 16px;font-size:15px;line-height:1.75;color:#3a3740;">
            Thanks for subscribing to <strong>CMS &mdash; Daily News Hub</strong>. Fresh breaking news and
            trending stories will land in your inbox &mdash; curated across technology, business, finance,
            health, science and world affairs.
          </p>

          <!-- Bulletproof-ish CTA button -->
          <table role="presentation" cellpadding="0" cellspacing="0" style="margin:22px 0 6px;">
            <tr>
              <td align="center" bgcolor="#111014" style="border-radius:6px;">
                <a href="' . $home_url . '" target="_blank"
                   style="display:inline-block;padding:14px 30px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;font-family:Arial,Helvetica,sans-serif;border-radius:6px;">
                   Read today&rsquo;s stories &rarr;
                </a>
              </td>
            </tr>
          </table>

          <!-- Plain-URL fallback in case the button styling is stripped -->
          <p style="margin:8px 0 0;font-size:12px;line-height:1.6;color:#8b8892;">
            Or open this link in your browser:<br>
            <a href="' . $home_url . '" target="_blank" style="color:#c0362c;word-break:break-all;">' . $home_url . '</a>
          </p>

          <hr style="border:none;border-top:1px solid #eee5db;margin:26px 0 16px;">
          <p style="margin:0 0 4px;font-size:12px;color:#8b8892;line-height:1.6;">
            You&rsquo;re receiving this because someone entered this address on the CMS newsletter form.
            If that wasn&rsquo;t you, you can safely ignore this email &mdash; no further messages will be sent unless you subscribe.
          </p>
        </div>

        <!-- Footer -->
        <div style="background:#111014;padding:16px 32px;text-align:center;margin-top:20px;">
          <span style="color:#9b968f;font-size:12px;">&copy; 2024 CMS &mdash; Built with passion for journalism.</span>
        </div>
      </div>
    </div>';
}
