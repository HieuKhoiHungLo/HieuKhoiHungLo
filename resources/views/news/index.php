<?php $title = ($category ? $category . ' - ' : 'Tin tức - ') . 'Hùng Vương University'; include __DIR__ . '/../layouts/header.php'; ?>

<!-- Abstract Background -->
<div class="fixed inset-0 z-0 pointer-events-none opacity-30">
    <div class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-red-50 to-transparent"></div>
    <div class="absolute -top-24 -right-24 w-96 h-96 bg-red-100 rounded-full blur-3xl opacity-50"></div>
</div>

<div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <!-- Breadcrumb -->
    <nav class="flex mb-8 text-xs font-bold uppercase tracking-widest text-gray-400 font-sans">
        <a href="<?= url('/') ?>" class="hover:text-hvu-red transition">Trang chủ</a>
        <span class="mx-3 text-gray-300">/</span>
        <span class="text-hvu-red"><?= $category ?: 'Tất cả tin tức' ?></span>
    </nav>

    <div class="flex items-center justify-between mb-12">
        <h1 class="text-3xl md:text-4xl font-black font-heading text-slate-900 uppercase tracking-tight">
            <?= $category ?: 'Tin tức & Sự kiện' ?>
        </h1>
        <div class="h-1 flex-grow ml-8 bg-gradient-to-r from-red-100 to-transparent rounded-full hidden md:block"></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
        <!-- Main Content -->
        <div class="lg:col-span-8">
            <?php if (empty($posts)): ?>
                <div class="bg-white rounded-3xl p-12 text-center border border-slate-100 shadow-sm">
                    <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-newspaper text-3xl text-slate-300"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Chưa có bài viết nào</h3>
                    <p class="text-slate-500">Vui lòng quay lại sau hoặc chọn chuyên mục khác.</p>
                </div>
            <?php else: ?>
                <div class="space-y-8">
                    <?php foreach ($posts as $p): ?>
                        <article class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden group hover:shadow-xl transition-all duration-500 flex flex-col md:flex-row">
                            <div class="md:w-1/3 relative overflow-hidden aspect-[4/3] md:aspect-auto md:h-64 bg-gray-100 flex-shrink-0">
                                <img loading="lazy" src="<?= $p['thumbnail'] ? (filter_var($p['thumbnail'], FILTER_VALIDATE_URL) ? $p['thumbnail'] : url('/' . $p['thumbnail'])) : url('/assets/img/Logo.png') ?>" 
                                     class="w-full h-full object-cover transform group-hover:scale-110 transition duration-700">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            </div>
                            <div class="md:w-2/3 p-6 md:p-8 flex flex-col justify-center">
                                <div class="flex items-center gap-4 mb-4">
                                    <span class="px-3 py-1 bg-red-50 text-red-600 rounded text-[10px] font-black uppercase tracking-widest">
                                        <?= htmlspecialchars($p['category']) ?>
                                    </span>
                                    <span class="text-[11px] text-slate-400 font-bold uppercase tracking-widest">
                                        <i class="far fa-calendar-alt mr-1.5"></i> <?= date('d/m/Y', strtotime($p['created_at'])) ?>
                                    </span>
                                </div>
                                <h2 class="text-xl md:text-2xl font-black font-heading text-slate-900 group-hover:text-hvu-red transition-colors mb-4 leading-tight">
                                    <a href="<?= url('/news/detail?slug=' . $p['slug']) ?>">
                                        <?= htmlspecialchars($p['title']) ?>
                                    </a>
                                </h2>
                                <p class="text-slate-600 text-sm line-clamp-2 leading-relaxed mb-6">
                                    <?= htmlspecialchars($p['summary'] ?: mb_substr(strip_tags($p['content']), 0, 160) . '...') ?>
                                </p>
                                <a href="<?= url('/news/detail?slug=' . $p['slug']) ?>" class="text-sm font-black text-hvu-red flex items-center group/link">
                                    Xem chi tiết <i class="fas fa-arrow-right ml-2 group-hover/link:translate-x-2 transition-transform"></i>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <!-- Simple Pagination if needed later -->
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <aside class="lg:col-span-4 space-y-6">
             <!-- CTA Card (Top) -->
             <div class="bg-gradient-to-br from-red-600 to-red-800 rounded-[1.5rem] p-6 text-white shadow-lg relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-12 -mt-12 blur-2xl group-hover:bg-white/20 transition duration-500"></div>
                <div class="relative z-10">
                    <div class="flex items-center gap-4 mb-5">
                        <div class="w-12 h-12 bg-white/20 backdrop-blur rounded-full flex items-center justify-center text-xl shadow-inner flex-shrink-0">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="text-left">
                            <h3 class="text-lg font-black font-heading leading-tight uppercase tracking-tight">Đợt ghi danh sớm</h3>
                            <p class="text-red-100 text-[10px] font-bold uppercase tracking-widest opacity-80 mt-0.5">Tuyển sinh <?= date('Y') ?></p>
                        </div>
                    </div>
                    <a href="<?= url('/register') ?>" class="block w-full py-3 bg-white text-red-700 rounded-xl font-black uppercase tracking-widest text-[12px] shadow-md hover:shadow-lg transition-all hover:-translate-y-0.5 text-center">
                        Đăng ký xét tuyển
                    </a>
                </div>
            </div>

            <!-- Recent News -->
            <div class="bg-white rounded-[1.5rem] shadow-sm border border-slate-100 p-6">
                <h3 class="text-base font-black font-heading text-slate-900 uppercase mb-5 flex items-center">
                    <span class="w-1.5 h-6 bg-hvu-red rounded-full mr-3"></span>
                    Tin mới nhất
                </h3>
                <div class="space-y-6">
                    <?php if (isset($recentPosts) && is_array($recentPosts)): ?>
                        <?php foreach ($recentPosts as $p): ?>
                            <a href="<?= url('/news/detail?slug=' . $p['slug']) ?>" class="group block">
                                <div class="flex items-start gap-4 mb-3">
                                    <div class="w-20 h-16 flex-shrink-0 rounded-lg overflow-hidden border border-slate-100 shadow-sm">
                                        <img src="<?= $p['thumbnail'] ? (filter_var($p['thumbnail'], FILTER_VALIDATE_URL) ? $p['thumbnail'] : url('/' . $p['thumbnail'])) : url('/assets/img/Logo.png') ?>" 
                                             class="w-full h-full object-cover transform group-hover:scale-110 transition duration-500">
                                    </div>
                                    <div class="flex-grow">
                                        <h4 class="text-[13px] font-bold text-slate-800 group-hover:text-hvu-red transition-colors line-clamp-2 leading-snug">
                                            <?= htmlspecialchars($p['title']) ?>
                                        </h4>
                                        <span class="text-[10px] text-green-600 font-bold tracking-widest mt-1 block">
                                            <?= date('d/m/Y', strtotime($p['created_at'])) ?>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Categories -->
            <div class="bg-white rounded-[1.5rem] shadow-sm border border-slate-100 p-6">
                <h3 class="text-base font-black font-heading text-slate-900 uppercase mb-5 flex items-center">
                    <span class="w-1.5 h-6 bg-slate-200 rounded-full mr-3"></span>
                    Chuyên mục
                </h3>
                <ul class="space-y-2">
                    <?php if (isset($categories) && is_array($categories)): ?>
                        <?php foreach ($categories as $cat): ?>
                            <li>
                                <a href="<?= url('/news?category=' . urlencode($cat['name'])) ?>" class="flex items-center justify-between p-3 rounded-xl <?= $category === $cat['name'] ? 'bg-red-50 text-hvu-red' : 'hover:bg-slate-50' ?> transition-all group">
                                    <span class="flex items-center text-sm font-bold <?= $category === $cat['name'] ? 'text-hvu-red' : 'text-slate-600' ?> group-hover:text-hvu-red">
                                        <i class="fas <?= $cat['name'] === 'Thông báo' ? 'fa-bell' : ($cat['name'] === 'Hướng dẫn' ? 'fa-book-open' : 'fa-newspaper') ?> <?= $category === $cat['name'] ? 'text-hvu-red' : 'text-slate-400' ?> group-hover:text-hvu-red mr-3 opacity-70"></i> 
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </span>
                                    <i class="fas fa-chevron-right text-[10px] <?= $category === $cat['name'] ? 'text-hvu-red' : 'text-slate-300' ?> group-hover:text-hvu-red group-hover:translate-x-1 transition-all"></i>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </aside>
    </div>
</div>

<style>
    .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
