<?php
if (!defined('__TYPECHO_ROOT_DIR__'))
    exit;

/**
 * Dear Theme Updater
 * 
 * Handles theme update checks against GitHub Releases
 * and automatic installation/overwriting of theme files.
 */
class DearTheme_Updater
{
    const REPO_OWNER = 'UtopiaXC';
    const REPO_NAME = 'Theme-Dear-For-Typecho';
    const GITHUB_API_LATEST = 'https://api.github.com/repos/UtopiaXC/Theme-Dear-For-Typecho/releases/latest';

    /**
     * Get current theme version from index.php docblock
     *
     * @return string
     */
    public static function getVersion()
    {
        $indexPath = dirname(dirname(__DIR__)) . '/index.php';
        if (file_exists($indexPath)) {
            $content = file_get_contents($indexPath, false, null, 0, 2048);
            if (preg_match('/@version\s+([0-9\.]+[\w\-.]*)/i', $content, $matches)) {
                return trim($matches[1]);
            }
        }
        return '1.0.0';
    }

    /**
     * Get theme package name from index.php docblock
     *
     * @return string
     */
    public static function getPackage()
    {
        $indexPath = dirname(dirname(__DIR__)) . '/index.php';
        if (file_exists($indexPath)) {
            $content = file_get_contents($indexPath, false, null, 0, 2048);
            if (preg_match('/@package\s+([^\r\n]+)/i', $content, $matches)) {
                return trim($matches[1]);
            }
        }
        return 'Dear-For-Typecho';
    }

