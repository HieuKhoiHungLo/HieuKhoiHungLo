<?php
namespace App\Constants;

class UserStatus {
    const PENDING = 'Chờ duyệt';
    const APPROVED = 'Đã duyệt';
    const REJECTED = 'Từ chối';
    const MISSING = 'Thiếu thông tin';
}

class Permission {
    const REVIEW = 'review';
    const STATS = 'stats';
    const MANAGE_ACCOUNTS = 'manage_accounts';
    const MANAGE_SETTINGS = 'manage_settings';
}

class UploadPath {
    const PROFILES = 'uploads/profiles/';
    const EVIDENCE = 'uploads/evidence/';
}
