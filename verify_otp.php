<?php
session_start();
$purpose = $_GET['purpose'] ?? '';
if (!in_array($purpose, ['register', 'reset'], true)) {
    header('Location: login.php');
    exit();
}

$email = $_SESSION['otp_email'][$purpose] ?? '';
if (!$email) {
    header('Location: ' . ($purpose === 'register' ? 'register.php' : 'forgot_password.php'));
    exit();
}

include 'includes/header.php';

$error_messages = [
    'invalid_otp' => 'The OTP is incorrect.',
    'otp_expired' => 'This OTP has expired. Please request a new one.',
    'otp_not_found' => 'No active OTP request was found.',
    'too_many_attempts' => 'Too many incorrect attempts. Please request a new OTP.',
    'resend_wait' => 'Please wait 60 seconds before requesting another OTP.',
    'otp_send_failed' => 'The OTP email could not be sent. Check the SMTP configuration.',
    'security_mismatch' => 'Security verification failed. Please try again.',
];
?>

<section class="py-40 px-6 max-w-xl mx-auto min-h-[80vh] flex flex-col justify-center">
    <div class="bg-white/5 border border-white/10 p-10 rounded-[3rem] shadow-2xl relative overflow-hidden" data-aos="zoom-in">
        <div class="absolute -right-10 -top-10 w-40 h-40 bg-[#bcff00]/10 rounded-full blur-3xl"></div>
        <div class="relative z-10">
            <h1 class="text-4xl font-bold tracking-tight mb-4">Verify <span class="neon-text">Email OTP</span></h1>
            <p class="text-gray-400 mb-8 leading-relaxed">Enter the 6-digit OTP sent to <span class="text-white font-bold"><?php echo htmlspecialchars($email); ?></span>.</p>

            <?php if(isset($_GET['error'])): ?>
                <div class="mb-6 bg-red-500/10 border border-red-500/20 text-red-400 p-4 rounded-xl text-sm font-bold text-center">
                    <?php echo htmlspecialchars($error_messages[$_GET['error']] ?? 'Unable to verify OTP.'); ?>
                </div>
            <?php elseif(isset($_GET['success'])): ?>
                <div class="mb-6 bg-[#bcff00]/10 border border-[#bcff00]/20 text-[#bcff00] p-4 rounded-xl text-sm font-bold text-center">A new OTP has been sent.</div>
            <?php endif; ?>

            <?php if(isset($_SESSION['dev_otp'][$purpose])): ?>
                <div class="mb-6 bg-yellow-500/10 border border-yellow-500/20 text-yellow-300 p-4 rounded-xl text-sm text-center">
                    Development OTP: <span class="font-bold tracking-widest"><?php echo htmlspecialchars($_SESSION['dev_otp'][$purpose]); ?></span>
                </div>
            <?php endif; ?>

            <form action="actions/verify_otp_action.php" method="POST" class="space-y-6">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="purpose" value="<?php echo htmlspecialchars($purpose); ?>">
                <input type="text" name="otp" required inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,6)" placeholder="000000"
                    class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-center text-3xl tracking-[0.5em] font-bold focus:outline-none focus:border-[#bcff00] transition text-white">
                <button type="submit" class="neon-btn w-full font-bold py-4 rounded-2xl">Verify OTP</button>
            </form>

            <form action="actions/resend_otp_action.php" method="POST" class="mt-6 text-center">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="purpose" value="<?php echo htmlspecialchars($purpose); ?>">
                <button type="submit" class="text-sm text-[#bcff00] hover:underline font-bold">Resend OTP</button>
            </form>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
