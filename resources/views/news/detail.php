<?php $title = $post['title'] . ' - Hùng Vương University'; include __DIR__ . '/../layouts/header.php'; ?>

<!-- Abstract Background (shared with Home but simplified) -->
<div class="fixed inset-0 z-0 pointer-events-none opacity-30">
    <div class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-red-50 to-transparent"></div>
    <div class="absolute -top-24 -right-24 w-96 h-96 bg-red-100 rounded-full blur-3xl opacity-50"></div>
</div>

<div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
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
        <article class="lg:col-span-8 bg-white rounded-[1.5rem] shadow-sm border border-slate-100 p-6 md:p-10 lg:p-12">
            <header class="mb-8 border-b border-slate-100 pb-8">
                <a href="<?= url('/news?category=' . urlencode($post['category'])) ?>" class="inline-block px-3 py-1 bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition-colors rounded text-xs font-black uppercase tracking-wider mb-5">
                    <?= $post['category'] ?>
                </a>
                <h1 class="text-3xl md:text-4xl lg:text-5xl font-black font-heading text-slate-900 leading-tight mb-6">
                    <?php 
                        $displayTitle = $post['title'];
                        if (mb_strlen($displayTitle) > 5 && mb_strtoupper($displayTitle, 'UTF-8') === $displayTitle) {
                            $displayTitle = mb_convert_case($displayTitle, MB_CASE_LOWER, 'UTF-8');
                            $displayTitle = mb_strtoupper(mb_substr($displayTitle, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($displayTitle, 1, null, 'UTF-8');
                        }
                        echo htmlspecialchars($displayTitle);
                    ?>
                </h1>
                <div class="flex flex-wrap items-center gap-6 text-[13px] text-slate-500 font-medium font-sans mt-6">
                    <span class="flex items-center">
                        <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 mr-3 font-bold border border-slate-200">
                            HVU
                        </div>
                        <span class="text-slate-700 font-bold">Ban Tuyển sinh</span>
                    </span>
                    <span class="w-1 h-1 bg-slate-300 rounded-full hidden sm:block"></span>
                    <span class="flex items-center">
                        <i class="far fa-calendar-alt text-slate-400 mr-2"></i>
                        <?= date('d/m/Y', strtotime($post['created_at'])) ?>
                    </span>
                    <span class="w-1 h-1 bg-slate-300 rounded-full hidden sm:block"></span>
                    <span class="flex items-center">
                        <i class="far fa-eye text-slate-400 mr-2"></i>
                        <?= number_format($post['view_count']) ?> lượt xem
                    </span>
                </div>
            </header>

            <?php if (!empty($post['summary'])): ?>
                <div class="mb-8 text-lg md:text-xl font-medium text-slate-600 leading-relaxed italic border-l-4 border-slate-200 pl-6 py-2">
                    <?= htmlspecialchars($post['summary']) ?>
                </div>
            <?php endif; ?>

            <?php if ($post['thumbnail']): ?>
                <figure class="mb-10 group">
                    <div class="relative rounded-xl overflow-hidden shadow-sm bg-slate-100">
                        <img loading="lazy" src="<?= filter_var($post['thumbnail'], FILTER_VALIDATE_URL) ? $post['thumbnail'] : url('/' . $post['thumbnail']) ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="w-full h-auto object-cover transform group-hover:scale-105 transition duration-700">
                    </div>
                    <figcaption class="text-center text-xs text-slate-400 mt-3 font-medium uppercase tracking-wider">Ảnh minh họa cho bài viết</figcaption>
                </figure>
            <?php endif; ?>

            <div class="prose prose-lg max-w-none text-slate-700 font-sans leading-relaxed post-content">
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
                            <h3 class="text-lg font-black font-heading leading-tight uppercase tracking-tight">
                                <?= !empty($activeSession['ten_dot']) ? htmlspecialchars($activeSession['ten_dot']) : 'Đợt ghi danh sớm' ?>
                            </h3>
                            <p class="text-red-100 text-[10px] font-bold uppercase tracking-widest opacity-80 mt-0.5">Tuyển sinh <?= date('Y') ?></p>
                        </div>
                    </div>
                    
                    <a href="<?= url('/register') ?>" class="block w-full py-3 bg-white text-red-700 rounded-xl font-black uppercase tracking-widest text-[12px] shadow-md hover:shadow-lg transition-all hover:-translate-y-0.5 text-center">
                        Đăng ký xét tuyển
                    </a>
                </div>
            </div>

            <!-- Recent News (Middle) -->
            <div class="bg-white rounded-[1.5rem] shadow-sm border border-slate-100 p-6">
                <h3 class="text-base font-black font-heading text-slate-900 uppercase mb-5 flex items-center">
                    <span class="w-1.5 h-6 bg-hvu-red rounded-full mr-3"></span>
                    Tin mới nhất
                </h3>
                <div class="space-y-6">
                    <?php if (isset($recentPosts) && is_array($recentPosts)): ?>
                        <?php 
                            $count = 0;
                            foreach ($recentPosts as $p): 
                                if ($p['id'] == $post['id']) continue;
                                if ($count >= 5) break; // Limit to 5
                                $count++;
                        ?>
                            <a href="<?= url('/news/detail?slug=' . $p['slug']) ?>" class="group block">
                                <div class="flex items-start gap-4 mb-3">
                                    <div class="w-20 h-16 flex-shrink-0 rounded-lg overflow-hidden border border-slate-100 shadow-sm">
                                        <img src="<?= $p['thumbnail'] ? (filter_var($p['thumbnail'], FILTER_VALIDATE_URL) ? $p['thumbnail'] : url('/' . $p['thumbnail'])) : url('/assets/img/Logo.png') ?>" 
                                             class="w-full h-full object-cover transform group-hover:scale-110 transition duration-500">
                                    </div>
                                    <div class="flex-grow">
                                        <h4 class="text-[13px] font-bold text-slate-800 group-hover:text-hvu-red transition-colors line-clamp-2 leading-snug">
                                            <?php 
                                                $pTitle = $p['title'];
                                                if (mb_strlen($pTitle) > 5 && mb_strtoupper($pTitle, 'UTF-8') === $pTitle) {
                                                    $pTitle = mb_convert_case($pTitle, MB_CASE_LOWER, 'UTF-8');
                                                    $pTitle = mb_strtoupper(mb_substr($pTitle, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($pTitle, 1, null, 'UTF-8');
                                                }
                                                echo htmlspecialchars($pTitle);
                                            ?>
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

            <!-- Categories (Bottom) -->
            <div class="bg-white rounded-[1.5rem] shadow-sm border border-slate-100 p-6">
                <h3 class="text-base font-black font-heading text-slate-900 uppercase mb-5 flex items-center">
                    <span class="w-1.5 h-6 bg-slate-200 rounded-full mr-3"></span>
                    Chuyên mục
                </h3>
                <ul class="space-y-2">
                    <?php if (isset($categories) && is_array($categories)): ?>
                        <?php foreach ($categories as $cat): ?>
                            <li>
                                <a href="<?= url('/news?category=' . urlencode($cat['name'])) ?>" class="flex items-center justify-between p-3 rounded-xl hover:bg-slate-50 transition-all group">
                                    <span class="flex items-center text-sm font-bold text-slate-600 group-hover:text-hvu-red">
                                        <i class="fas <?= $cat['name'] === 'Thông báo' ? 'fa-bell' : ($cat['name'] === 'Hướng dẫn' ? 'fa-book-open' : 'fa-newspaper') ?> text-slate-400 group-hover:text-hvu-red mr-3 opacity-70"></i> 
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </span>
                                    <i class="fas fa-chevron-right text-[10px] text-slate-300 group-hover:text-hvu-red group-hover:translate-x-1 transition-all"></i>
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
    /* Professional Editorial Typography */
    .post-content {
        font-size: 1.125rem; /* 18px */
        line-height: 1.8;
        color: #334155;
    }
    .post-content p { 
        margin-bottom: 1.5em; 
    }
    .post-content h2, .post-content h3, .post-content h4 { 
        font-family: 'Montserrat', sans-serif; 
        color: #0f172a; 
        line-height: 1.4;
    }
    .post-content h2 { font-weight: 800; font-size: 1.875rem; margin-top: 2em; margin-bottom: 1em; }
    .post-content h3 { font-weight: 800; font-size: 1.5rem; margin-top: 1.5em; margin-bottom: 0.75em; }
    .post-content h4 { font-weight: 700; font-size: 1.25rem; margin-top: 1.5em; margin-bottom: 0.5em; }
    .post-content ul { list-style-type: none; padding-left: 0; margin-bottom: 1.5em; }
    .post-content ul li { position: relative; padding-left: 1.5em; margin-bottom: 0.5em; }
    .post-content ul li::before {
        content: "•";
        position: absolute;
        left: 0;
        color: #ef4444; /* red-500 */
        font-weight: bold;
        font-size: 1.2em;
        line-height: 1;
    }
    .post-content ol { padding-left: 1.5em; margin-bottom: 1.5em; }
    .post-content ol li { margin-bottom: 0.5em; }
    .post-content img { 
        border-radius: 0.75rem; 
        margin: 2rem auto; 
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); 
        max-width: 100%;
        display: block;
    }
    .post-content a { color: #2563eb; text-decoration: none; border-bottom: 1px solid transparent; transition: all 0.2s; }
    .post-content a:hover { border-bottom-color: #2563eb; }
    .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
