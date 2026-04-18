<!-- Modals -->

<!-- Password Reset Modal -->
<div id="password-modal" class="fixed inset-0 min-h-screen flex items-center justify-center p-4 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true" style="z-index: 99999 !important;">
    <!-- Backdrop with blur -->
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" aria-hidden="true" onclick="closeModal('password-modal')"></div>
    
    <!-- Modal Content -->
    <div class="relative w-full max-w-lg bg-white rounded-[2.5rem] shadow-[0_25px_50px_-12px_rgba(0,0,0,0.5)] border border-slate-100 transform transition-all p-8 md:p-10 pointer-events-auto overflow-hidden">
        <!-- Close Button -->
        <button type="button" onclick="closeModal('password-modal')" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 transition-colors">
            <i class="fas fa-times text-xl"></i>
        </button>

        <form id="password-modal-form" method="POST" action="<?= url('/admin/candidates/change-password') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="cccd" id="pwd-modal-cccd" value="">
            <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? url('/admin/candidate-management')) ?>">

            <div class="text-center">
                <!-- Icon & Title -->
                <div class="w-16 h-16 bg-blue-50 text-[#0066FF] rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-sm">
                    <i class="fas fa-shield-alt text-2xl"></i>
                </div>
                
                <h3 id="pwd-modal-title" class="text-2xl font-black text-slate-800 tracking-tight mb-2">Đổi mật khẩu</h3>
                <p id="pwd-modal-desc" class="text-slate-500 text-sm font-medium mb-1">Đang thiết lập cho thí sinh:</p>
                <div id="pwd-modal-name" class="text-xl font-bold text-[#0066FF] mb-8 truncate uppercase tracking-wide">...</div>

                <!-- Input Field -->
                <div class="text-left mb-8">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 px-1">Mật khẩu mới (Để trống để tự động sinh)</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-slate-300 group-focus-within:text-[#0066FF] transition-colors"></i>
                        </div>
                        <input type="text" name="new_password" 
                            class="w-full pl-11 pr-4 py-4 bg-slate-50 border-2 border-slate-100 rounded-2xl text-slate-800 font-bold placeholder:text-slate-300 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 outline-none transition-all text-center"
                            placeholder="Nhập mật khẩu mới...">
                    </div>
                    <div class="mt-4 p-3 bg-amber-50 border border-amber-100 rounded-xl flex items-start gap-2.5 text-left">
                        <i class="fas fa-info-circle text-amber-500 mt-0.5"></i>
                        <p class="text-[11px] text-amber-800 font-medium leading-relaxed">
                            Mật khẩu sẽ được <b>gửi tự động qua Email</b> cho thí sinh ngay sau khi bạn xác nhận.
                        </p>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="flex items-center gap-3">
                    <button type="button" onclick="closeModal('password-modal')" 
                        class="flex-1 px-6 py-4 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-2xl transition-all">
                        Hủy
                    </button>
                    <button type="submit" onclick="Loading.show()"
                        class="flex-[1.5] px-6 py-4 bg-[#0066FF] hover:bg-blue-700 text-white font-bold rounded-2xl shadow-lg shadow-blue-200 hover:-translate-y-0.5 active:translate-y-0 transition-all">
                        Xác nhận đổi
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Transfer Modal -->
<div id="transfer-modal" class="fixed inset-0 min-h-screen flex items-center justify-center p-4 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true" style="z-index: 99999 !important;">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" aria-hidden="true" onclick="closeModal('transfer-modal')"></div>
    
    <div class="relative w-full max-w-lg bg-white rounded-[2.5rem] shadow-[0_25px_50px_-12px_rgba(0,0,0,0.5)] border border-slate-100 transform transition-all p-8 md:p-10 pointer-events-auto overflow-hidden">
        <button type="button" onclick="closeModal('transfer-modal')" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 transition-colors">
            <i class="fas fa-times text-xl"></i>
        </button>

        <div class="text-center">
            <div class="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-sm">
                <i class="fas fa-exchange-alt text-2xl"></i>
            </div>
            
            <h3 class="text-2xl font-black text-slate-800 mb-2">Chuyển đợt tuyển sinh</h3>
            <p class="text-sm text-slate-500 mb-6">Chọn đợt đích để chuyển <span id="transfer-count" class="font-bold text-indigo-600">0</span> hồ sơ đã chọn.</p>

            <div class="text-left mb-8">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 px-1">Đợt tuyển sinh đích</label>
                <select id="modal-target-session" class="w-full px-4 py-4 bg-slate-50 border-2 border-slate-100 rounded-2xl text-slate-800 font-bold focus:bg-white focus:border-indigo-500 outline-none transition-all">
                    <option value="">-- Chọn đợt tuyển sinh --</option>
                    <?php if (isset($yearSessions)): ?>
                        <?php foreach ($yearSessions as $s): ?>
                            <option value="<?= $s['id'] ?>">
                                <?= htmlspecialchars(!empty($s['ma_dot']) ? $s['ma_dot'] : $s['ten_dot']) ?> - <?= $s['nam_tuyen_sinh'] ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="flex items-center gap-3">
                <button type="button" onclick="closeModal('transfer-modal')" class="flex-1 px-6 py-4 bg-slate-100 text-slate-600 font-bold rounded-2xl transition-all">Hủy</button>
                <button type="button" onclick="confirmTransfer()" class="flex-[1.5] px-6 py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-2xl shadow-lg transition-all">Chuyển ngay</button>
            </div>
        </div>
    </div>
