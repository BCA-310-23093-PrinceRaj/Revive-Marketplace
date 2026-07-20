<?php
http_response_code(404);
include 'includes/header.php';
?>

<section class="py-24 px-6 min-h-[70vh] flex items-center justify-center text-center">
    <div data-aos="zoom-in">
        <div class="inline-flex items-center space-x-2 bg-red-500/10 border border-red-500/20 text-red-500 text-xs font-bold uppercase tracking-widest px-5 py-2 rounded-full mb-8">
            <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
            <span>Error 404</span>
        </div>
        
        <h1 class="text-8xl md:text-9xl font-black tracking-tighter leading-none mb-6 relative group">
            <span class="text-white relative z-10">404</span>
            <span class="absolute top-0 left-0 text-[#bcff00] -translate-x-1 translate-y-1 -z-10 opacity-0 group-hover:opacity-100 transition duration-300">404</span>
            <span class="absolute top-0 left-0 text-red-500 translate-x-1 -translate-y-1 -z-10 opacity-0 group-hover:opacity-100 transition duration-300">404</span>
        </h1>
        
        <h2 class="text-3xl font-bold mb-4">Page Not <span class="neon-text italic">Found</span></h2>
        
        <p class="text-gray-400 text-lg max-w-md mx-auto mb-10">
            The style you're looking for seems to have vanished from our collection. It might have been moved or deleted.
        </p>
        
        <a href="index.php" class="neon-btn font-bold px-10 py-4 rounded-full text-sm tracking-widest uppercase transition inline-block">
            Take Me Back Home
        </a>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
