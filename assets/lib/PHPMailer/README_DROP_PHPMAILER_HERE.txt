HOW TO ADD PHPMailer (one time, ~30 seconds)
============================================

The newsletter confirmation email uses PHPMailer. You just need to drop
3 files into THIS folder.

STEP 1 — Download PHPMailer
   Go to:  https://github.com/PHPMailer/PHPMailer/releases
   Download the latest "Source code (zip)" (e.g. v6.9.x).

STEP 2 — Unzip it, then open the "src" folder inside.

STEP 3 — Copy these THREE files from that "src" folder into THIS folder
         (C:\xampp\htdocs\de6\assets\lib\PHPMailer\):

         PHPMailer.php
         SMTP.php
         Exception.php

   (You do NOT need OAuth.php, POP3.php, or the language folder.)

That's it. When these files are present AND mail_config.php has 'enabled' => true
with your Gmail App Password, confirmation emails will start sending automatically.

If the files are missing, the site still works — subscribers are saved to the
database and the email step is simply skipped (no errors shown to visitors).

Final folder should look like:
   assets/lib/PHPMailer/PHPMailer.php
   assets/lib/PHPMailer/SMTP.php
   assets/lib/PHPMailer/Exception.php
