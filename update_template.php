<?php
require_once 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$db = new PDO('pgsql:host='.$_ENV['DB_HOST'].';port='.$_ENV['DB_PORT'].';dbname='.$_ENV['DB_DATABASE'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$templateHtml = <<<HTML
<!-- BẮT ĐẦU MẪU THÔNG BÁO (Sao chép toàn bộ phần này) -->
<div class="max-w-4xl mx-auto font-sans bg-white sm:rounded-2xl sm:shadow-lg sm:border sm:border-gray-200 overflow-hidden" style="max-width: 850px; margin: 0 auto; font-family: Arial, Helvetica, sans-serif; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
    
    <!-- Header / Banner -->
    <div class="bg-gradient-to-r from-red-700 to-red-900 px-6 py-8 text-center text-white" style="background: linear-gradient(135deg, #b91c1c, #7f1d1d); padding: 35px 20px; text-align: center; color: white;">
        <h1 class="text-2xl sm:text-3xl font-bold uppercase tracking-wider m-0" style="margin: 0; font-size: 26px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: bold;">Thông báo Trúng tuyển</h1>
        <p class="mt-2 text-red-100 font-medium text-sm sm:text-base tracking-widest uppercase m-0" style="margin: 10px 0 0 0; font-size: 14px; opacity: 0.9; text-transform: uppercase; letter-spacing: 2px;">Trường Đại học Hùng Vương năm 2026</p>
    </div>

    <!-- Wrapper with Alpine.js -->
    <div class="p-4 sm:p-8" style="padding: 20px;" x-data="{ activeTab: 1 }">
        
        <!-- Tab Navigation (Acts as Table of Contents on Email, Interactive Tabs on Web) -->
        <div class="flex flex-wrap border-b border-gray-200 mb-6 bg-gray-50 rounded-t-lg overflow-hidden" style="display: flex; flex-wrap: wrap; border-bottom: 1px solid #e5e7eb; margin-bottom: 25px; background: #f9fafb; border-radius: 8px 8px 0 0;">
            <a href="#tab1" @click.prevent="activeTab = 1" :class="activeTab === 1 ? 'border-red-600 text-red-700 bg-white' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-100'" class="flex-1 text-center py-4 px-2 text-sm font-bold uppercase border-b-2 transition-colors duration-200" style="flex: 1; min-width: 150px; text-align: center; padding: 15px 10px; font-size: 13px; font-weight: bold; text-transform: uppercase; text-decoration: none; color: #4b5563; border-bottom: 2px solid transparent;">
                <i class="fas fa-user-graduate mb-1 text-lg block" style="display: block; font-size: 18px; margin-bottom: 5px;"></i> Thông tin
            </a>
            <a href="#tab2" @click.prevent="activeTab = 2" :class="activeTab === 2 ? 'border-red-600 text-red-700 bg-white' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-100'" class="flex-1 text-center py-4 px-2 text-sm font-bold uppercase border-b-2 transition-colors duration-200" style="flex: 1; min-width: 150px; text-align: center; padding: 15px 10px; font-size: 13px; font-weight: bold; text-transform: uppercase; text-decoration: none; color: #4b5563; border-bottom: 2px solid transparent;">
                <i class="fas fa-mouse-pointer mb-1 text-lg block" style="display: block; font-size: 18px; margin-bottom: 5px;"></i> Xác nhận
            </a>
            <a href="#tab3" @click.prevent="activeTab = 3" :class="activeTab === 3 ? 'border-red-600 text-red-700 bg-white' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-100'" class="flex-1 text-center py-4 px-2 text-sm font-bold uppercase border-b-2 transition-colors duration-200" style="flex: 1; min-width: 150px; text-align: center; padding: 15px 10px; font-size: 13px; font-weight: bold; text-transform: uppercase; text-decoration: none; color: #4b5563; border-bottom: 2px solid transparent;">
                <i class="fas fa-file-invoice-dollar mb-1 text-lg block" style="display: block; font-size: 18px; margin-bottom: 5px;"></i> Kinh phí
            </a>
            <a href="#tab4" @click.prevent="activeTab = 4" :class="activeTab === 4 ? 'border-red-600 text-red-700 bg-white' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-100'" class="flex-1 text-center py-4 px-2 text-sm font-bold uppercase border-b-2 transition-colors duration-200" style="flex: 1; min-width: 150px; text-align: center; padding: 15px 10px; font-size: 13px; font-weight: bold; text-transform: uppercase; text-decoration: none; color: #4b5563; border-bottom: 2px solid transparent;">
                <i class="fas fa-folder-open mb-1 text-lg block" style="display: block; font-size: 18px; margin-bottom: 5px;"></i> Nhập học
            </a>
        </div>

        <!-- TAB 1: THÔNG TIN -->
        <div id="tab1" x-show="activeTab === 1 || !window.Alpine" class="tab-pane" style="margin-bottom: 30px;">
            <h2 class="text-xl font-bold text-red-700 uppercase mb-4 border-b-2 border-red-100 pb-2 m-0" style="font-size: 18px; font-weight: bold; color: #b91c1c; margin: 0 0 15px 0; border-bottom: 2px solid #fee2e2; padding-bottom: 10px; text-transform: uppercase;">1. Thông tin thí sinh & Trúng tuyển</h2>
            <p class="text-base text-gray-700 leading-relaxed mb-4" style="font-size: 16px; color: #374151; line-height: 1.6; margin-bottom: 15px;">
                Kính gửi thí sinh <strong class="text-gray-900" style="color: #111827;">{{ho_ten}}</strong>,<br>
                Hội đồng Tuyển sinh Trường Đại học Hùng Vương trân trọng chúc mừng bạn đã chính thức đủ điều kiện trúng tuyển (trừ điều kiện tốt nghiệp THPT) vào trường theo phương thức xét tuyển sớm.
            </p>
            <div class="bg-slate-50 rounded-xl border border-slate-200 p-5" style="background-color: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0; padding: 25px;">
                <table width="100%" cellpadding="10" cellspacing="0" style="font-size: 15px; color: #374151; text-align: left;">
                    <tr>
                        <td width="35%" class="font-bold text-gray-500 border-b border-gray-100" style="font-weight: bold; color: #6b7280; border-bottom: 1px solid #f1f5f9; padding: 12px 5px;">Họ và tên:</td>
                        <td width="65%" class="font-bold text-gray-900 border-b border-gray-100 uppercase" style="font-weight: bold; color: #111827; border-bottom: 1px solid #f1f5f9; text-transform: uppercase; padding: 12px 5px;">{{ho_ten}}</td>
                    </tr>
                    <tr>
                        <td class="font-bold text-gray-500 border-b border-gray-100" style="font-weight: bold; color: #6b7280; border-bottom: 1px solid #f1f5f9; padding: 12px 5px;">Số CCCD / Mã hồ sơ:</td>
                        <td class="font-bold text-gray-900 border-b border-gray-100" style="font-weight: bold; color: #111827; border-bottom: 1px solid #f1f5f9; padding: 12px 5px;">{{so_cccd}}</td>
                    </tr>
                    <tr>
                        <td class="font-bold text-gray-500 border-b border-gray-100" style="font-weight: bold; color: #6b7280; border-bottom: 1px solid #f1f5f9; padding: 12px 5px;">Ngành trúng tuyển:</td>
                        <td class="font-bold text-red-700 border-b border-gray-100 uppercase text-lg" style="font-weight: bold; color: #b91c1c; font-size: 16px; border-bottom: 1px solid #f1f5f9; text-transform: uppercase; padding: 12px 5px;">{{ten_nganh}}</td>
                    </tr>
                    <tr>
                        <td class="font-bold text-gray-500 border-b border-gray-100" style="font-weight: bold; color: #6b7280; border-bottom: 1px solid #f1f5f9; padding: 12px 5px;">Mã ngành:</td>
                        <td class="font-bold text-gray-900 border-b border-gray-100" style="font-weight: bold; color: #111827; border-bottom: 1px solid #f1f5f9; padding: 12px 5px;">{{ma_nganh}}</td>
                    </tr>
                    <tr>
                        <td class="font-bold text-gray-500 border-b border-gray-100" style="font-weight: bold; color: #6b7280; border-bottom: 1px solid #f1f5f9; padding: 12px 5px;">Khối xét tuyển (Tổ hợp):</td>
                        <td class="font-bold text-gray-900 border-b border-gray-100" style="font-weight: bold; color: #111827; border-bottom: 1px solid #f1f5f9; padding: 12px 5px;">{{to_hop}}</td>
                    </tr>
                    <tr>
                        <td class="font-bold text-gray-500" style="font-weight: bold; color: #6b7280; padding: 12px 5px;">Điểm xét tuyển:</td>
                        <td class="font-bold text-green-700 text-lg" style="font-weight: bold; color: #15803d; font-size: 16px; padding: 12px 5px;">{{diem_xt}}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- TAB 2: XÁC NHẬN -->
        <div id="tab2" x-show="activeTab === 2 || !window.Alpine" class="tab-pane" style="margin-bottom: 30px;">
            <h2 class="text-xl font-bold text-red-700 uppercase mb-4 border-b-2 border-red-100 pb-2 m-0" style="font-size: 18px; font-weight: bold; color: #b91c1c; margin: 0 0 15px 0; border-bottom: 2px solid #fee2e2; padding-bottom: 10px; text-transform: uppercase;">2. Hướng dẫn & Xác nhận Nhập học</h2>
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-xl mb-6" style="background-color: #eff6ff; border-left: 4px solid #3b82f6; padding: 15px; border-radius: 0 12px 12px 0; margin-bottom: 25px;">
                <p class="m-0 text-sm sm:text-base text-blue-900 mb-2" style="margin: 0 0 8px 0; font-size: 15px; color: #1e3a8a;"><strong>⚠️ QUAN TRỌNG:</strong> Thí sinh bắt buộc phải thực hiện xác nhận nhập học trên <strong>CẢ HAI</strong> hệ thống dưới đây trước 17:00 ngày 27/08/2026.</p>
                <p class="m-0 text-sm sm:text-base text-blue-900 italic" style="margin: 0; font-size: 14px; color: #1e3a8a; font-style: italic;">Nếu không thực hiện đầy đủ, nhà trường sẽ coi như bạn từ chối quyền trúng tuyển.</p>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-6" style="display: grid; grid-template-columns: 1fr; gap: 20px; text-align: center;">
                <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm" style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 25px;">
                    <div class="w-16 h-16 mx-auto bg-red-100 text-red-600 rounded-full flex items-center justify-center mb-4" style="width: 64px; height: 64px; margin: 0 auto 15px; background: #fee2e2; color: #dc2626; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px;"><i class="fas fa-university"></i></div>
                    <h3 class="font-bold text-gray-900 mb-2" style="font-weight: bold; color: #111827; margin-bottom: 10px;">HỆ THỐNG NHÀ TRƯỜNG</h3>
                    <p class="text-sm text-gray-600 mb-6" style="font-size: 14px; color: #4b5563; margin-bottom: 20px; min-height: 40px;">Xác nhận trực tuyến qua Cổng thông tin Tuyển sinh Trường Đại học Hùng Vương.</p>
                    <a href="{{login_url}}" target="_blank" class="inline-block bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg w-full" style="display: block; background-color: #dc2626; color: #ffffff; text-decoration: none; padding: 12px; border-radius: 8px; font-weight: bold; text-align: center;">
                        <i class="fas fa-check mr-2"></i> BƯỚC 1: XÁC NHẬN TẠI TRƯỜNG
                    </a>
                </div>
                
                <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm" style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 25px;">
                    <div class="w-16 h-16 mx-auto bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mb-4" style="width: 64px; height: 64px; margin: 0 auto 15px; background: #dbeafe; color: #2563eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px;"><i class="fas fa-globe"></i></div>
                    <h3 class="font-bold text-gray-900 mb-2" style="font-weight: bold; color: #111827; margin-bottom: 10px;">HỆ THỐNG BỘ GD&ĐT</h3>
                    <p class="text-sm text-gray-600 mb-6" style="font-size: 14px; color: #4b5563; margin-bottom: 20px; min-height: 40px;">Xác nhận trên Cổng thông tin của Bộ Giáo dục (chọn đúng ngành trúng tuyển).</p>
                    <a href="https://thisinh.thitotnghiepthpt.edu.vn/" target="_blank" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg w-full" style="display: block; background-color: #2563eb; color: #ffffff; text-decoration: none; padding: 12px; border-radius: 8px; font-weight: bold; text-align: center;">
                        <i class="fas fa-check mr-2"></i> BƯỚC 2: XÁC NHẬN TẠI BỘ
                    </a>
                </div>
            </div>
        </div>

        <!-- TAB 3: KINH PHÍ -->
        <div id="tab3" x-show="activeTab === 3 || !window.Alpine" class="tab-pane" style="margin-bottom: 30px;">
            <h2 class="text-xl font-bold text-red-700 uppercase mb-4 border-b-2 border-red-100 pb-2 m-0" style="font-size: 18px; font-weight: bold; color: #b91c1c; margin: 0 0 15px 0; border-bottom: 2px solid #fee2e2; padding-bottom: 10px; text-transform: uppercase;">3. Thông tin Kinh phí Nhập học</h2>
            <p class="text-base text-gray-700 leading-relaxed mb-6" style="font-size: 15px; color: #374151; margin-bottom: 20px;">
                Thí sinh vui lòng nộp các khoản kinh phí (Lệ phí nhập học, Tạm thu học phí học kỳ I...) qua hình thức chuyển khoản để việc đối soát diễn ra tự động và nhanh chóng.
            </p>
            
            <div class="bg-gray-50 border border-gray-200 rounded-2xl p-6 shadow-sm" style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 16px; padding: 30px 20px;">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center" style="display: flex; flex-wrap: wrap; gap: 20px; align-items: center;">
                    <!-- QR Code -->
                    <div class="text-center" style="flex: 1; min-width: 250px; text-align: center;">
                        <p class="text-sm text-gray-600 mb-4 font-bold" style="font-size: 14px; color: #4b5563; margin-bottom: 15px; font-weight: bold;">QUÉT MÃ QR BẰNG APP NGÂN HÀNG</p>
                        {{QR_ThanhToan}}
                    </div>
                    
                    <!-- Bank Info -->
                    <div style="flex: 1.5; min-width: 280px;">
                        <div style="margin-bottom: 15px;">
                            <p class="text-sm text-gray-500 uppercase tracking-widest font-bold mb-1 m-0" style="font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; font-weight: bold; margin: 0 0 5px 0;">Ngân hàng</p>
                            <p class="text-xl font-bold text-gray-900 mb-0 m-0" style="font-size: 20px; font-weight: bold; color: #111827; margin: 0;">{{NGANHANG}}</p>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <p class="text-sm text-gray-500 uppercase font-bold mb-1 m-0" style="font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: bold; margin: 0 0 5px 0;">Số tài khoản</p>
                            <p class="text-xl font-bold text-gray-900 mb-0 m-0" style="font-size: 22px; font-weight: bold; color: #111827; margin: 0; font-family: monospace;">{{SOTK}}</p>
                        </div>
                        
                        <div style="margin-bottom: 20px;">
                            <p class="text-sm text-gray-500 uppercase font-bold mb-1 m-0" style="font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: bold; margin: 0 0 5px 0;">Số tiền (Tạm thu)</p>
                            <p class="text-2xl font-bold text-red-700 mb-0 m-0" style="font-size: 24px; font-weight: bold; color: #b91c1c; margin: 0;">{{SoTien}} VNĐ</p>
                        </div>
                        
                        <div class="bg-yellow-50 px-4 py-3 rounded-lg border border-yellow-200" style="background-color: #fefce8; padding: 12px 16px; border-radius: 8px; border: 1px solid #fef08a;">
                            <p class="text-sm text-gray-700 font-bold mb-1 m-0" style="font-size: 13px; color: #374151; font-weight: bold; margin: 0 0 5px 0;">Nội dung chuyển khoản (Ghi chính xác):</p>
                            <p class="text-lg font-mono font-bold text-blue-800 m-0" style="font-size: 18px; font-family: monospace; font-weight: bold; color: #1e40af; margin: 0; letter-spacing: 1px;">{{NOIDUNGCK}}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 4: HỒ SƠ -->
        <div id="tab4" x-show="activeTab === 4 || !window.Alpine" class="tab-pane" style="margin-bottom: 30px;">
            <h2 class="text-xl font-bold text-red-700 uppercase mb-4 border-b-2 border-red-100 pb-2 m-0" style="font-size: 18px; font-weight: bold; color: #b91c1c; margin: 0 0 15px 0; border-bottom: 2px solid #fee2e2; padding-bottom: 10px; text-transform: uppercase;">4. Thông tin và Hồ sơ Nhập học</h2>
            
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-r-xl mb-6" style="background-color: #f0fdf4; border-left: 4px solid #22c55e; padding: 15px; border-radius: 0 12px 12px 0; margin-bottom: 25px;">
                <h3 class="font-bold text-green-900 text-base mb-2 m-0" style="font-weight: bold; color: #14532d; font-size: 16px; margin: 0 0 8px 0;"><i class="fas fa-calendar-alt mr-2"></i> Lịch nhập học trực tiếp</h3>
                <p class="m-0 text-sm sm:text-base text-green-800" style="margin: 0 0 5px 0; font-size: 15px; color: #166534;"><strong>⏱ Thời gian:</strong> 08:00 - 17:00, Chủ nhật ngày 06/09/2026</p>
                <p class="m-0 text-sm sm:text-base text-green-800" style="margin: 0; font-size: 15px; color: #166534;"><strong>📍 Địa điểm:</strong> Hội trường Đa năng, Trường Đại học Hùng Vương, Phường Nông Trang, TP. Việt Trì.</p>
            </div>

            <h3 class="font-bold text-gray-900 text-base mb-3 m-0" style="font-weight: bold; color: #111827; font-size: 16px; margin: 0 0 15px 0;">Thí sinh cần chuẩn bị các giấy tờ sau:</h3>
            <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm" style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px;">
                <ul class="list-none text-gray-700 space-y-3 m-0 p-0" style="font-size: 15px; color: #374151; list-style: none; margin: 0; padding: 0;">
                    <li style="margin-bottom: 12px; display: flex; align-items: flex-start;"><i class="fas fa-check-square text-red-600 mt-1 mr-3" style="color: #dc2626; margin-right: 10px; margin-top: 4px;"></i> <div><strong>Giấy báo trúng tuyển:</strong> Bản in từ hệ thống hoặc nhận trực tiếp tại trường.</div></li>
                    <li style="margin-bottom: 12px; display: flex; align-items: flex-start;"><i class="fas fa-check-square text-red-600 mt-1 mr-3" style="color: #dc2626; margin-right: 10px; margin-top: 4px;"></i> <div><strong>Giấy chứng nhận kết quả thi TN THPT:</strong> Bản chính có dấu đỏ hợp lệ.</div></li>
                    <li style="margin-bottom: 12px; display: flex; align-items: flex-start;"><i class="fas fa-check-square text-red-600 mt-1 mr-3" style="color: #dc2626; margin-right: 10px; margin-top: 4px;"></i> <div><strong>Học bạ THPT:</strong> 01 bản sao có công chứng/chứng thực.</div></li>
                    <li style="margin-bottom: 12px; display: flex; align-items: flex-start;"><i class="fas fa-check-square text-red-600 mt-1 mr-3" style="color: #dc2626; margin-right: 10px; margin-top: 4px;"></i> <div><strong>Căn cước công dân & Giấy khai sinh:</strong> 01 bản sao công chứng mỗi loại.</div></li>
                    <li style="margin-bottom: 12px; display: flex; align-items: flex-start;"><i class="fas fa-check-square text-red-600 mt-1 mr-3" style="color: #dc2626; margin-right: 10px; margin-top: 4px;"></i> <div><strong>Giấy tờ ưu tiên:</strong> Giấy xác nhận hộ nghèo, con thương binh... (nếu có).</div></li>
                </ul>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 pt-6 border-t border-gray-200 text-center text-xs sm:text-sm text-gray-500 m-0" style="margin-top: 40px; padding-top: 25px; border-top: 1px solid #e5e7eb; text-align: center; font-size: 13px; color: #6b7280;">
            <p class="font-bold mb-1 text-gray-800 m-0" style="font-weight: bold; color: #374151; margin: 0 0 5px 0; text-transform: uppercase;">HỘI ĐỒNG TUYỂN SINH TRƯỜNG ĐẠI HỌC HÙNG VƯƠNG</p>
            <p class="m-0" style="margin: 0; margin-bottom: 5px;"><i class="fas fa-phone-alt"></i> Hotline: 1900 1234 &nbsp;|&nbsp; <i class="fas fa-envelope"></i> Email: tuyensinh@hvu.edu.vn</p>
            <p class="mt-2 text-xs italic m-0" style="margin-top: 10px; font-size: 11px; font-style: italic;">Lưu ý: Thư này được tạo tự động từ Hệ thống tuyển sinh. Vui lòng không trả lời qua email này.</p>
        </div>
    </div>
</div>
<!-- KẾT THÚC MẪU THÔNG BÁO -->
HTML;

$stmt = $db->prepare("UPDATE email_templates SET body = :body WHERE code = 'ADMISSION_LETTER'");
$stmt->execute(['body' => $templateHtml]);

echo "Updated email template ADMISSION_LETTER successfully.\n";
