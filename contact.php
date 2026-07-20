<?php 
include 'includes/header.php'; 
?>

<section class="py-24 px-6 max-w-4xl mx-auto">
    <div class="mb-12" data-aos="fade-right">
        <h1 class="text-5xl font-bold tracking-tight mb-4">Get in <span class="neon-text">Touch</span></h1>
        <p class="text-gray-400 text-lg">Have a question or feedback? We'd love to hear from you.</p>
    </div>

    <?php if(isset($_GET['success'])): ?>
    <div class="mb-8 bg-[#bcff00]/10 border border-[#bcff00]/20 text-[#bcff00] p-6 rounded-2xl font-bold">
        Message sent successfully! We will get back to you soon.
    </div>
    <?php endif; ?>

    <?php if(isset($_GET['error'])): ?>
    <div class="mb-8 bg-red-500/10 border border-red-500/20 text-red-500 p-6 rounded-2xl font-bold">
        Error: <?php echo htmlspecialchars($_GET['error']); ?>. Please try again.
    </div>
    <?php endif; ?>

    <form action="actions/contact_action.php" method="POST" class="space-y-8" data-aos="fade-up">
        <?php echo csrf_field(); ?>
        
        <div>
            <label class="block text-sm font-bold uppercase tracking-widest text-gray-500 mb-3">Your Name</label>
            <input type="text" name="name" required maxlength="100" pattern="[A-Za-z .'-]+" title="Name can only contain letters, spaces, apostrophes, periods, and hyphens" oninput="this.value=this.value.replace(/[0-9]/g,'')" placeholder="John Doe"
                class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 focus:outline-none focus:border-[#bcff00] transition text-white">
        </div>

        <div>
            <label class="block text-sm font-bold uppercase tracking-widest text-gray-500 mb-3">Email Address</label>
            <input type="email" name="email" required placeholder="john@example.com"
                class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 focus:outline-none focus:border-[#bcff00] transition text-white">
        </div>

        <div>
            <label class="block text-sm font-bold uppercase tracking-widest text-gray-500 mb-3">Message</label>
            <textarea name="message" rows="6" required placeholder="Type your message here..."
                class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 focus:outline-none focus:border-[#bcff00] transition text-gray-300"></textarea>
        </div>

        <button type="submit" class="neon-btn w-full font-bold py-5 rounded-2xl text-xl shadow-lg transform hover:scale-[1.01] active:scale-[0.99] transition">
            Send Message
        </button>
    </form>
</section>

<?php include 'includes/footer.php'; ?>
