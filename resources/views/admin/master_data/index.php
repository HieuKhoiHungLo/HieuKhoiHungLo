<?php $title = 'Quản lý danh mục - Admin'; ?>
<?php ob_start(); ?>

<div class="max-w-7xl mx-auto p-4 md:p-8">
    <header class="mb-10 text-center md:text-left">
        <h2 class="text-3xl font-black text-slate-800 uppercase font-heading tracking-tight">Quản lý danh mục</h2>
        <p class="text-slate-500 mt-2 font-medium">Thiết lập dữ liệu nền cho hệ thống tuyển sinh</p>
    </header>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
        <!-- Major Management -->
        <a href="<?= url('/admin/master-data/majors') ?>" class="bg-white p-10 rounded-[2.5rem] shadow-sm border border-slate-100 hover:shadow-2xl hover:border-[#0066FF]/20 transition-all duration-300 group relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-50/50 rounded-bl-full -mr-16 -mt-16 group-hover:bg-[#0066FF]/5 transition-colors"></div>
            <div class="w-16 h-16 rounded-2xl bg-indigo-50 text-[#0066FF] flex items-center justify-center mb-8 shadow-inner group-hover:bg-[#0066FF] group-hover:text-white group-hover:rotate-6 transition-all duration-500">
                <i class="fas fa-graduation-cap text-2xl"></i>
            </div>
            <h3 class="text-2xl font-black text-slate-800 mb-4 font-heading group-hover:text-[#0066FF] transition">Ngành học</h3>
            <p class="text-slate-500 text-sm leading-relaxed font-medium">Quản lý danh sách ngành tuyển sinh, mã ngành và các tổ hợp xét tuyển tương ứng.</p>
            <div class="mt-8 flex items-center text-xs font-black text-[#0066FF] uppercase tracking-widest group-hover:text-[#0066FF]">
                Truy cập ngay <i class="fas fa-arrow-right ml-2 group-hover:translate-x-2 transition-transform"></i>
            </div>
        </a>

        <!-- Session Management -->
        <a href="<?= url('/admin/master-data/sessions') ?>" class="bg-white p-10 rounded-[2.5rem] shadow-sm border border-slate-100 hover:shadow-2xl hover:border-[#0066FF]/20 transition-all duration-300 group relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-amber-50/50 rounded-bl-full -mr-16 -mt-16 group-hover:bg-[#0066FF]/5 transition-colors"></div>
            <div class="w-16 h-16 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mb-8 shadow-inner group-hover:bg-[#0066FF] group-hover:text-white group-hover:-rotate-6 transition-all duration-500">
                <i class="fas fa-calendar-alt text-2xl"></i>
            </div>
            <h3 class="text-2xl font-black text-slate-800 mb-4 font-heading group-hover:text-[#0066FF] transition">Đợt tuyển sinh</h3>
            <p class="text-slate-500 text-sm leading-relaxed font-medium">Thiết lập thời gian bắt đầu, kết thúc các đợt xét tuyển và kích hoạt đợt hiện tại.</p>
            <div class="mt-8 flex items-center text-xs font-black text-amber-600 uppercase tracking-widest group-hover:text-[#0066FF]">
                Truy cập ngay <i class="fas fa-arrow-right ml-2 group-hover:translate-x-2 transition-transform"></i>
            </div>
        </a>

        <!-- School Management -->
        <a href="<?= url('/admin/master-data/schools') ?>" class="bg-white p-10 rounded-[2.5rem] shadow-sm border border-slate-100 hover:shadow-2xl hover:border-[#0066FF]/20 transition-all duration-300 group relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-50/50 rounded-bl-full -mr-16 -mt-16 group-hover:bg-[#0066FF]/5 transition-colors"></div>
            <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-8 shadow-inner group-hover:bg-[#0066FF] group-hover:text-white group-hover:scale-110 transition-all duration-500">
                <i class="fas fa-school text-2xl"></i>
            </div>
            <h3 class="text-2xl font-black text-slate-800 mb-4 font-heading group-hover:text-[#0066FF] transition">Trường THPT</h3>
            <p class="text-slate-500 text-sm leading-relaxed font-medium">Danh mục các trường THPT trên toàn quốc để thí sinh lựa chọn khi nhập liệu học bạ.</p>
            <div class="mt-8 flex items-center text-xs font-black text-emerald-600 uppercase tracking-widest group-hover:text-[#0066FF]">
                Truy cập ngay <i class="fas fa-arrow-right ml-2 group-hover:translate-x-2 transition-transform"></i>
            </div>
        </a>

        <!-- Combination Management -->
        <a href="<?= url('/admin/master-data/combinations') ?>" class="bg-white p-10 rounded-[2.5rem] shadow-sm border border-slate-100 hover:shadow-2xl hover:border-[#0066FF]/20 transition-all duration-300 group relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-blue-50/50 rounded-bl-full -mr-16 -mt-16 group-hover:bg-[#0066FF]/5 transition-colors"></div>
            <div class="w-16 h-16 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mb-8 shadow-inner group-hover:bg-[#0066FF] group-hover:text-white group-hover:rotate-6 transition-all duration-500">
                <i class="fas fa-layer-group text-2xl"></i>
            </div>
            <h3 class="text-2xl font-black text-slate-800 mb-4 font-heading group-hover:text-[#0066FF] transition">Tổ hợp môn</h3>
            <p class="text-slate-500 text-sm leading-relaxed font-medium">Quản lý các tổ hợp xét tuyển truyền thống (A00, D01...) và các môn học thành phần.</p>
            <div class="mt-8 flex items-center text-xs font-black text-blue-600 uppercase tracking-widest group-hover:text-[#0066FF]">
                Truy cập ngay <i class="fas fa-arrow-right ml-2 group-hover:translate-x-2 transition-transform"></i>
            </div>
        </a>

        <!-- System Settings -->
        <a href="<?= url('/admin/master-data/settings') ?>" class="bg-white p-10 rounded-[2.5rem] shadow-sm border border-slate-100 hover:shadow-2xl hover:border-[#0066FF]/20 transition-all duration-300 group relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-rose-50/50 rounded-bl-full -mr-16 -mt-16 group-hover:bg-[#0066FF]/5 transition-colors"></div>
            <div class="w-16 h-16 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mb-8 shadow-inner group-hover:bg-[#0066FF] group-hover:text-white group-hover:rotate-12 transition-all duration-500">
                <i class="fas fa-cogs text-2xl"></i>
            </div>
            <h3 class="text-2xl font-black text-slate-800 mb-4 font-heading group-hover:text-[#0066FF] transition">Cấu hình chung</h3>
            <p class="text-slate-500 text-sm leading-relaxed font-medium">Thiết lập các tham số hệ thống, bật/tắt các tính năng và quy trình nộp hồ sơ.</p>
            <div class="mt-8 flex items-center text-xs font-black text-rose-600 uppercase tracking-widest group-hover:text-[#0066FF]">
                Truy cập ngay <i class="fas fa-arrow-right ml-2 group-hover:translate-x-2 transition-transform"></i>
            </div>
        </a>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
?>
