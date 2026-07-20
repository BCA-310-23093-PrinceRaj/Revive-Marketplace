<?php
session_start();

$email = $_SESSION['reset_verified_email'] ?? '';
$verified_at = (int)($_SESSION['reset_verified_at'] ?? 0);
if (!$email || $verified_at < time() - 900) {
    unset($_SESSION['reset_verified_email'], $_SESSION['reset_verified_at']);
    header('Location: forgot_password.php?error=verification_expired');
    exit();
}

include 'includes/header.php';

$errors = [
    'passwords_do_not_match' => 'The passwords do not match.',
    'weak_password' => 'Password must be at least 8 characters long.',
    'security_mismatch' => 'Security verification failed. Please try again.',
];
?>

<section class="py-40 px-6 max-w-xl mx-auto min-h-[80vh] flex flex-col justify-center">
    <div class="bg-white/5 border border-white/10 p-10 rounded-[3rem] shadow-2xl" data-aos="zoom-in">
        <h1 class="text-4xl font-bold tracking-tight mb-4">New <span class="neon-text">Password</span></h1>
        <p class="text-gray-400 mb-8 leading-relaxed">OTP verified for <span class="font-bold text-white"><?php echo htmlspecialchars($email); ?></span>. Enter your new password.</p>

        <?php if(isset($_GET['error'])): ?>
            <div class="mb-6 bg-red-500/10 border border-red-500/20 text-red-400 p-4 rounded-xl text-sm font-bold text-center">
                <?php echo htmlspecialchars($errors[$_GET['error']] ?? 'Unable to update password.'); ?>
            </div>
        <?php endif; ?>

        <form action="actions/reset_password_action.php" method="POST" class="space-y-6">
            <?php echo csrf_field(); ?>
            <div>
                <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-3 ml-1">New Password</label>
                <input type="password" name="password" required minlength="8" placeholder="Minimum 8 characters"
                    class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 focus:outline-none focus:border-[#bcff00] transition text-white">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-3 ml-1">Confirm New Password</label>
                <input type="password" name="confirm_password" required minlength="8" placeholder="Repeat new password"
                    class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 focus:outline-none focus:border-[#bcff00] transition text-white">
            </div>
            <button type="submit" class="neon-btn w-full font-bold py-4 rounded-2xl">Update Password</button>
        </form>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

