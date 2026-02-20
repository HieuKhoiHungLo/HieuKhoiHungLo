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
                        <a href="#" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center hover:bg-hvu-red transition"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-bold font-heading mb-6 border-l-4 border-hvu-red pl-4">LIÊN KẾT NHANH</h3>
                    <ul class="space-y-3 text-sm text-gray-400">
                        <li><a href="#" class="hover:text-white transition flex items-center"><i class="fas fa-angle-right mr-2 text-hvu-red"></i> Trang chủ</a></li>
                        <li><a href="#" class="hover:text-white transition flex items-center"><i class="fas fa-angle-right mr-2 text-hvu-red"></i> Giới thiệu chung</a></li>
                        <li><a href="#" class="hover:text-white transition flex items-center"><i class="fas fa-angle-right mr-2 text-hvu-red"></i> Thông tin tuyển sinh</a></li>
                        <li><a href="#" class="hover:text-white transition flex items-center"><i class="fas fa-angle-right mr-2 text-hvu-red"></i> Tin tức & Sự kiện</a></li>
                        <li><a href="#" class="hover:text-white transition flex items-center"><i class="fas fa-angle-right mr-2 text-hvu-red"></i> Liên hệ</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-bold font-heading mb-6 border-l-4 border-hvu-red pl-4">HỖ TRỢ THÍ SINH</h3>
                    <ul class="space-y-3 text-sm text-gray-400">
                        <li><a href="<?= url('/register') ?>" class="hover:text-white transition flex items-center"><i class="fas fa-check-circle mr-2 text-hvu-red"></i> Đăng ký xét tuyển</a></li>
                        <li><a href="<?= url('/login') ?>" class="hover:text-white transition flex items-center"><i class="fas fa-check-circle mr-2 text-hvu-red"></i> Tra cứu hồ sơ</a></li>
                        <li><a href="#" class="hover:text-white transition flex items-center"><i class="fas fa-check-circle mr-2 text-hvu-red"></i> Câu hỏi thường gặp</a></li>
                        <li><a href="#" class="hover:text-white transition flex items-center"><i class="fas fa-check-circle mr-2 text-hvu-red"></i> Hướng dẫn đăng ký</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-bold font-heading mb-6 border-l-4 border-hvu-red pl-4">THÔNG TIN LIÊN HỆ</h3>
                    <ul class="space-y-4 text-sm text-gray-400">
                        <li class="flex items-start"><i class="fas fa-map-marker-alt mt-1 mr-3 text-hvu-red"></i><span>Phường Nông Trang, TP. Việt Trì, Tỉnh Phú Thọ</span></li>
                        <li class="flex items-center"><i class="fas fa-phone-alt mr-3 text-hvu-red"></i><span class="text-white font-bold text-lg">0210.3993.369</span></li>
                        <li class="flex items-center"><i class="fas fa-envelope mr-3 text-hvu-red"></i><span>info@hvu.edu.vn</span></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 pt-8 flex justify-between items-center text-xs text-gray-500">
                <p>&copy; <?= date('Y') ?> Trường Đại học Hùng Vương. All rights reserved.</p>
                <div class="flex space-x-6">
                    <a href="#" class="hover:text-white">Điều khoản sử dụng</a>
                    <a href="#" class="hover:text-white">Chính sách bảo mật</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Mobile Footer (Compact) -->
    <footer class="md:hidden bg-gray-900 text-white pt-6 pb-4 mt-auto">
        <div class="px-4 text-center">
            <p class="text-white font-bold text-sm">Trường Đại học Hùng Vương</p>
            <div class="flex items-center justify-center gap-4 mt-3 text-gray-400 text-xs">
                <a href="tel:02103993369" class="flex items-center gap-1.5 hover:text-white transition">
                    <i class="fas fa-phone-alt text-hvu-red"></i> 0210.3993.369
                </a>
                <span class="text-gray-600">|</span>
                <a href="mailto:info@hvu.edu.vn" class="flex items-center gap-1.5 hover:text-white transition">
                    <i class="fas fa-envelope text-hvu-red"></i> info@hvu.edu.vn
                </a>
            </div>
            <div class="flex justify-center gap-3 mt-3">
                <a href="http://facebook.com/daihochungvuong" target="_blank" class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center hover:bg-hvu-red transition text-sm"><i class="fab fa-facebook-f"></i></a>
                <a href="https://hvu.edu.vn/" target="_blank" class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center hover:bg-hvu-red transition text-sm"><i class="fas fa-globe"></i></a>
                <a href="#" class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center hover:bg-hvu-red transition text-sm"><i class="fab fa-youtube"></i></a>
            </div>
            <p class="text-gray-600 text-[10px] mt-3">&copy; <?= date('Y') ?> Đại học Hùng Vương</p>
        </div>
    </footer>

    <!-- Three.js Library (Required for background particles) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="<?= url('/assets/js/background-particles.js') ?>" defer></script>

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
            const baseUrl = '<?= url('') ?>';
            
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
            
            // Fetch notifications
            function fetchNotifications() {
                list.innerHTML = '<div class="p-4 text-center text-gray-400 text-sm"><i class="fas fa-spinner fa-spin mr-2"></i> Đang tải...</div>';
                
                fetch(baseUrl + '/api/notifications')
                    .then(r => r.json())
                    .then(data => {
                        if (!data.success || !data.notifications.length) {
                            list.innerHTML = '<div class="p-6 text-center text-gray-400 text-sm"><i class="fas fa-bell-slash text-2xl mb-2"></i><p>Không có thông báo</p></div>';
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
                                 onclick="markNotificationRead(${n.id}, this)">
                                <div class="flex items-start">
                                    <div class="w-8 h-8 rounded-full ${typeColors[n.type] || typeColors.info} flex items-center justify-center mr-3 flex-shrink-0">
                                        <i class="fas fa-${n.type === 'warning' ? 'exclamation-triangle' : n.type === 'success' ? 'check' : n.type === 'important' ? 'fire' : 'info'}"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-bold text-gray-800 text-sm truncate">${n.title}</p>
                                        <p class="text-xs text-gray-500 line-clamp-2">${n.content.replace(/<[^>]*>/g, '').substring(0, 80)}...</p>
                                        <p class="text-xs text-gray-400 mt-1">${new Date(n.created_at).toLocaleDateString('vi-VN')}</p>
                                    </div>
                                    ${!n.is_read ? '<span class="w-2 h-2 bg-red-500 rounded-full ml-2"></span>' : ''}
                                </div>
                            </div>
                        `).join('');
                    });
            }
            
            // Mark as read
            window.markNotificationRead = function(id, el) {
                fetch(baseUrl + '/api/notifications/mark-read', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'id=' + id
                }).then(() => {
                    el.classList.add('opacity-60');
                    el.querySelector('.bg-red-500')?.remove();
                    fetchUnreadCount();
                });
            };
            
            // Mark all read
            markAllBtn?.addEventListener('click', () => {
                fetch(baseUrl + '/api/notifications/mark-all-read', {method: 'POST'})
                    .then(() => {
                        fetchNotifications();
                        fetchUnreadCount();
                    });
            });
            
            // Toggle dropdown
            bell.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdown.classList.toggle('hidden');
                if (!dropdown.classList.contains('hidden')) {
                    fetchNotifications();
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
    0% { transform: scale(0.8); opacity: 0; }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); opacity: 1; }
}
.animate-bounce-in { animation: bounce-in 0.3s ease-out; }
</style>

<script>
(function() {
    const SESSION_TIMEOUT = 10 * 60;    // 10 minutes (matches server)
    const WARNING_BEFORE  = 2 * 60;     // Show warning 2 minutes before expiry
    const COUNTDOWN_SECS  = 60;         // 60-second final countdown

    const isAdmin = <?= isset($_SESSION['admin_id']) ? 'true' : 'false' ?>;
    const logoutUrl = isAdmin
        ? '<?= url("/admin/login?timeout=1") ?>'
        : '<?= url("/?timeout=1") ?>';

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
        }, { passive: true });
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
        fetch(window.location.href, { method: 'HEAD', cache: 'no-store' }).catch(() => {});
    };
})();
</script>
<?php endif; ?>

</body>
</html>
