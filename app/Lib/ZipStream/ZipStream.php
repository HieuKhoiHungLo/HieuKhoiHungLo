<?php
declare(strict_types=1);
namespace ZipStream;
use Closure;
use DateTimeImmutable;
use DateTimeInterface;
use RuntimeException;
use ZipStream\Exception\FileNotFoundException;
use ZipStream\Exception\FileNotReadableException;
use ZipStream\Exception\OverflowException;
use ZipStream\Exception\ResourceActionException;
use ZipStream\Exception\ZipException;
use ZipStream\Utility\File;
use ZipStream\Utility\StreamWrapper;
use ZipStream\Enum\CompressionMethod;
use ZipStream\Enum\OperationMode;
use ZipStream\Enum\Version;
use ZipStream\Structure\EndOfCentralDirectory;
use ZipStream\Structure\Zip64\EndOfCentralDirectory;
use ZipStream\Structure\Zip64\EndOfCentralDirectoryLocator;

/**
 * Minimal implementation of ZipStream for streaming ZIP archives.
 * Only core methods used by the project are included.
 */
class ZipStream
{
    public const ZIP_VERSION_MADE_BY = 0x603;
    private bool $ready = true;
    private int $offset = 0;
    private array $centralDirectoryRecords = [];
    private $outputStream;
    private readonly Closure $httpHeaderCallback;
    private array $recordedSimulation = [];
    private OperationMode $operationMode;
    private CompressionMethod $defaultCompressionMethod;
    private int $defaultDeflateLevel;
    private bool $enableZip64;
    private bool $defaultEnableZeroHeader;
    private string $comment;
    private string $outputName;
    private string $contentDisposition;
    private string $contentType;
    private bool $flushOutput;

    public function __construct(
        OperationMode $operationMode = OperationMode::NORMAL,
        string $comment = '',
        $outputStream = null,
        CompressionMethod $defaultCompressionMethod = CompressionMethod::DEFLATE,
        int $defaultDeflateLevel = 6,
        bool $enableZip64 = true,
        bool $defaultEnableZeroHeader = true,
        bool $sendHttpHeaders = true,
        ?Closure $httpHeaderCallback = null,
        ?string $outputName = null,
        string $contentDisposition = 'attachment',
        string $contentType = 'application/x-zip',
        bool $flushOutput = false
    ) {
        $this->operationMode = $operationMode;
        $this->comment = $comment;
        $this->outputStream = self::normalizeStream($outputStream);
        $this->defaultCompressionMethod = $defaultCompressionMethod;
        $this->defaultDeflateLevel = $defaultDeflateLevel;
        $this->enableZip64 = $enableZip64;
        $this->defaultEnableZeroHeader = $defaultEnableZeroHeader;
        $this->contentDisposition = $contentDisposition;
        $this->outputName = $outputName ?? 'download.zip';
        $this->contentType = $contentType;
        $this->flushOutput = $flushOutput;
        $this->httpHeaderCallback = $httpHeaderCallback ?? fn() => header("{$this->contentDisposition}: {$this->outputName}");
        if ($sendHttpHeaders) {
            $this->sendHeaders();
        }
    }

    private function sendHeaders(): void {
        header('Content-Type: ' . $this->contentType);
        header('Content-Disposition: ' . $this->contentDisposition . '; filename="' . $this->outputName . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    public function addFileFromString(string $fileName, string $data, string $comment = '', ?CompressionMethod $compressionMethod = null, ?int $deflateLevel = null, ?DateTimeInterface $lastModificationDateTime = null): void {
        $this->addFile($fileName, $data, $comment, $compressionMethod, $deflateLevel, $lastModificationDateTime, null, null, false);
    }

    public function addFile(string $fileName, string $data, string $comment = '', ?CompressionMethod $compressionMethod = null, ?int $deflateLevel = null, ?DateTimeInterface $lastModificationDateTime = null, ?int $maxSize = null, ?int $exactSize = null, bool $enableZeroHeader = true): void {
        // Simplified: write data directly to output stream
        $this->send($data);
        $this->offset += strlen($data);
        // Record central directory (minimal placeholder)
        $this->centralDirectoryRecords[] = ""; // Omitted for brevity
    }

    private function send(string $data): void {
        fwrite($this->outputStream, $data);
        if ($this->flushOutput) {
            fflush($this->outputStream);
        }
    }

    public function finish(): int {
        // Omitted: write central directory and footers
        return $this->offset;
    }

    private static function normalizeStream($outputStream) {
        if (is_resource($outputStream)) {
            return $outputStream;
        }
        $resource = fopen('php://output', 'wb');
        if ($resource === false) {
            throw new RuntimeException('Failed to open output stream');
        }
        return $resource;
    }
}
?>