</div>

<!-- Email Modal (Gmail-Inspired Premium Redesign) -->
<div id="email-modal" class="fixed inset-0 min-h-screen flex items-center justify-center p-0 md:p-4 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true" style="z-index: 99999 !important;">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" aria-hidden="true" onclick="closeModal('email-modal')"></div>
    
    <div class="relative w-full max-w-3xl bg-white rounded-none md:rounded-t-2xl shadow-[0_25px_50px_-12px_rgba(0,0,0,0.5)] transform transition-all pointer-events-auto overflow-hidden flex flex-col h-full md:h-auto md:max-h-[90vh]">
        
        <!-- Gmail Window Header (HVU Blue) -->
        <div class="bg-[#0066FF] text-white px-5 py-3 flex items-center justify-between cursor-default">
            <h3 class="text-sm font-bold tracking-tight">Thư mới</h3>
            <div class="flex items-center gap-4 text-white/70">
                <button type="button" class="hover:text-white transition-colors"><i class="fas fa-minus text-[10px]"></i></button>
                <button type="button" class="hover:text-white transition-colors"><i class="fas fa-expand-alt text-[10px]"></i></button>
                <button type="button" onclick="closeModal('email-modal')" class="hover:text-white transition-colors">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
        </div>

        <form id="email-modal-form" method="POST" class="flex flex-col flex-1 overflow-hidden">
            <!-- Fields Section -->
            <div class="bg-white">
                <!-- Recipient Info -->
                <div class="px-5 py-2.5 border-b border-slate-100 flex items-center gap-3">
                    <span class="text-sm text-slate-500 w-20 shrink-0">Đến</span>
                    <div class="flex-1 flex items-center justify-between">
                        <span class="text-sm text-slate-900 font-bold bg-blue-50 text-blue-700 px-2.5 py-1 rounded border border-blue-100 shadow-sm"><span id="email-target-count">1</span> Thí sinh đã chọn</span>
                        <div class="flex gap-4 text-xs font-medium text-slate-400">
                            <button type="button" class="hover:underline hover:text-slate-600">Cc</button>
                            <button type="button" class="hover:underline hover:text-slate-600">Bcc</button>
                        </div>
                    </div>
                </div>

                <!-- Internal Note -->
                <div class="px-5 py-2 border-b border-slate-100 flex items-center gap-3 bg-amber-50/10">
                    <span class="text-[10px] font-black text-amber-600 w-20 shrink-0 uppercase tracking-tight">Ghi chú bộ</span>
                    <input type="text" name="internal_note" id="email-modal-internal-note"
                        class="flex-1 bg-transparent border-none text-slate-900 text-sm font-medium placeholder:text-amber-200 focus:ring-0 p-0 outline-none"
                        placeholder="Nhập ghi chú hoặc lịch sử công việc...">
                </div>

                <!-- Template Selection -->
                <div class="px-5 py-2.5 border-b border-slate-100 flex items-center gap-3">
                    <span class="text-sm text-slate-500 w-20 shrink-0">Mẫu thư</span>
                    <div class="flex-1 relative group">
                        <select name="template_id" id="email-template-select" 
                            onchange="applyEmailTemplate(this.value)"
                            class="w-full bg-transparent border-none text-slate-900 text-sm font-bold focus:ring-0 p-0 outline-none cursor-pointer appearance-none">
                            <option value="">-- Tự soạn hoặc chọn mẫu --</option>
                            <?php if (isset($emailTemplates)): ?>
                                <?php foreach ($emailTemplates as $t): ?>
                                    <option value="<?= $t['id'] ?>" 
                                            data-subject="<?= htmlspecialchars($t['subject'] ?? '') ?>"
                                            data-body="<?= htmlspecialchars($t['body'] ?? '') ?>">
                                        <?= htmlspecialchars($t['subject'] ?? $t['code'] ?? 'Mẫu thư') ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <!-- Subject -->
                <div class="px-5 py-3 border-b border-slate-100 flex items-center gap-3">
                    <input type="text" name="subject" id="email-modal-subject"
                        class="flex-1 bg-transparent border-none text-slate-900 text-base font-medium placeholder:text-slate-300 focus:ring-0 p-0 outline-none"
                        placeholder="Tiêu đề">
                </div>
            </div>

            <!-- Body Area (Rich Text Editor) -->
            <div class="flex-1 overflow-y-auto px-5 py-4 flex flex-col">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-bold text-slate-400 italic">Dùng {{name}} để chèn tự động tên thí sinh</span>
                </div>
                
                <!-- Rich Editor Container -->
                <div id="email-editor" 
                    contenteditable="true"
                    class="flex-1 min-h-[300px] border-none text-slate-800 text-sm leading-relaxed focus:ring-0 p-0 outline-none overflow-y-auto"
                    oninput="this.dataset.placeholder = this.innerText === ''"></div>
                
                <style>
                    #email-editor[contenteditable]:empty::before {
                        content: "Nhập nội dung thư...";
                        color: #cbd5e1;
                        pointer-events: none;
                    }
                </style>
            </div>

            <!-- Gmail Bottom Action Bar (Functional Toolbar) -->
            <div class="px-5 py-4 border-t border-slate-100 flex items-baseline justify-between bg-white shrink-0">
                <div class="flex items-center gap-4">
                    <!-- Send Button -->
                    <button type="button" onclick="confirmSendEmail()"
                        class="bg-[#0066FF] hover:bg-blue-700 text-white font-bold px-8 py-2.5 rounded-full flex items-center gap-2 transition-all shadow-lg active:scale-95">
                        Gửi ngay <i class="fas fa-paper-plane text-[10px]"></i>
                    </button>

                    <!-- Text Formatting Toolbar -->
                    <div class="flex items-center gap-1.5 text-slate-500 border-l border-slate-100 pl-4 ml-1">
                        <button type="button" onclick="execCmd('bold')" class="w-8 h-8 rounded hover:bg-slate-100 flex items-center justify-center transition-all" title="In đậm (Ctrl+B)"><i class="fas fa-bold text-xs"></i></button>
                        <button type="button" onclick="execCmd('italic')" class="w-8 h-8 rounded hover:bg-slate-100 flex items-center justify-center transition-all" title="In nghiêng (Ctrl+I)"><i class="fas fa-italic text-xs"></i></button>
                        <button type="button" onclick="execCmd('underline')" class="w-8 h-8 rounded hover:bg-slate-100 flex items-center justify-center transition-all" title="Gạch chân (Ctrl+U)"><i class="fas fa-underline text-xs"></i></button>
                        <div class="h-4 w-px bg-slate-200 mx-1"></div>
                        <button type="button" onclick="execCmd('justifyLeft')" class="w-8 h-8 rounded hover:bg-slate-100 flex items-center justify-center transition-all" title="Căn lề trái"><i class="fas fa-align-left text-xs"></i></button>
                        <button type="button" onclick="execCmd('justifyCenter')" class="w-8 h-8 rounded hover:bg-slate-100 flex items-center justify-center transition-all" title="Căn giữa"><i class="fas fa-align-center text-xs"></i></button>
                        <button type="button" onclick="execCmd('justifyRight')" class="w-8 h-8 rounded hover:bg-slate-100 flex items-center justify-center transition-all" title="Căn lề phải"><i class="fas fa-align-right text-xs"></i></button>
                        <div class="h-4 w-px bg-slate-200 mx-1"></div>
                        <button type="button" onclick="execCmd('insertUnorderedList')" class="w-8 h-8 rounded hover:bg-slate-100 flex items-center justify-center transition-all" title="Danh sách liệt kê"><i class="fas fa-list-ul text-xs"></i></button>
                        <button type="button" onclick="promptLink()" class="w-8 h-8 rounded hover:bg-slate-100 flex items-center justify-center transition-all" title="Chèn liên kết"><i class="fas fa-link text-xs"></i></button>
                        <button type="button" onclick="execCmd('removeFormat')" class="w-8 h-8 rounded hover:bg-slate-100 flex items-center justify-center transition-all" title="Xóa định dạng"><i class="fas fa-eraser text-xs"></i></button>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" onclick="closeModal('email-modal')" class="hover:bg-red-50 w-9 h-9 rounded-full flex items-center justify-center text-slate-400 hover:text-red-500 transition-colors" title="Hủy">
                        <i class="fas fa-trash-alt text-xs"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Simple Rich Text Logic -->
<script>
    // Ensure consistent paragraph structure
    if (document.queryCommandSupported('defaultParagraphSeparator')) {
        document.execCommand('defaultParagraphSeparator', false, 'p');
    }

    function execCmd(command, value = null) {
        const editor = document.getElementById('email-editor');
        editor.focus();
        document.execCommand(command, false, value);
    }
    
    function promptLink() {
        const url = prompt("Nhập địa chỉ URL (ví dụ: https://hvu.edu.vn):", "https://");
        if (url) execCmd('createLink', url);
    }
</script>
    </div>
</div>
