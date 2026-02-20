<?php $title = $post['title'] . ' - Hùng Vương University'; include __DIR__ . '/../layouts/header.php'; ?>

<!-- Abstract Background (shared with Home but simplified) -->
<div class="fixed inset-0 z-0 pointer-events-none opacity-30">
    <div class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-red-50 to-transparent"></div>
    <div class="absolute -top-24 -right-24 w-96 h-96 bg-red-100 rounded-full blur-3xl opacity-50"></div>
</div>

<div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Breadcrumb -->
    <nav class="flex mb-8 text-xs font-bold uppercase tracking-widest text-gray-400 font-sans">
        <a href="<?= url('/') ?>" class="hover:text-hvu-red transition">Trang chủ</a>
        <span class="mx-3 text-gray-300">/</span>
        <a href="<?= url('/news') ?>" class="hover:text-hvu-red transition">Tin tức</a>
        <span class="mx-3 text-gray-300">/</span>
        <span class="text-hvu-red"><?= $post['category'] ?></span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
        <!-- Main Content (8/12) -->
        <article class="lg:col-span-8 bg-white rounded-[2rem] shadow-sm border border-gray-100 p-6 md:p-10">
            <header class="mb-10 text-center md:text-left">
                <div class="inline-block px-3 py-1 bg-red-50 text-hvu-red rounded-full text-xs font-black uppercase tracking-wider mb-4">
                    <?= $post['category'] ?>
                </div>
                <h1 class="text-3xl md:text-5xl font-black font-heading text-gray-900 leading-tight mb-6">
                    <?= htmlspecialchars($post['title']) ?>
                </h1>
                <div class="flex flex-wrap items-center justify-center md:justify-start gap-6 text-sm text-gray-500 font-medium font-sans">
                    <span class="flex items-center">
                        <i class="far fa-calendar-alt text-hvu-red mr-2"></i>
                        <?= date('d/m/Y', strtotime($post['created_at'])) ?>
                    </span>
                    <span class="flex items-center">
                        <i class="far fa-eye text-hvu-red mr-2"></i>
                        <?= number_format($post['view_count']) ?> lượt xem
                    </span>
                    <span class="flex items-center">
                        <i class="far fa-user text-hvu-red mr-2"></i>
                        Ban Tuyển sinh
                    </span>
                </div>
            </header>

            <?php if ($post['thumbnail']): ?>
                <div class="relative rounded-2xl overflow-hidden shadow-lg mb-12 group">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent z-10"></div>
                    <img loading="lazy" src="<?= $post['thumbnail'] ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="w-full h-auto object-cover transform group-hover:scale-105 transition duration-700">
                </div>
            <?php endif; ?>

            <div class="prose prose-lg max-w-none text-gray-700 font-medium font-sans leading-relaxed">
                <?= nl2br($post['content']) ?>
            </div>

            <!-- Share / Foot -->
            <div class="mt-16 pt-8 border-t border-gray-100 flex flex-col md:flex-row justify-between items-center gap-6">
                <div class="flex items-center space-x-4">
                    <span class="text-xs font-black uppercase tracking-widest text-gray-400">Chia sẻ bài viết:</span>
                    <div class="flex space-x-3">
                        <a href="#" class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-600 hover:text-white transition shadow-sm"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="w-8 h-8 rounded-full bg-sky-50 text-sky-500 flex items-center justify-center hover:bg-sky-500 hover:text-white transition shadow-sm"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="w-8 h-8 rounded-full bg-gray-50 text-gray-600 flex items-center justify-center hover:bg-gray-800 hover:text-white transition shadow-sm"><i class="fas fa-link"></i></a>
                    </div>
                </div>
                <a href="<?= url('/') ?>" class="px-6 py-3 bg-gray-50 text-gray-600 font-bold rounded-xl hover:bg-hvu-red hover:text-white transition flex items-center text-sm shadow-sm">
                    <i class="fas fa-arrow-left mr-2"></i> Quay lại trang chủ
                </a>
            </div>
        </article>

        <!-- Sidebar (4/12) -->
        <aside class="lg:col-span-4 space-y-8">
            <!-- CTA Card -->
            <div class="bg-gradient-to-br from-hvu-red to-red-800 rounded-[2rem] p-8 text-white shadow-xl relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16 blur-2xl group-hover:bg-white/20 transition duration-500"></div>
                <div class="relative z-10 text-center">
                    <div class="w-16 h-16 bg-white/20 backdrop-blur rounded-full flex items-center justify-center text-3xl mb-6 mx-auto">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3 class="text-2xl font-black font-heading mb-3 uppercase">Xét tuyển 2026</h3>
                    <p class="text-red-100 text-sm mb-8 font-medium font-sans">Đăng ký hồ sơ trực tuyến ngay hôm nay để nhận ưu tiên xét tuyển.</p>
                    
                    <a href="<?= url('/register') ?>" class="block w-full py-3.5 bg-white text-hvu-red rounded-xl font-black uppercase tracking-widest text-sm shadow-lg hover:shadow-xl hover:scale-105 transition transform mb-3">
                        Đăng ký ngay
                    </a>
                    <a href="<?= url('/login') ?>" class="block w-full py-3.5 bg-black/20 text-white border border-white/30 rounded-xl font-bold uppercase tracking-widest text-sm hover:bg-white/20 transition">
                        Tra cứu
                    </a>
                </div>
            </div>

            <!-- Recent News -->
            <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 p-6 md:p-8">
                <h3 class="text-lg font-black font-heading text-gray-900 uppercase border-l-4 border-hvu-red pl-3 mb-6">Tin tức mới nhất</h3>
                <div class="space-y-6">
                    <?php if (isset($recentPosts) && is_array($recentPosts)): ?>
                        <?php foreach ($recentPosts as $p): ?>
                            <?php if ($p['id'] == $post['id']) continue; ?>
                            <a href="<?= url('/news/detail?slug=' . $p['slug']) ?>" class="group flex items-start p-2 -mx-2 hover:bg-gray-50 rounded-xl transition">
                                <div class="w-20 h-20 flex-shrink-0 rounded-lg overflow-hidden relative">
                                    <img src="<?= $p['thumbnail'] ?: url('/assets/img/Logo.png') ?>" 
                                         class="w-full h-full object-cover transform group-hover:scale-110 transition duration-500">
                                </div>
                                <div class="ml-4 flex-1">
                                    <h4 class="text-sm font-bold font-heading text-gray-900 group-hover:text-hvu-red transition line-clamp-2 leading-snug">
                                        <?= htmlspecialchars($p['title']) ?>
                                    </h4>
                                    <span class="text-[10px] text-gray-400 uppercase font-bold mt-2 block tracking-wider font-sans">
                                        <i class="far fa-clock mr-1"></i> <?= date('d/m/Y', strtotime($p['created_at'])) ?>
                                    </span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
    </div>
