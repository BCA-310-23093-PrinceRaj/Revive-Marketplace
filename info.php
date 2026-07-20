<?php 
include 'includes/header.php'; 

$page = $_GET['page'] ?? 'about';

$content = [
    'about' => [
        'title' => 'About <span class="neon-text">Us</span>',
        'text' => 'Revive is the premium marketplace for second-hand fashion. We believe in giving clothes a second life and promoting sustainable fashion. Our platform connects buyers and sellers who care about style and the environment.'
    ],
    'terms' => [
        'title' => 'Terms of <span class="neon-text">Service</span>',
        'text' => 'By using Revive, you agree to abide by our terms and conditions. We expect all users to be respectful, honest, and follow our community guidelines. Transactions are final unless disputed within 48 hours.'
    ],
    'privacy' => [
        'title' => 'Privacy <span class="neon-text">Policy</span>',
        'text' => 'We value your privacy. We only collect data necessary to process your orders and improve your experience. We never sell your personal information to third parties.'
    ],
    'how-it-works' => [
        'title' => 'How it <span class="neon-text">Works</span>',
        'text' => '1. List your item with clear photos and details.<br>2. Wait for a buyer to purchase.<br>3. Ship the item and get paid securely!'
    ]
];

$page_data = $content[$page] ?? $content['about'];
?>

<section class="py-24 px-6 max-w-4xl mx-auto">
    <div class="mb-12" data-aos="fade-right">
        <h1 class="text-5xl font-bold tracking-tight mb-6"><?php echo $page_data['title']; ?></h1>
        <div class="text-gray-400 text-lg leading-relaxed">
            <?php echo $page_data['text']; ?>
        </div>
    </div>
    <a href="shop.php" class="neon-btn px-8 py-3 rounded-xl font-bold inline-block mt-8">Explore Collection</a>
</section>

<?php include 'includes/footer.php'; ?>
