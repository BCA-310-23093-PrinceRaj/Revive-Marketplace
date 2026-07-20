<?php
session_start();

// Must have a pending admin OTP session
$email = $_SESSION['otp_email']['admin_login'] ?? '';
if (!$email) {
    header('Location: admin_login.php?error=session_expired');
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error_messages = [
    'invalid_otp'       => 'The OTP entered is incorrect. Please try again.',
    'otp_expired'       => 'OTP has expired. Please login again.',
    'otp_not_found'     => 'No active OTP session found.',
    'too_many_attempts' => 'Too many incorrect attempts. Please login again.',
    'resend_wait'       => 'Please wait 60 seconds before requesting another OTP.',
    'otp_send_failed'   => 'Could not send OTP email. Check SMTP configuration.',
    'security_mismatch' => 'Security verification failed. Please try again.',
    'session_expired'   => 'Session expired. Please login again.',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revive | Admin OTP Verification</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a; }
        .admin-card { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.1); }
        .otp-input { letter-spacing: 0.5em; font-size: 2rem; text-align: center; }
        @keyframes pulse-ring {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(147, 51, 234, 0.5); }
            70% { transform: scale(1); box-shadow: 0 0 0 15px rgba(147, 51, 234, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(147, 51, 234, 0); }
        }
        .pulse-icon { animation: pulse-ring 2s infinite; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">

    <div class="w-full max-w-md">
        <!-- Header -->
        <div class="text-center mb-10">
            <div class="w-20 h-20 bg-purple-500/10 border-2 border-purple-500/30 rounded-3xl flex items-center justify-center mx-auto mb-6 pulse-icon">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-white tracking-tight">REVIVE <span class="text-purple-500">ADMIN</span></h1>
            <p class="text-slate-400 mt-2 text-sm">Two-Factor Authentication Required</p>
        </div>

        <div class="admin-card p-8 rounded-3xl shadow-2xl">

            <!-- Error/Success Messages -->
            <?php if(isset($_GET['error'])): ?>
                <div class="bg-red-500/10 border border-red-500/20 text-red-400 text-sm p-4 rounded-xl mb-6 text-center font-semibold">
                    <?php echo htmlspecialchars($error_messages[$_GET['error']] ?? 'Verification failed.'); ?>
                </div>
            <?php elseif(isset($_GET['success'])): ?>
                <div class="bg-green-500/10 border border-green-500/20 text-green-400 text-sm p-4 rounded-xl mb-6 text-center font-semibold">
                    A new OTP has been sent to your email.
                </div>
            <?php endif; ?>

            <!-- Dev Mode OTP Display -->
            <?php if(isset($_SESSION['dev_otp']['admin_login'])): ?>
                <div class="bg-yellow-500/10 border border-yellow-500/20 text-yellow-300 p-4 rounded-xl mb-6 text-sm text-center">
                    <p class="font-bold mb-1">🛠 Dev Mode OTP:</p>
                    <p class="font-black tracking-[0.4em] text-2xl"><?php echo htmlspecialchars($_SESSION['dev_otp']['admin_login']); ?></p>
                </div>
            <?php endif; ?>

            <!-- Info -->
            <div class="text-center mb-8">
                <p class="text-slate-400 text-sm leading-relaxed">
                    An OTP has been sent to<br>
                    <span class="text-white font-bold"><?php echo htmlspecialchars($email); ?></span>
                </p>
                <p class="text-slate-600 text-xs mt-2">Valid for 10 minutes</p>
            </div>

            <!-- OTP Form -->
            <form action="actions/verify_otp_action.php" method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="purpose" value="admin_login">

                <div>
                    <input type="text" name="otp" required
                        inputmode="numeric" autocomplete="one-time-code"
                        maxlength="6" pattern="[0-9]{6}"
                        oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,6); checkOtpLength(this)"
                        placeholder="000000"
                        class="otp-input w-full bg-slate-900/50 border-2 border-slate-700 focus:border-purple-500 rounded-2xl px-6 py-4 text-white font-black focus:outline-none transition">
                </div>

                <button type="submit" id="verify-btn"
                    class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-4 rounded-xl transition shadow-lg shadow-purple-500/20 flex items-center justify-center space-x-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span>Verify & Access Dashboard</span>
                </button>
            </form>

            <!-- Resend OTP -->
            <form action="actions/resend_otp_action.php" method="POST" class="mt-4 text-center">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="purpose" value="admin_login">
                <button type="submit" class="text-sm text-purple-400 hover:text-purple-300 hover:underline font-semibold transition">
                    Didn't receive OTP? Resend
                </button>
            </form>

            <!-- Cancel -->
            <div class="text-center mt-6 pt-6 border-t border-slate-700">
                <a href="admin_login.php" class="text-slate-500 hover:text-slate-300 text-sm transition">
                    &larr; Cancel &amp; Go Back to Login
                </a>
            </div>
        </div>

        <div class="text-center mt-6">
            <p class="text-slate-600 text-xs">🔒 This extra layer protects admin access</p>
        </div>
    </div>

<script>
    function checkOtpLength(input) {
        const btn = document.getElementById('verify-btn');
        if (input.value.length === 6) {
            btn.classList.remove('bg-purple-600', 'hover:bg-purple-700');
            btn.classList.add('bg-green-600', 'hover:bg-green-700');
        } else {
            btn.classList.remove('bg-green-600', 'hover:bg-green-700');
            btn.classList.add('bg-purple-600', 'hover:bg-purple-700');
        }
    }

    // Auto-focus OTP input
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelector('input[name="otp"]').focus();
    });

    // Auto-submit when 6 digits entered
    document.querySelector('input[name="otp"]').addEventListener('input', function() {
        if (this.value.length === 6) {
            setTimeout(() => this.closest('form').submit(), 300);
        }
    });
</script>
</body>
</html>
