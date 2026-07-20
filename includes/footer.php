<?php if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin'): ?>
<!-- Newsletter Section -->
<section class="py-24 px-6 border-t border-white/5 bg-black">
    <div class="max-w-7xl mx-auto">
        <div class="bg-gradient-to-r from-purple-900/20 to-[#bcff00]/10 p-12 md:p-20 rounded-[3rem] border border-white/10 relative overflow-hidden text-center" data-aos="zoom-in">
            <!-- Decorative Blur -->
            <div class="absolute -top-24 -right-24 w-64 h-64 bg-[#bcff00]/20 rounded-full blur-[100px]"></div>
            
            <h2 class="text-4xl md:text-5xl font-bold mb-6 relative z-10">Join the <span class="neon-text">Revive</span> Circle</h2>
            <p class="text-gray-400 text-lg mb-10 max-w-xl mx-auto relative z-10">Get early access to exclusive drops, sustainable fashion tips, and special offers.</p>
            
            <form class="max-w-md mx-auto flex flex-col sm:flex-row gap-4 relative z-10" onsubmit="event.preventDefault(); subscribeNewsletter(this);">
                <?php echo csrf_field(); ?>
                <input type="email" name="email" required placeholder="Enter your email" class="flex-1 bg-black/40 border border-white/10 rounded-2xl px-6 py-4 focus:outline-none focus:border-[#bcff00] transition">
                <button type="submit" class="neon-btn font-bold px-8 py-4 rounded-2xl whitespace-nowrap">Subscribe Now</button>
            </form>
            <script>
            function subscribeNewsletter(form) {
                const email = form.querySelector('input[name="email"]').value;
                const csrf = form.querySelector('input[name="csrf_token"]').value;
                fetch('actions/newsletter_action.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `email=${encodeURIComponent(email)}&csrf_token=${encodeURIComponent(csrf)}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('Thank you for subscribing!');
                        form.reset();
                    } else {
                        alert(data.error || 'Subscription failed.');
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('An error occurred. Please try again.');
                });
            }
            </script>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin'): ?>
<footer class="bg-black border-t border-white/10 py-12 px-6 mt-20">
        <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-12">
            <div class="col-span-1 md:col-span-2">
                <a href="index.php" class="text-3xl font-bold tracking-tighter mb-6 block">
                    REVIVE<span class="neon-text">.</span>
                </a>
                <p class="text-gray-400 max-w-sm mb-6">
                    The premium marketplace for second-hand fashion. Give your clothes a second life.
                </p>
                <div class="flex space-x-4">
                    <!-- Social Icons placeholders -->
                    <div class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center hover:bg-white/10 cursor-pointer transition">IG</div>
                    <div class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center hover:bg-white/10 cursor-pointer transition">TW</div>
                    <div class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center hover:bg-white/10 cursor-pointer transition">FB</div>
                </div>
            </div>
            
            <div>
                <h4 class="font-bold mb-6 text-white">Platform</h4>
                <ul class="space-y-4 text-gray-400">
                    <li><a href="info.php?page=how-it-works" class="hover:text-white transition">How it works</a></li>
                    <li><a href="#" class="hover:text-white transition">Selling Guide</a></li>
                    <li><a href="#" class="hover:text-white transition">Buying Guide</a></li>
                    <li><a href="#" class="hover:text-white transition">Trust & Safety</a></li>
                </ul>
            </div>

            <div>
                <h4 class="font-bold mb-6 text-white">Company</h4>
                <ul class="space-y-4 text-gray-400">
                    <li><a href="info.php?page=about" class="hover:text-white transition">About Us</a></li>
                    <li><a href="contact.php" class="hover:text-white transition">Contact Us</a></li>
                    <li><a href="info.php?page=terms" class="hover:text-white transition">Terms of Service</a></li>
                    <li><a href="info.php?page=privacy" class="hover:text-white transition">Privacy Policy</a></li>
                </ul>
            </div>
        </div>
        <div class="max-w-7xl mx-auto border-t border-white/5 mt-12 pt-8 text-center text-gray-500 text-sm">
            &copy; <?php echo date("Y"); ?> Revive Marketplace. Built for the future of fashion.
        </div>
    </footer>
<?php else: ?>
    <!-- Minimal Admin Footer -->
    <footer class="py-12 border-t border-white/5 mt-20 opacity-50">
        <div class="max-w-7xl mx-auto text-center text-[10px] uppercase tracking-[0.3em] font-bold text-gray-500">
            &copy; <?php echo date("Y"); ?> Revive Systems • Administrative Environment
        </div>
    </footer>
<?php endif; ?>

    <!-- AOS Script -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true,
            easing: 'ease-in-out',
        });
    </script>
</body>
</html>
