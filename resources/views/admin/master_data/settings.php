<?php $title = 'Cấu hình hệ thống - Admin'; ?>
<?php ob_start(); ?>

<div class="max-w-4xl mx-auto p-4 md:p-8">
    <header class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <nav class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 flex items-center">
                <a href="<?= url('/admin/master-data') ?>" class="hover:text-[#0066FF] transition">Danh mục</a>
                <i class="fas fa-chevron-right mx-2 opacity-50 text-[8px]"></i>
                <span class="text-slate-800">Cấu hình hệ thống</span>
            </nav>
            <h2 class="text-3xl font-black text-slate-800 uppercase font-heading">Thiết lập hệ thống</h2>
        </div>
    </header>

    <?php if (isset($_GET['updated'])): ?>
        <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 p-4 mb-8 rounded-2xl shadow-sm flex items-center animate-bounce" style="animation-iteration-count: 2;">
            <i class="fas fa-check-circle mr-3"></i>
            <span class="font-bold">Cập nhật cấu hình thành công!</span>
        </div>
    <?php endif; ?>

    <div class="max-w-4xl">
        <form action="<?= url('/admin/master-data/settings/save') ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= (string) $this->csrfToken() ?>">

            <div class="bg-white rounded-[2.5rem] shadow-xl border border-slate-100 overflow-hidden">
                <div class="p-8 border-b border-slate-50 bg-slate-50/50">
                    <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight flex items-center">
                        <i class="fas fa-sliders-h mr-3 text-[#0066FF]"></i>
                        Tham số quy trình nộp hồ sơ
                    </h3>
                </div>

                <div class="p-8 space-y-8">
                    <!-- Step 4 Toggle -->
                    <?php
                    $enableTHPT = '0';
                    foreach ($settings as $s) {
                        if ($s['key'] === 'enable_thpt_step') {
                            $enableTHPT = $s['value'];
                            break;
                        }
                    }
                    ?>
                    <div class="flex items-center justify-between p-7 rounded-[2rem] bg-slate-50 border border-slate-100 hover:border-[#0066FF]/20 transition group">
                        <div class="flex items-start">
                            <div class="w-14 h-14 rounded-2xl bg-white shadow-sm flex items-center justify-center text-[#0066FF] mr-6 flex-shrink-0 group-hover:scale-110 transition">
                                <i class="fas fa-graduation-cap text-2xl"></i>
                            </div>
                            <div>
                                <h4 class="font-black text-slate-800 mb-1">Hiển thị Bước 4 (Nhập điểm thi THPT)</h4>
                                <p class="text-xs text-slate-500 max-w-md leading-relaxed">Bật tính năng này để thí sinh có thể nhập điểm thi THPT năm 2026. Nếu tắt, hệ thống sẽ ẩn bước này để rút ngắn quy trình đăng ký.</p>
                            </div>
                        </div>
                        <div class="relative inline-block w-14 h-8 transition duration-200 ease-in">
                            <input type="hidden" name="settings[enable_thpt_step]" value="0">
                            <input type="checkbox" name="settings[enable_thpt_step]" id="enable_thpt" value="1" <?= $enableTHPT == '1' ? 'checked' : '' ?>
                                class="absolute block w-8 h-8 rounded-full bg-white border-4 appearance-none cursor-pointer z-10 transition-all right-6 checked:right-0 checked:border-[#0066FF] outline-none shadow-sm shadow-black/10">
                            <label for="enable_thpt" class="block overflow-hidden h-8 rounded-full bg-slate-200 cursor-pointer transition-colors"></label>
                        </div>
                    </div>

                    <!-- External Redirection Toggle & URL -->
                    <?php
                    $redirectExternalEnable = '0';
                    $redirectExternalUrl = '';
                    foreach ($settings as $s) {
                        if ($s['key'] === 'redirect_external_enable') {
                            $redirectExternalEnable = $s['value'];
                        }
                        if ($s['key'] === 'redirect_external_url') {
                            $redirectExternalUrl = $s['value'];
                        }
                    }
                    ?>
                    <div class="flex flex-col p-7 rounded-[2rem] bg-slate-50 border border-slate-100 hover:border-[#0066FF]/20 transition group">
                        <div class="flex items-center justify-between">
                            <div class="flex items-start">
                                <div class="w-14 h-14 rounded-2xl bg-white shadow-sm flex items-center justify-center text-orange-500 mr-6 flex-shrink-0 group-hover:scale-110 transition">
                                    <i class="fas fa-external-link-alt text-2xl"></i>
                                </div>
                                <div>
                                    <h4 class="font-black text-slate-800 mb-1">Chuyển hướng Đăng ký / Đăng nhập ngoài</h4>
                                    <p class="text-xs text-slate-500 max-w-md leading-relaxed">Bật tính năng này để chuyển hướng thí sinh đăng ký hoặc đăng nhập sang cổng thông tin bên ngoài (ví dụ: Cổng thông tin thi tốt nghiệp THPT của Bộ GD&ĐT).</p>
                                </div>
                            </div>
                            <div class="relative inline-block w-14 h-8 transition duration-200 ease-in flex-shrink-0">
                                <input type="hidden" name="settings[redirect_external_enable]" value="0">
                                <input type="checkbox" name="settings[redirect_external_enable]" id="redirect_external_enable" value="1" <?= $redirectExternalEnable == '1' ? 'checked' : '' ?>
                                    class="absolute block w-8 h-8 rounded-full bg-white border-4 appearance-none cursor-pointer z-10 transition-all right-6 checked:right-0 checked:border-orange-500 outline-none shadow-sm shadow-black/10">
                                <label for="redirect_external_enable" class="block overflow-hidden h-8 rounded-full bg-slate-200 cursor-pointer transition-colors"></label>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-slate-100">
                            <label class="block">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2 mb-2 block">Liên kết chuyển hướng ngoài (URL)</span>
                                <input type="url" name="settings[redirect_external_url]" value="<?= htmlspecialchars($redirectExternalUrl) ?>"
                                    class="mt-2 block w-full rounded-[1.5rem] bg-white border border-slate-200 px-6 py-4 text-sm font-medium focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 outline-none transition shadow-inner"
                                    placeholder="Ví dụ: https://thisinh.thitotnghiepthpt.edu.vn/">
                            </label>
                        </div>
                    </div>

                    <!-- Home Announcement -->
                    <?php
                    $announcement = '';
                    foreach ($settings as $s) {
                        if ($s['key'] === 'home_announcement') {
                            $announcement = $s['value'];
                            break;
                        }
                    }
                    ?>
                    <div class="space-y-4">
                        <label class="block">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2 mb-2 block">Dòng chữ thông báo chạy (Trang chủ)</span>
                            <textarea name="settings[home_announcement]" rows="4"
                                class="mt-2 block w-full rounded-[2rem] bg-slate-50 border border-slate-200 px-8 py-5 text-sm font-medium focus:border-[#0066FF] focus:bg-white focus:ring-4 focus:ring-[#0066FF]/10 outline-none transition shadow-inner"
                                placeholder="Hãy nhập nội dung thông báo sẽ hiển thị chạy trên trang chủ..."><?= htmlspecialchars($announcement) ?></textarea>
                            <p class="text-[10px] text-slate-400 mt-2 ml-4 italic">Dòng chữ này sẽ hiển thị dưới dạng thanh chạy (marquee) ngay bên dưới menu chính của trang chủ.</p>
                        </label>
                    </div>

                    <!-- Email Retention Days -->
                    <?php
                    $retentionDays = '10';
                    foreach ($settings as $s) {
                        if ($s['key'] === 'email_retention_days') {
                            $retentionDays = $s['value'];
                            break;
                        }
                    }
                    ?>
                    <div class="space-y-4">
                        <label class="block">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2 mb-2 block">Số ngày lưu trữ email đã gửi</span>
                            <input type="number" name="settings[email_retention_days]" value="<?= htmlspecialchars($retentionDays) ?>" min="1" max="365"
                                class="mt-2 block w-full rounded-[2rem] bg-slate-50 border border-slate-200 px-8 py-5 text-sm font-medium focus:border-[#0066FF] focus:bg-white focus:ring-4 focus:ring-[#0066FF]/10 outline-none transition shadow-inner"
                                placeholder="Ví dụ: 10">
                            <p class="text-[10px] text-slate-400 mt-2 ml-4 italic">Các email có trạng thái "đã gửi" (sent) vượt quá số ngày này sẽ tự động được dọn dẹp hàng ngày và cộng dồn vào thống kê.</p>
                        </label>
                    </div>

                    <!-- Master Password -->
                    <?php
                    $masterPassword = '';
                    foreach ($settings as $s) {
                        if ($s['key'] === 'master_password') {
                            $masterPassword = $s['value'];
                            break;
                        }
                    }
                    ?>
                    <div class="space-y-4">
                        <label class="block">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2 mb-2 block">Mật khẩu chung cho Cán bộ (Master Password)</span>
                            <input type="text" name="settings[master_password]" value="<?= htmlspecialchars($masterPassword) ?>"
                                class="mt-2 block w-full rounded-[2rem] bg-slate-50 border border-slate-200 px-8 py-5 text-sm font-medium focus:border-[#0066FF] focus:bg-white focus:ring-4 focus:ring-[#0066FF]/10 outline-none transition shadow-inner"
                                placeholder="Ví dụ: hvu@master2026">
                            <p class="text-[10px] text-slate-400 mt-2 ml-4 italic">Mật khẩu này cho phép cán bộ tuyển sinh đăng nhập vào bất kỳ tài khoản thí sinh nào mà không cần biết mật khẩu thật của họ (nếu để trống, hệ thống sẽ sử dụng mật khẩu mặc định hoặc cấu hình trong file .env).</p>
                        </label>
                    </div>
                </div>
            </div>

                <div class="px-8 py-10 bg-slate-50 border-t border-slate-100 flex justify-end">
                    <button type="submit" class="px-12 py-5 bg-[#0066FF] text-white font-black rounded-2xl shadow-2xl shadow-blue-200 hover:bg-blue-700 transition transform hover:-translate-y-1 active:scale-95 uppercase tracking-widest text-xs">
                        <i class="fas fa-save mr-2"></i> Lưu cấu hình
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
    /* Styling Switch toggle check */
    #enable_thpt:checked+label {
        background-color: #0066FF;
    }
    #countdown_toggle:checked+label {
        background-color: #f97316;
    }
    #redirect_external_enable:checked+label {
        background-color: #f97316;
    }
</style>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
?>