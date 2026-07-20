<?php 
include 'includes/header.php'; 

if(isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='index.php';</script>";
    exit();
}
?>

<section class="min-h-screen flex items-center justify-center px-6 py-20 relative overflow-hidden">
    <!-- Background Blurs -->
    <div class="absolute top-1/4 -left-20 w-80 h-80 bg-purple-600/10 rounded-full blur-[100px]"></div>
    <div class="absolute bottom-1/4 -right-20 w-80 h-80 bg-[#bcff00]/10 rounded-full blur-[100px]"></div>

    <div class="w-full max-w-md relative z-10" data-aos="fade-up">
        <div class="glass-nav p-8 rounded-3xl border border-white/10 shadow-2xl">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold tracking-tight mb-2">Welcome Back</h2>
                <p class="text-gray-400">Log in to your <span class="neon-text">Revive</span> account.</p>
            </div>

            <?php if(isset($_GET['error'])): ?>
                <div class="mb-6 bg-red-500/10 border border-red-500/20 text-red-400 p-4 rounded-xl text-sm font-bold text-center">
                    <?php
                    $wait = (int)($_GET['wait'] ?? 15);
                    $errors = [
                        'invalid_credentials' => 'Incorrect email or password.',
                        'user_not_found' => 'No account was found with that email.',
                        'email_not_verified' => 'Verify your email before signing in.',
                        'banned' => 'This account has been suspended.',
                        'security_mismatch' => 'Security verification failed. Please try again.',
                        'too_many_attempts' => "Too many failed attempts. Please wait {$wait} minute(s) before trying again.",
                    ];
                    echo htmlspecialchars($errors[$_GET['error']] ?? 'Unable to sign in.');
                    ?>
                </div>
            <?php elseif(($_GET['success'] ?? '') === 'password_reset'): ?>
                <div class="mb-6 bg-[#bcff00]/10 border border-[#bcff00]/20 text-[#bcff00] p-4 rounded-xl text-sm font-bold text-center">
                    Password updated successfully. You can now sign in.
                </div>
            <?php endif; ?>

            <form action="actions/auth_action.php" method="POST" class="space-y-6">
                <input type="hidden" name="action" value="login">
                <?php echo csrf_field(); ?>
                
                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-300">Email Address</label>
                    <input type="email" name="email" required
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 focus:outline-none focus:border-[#bcff00] transition text-white"
                        placeholder="name@example.com">
                </div>

                <div>
                    <div class="flex justify-between items-center mb-3">
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 ml-1">Password</label>
                        <a href="forgot_password.php" class="text-[10px] uppercase font-bold text-[#bcff00] hover:underline">Forgot?</a>
                    </div>
                    <div class="relative">
                        <input type="password" name="password" id="login-password" required
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 pr-12 focus:outline-none focus:border-[#bcff00] transition text-white"
                            placeholder="••••••••">
                        <button type="button" onclick="togglePasswordVisibility('login-password', this)" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white transition">
                            <svg class="w-5 h-5 eye-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            <svg class="w-5 h-5 eye-off-icon hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                        </button>
                    </div>
                </div>

                <button type="submit" 
                    class="neon-btn w-full font-bold py-4 rounded-xl text-lg mt-4">
                    Sign In
                </button>

                <p class="text-center text-gray-400 text-sm mt-6">
                    New to Revive? <a href="register.php" class="text-[#bcff00] hover:underline font-semibold">Create an account</a>
                </p>
            </form>
        </div>
    </div>
</section>

<script>
function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    const eyeIcon = btn.querySelector('.eye-icon');
    const eyeOffIcon = btn.querySelector('.eye-off-icon');
    
    if (input.type === 'password') {
        input.type = 'text';
        eyeIcon.classList.add('hidden');
        eyeOffIcon.classList.remove('hidden');
    } else {
        input.type = 'password';
        eyeIcon.classList.remove('hidden');
        eyeOffIcon.classList.add('hidden');
    }
}
</script>

<?php include 'includes/footer.php'; ?>
