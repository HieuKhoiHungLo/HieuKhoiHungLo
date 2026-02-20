<?php
namespace App\Services;

use App\Core\FileUploader;

class DriveService {
    protected $uploader;
    protected $rootFolderId;

    public function __construct(FileUploader $uploader) {
        $this->uploader = $uploader;
        $this->rootFolderId = $_ENV['GOOGLE_DRIVE_FOLDER_ID'] ?? '';
    }

    /**
     * Resolve the full path on Drive: Year > Session > Candidate
     * Returns the Folder ID of the Candidate folder.
     */
    public function resolveCandidateFolder($year, $sessionName, $cccd) {
        if (!$this->rootFolderId) return null;

        // 1. Year Folder
        // Skip creating Year folder as it is likely the root folder or managed externally
        // $yearFolderId = $this->ensureFolder((string)$year, $this->rootFolderId);
        // if (!$yearFolderId) return null;
        $yearFolderId = $this->rootFolderId;

        // 2. Session Folder
        $sessionFolderId = $this->ensureFolder($sessionName, $yearFolderId);
        if (!$sessionFolderId) return null;

        // 3. Candidate Folder
        $candidateFolderId = $this->ensureFolder($cccd, $sessionFolderId);
        
        return $candidateFolderId;
    }

    private function ensureFolder($name, $parentId) {
        // Try to find
        $id = $this->uploader->findFolder($name, $parentId);
        if ($id) return $id;

        // If not found, create
        return $this->uploader->createFolder($name, $parentId);
    }
}