    /**
     * Check if current user is logged in administrator
     *
     * @return bool
     */
    public static function isAdmin()
    {
        try {
            if (class_exists('\\Widget\\User')) {
                $user = \Widget\User::alloc();
            } elseif (class_exists('Typecho_Widget')) {
                $user = \Typecho_Widget::widget('Widget_User');
            } else {
                return false;
            }
            return $user->hasLogin() && $user->pass('administrator', true);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Require administrator privileges
     */
    public static function requireAdmin()
    {
        if (!self::isAdmin()) {
            self::jsonResponse(['success' => false, 'message' => '无权操作，请以管理员身份登录'], 403);
            exit;
        }
    }

    /**
     * Output JSON response
     *
     * @param array $data
     * @param int $statusCode
     */
    public static function jsonResponse($data, $statusCode = 200)
    {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Intercept and route update AJAX requests
     */
    public static function handleRequest()
    {
        self::requireAdmin();
        $action = isset($_REQUEST['dear_update_action']) ? trim($_REQUEST['dear_update_action']) : '';

        try {
            switch ($action) {
                case 'check':
                    self::actionCheck();
                    break;
                case 'do_update':
                    self::actionDoUpdate();
                    break;
                default:
                    self::jsonResponse(['success' => false, 'message' => '无效的操作指令'], 400);
            }
        } catch (\Exception $e) {
            self::jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
        exit;
    }

    /**
     * Check for latest release via GitHub API (server fallback)
     */
    public static function actionCheck()
    {
        $currentVersion = self::getVersion();
        $release = self::fetchLatestRelease();

        $tag = isset($release['tag_name']) ? trim($release['tag_name']) : '';
        $cleanTag = ltrim($tag, 'vV');
        $cleanCurrent = ltrim($currentVersion, 'vV');

        $hasUpdate = version_compare($cleanTag, $cleanCurrent, '>');

        // Locate download zip asset or fallback to zipball_url
        $downloadUrl = '';
        if (!empty($release['assets']) && is_array($release['assets'])) {
            foreach ($release['assets'] as $asset) {
                if (isset($asset['name']) && preg_match('/\.zip$/i', $asset['name'])) {
                    $downloadUrl = isset($asset['browser_download_url']) ? $asset['browser_download_url'] : '';
                    break;
                }
            }
        }
        if (empty($downloadUrl) && !empty($release['zipball_url'])) {
            $downloadUrl = $release['zipball_url'];
        }

        self::jsonResponse([
            'success' => true,
            'current_version' => $currentVersion,
            'latest_tag' => $tag,
            'clean_latest' => $cleanTag,
            'clean_current' => $cleanCurrent,
            'has_update' => $hasUpdate,
            'release_name' => isset($release['name']) ? $release['name'] : $tag,
            'release_body' => isset($release['body']) ? $release['body'] : '',
            'published_at' => isset($release['published_at']) ? $release['published_at'] : '',
            'download_url' => $downloadUrl
        ]);
    }

    /**
     * Download and apply theme update
     */
    public static function actionDoUpdate()
    {
        @set_time_limit(300);
        @ini_set('memory_limit', '256M');

        $downloadUrl = isset($_POST['download_url']) ? trim($_POST['download_url']) : '';

        // If not provided in POST, query latest release from GitHub API
        if (empty($downloadUrl)) {
            $release = self::fetchLatestRelease();
            if (!empty($release['assets']) && is_array($release['assets'])) {
                foreach ($release['assets'] as $asset) {
                    if (isset($asset['name']) && preg_match('/\.zip$/i', $asset['name'])) {
                        $downloadUrl = isset($asset['browser_download_url']) ? $asset['browser_download_url'] : '';
                        break;
                    }
                }
            }
            if (empty($downloadUrl) && !empty($release['zipball_url'])) {
                $downloadUrl = $release['zipball_url'];
            }
        }

        if (empty($downloadUrl)) {
            throw new \Exception('未能获取有效的更新包下载链接');
        }

        // Validate download URL origin for security
        $parsedUrl = parse_url($downloadUrl);
        $allowedHosts = [
            'github.com',
            'api.github.com',
            'codeload.github.com',
            'release-assets.githubusercontent.com',
            'objects.githubusercontent.com'
        ];
        $host = isset($parsedUrl['host']) ? strtolower($parsedUrl['host']) : '';
        $isHostAllowed = in_array($host, $allowedHosts, true) ||
                         preg_match('/\.githubusercontent\.com$/i', $host) ||
                         preg_match('/\.amazonaws\.com$/i', $host) ||
                         preg_match('/\.core\.windows\.net$/i', $host);
        if (!$isHostAllowed) {
            throw new \Exception('非法的下载源，仅允许来自 GitHub 官方 Release 的下载链接');
        }

        $themeDir = dirname(dirname(__DIR__));
        if (!is_writable($themeDir)) {
            throw new \Exception('主题目录 [' . $themeDir . '] 没有写入权限，请检查服务器文件权限');
        }

        if (!class_exists('ZipArchive')) {
            throw new \Exception('服务器 PHP 未开启 ZipArchive 扩展，无法解压主题更新包');
        }

        // Download zip to temp location
        $tempZip = tempnam(sys_get_temp_dir(), 'dear_update_') . '.zip';
        self::downloadFile($downloadUrl, $tempZip);

        if (!file_exists($tempZip) || filesize($tempZip) < 100) {
            @unlink($tempZip);
            throw new \Exception('更新包下载失败或文件为空，请检查服务器外网网络连接');
        }

        // Open zip
        $zip = new \ZipArchive();
        $openResult = $zip->open($tempZip);
        if ($openResult !== true) {
            @unlink($tempZip);
            throw new \Exception('无法打开更新压缩包，文件可能损坏 (错误码: ' . $openResult . ')');
        }

        // Detect root folder prefix (e.g. 'Dear-For-Typecho/' or 'UtopiaXC-Theme-Dear-For-Typecho-xxxx/')
        $prefix = '';
        if ($zip->numFiles > 0) {
            $firstEntry = $zip->getNameIndex(0);
            $slashPos = strpos($firstEntry, '/');
            if ($slashPos !== false) {
                $candidate = substr($firstEntry, 0, $slashPos + 1);
                $allMatch = true;
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entryName = $zip->getNameIndex($i);
                    if (strpos($entryName, $candidate) !== 0) {
                        $allMatch = false;
                        break;
                    }
                }
                if ($allMatch) {
                    $prefix = $candidate;
                }
            }
        }

        $prefixLen = strlen($prefix);
        $extractedCount = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            $relPath = $prefixLen > 0 ? substr($entryName, $prefixLen) : $entryName;
            $relPath = trim($relPath, "/\\");

            // Prevent path traversal
            if ($relPath === '' || strpos($relPath, '..') !== false) {
                continue;
            }

            // Exclude git/github/idea/demo/release_note metadata
            if (strpos($relPath, '.git') === 0 || 
                strpos($relPath, '.github') === 0 || 
                strpos($relPath, '.idea') === 0 ||
                strpos($relPath, 'demo') === 0 ||
                strpos($relPath, 'release_note') === 0) {
                continue;
            }

            $dest = $themeDir . '/' . $relPath;
            if (substr($entryName, -1) === '/') {
                if (!is_dir($dest)) {
                    @mkdir($dest, 0755, true);
                }
            } else {
                $parent = dirname($dest);
                if (!is_dir($parent)) {
                    @mkdir($parent, 0755, true);
                }
                $fileData = $zip->getFromIndex($i);
                if ($fileData !== false) {
                    file_put_contents($dest, $fileData);
                    $extractedCount++;
                }
            }
        }

        $zip->close();
        @unlink($tempZip);

        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }

        $newVersion = self::getVersion();
        self::jsonResponse([
            'success' => true,
            'message' => '主题更新成功！已更新至 v' . $newVersion,
            'version' => $newVersion,
            'files_updated' => $extractedCount
        ]);
    }

    /**
     * Download file via cURL or stream context
     *
     * @param string $url
     * @param string $destination
     * @return bool
     * @throws Exception
     */
    private static function downloadFile($url, $destination)
    {
        $fp = fopen($destination, 'w+');
        if (!$fp) {
            throw new \Exception('无法创建临时文件进行下载写入');
        }

        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 6);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Dear-Theme-Updater');
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
            curl_setopt($ch, CURLOPT_TIMEOUT, 180);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            fclose($fp);
            curl_close($ch);

            if (!$result || ($httpCode >= 400 && $httpCode !== 0)) {
                throw new \Exception('下载失败：HTTP ' . $httpCode . ' ' . $curlError);
            }
            return true;
        } else {
            fclose($fp);
            $opts = [
                'http' => [
                    'method' => 'GET',
                    'header' => "User-Agent: Dear-Theme-Updater\r\n",
                    'follow_location' => 1,
                    'max_redirects' => 6,
                    'timeout' => 180
                ],
                'ssl' => [
                    'verify_peer' => true
                ]
            ];
            $context = stream_context_create($opts);
            $data = @file_get_contents($url, false, $context);
            if ($data === false) {
                throw new \Exception('file_get_contents 下载更新包失败');
            }
            file_put_contents($destination, $data);
            return true;
        }
    }

    /**
     * Fetch latest release JSON from GitHub API
     *
     * @return array
     * @throws Exception
     */
    private static function fetchLatestRelease()
    {
        $url = self::GITHUB_API_LATEST;
        $content = '';

        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Dear-Theme-Updater');
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/vnd.github.v3+json'
            ]);
            $content = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($httpCode >= 400 || !$content) {
                throw new \Exception('无法获取 GitHub Release 信息 (HTTP ' . $httpCode . '): ' . $error);
            }
        } else {
            $opts = [
                'http' => [
                    'method' => 'GET',
                    'header' => "User-Agent: Dear-Theme-Updater\r\nAccept: application/vnd.github.v3+json\r\n",
                    'timeout' => 30
                ]
            ];
            $context = stream_context_create($opts);
            $content = @file_get_contents($url, false, $context);
            if ($content === false) {
                throw new \Exception('无法连接到 GitHub API 获取 Release 信息');
            }
        }

        $json = json_decode($content, true);
        if (!is_array($json)) {
            throw new \Exception('GitHub API 返回的数据格式无法解析');
        }

        return $json;
    }
}

