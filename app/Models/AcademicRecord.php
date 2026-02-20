<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class AcademicRecord extends \App\Core\Model {
    protected $table = 'ket_qua_hoc_tap';

    public function __construct() {
        parent::__construct();
    }

    public function getByCCCD($cccd) {
        $sql = "SELECT * FROM {$this->table} WHERE so_cccd = :cccd ORDER BY lop ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cccd' => $cccd]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByCCCDAndGrade($cccd, $grade) {
        $sql = "SELECT * FROM {$this->table} WHERE so_cccd = :cccd AND lop = :grade";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cccd' => $cccd, 'grade' => $grade]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function save($cccd, $grade, $data) {
        $record = $this->getByCCCDAndGrade($cccd, $grade);
        
        if ($record) {
             // Update
             $fields = [];
             $params = ['cccd' => $cccd, 'grade' => $grade];
             foreach ($data as $key => $value) {
                 $fields[] = "$key = :$key";
                 $params[$key] = $value;
             }
             $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE so_cccd = :cccd AND lop = :grade";
        } else {
             // Insert
             $data['so_cccd'] = $cccd;
             $data['lop'] = $grade;
             
             $cols = implode(', ', array_keys($data));
             $vals = ':' . implode(', :', array_keys($data));
             
             $sql = "INSERT INTO {$this->table} ($cols) VALUES ($vals)";
             $params = $data;
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function updateFiles($cccd, $grade, $paths) {
        // ... (Keep existing if relevant or merge logic)
        return $this->save($cccd, $grade, [
            'file_hoc_ba' => $paths['hoc_ba'] ?? null,
            'file_bang_tot_nghiep' => $paths['bang_tot_nghiep'] ?? null
        ]);
    }
}
