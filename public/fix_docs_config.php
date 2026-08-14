<?php
try {
    $pdoLocal = new PDO('pgsql:host=aws-1-ap-northeast-2.pooler.supabase.com;port=6543;dbname=postgres', 'postgres.zorxrwobsfhejutgjsbi', 'Phutho2024@!');
    $pdoVPS = new PDO('pgsql:host=127.0.0.1;port=5433;dbname=tuyensinh_thv', 'tuyensinh_app', 'Phutho2024@!');
    
    foreach ([$pdoLocal, $pdoVPS] as $db) {
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $db->query("SELECT id FROM dot_tuyen_sinh");
        $sessions = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $docs = [
            [
                'ten' => 'Giấy chứng nhận kết quả thi tốt nghiệp THPT năm 2026',
                'vals' => json_encode(['Bản gốc', 'Chưa nộp', 'Bản sao'], JSON_UNESCAPED_UNICODE),
                'def' => 'Bản gốc',
                'req' => 1
            ],
            [
                'ten' => 'Học bạ Trung học phổ thông',
                'vals' => json_encode(['Bản gốc + sao chứng thực', 'Chưa nộp', 'Bản gốc', 'Bản sao chứng thực'], JSON_UNESCAPED_UNICODE),
                'def' => 'Bản gốc + sao chứng thực',
                'req' => 1
            ]
        ];

        $insStmt = $db->prepare("INSERT INTO nhap_hoc_ho_so (session_id, ten_ho_so, cac_gia_tri, gia_tri_mac_dinh, bat_buoc, thu_tu) VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($sessions as $sessionId) {
            $db->exec("DELETE FROM nhap_hoc_ho_so WHERE session_id = " . intval($sessionId));
            foreach ($docs as $i => $doc) {
                $insStmt->execute([
                    $sessionId,
                    $doc['ten'],
                    $doc['vals'],
                    $doc['def'],
                    $doc['req'],
                    $i + 1
                ]);
            }
        }
    }
    
    echo "Fixed documents config on both Local and VPS.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
