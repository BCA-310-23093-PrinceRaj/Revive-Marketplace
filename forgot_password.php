<?php 
require_once 'config/db.php';
include 'includes/header.php'; 
?>

<section class="py-40 px-6 max-w-xl mx-auto min-h-[80vh] flex flex-col justify-center">
    <div class="bg-white/5 border border-white/10 p-10 rounded-[3rem] shadow-2xl relative overflow-hidden" data-aos="zoom-in">
        <!-- Decoration -->
        <div class="absolute -right-10 -top-10 w-40 h-40 bg-[#bcff00]/10 rounded-full blur-3xl"></div>
        
        <div class="relative z-10">
            <h1 class="text-4xl font-bold tracking-tight mb-4">Reset <span class="neon-text">Password</span></h1>
            <p class="text-gray-400 mb-8 leading-relaxed">Enter your registered email address and we'll send you a secure 6-digit OTP.</p>

            <?php if(isset($_GET['error'])): ?>
                <div class="mb-6 bg-red-500/10 border border-red-500/20 text-red-400 p-4 rounded-xl text-sm font-bold text-center">
                    <?php
                    $errors = [
                        'email_not_found' => 'No account was found with that email.',
                        'invalid_email' => 'Please enter a valid email address.',
                        'otp_send_failed' => 'The OTP email could not be sent. Check the SMTP configuration.',
                        'verification_expired' => 'Your password-reset verification expired. Request a new OTP.',
                        'security_mismatch' => 'Security verification failed. Please try again.',
                    ];
                    echo htmlspecialchars($errors[$_GET['error']] ?? 'Unable to process your request.');
                    ?>
                </div>
            <?php endif; ?>

            <form action="actions/reset_request_action.php" method="POST" class="space-y-6">
                <?php echo csrf_field(); ?>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-3 ml-1">Email Address</label>
                    <input type="email" name="email" required placeholder="name@example.com"
                        class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 focus:outline-none focus:border-[#bcff00] transition text-white">
                </div>

                <button type="submit" class="neon-btn w-full font-bold py-4 rounded-2xl shadow-lg shadow-[#bcff00]/10 hover:scale-[1.02] transition transform">
                    Send Reset OTP
                </button>
            </form>

            <div class="mt-8 text-center">
                <a href="login.php" class="text-sm text-gray-500 hover:text-white transition">Back to Login</a>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
