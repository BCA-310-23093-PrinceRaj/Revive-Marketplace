# Email OTP Setup

The OTP system is installed and currently runs in development mode. Development mode displays the OTP on the verification page instead of sending email.

## Enable Gmail SMTP

1. Enable 2-Step Verification on the Gmail account that will send Revive emails.
2. Create a Google App Password for Revive.
3. Open `config/mail.php`.
4. Set:

```php
'enabled' => true,
'username' => 'your-email@gmail.com',
'password' => 'your-16-character-app-password',
'from_email' => 'your-email@gmail.com',
'dev_show_otp' => false,
```

Do not use the normal Gmail account password. `config/mail.php` is ignored by Git so credentials are not committed.

## OTP Rules

- OTPs contain 6 digits.
- OTPs expire after 10 minutes.
- Users can make 5 verification attempts.
- Users must wait 60 seconds before requesting another OTP.
- OTPs are stored as password hashes, not plain text.

## Test

1. Register with an email address you can access.
2. Enter the OTP received by email.
3. Log out and use **Forgot?** on the login page.
4. Enter the reset OTP and choose a new password.

