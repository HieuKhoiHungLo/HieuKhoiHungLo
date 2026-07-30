<?php

namespace App\Services;

class NotebookLMService
{
    protected $mcpUrl;
    protected $defaultNotebookUrl;

    public function __construct()
    {
        $this->mcpUrl = $_ENV['NOTEBOOKLM_MCP_URL'] ?? 'http://127.0.0.1:3000/mcp';
        $this->defaultNotebookUrl = $_ENV['NOTEBOOKLM_DEFAULT_NOTEBOOK_URL'] ?? '';
    }

    /**
     * Send a JSON-RPC request to the NotebookLM MCP server.
     *
     * @param string $toolName
     * @param array $arguments
     * @return array
     * @throws \Exception
     */
    protected function callTool(string $toolName, array $arguments): array
    {
        // 1. Initialize MCP Session
        $payloadInit = [
            'jsonrpc' => '2.0',
            'method'  => 'initialize',
            'params'  => [
                'protocolVersion' => '2024-11-05',
                'capabilities'    => (object)[],
                'clientInfo'      => [
                    'name'    => 'PHP-Client',
                    'version' => '1.0.0'
                ]
            ],
            'id'      => 1
        ];

        $ch = curl_init($this->mcpUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payloadInit));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json, text/event-stream'
        ]);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \Exception("Không thể khởi động kết nối MCP tại {$this->mcpUrl}: {$error}");
        }

        if ($httpCode !== 200) {
            throw new \Exception("Không thể khởi động phiên làm việc MCP (HTTP {$httpCode}). Nội dung: {$response}");
        }

        $headersStr = substr($response, 0, $headerSize);
        $sessionId = null;
        if (preg_match('/mcp-session-id:\s*([^\r\n]+)/i', $headersStr, $matches)) {
            $sessionId = trim($matches[1]);
        }

        if (!$sessionId) {
            throw new \Exception("Không tìm thấy mã phiên làm việc 'mcp-session-id' trong phản hồi của server.");
        }

        // 2. Execute actual Tool Call
        $payloadTool = [
            'jsonrpc' => '2.0',
            'method'  => 'tools/call',
            'params'  => [
                'name'      => $toolName,
                'arguments' => (object)$arguments
            ],
            'id'      => 2
        ];

        $ch = curl_init($this->mcpUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payloadTool));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json, text/event-stream',
            "mcp-session-id: {$sessionId}"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 600); // 10 minutes timeout

        $toolResponse = curl_exec($ch);
        $toolHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $toolError = curl_error($ch);
        curl_close($ch);

        // 3. Immediately close/delete the session in background to release server slot
        $chDelete = curl_init($this->mcpUrl);
        curl_setopt($chDelete, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chDelete, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($chDelete, CURLOPT_HTTPHEADER, [
            "mcp-session-id: {$sessionId}"
        ]);
        curl_setopt($chDelete, CURLOPT_TIMEOUT, 10);
        curl_exec($chDelete);
        curl_close($chDelete);

        // 4. Process Tool Call response
        if ($toolResponse === false) {
            throw new \Exception("Lỗi kết nối khi gọi công cụ '{$toolName}': {$toolError}");
        }

        if ($toolHttpCode !== 200) {
            throw new \Exception("Lỗi phản hồi HTTP {$toolHttpCode} khi gọi công cụ '{$toolName}': {$toolResponse}");
        }

        // Parse SSE format (find the line starting with "data:")
        $dataLine = null;
        $lines = explode("\n", $toolResponse);
        foreach ($lines as $line) {
            if (stripos($line, 'data:') === 0) {
                $dataLine = trim(substr($line, 5));
                break;
            }
        }

        if (!$dataLine) {
            $dataLine = $toolResponse;
        }

        $result = json_decode($dataLine, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Dịch vụ MCP trả về dữ liệu không đúng cấu trúc: {$toolResponse}");
        }

        if (isset($result['error'])) {
            $msg = $result['error']['message'] ?? 'Lỗi không xác định';
            throw new \Exception("Dịch vụ MCP báo lỗi: {$msg}");
        }

        $content = $result['result'] ?? null;
        if (!$content) {
            throw new \Exception("Không tìm thấy kết quả trong phản hồi từ MCP.");
        }

        if (isset($content['isError']) && $content['isError'] === true) {
            $errorMsg = '';
            if (isset($content['content']) && is_array($content['content'])) {
                foreach ($content['content'] as $c) {
                    if (isset($c['text'])) {
                        $errorMsg .= $c['text'] . ' ';
                    }
                }
            }
            throw new \Exception("Lỗi thực thi công cụ '{$toolName}': " . trim($errorMsg));
        }

        return $content;
    }

    /**
     * Add a text source directly to the configured NotebookLM.
     *
     * @param string $title
     * @param string $content
     * @param string|null $notebookUrl
     * @return array
     * @throws \Exception
     */
    public function addTextSource(string $title, string $content, ?string $notebookUrl = null): array
    {
        $notebookUrl = $notebookUrl ?: $this->defaultNotebookUrl;
        if (empty($notebookUrl)) {
            throw new \Exception("Chưa cấu hình đường dẫn NotebookLM mặc định.");
        }

        $arguments = [
            'type'         => 'text',
            'title'        => $title,
            'content'      => $content,
            'notebook_url' => $notebookUrl
        ];

        return $this->callTool('add_source', $arguments);
    }

    /**
     * Add a URL source directly to the configured NotebookLM.
     *
     * @param string $title
     * @param string $url
     * @param string|null $notebookUrl
     * @return array
     * @throws \Exception
     */
    public function addUrlSource(string $title, string $url, ?string $notebookUrl = null): array
    {
        $notebookUrl = $notebookUrl ?: $this->defaultNotebookUrl;
        if (empty($notebookUrl)) {
            throw new \Exception("Chưa cấu hình đường dẫn NotebookLM mặc định.");
        }

        $arguments = [
            'type'         => 'url',
            'title'        => $title,
            'content'      => $url,
            'notebook_url' => $notebookUrl
        ];

        return $this->callTool('add_source', $arguments);
    }

    /**
     * Ask a question against the configured NotebookLM.
     *
     * @param string $question
     * @param string|null $sessionId
     * @param string|null $notebookUrl
     * @return array
     * @throws \Exception
     */
    public function askQuestion(string $question, ?string $sessionId = null, ?string $notebookUrl = null): array
    {
        $notebookUrl = $notebookUrl ?: $this->defaultNotebookUrl;
        if (empty($notebookUrl)) {
            throw new \Exception("Chưa cấu hình đường dẫn NotebookLM mặc định.");
        }

        $arguments = [
            'question'     => $question,
            'notebook_url' => $notebookUrl
        ];

        if ($sessionId) {
            $arguments['session_id'] = $sessionId;
        }

        return $this->callTool('ask_question', $arguments);
    }
}
