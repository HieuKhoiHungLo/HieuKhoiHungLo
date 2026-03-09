    </main>

    <!-- Desktop Footer -->
    <footer class="hidden md:block bg-gray-900 text-white pt-16 pb-8 mt-auto border-t border-gray-800">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-4 gap-12 mb-12">
                <div class="space-y-4">
                    <img loading="lazy" src="<?= url('/assets/img/Logo.png') ?>" class="h-20 object-contain bg-white/5 p-2 rounded-lg" alt="HVU Logo">
                    <p class="text-gray-400 text-sm leading-relaxed">
                        Trường Đại học Hùng Vương - Đơn vị đào tạo nguồn nhân lực chất lượng cao, nghiên cứu khoa học và chuyển giao công nghệ hàng đầu khu vực Trung du miền núi phía Bắc.
                    </p>
                    <div class="flex space-x-4">
                        <a href="http://facebook.com/daihochungvuong" target="_blank" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center hover:bg-hvu-red transition"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://hvu.edu.vn/" target="_blank" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center hover:bg-hvu-red transition"><i class="fas fa-globe"></i></a>
                        <a href="https://www.youtube.com/@daihochungvuong1486" target="_blank" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center hover:bg-hvu-red transition"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-bold font-heading mb-6 border-l-4 border-hvu-red pl-4">LIÊN KẾT NHANH</h3>
                    <ul class="space-y-3 text-sm text-gray-400">
                        <li><a href="https://hvu.edu.vn" target="_blank" class="hover:text-white transition flex items-center"><i class="fas fa-angle-right mr-2 text-hvu-red"></i> Trang chủ HVU</a></li>
                        <li><a href="https://www.hvu.edu.vn/tin-tuc/so-lieu-tuyen-sinh/1700635044.hvu" target="_blank" class="hover:text-white transition flex items-center"><i class="fas fa-angle-right mr-2 text-hvu-red"></i> Thông tin tuyển sinh 2026</a></li>
                        <li><a href="https://www.hvu.edu.vn/file/1268204397/Quychetuyensinhdaihoc.pdf" target="_blank" class="hover:text-white transition flex items-center"><i class="fas fa-angle-right mr-2 text-hvu-red"></i> Quy chế tuyển sinh</a></li>
                        <li><a href="https://www.hvu.edu.vn/tin-tuc/so-lieu-tuyen-sinh/1458613815.hvu" target="_blank" class="hover:text-white transition flex items-center"><i class="fas fa-angle-right mr-2 text-hvu-red"></i> Điểm trúng tuyển các năm</a></li>
                        <li><a href="https://www.hvu.edu.vn/tin-tuc/thong-bao-tuyen-sinh.hvu" target="_blank" class="hover:text-white transition flex items-center"><i class="fas fa-angle-right mr-2 text-hvu-red"></i> Thông báo</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-bold font-heading mb-6 border-l-4 border-hvu-red pl-4">HỖ TRỢ THÍ SINH</h3>
                    <?php
                    if (!isset($_SESSION['cache_footer_links'])) {
                        try {
                            $__db = \App\Core\Database::getInstance()->getConnection();
                            $__stmt = $__db->prepare("SELECT value FROM settings WHERE \"key\" = ?");
                            $__stmt->execute(['footer_support_links']);
                            $__json = $__stmt->fetchColumn();
                            $_SESSION['cache_footer_links'] = $__json ? json_decode($__json, true) : [];
                        } catch (\Exception $e) {
                            $_SESSION['cache_footer_links'] = [];
                        }
                    }
                    $footerLinks = $_SESSION['cache_footer_links'];
                    if (empty($footerLinks)) {
                        $footerLinks = [
                            ['label' => 'Đăng ký xét tuyển', 'url' => url('/register'), 'icon' => 'fas fa-check-circle'],
                            ['label' => 'Tra cứu hồ sơ', 'url' => url('/login'), 'icon' => 'fas fa-check-circle'],
                        ];
                    }
                    ?>
                    <ul class="space-y-3 text-sm text-gray-400">
                        <?php foreach ($footerLinks as $fl): ?>
                            <li><a href="<?= htmlspecialchars($fl['url']) ?>" <?= (strpos($fl['url'], 'http') === 0) ? 'target="_blank"' : '' ?> class="hover:text-white transition flex items-center"><i class="<?= htmlspecialchars($fl['icon'] ?? 'fas fa-check-circle') ?> mr-2 text-hvu-red"></i> <?= htmlspecialchars($fl['label']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-bold font-heading mb-6 border-l-4 border-hvu-red pl-4">THÔNG TIN LIÊN HỆ</h3>
                    <ul class="space-y-4 text-sm text-gray-400">
                        <li class="flex items-start"><i class="fas fa-map-marker-alt mt-1 mr-3 text-hvu-red"></i><span>Văn phòng Tuyển sinh<br>Phòng 114 nhà Điều hành<br>Phường Nông Trang, Tỉnh Phú Thọ</span></li>
                        <li class="flex items-center"><i class="fas fa-phone-alt mr-3 text-hvu-red"></i><span class="text-white font-bold text-lg">0866 993 468</span></li>
                        <li class="flex items-center"><i class="fas fa-envelope mr-3 text-hvu-red"></i><span>tuyensinh@hvu.edu.vn</span></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 pt-8 text-center text-xs text-gray-500">
                <p>&copy; <?= date('Y') ?> Trường Đại học Hùng Vương</p>
            </div>
        </div>
    </footer>

    <!-- Mobile Footer (Compact) -->
    <footer class="md:hidden bg-gray-900 text-white pt-6 pb-4 mt-auto">
        <div class="px-4 text-center">
            <p class="text-white font-bold text-sm">Trường Đại học Hùng Vương</p>
            <div class="flex items-center justify-center gap-4 mt-3 text-gray-400 text-xs">
                <a href="tel:0866993468" class="flex items-center gap-1.5 hover:text-white transition">
                    <i class="fas fa-phone-alt text-hvu-red"></i> 0866 993 468
                </a>
                <span class="text-gray-600">|</span>
                <a href="mailto:tuyensinh@hvu.edu.vn" class="flex items-center gap-1.5 hover:text-white transition">
                    <i class="fas fa-envelope text-hvu-red"></i> tuyensinh@hvu.edu.vn
                </a>
            </div>
            <div class="flex justify-center gap-3 mt-3">
                <a href="http://facebook.com/daihochungvuong" target="_blank" class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center hover:bg-hvu-red transition text-sm"><i class="fab fa-facebook-f"></i></a>
                <a href="https://hvu.edu.vn/" target="_blank" class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center hover:bg-hvu-red transition text-sm"><i class="fas fa-globe"></i></a>
                <a href="https://www.youtube.com/@daihochungvuong1486" target="_blank" class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center hover:bg-hvu-red transition text-sm"><i class="fab fa-youtube"></i></a>
            </div>
            <p class="text-gray-600 text-[10px] mt-3">&copy; <?= date('Y') ?> Đại học Hùng Vương</p>
    </footer>

    <script>
        // Mobile Menu Toggle
        const btn = document.getElementById('mobile-menu-btn');
        const menu = document.getElementById('mobile-menu');

        if (btn && menu) {
            btn.addEventListener('click', () => {
                menu.classList.toggle('hidden');
            });
        }

        // Register Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('<?= url("/sw.js") ?>')
                    .then(reg => console.log('SW Registered!', reg))
                    .catch(err => console.log('SW Failed!', err));
            });
        }

        // Notification System
        (function() {
            const bell = document.getElementById('notification-bell');
            const dropdown = document.getElementById('notification-dropdown');
            const badge = document.getElementById('notification-badge');
            const list = document.getElementById('notification-list');
            const markAllBtn = document.getElementById('mark-all-read');
            const baseUrl = '<?= \App\Core\App::getBaseUrl() ?>';

            if (!bell) return;

            // Fetch unread count
            function fetchUnreadCount() {
                fetch(baseUrl + '/api/notifications/unread-count')
                    .then(r => r.json())
                    .then(data => {
                        if (data.count > 0) {
                            badge.textContent = data.count > 9 ? '9+' : data.count;
                            badge.classList.remove('hidden');
                        } else {
                            badge.classList.add('hidden');
                        }
                    });
            }

            let currentFilter = 'all';

            // Fetch notifications
            window.fetchNotifications = function(filter = 'all') {
                currentFilter = filter;
                list.innerHTML = '<div class="p-4 text-center text-gray-400 text-sm"><i class="fas fa-spinner fa-spin mr-2"></i> Đang tải...</div>';

                fetch(baseUrl + '/api/notifications?filter=' + filter)
                    .then(r => {
                        if (!r.ok) throw new Error('HTTP error ' + r.status);
                        return r.json();
                    })
                    .then(data => {
                        console.log('Notif Data:', data);
                        if (!data.success || !data.notifications || !data.notifications.length) {
                            const msg = filter === 'unread' ? 'Không có thông báo chưa đọc' : 'Không có thông báo';
                            list.innerHTML = `<div class="p-6 text-center text-gray-400 text-sm"><i class="fas fa-bell-slash text-2xl mb-2"></i><p>${msg}</p></div>`;
                            return;
                        }

                        const typeColors = {
                            'info': 'bg-blue-100 text-blue-600',
                            'warning': 'bg-yellow-100 text-yellow-600',
                            'success': 'bg-green-100 text-green-600',
                            'important': 'bg-red-100 text-red-600'
                        };

                        list.innerHTML = data.notifications.map(n => `
                            <div class="px-4 py-3 border-b border-gray-50 hover:bg-gray-50 cursor-pointer transition ${n.is_read ? 'opacity-60' : ''}" 
                                 onclick="window.markNotificationRead(${n.id}, this)">
                                <div class="flex items-start">
                                    <div class="w-8 h-8 rounded-full ${typeColors[n.type] || typeColors.info} flex items-center justify-center mr-3 flex-shrink-0">
                                        <i class="fas fa-${n.type === 'warning' ? 'exclamation-triangle' : n.type === 'success' ? 'check' : n.type === 'important' ? 'fire' : 'info'}"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-bold text-gray-800 text-sm truncate">${n.title || 'Thông báo'}</p>
                                        <p class="text-xs text-gray-500 line-clamp-2">${(n.content || '').replace(/<[^>]*>/g, '').substring(0, 80)}...</p>
                                        <p class="text-xs text-gray-400 mt-1">${n.created_at ? new Date(n.created_at).toLocaleDateString('vi-VN') : ''}</p>
                                    </div>
                                    ${!n.is_read ? '<span class="w-2 h-2 bg-red-500 rounded-full ml-2"></span>' : ''}
                                </div>
                            </div>
                        `).join('');
                    })
                    .catch(err => {
                        console.error('Notif Fetch Error:', err);
                        list.innerHTML = '<div class="p-4 text-center text-red-500 text-xs">Lỗi tải thông báo</div>';
                    });
            }

            // Tab Switching Logic
            window.switchNotifTab = function(filter) {
                // Update UI tabs
                document.querySelectorAll('.notif-tab').forEach(t => t.classList.remove('active'));
                const targetTab = document.getElementById('tab-' + filter);
                if (targetTab) targetTab.classList.add('active');

                // Fetch filtered data
                window.fetchNotifications(filter);
            }

            // Mark as read
            window.markNotificationRead = function(id, el) {
                fetch(baseUrl + '/api/notifications/mark-read', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: 'id=' + id
                }).then(() => {
                    el.classList.add('opacity-60');
                    el.querySelector('.bg-red-500')?.remove();
                    fetchUnreadCount();
                });
            };

            // Mark all read
            markAllBtn?.addEventListener('click', () => {
                fetch(baseUrl + '/api/notifications/mark-all-read', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                        }
                    })
                    .then(() => {
                        window.fetchNotifications();
                        fetchUnreadCount();
                    });
            });

            // Toggle dropdown
            bell.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdown.classList.toggle('hidden');
                if (!dropdown.classList.contains('hidden')) {
                    window.fetchNotifications(currentFilter);

                    // Tự động đánh dấu tất cả đã đọc khi người dùng mở chuông thông báo
                    if (badge && !badge.classList.contains('hidden')) {
                        fetch(baseUrl + '/api/notifications/mark-all-read', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                            }
                        }).then(() => {
                            badge.classList.add('hidden');
                            badge.textContent = '0';
                        });
                    }
                }
            });

            // Close on outside click
            document.addEventListener('click', (e) => {
                if (!dropdown.contains(e.target) && e.target !== bell) {
                    dropdown.classList.add('hidden');
                }
            });

            // Initial fetch
            fetchUnreadCount();
            setInterval(fetchUnreadCount, 60000); // Refresh every minute
        })();
    </script>

    <?php if (isset($_SESSION['user_id']) || isset($_SESSION['admin_id'])): ?>
        <!-- Session Idle Timeout Warning -->
        <div id="idleWarningModal" class="fixed inset-0 z-[9999] hidden">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
            <div class="absolute inset-0 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6 text-center relative animate-bounce-in">
                    <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-hourglass-half text-yellow-500 text-2xl animate-pulse"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 mb-2">Phiên sắp hết hạn</h3>
                    <p class="text-sm text-gray-500 mb-1">Bạn không có thao tác trong thời gian dài.</p>
                    <p class="text-sm text-gray-500 mb-4">Hệ thống sẽ tự động đăng xuất sau:</p>
                    <div class="text-4xl font-black text-hvu-red mb-5" id="idleCountdown">60</div>
                    <button onclick="resetIdleTimer()" class="w-full py-3 bg-hvu-red text-white font-bold rounded-xl hover:bg-red-700 transition shadow-lg">
                        <i class="fas fa-mouse-pointer mr-2"></i> Tiếp tục phiên làm việc
                    </button>
                </div>
            </div>
        </div>

        <style>
            @keyframes bounce-in {
                0% {
                    transform: scale(0.8);
                    opacity: 0;
                }

                50% {
                    transform: scale(1.05);
                }

                100% {
                    transform: scale(1);
                    opacity: 1;
                }
            }

            .animate-bounce-in {
                animation: bounce-in 0.3s ease-out;
            }
        </style>

        <script>
            (function() {
                const SESSION_TIMEOUT = 10 * 60; // 10 minutes (matches server)
                const WARNING_BEFORE = 2 * 60; // Show warning 2 minutes before expiry
                const COUNTDOWN_SECS = 60; // 60-second final countdown

                const isAdmin = <?= isset($_SESSION['admin_id']) ? 'true' : 'false' ?>;
                const logoutUrl = isAdmin ?
                    '<?= url("/admin/login?timeout=1") ?>' :
                    '<?= url("/?timeout=1") ?>';

                let idleSeconds = 0;
                let countdownInterval = null;
                let countdownRemaining = COUNTDOWN_SECS;
                const modal = document.getElementById('idleWarningModal');
                const countdownEl = document.getElementById('idleCountdown');

                // Track activity
                const activityEvents = ['mousemove', 'keydown', 'click', 'touchstart', 'scroll'];
                activityEvents.forEach(evt => {
                    document.addEventListener(evt, () => {
                        if (!modal.classList.contains('hidden')) return; // Don't reset during countdown
                        idleSeconds = 0;
                    }, {
                        passive: true
                    });
                });

                // Main idle checker (runs every second)
                setInterval(() => {
                    idleSeconds++;
                    const warningAt = SESSION_TIMEOUT - WARNING_BEFORE;

                    if (idleSeconds >= warningAt && modal.classList.contains('hidden')) {
                        showWarning();
                    }
                }, 1000);

                function showWarning() {
                    countdownRemaining = COUNTDOWN_SECS;
                    modal.classList.remove('hidden');
                    updateCountdownDisplay();

                    countdownInterval = setInterval(() => {
                        countdownRemaining--;
                        updateCountdownDisplay();

                        if (countdownRemaining <= 0) {
                            clearInterval(countdownInterval);
                            window.location.href = logoutUrl;
                        }
                    }, 1000);
                }

                function updateCountdownDisplay() {
                    if (countdownEl) {
                        countdownEl.textContent = countdownRemaining;
                        countdownEl.style.color = countdownRemaining <= 10 ? '#ef4444' : '';
                    }
                }

                // Exposed globally for button onclick
                window.resetIdleTimer = function() {
                    idleSeconds = 0;
                    countdownRemaining = COUNTDOWN_SECS;
                    if (countdownInterval) clearInterval(countdownInterval);
                    modal.classList.add('hidden');

                    // Ping server to refresh last_activity
                    fetch(window.location.href, {
                        method: 'HEAD',
                        cache: 'no-store'
                    }).catch(() => {});
                };
            })();
        </script>
    <?php endif; ?>

    <!-- Global Form Submit Loading Overlay -->
    <div id="globalSubmitOverlay" class="fixed inset-0 z-[99999] hidden flex-col items-center justify-center bg-black/70 backdrop-blur-sm transition-opacity duration-300 opacity-0">
        <div class="bg-white p-6 rounded-2xl shadow-2xl flex flex-col items-center max-w-sm w-11/12 text-center transform scale-95 transition-transform duration-300">
            <div class="relative w-16 h-16 mb-4">
                <div class="absolute inset-0 border-4 border-gray-200 rounded-full"></div>
                <div class="absolute inset-0 border-4 border-hvu-red rounded-full border-t-transparent animate-spin"></div>
                <i class="fas fa-cloud-upload-alt absolute inset-0 flex items-center justify-center text-hvu-red text-xl animate-pulse"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-800 mb-2">Đang xử lý dữ liệu...</h3>
            <p class="text-sm text-gray-500 mb-2" id="overlayMessage">Hệ thống đang nén và tải tệp lên máy chủ. Vui lòng không đóng trình duyệt!</p>
            <div class="w-full bg-gray-200 rounded-full h-1.5 mt-2 overflow-hidden hidden" id="overlayProgressContainer">
                <div class="bg-hvu-red h-1.5 rounded-full transition-all duration-300 w-0" id="overlayProgressBar"></div>
            </div>
        </div>
    </div>

    <script>
        /**
         * Frontend Image Compression & Form Submission Optimizer
         * Applied globally to all forms except login/search
         */
        document.addEventListener('DOMContentLoaded', function() {
            const COMPRESS_MAX_WIDTH = 1600;
            const COMPRESS_MAX_HEIGHT = 1600;
            const COMPRESS_QUALITY = 0.75; // 75% quality JPEG
            const MAX_FILE_SIZE_BYTES = 500 * 1024; // If file is smaller than 500KB, don't compress

            // Find all main application forms (Step 1-5 usually have post method and multipart)
            const forms = document.querySelectorAll('form[method="POST"]');

            forms.forEach(form => {
                // Skip obvious non-data forms like logout
                if (form.action.includes('logout') || form.id === 'searchForm') return;

                form.addEventListener('submit', async function(e) {
                    // Check if form is valid first (HTML5 validation)
                    if (!this.checkValidity()) return;

                    // Show custom overlay if the submit button hasn't been blocked yet
                    const overlay = document.getElementById('globalSubmitOverlay');
                    if (overlay.classList.contains('hidden')) {
                        e.preventDefault(); // Pause submission to process images

                        // Show Overlay UX
                        overlay.classList.remove('hidden');
                        setTimeout(() => {
                            overlay.classList.remove('opacity-0');
                            overlay.querySelector('.bg-white').classList.remove('scale-95');
                        }, 10);

                        document.getElementById('overlayMessage').textContent = 'Đang tự động nén ảnh để tăng tốc tải lên...';

                        const fileInputs = form.querySelectorAll('input[type="file"][accept*="image"]');
                        let hasFilesToProcess = false;

                        // First pass check if there are any files
                        for (let input of fileInputs) {
                            if (input.files && input.files.length > 0) {
                                hasFilesToProcess = true;
                                break;
                            }
                        }

                        if (hasFilesToProcess) {
                            try {
                                const dataTransferMap = new Map(); // Store processed files per input

                                for (let input of fileInputs) {
                                    if (!input.files || input.files.length === 0) continue;

                                    const dt = new DataTransfer();

                                    for (let i = 0; i < input.files.length; i++) {
                                        const file = input.files[i];

                                        // Only compress large images
                                        if (file.type.startsWith('image/') && file.size > MAX_FILE_SIZE_BYTES) {
                                            try {
                                                const compressedBlob = await compressImage(file, COMPRESS_MAX_WIDTH, COMPRESS_MAX_HEIGHT, COMPRESS_QUALITY);
                                                // Some older browsers don't support File constructor well, but modern ones do
                                                const compressedFile = new File([compressedBlob], file.name, {
                                                    type: file.type === 'image/png' ? 'image/png' : 'image/jpeg',
                                                    lastModified: Date.now()
                                                });
                                                dt.items.add(compressedFile);
                                                console.log(`[Optimizer] Compressed ${file.name}: ${(file.size/1024).toFixed(1)}KB -> ${(compressedFile.size/1024).toFixed(1)}KB`);
                                            } catch (err) {
                                                console.error('[Optimizer] Compression failed for', file.name, err);
                                                dt.items.add(file); // Fallback to original
                                            }
                                        } else {
                                            dt.items.add(file); // Already small enough, keep original
                                        }
                                    }
                                    dataTransferMap.set(input, dt.files);
                                }

                                // Apply processed files back to inputs
                                for (let [input, files] of dataTransferMap.entries()) {
                                    input.files = files;
                                }
                            } catch (err) {
                                console.error('[Optimizer] Global processing error:', err);
                            }
                        }

                        // Update UX and submit
                        document.getElementById('overlayMessage').textContent = 'Đang lưu hồ sơ và kết nối mây...';

                        // Submit form natively bypassing this event listener
                        HTMLFormElement.prototype.submit.call(form);
                    }
                });
            });

            // Core Compression Logic via Canvas
            function compressImage(file, maxWidth, maxHeight, quality) {
                return new Promise((resolve, reject) => {
                    const reader = new FileReader();
                    reader.readAsDataURL(file);
                    reader.onload = function(event) {
                        const img = new Image();
                        img.src = event.target.result;
                        img.onload = function() {
                            let width = img.width;
                            let height = img.height;

                            // Calculate new dimensions while keeping aspect ratio
                            if (width > height) {
                                if (width > maxWidth) {
                                    height = Math.round((height * maxWidth) / width);
                                    width = maxWidth;
                                }
                            } else {
                                if (height > maxHeight) {
                                    width = Math.round((width * maxHeight) / height);
                                    height = maxHeight;
                                }
                            }

                            const canvas = document.createElement('canvas');
                            canvas.width = width;
                            canvas.height = height;

                            const ctx = canvas.getContext('2d');
                            // Draw white background for transparent PNGs converting to JPEG
                            if (file.type !== 'image/png') {
                                ctx.fillStyle = '#FFFFFF';
                                ctx.fillRect(0, 0, width, height);
                            }
                            ctx.drawImage(img, 0, 0, width, height);

                            // Force JPEG for better compression unless it's explicitly PNG
                            const mimeType = file.type === 'image/png' ? 'image/png' : 'image/jpeg';
                            const outputQuality = file.type === 'image/png' ? undefined : quality;

                            canvas.toBlob((blob) => {
                                if (blob) {
                                    resolve(blob);
                                } else {
                                    reject(new Error("Canvas toBlob failed"));
                                }
                            }, mimeType, outputQuality);
                        };
                        img.onerror = function() {
                            reject(new Error("Image loaded error"));
                        };
                    };
                    reader.onerror = function() {
                        reject(new Error("File read error"));
                    };
                });
            }
        });
    </script>

    </body>

    </html>