</div>

<style>
    .prose p { margin-bottom: 1.5em; line-height: 1.8; }
    .prose h2 { font-family: 'Montserrat', sans-serif; font-weight: 800; font-size: 1.5em; margin-top: 2em; margin-bottom: 1em; color: #111827; }
    .prose h3 { font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 1.25em; margin-top: 1.5em; margin-bottom: 0.75em; color: #1F2937; }
    .prose ul { list-style-type: disc; padding-left: 1.5em; margin-bottom: 1.5em; }
    .prose li { margin-bottom: 0.5em; }
    .prose img { border-radius: 1rem; margin: 2rem 0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); }
    .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
        <!-- Main Content (8/12) -->
        <article class="lg:col-span-8">
            <nav class="flex mb-8 text-xs font-bold uppercase tracking-widest text-gray-400">
                <a href="<?= url('/') ?>" class="hover:text-hvu-red transition">Trang chủ</a>
                <span class="mx-2">/</span>
                <span class="text-gray-500"><?= $post['category'] ?></span>
            </nav>

            <header class="mb-10">
                <h1 class="text-4xl md:text-5xl font-black text-gray-900 leading-tight mb-6"><?= htmlspecialchars($post['title']) ?></h1>
                <div class="flex items-center space-x-6 text-sm text-gray-500 font-medium">
                    <span class="flex items-center">
                        <i class="far fa-calendar-alt text-hvu-red mr-2"></i>
                        <?= date('d/m/Y', strtotime($post['created_at'])) ?>
                    </span>
                    <span class="flex items-center">
                        <i class="far fa-eye text-hvu-red mr-2"></i>
                        <?= number_format($post['view_count']) ?> lượt xem
                    </span>
                </div>
            </header>

            <?php if ($post['thumbnail']): ?>
                <div class="rounded-3xl overflow-hidden shadow-2xl mb-12">
                    <img loading="lazy" src="<?= $post['thumbnail'] ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="w-full object-cover">
                </div>
            <?php endif; ?>

            <div class="prose prose-lg max-w-none text-gray-700 font-medium leading-relaxed">
                <?= nl2br($post['content']) ?>
            </div>

            <!-- Share / Foot -->
            <div class="mt-16 pt-8 border-t border-gray-100 flex justify-between items-center">
                <div class="flex space-x-4">
                    <span class="text-xs font-black uppercase tracking-widest text-gray-400">Chia sẻ:</span>
                    <a href="#" class="text-gray-400 hover:text-blue-600 transition"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-gray-400 hover:text-blue-400 transition"><i class="fab fa-twitter"></i></a>
                </div>
                <a href="<?= url('/') ?>" class="text-xs font-black uppercase tracking-widest text-hvu-red hover:underline">&larr; Quay lại trang chủ</a>
            </div>
        </article>

        <!-- Sidebar (4/12) -->
        <aside class="lg:col-span-4 space-y-10">
            <!-- Recent News -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
                <h3 class="text-xl font-black text-gray-800 uppercase italic mb-6">Tin tức mới nhất</h3>
                <div class="space-y-6">
                    <?php foreach ($recentPosts as $p): ?>
                        <?php if ($p['id'] == $post['id']) continue; ?>
                        <a href="<?= url('/news/detail?slug=' . $p['slug']) ?>" class="group flex items-start">
                            <img src="<?= $p['thumbnail'] ?: url('/assets/img/Logo.png') ?>" 
                                 class="w-20 h-16 rounded-xl object-contain bg-gray-50 p-1 shadow-sm group-hover:scale-105 transition duration-300">
                            <div class="ml-4">
                                <h4 class="text-sm font-bold text-gray-900 group-hover:text-hvu-red transition line-clamp-2"><?= htmlspecialchars($p['title']) ?></h4>
                                <span class="text-[10px] text-gray-400 uppercase font-bold mt-1 block tracking-wider"><?= date('d/m/Y', strtotime($p['created_at'])) ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- CTA Card -->
            <div class="bg-gradient-to-br from-red-600 to-hvu-red rounded-3xl p-8 text-white shadow-xl">
                <h3 class="text-2xl font-black mb-4 leading-tight">Sẵn sàng gia nhập HVU?</h3>
                <p class="text-red-100 text-sm mb-8 font-medium italic">Đừng bỏ lỡ cơ hội xét tuyển sớm nhất năm 2025.</p>
                <div class="space-y-3">
                    <a href="<?= url('/register') ?>" class="block w-full py-4 bg-white text-hvu-red text-center rounded-2xl font-black uppercase tracking-widest shadow-lg hover:shadow-2xl transition transform hover:-translate-y-1">
                        ĐĂNG KÝ NGAY
                    </a>
                    <a href="<?= url('/login') ?>" class="block w-full py-4 text-white text-center rounded-2xl font-black uppercase tracking-widest hover:bg-white/10 transition">
                        ĐĂNG NHẬP
                    </a>
                </div>
            </div>
        </aside>
    </div>
</div>

<style>
    .prose p { margin-bottom: 1.5em; }
    .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>

<?php include __DIR__ . '/../layouts/header.php'; ?>
