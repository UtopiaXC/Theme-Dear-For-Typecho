<?php
if (!defined('__TYPECHO_ROOT_DIR__'))
    exit;

class DearTheme_AiSummary
{
    private static $tableChecked = false;

    public static function handleRequest()
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        $actionParameter = isset($_REQUEST['dear_ai_action']) ? trim($_REQUEST['dear_ai_action']) : '';
        try {
            switch ($actionParameter) {
                // Public frontend actions
                case 'get':
                    self::actionGet();
                    break;
                case 'generate':
                    self::actionGenerate();
                    break;
                case 'models':
                    self::actionModels();
                    break;

                // Admin actions
                case 'admin_stats':
                    self::actionAdminStats();
                    break;
                case 'admin_clean_orphaned':
                    self::actionAdminCleanOrphaned();
                    break;
                case 'admin_clean_uncompleted':
                    self::actionAdminCleanUncompleted();
                    break;
                case 'admin_clean_model':
                    self::actionAdminCleanModel();
                    break;
                case 'admin_clean_rate_log':
                    self::actionAdminCleanRateLog();
                    break;
                case 'admin_clean_all':
                    self::actionAdminCleanAll();
                    break;
                case 'admin_rename_model_id':
                    self::actionAdminRenameModelId();
                    break;
                case 'admin_list_summaries':
                    self::actionAdminListSummaries();
                    break;
                case 'admin_delete_summary':
                    self::actionAdminDeleteSummary();
                    break;

                default:
                    self::error('无效的操作请求', 400);
            }
        } catch (\Exception $exception) {
            self::error($exception->getMessage(), 500);
        }
        exit;
    }

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

    private static function requireAdmin()
    {
        if (!self::isAdmin()) {
            self::error('无权操作，请先登录 Typecho 管理员账号', 403);
            exit;
        }
    }

    public static function hasColumn($tableName, $columnName)
    {
        $database = \Typecho\Db::get();
        $tablePrefix = $database->getPrefix();
        $databaseAdapter = $database->getAdapterName();
        $isSQLite = stripos($databaseAdapter, 'SQLite') !== false;

        try {
            if ($isSQLite) {
                $rows = $database->fetchAll($database->query("PRAGMA table_info(\"{$tablePrefix}{$tableName}\")"));
                foreach ($rows as $row) {
                    if (isset($row['name']) && strcasecmp($row['name'], $columnName) === 0) {
                        return true;
                    }
                }
            } else {
                $rows = $database->fetchAll($database->query("SHOW COLUMNS FROM `{$tablePrefix}{$tableName}` LIKE '{$columnName}'"));
                return !empty($rows);
            }
        } catch (\Exception $e) {
        }
        return false;
    }

    public static function tableExists($tableName)
    {
        $database = \Typecho\Db::get();
        $tablePrefix = $database->getPrefix();
        $databaseAdapter = $database->getAdapterName();
        $isSQLite = stripos($databaseAdapter, 'SQLite') !== false;

        try {
            if ($isSQLite) {
                $row = $database->fetchRow($database->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name=\"{$tablePrefix}{$tableName}\""));
                return !empty($row);
            } else {
                $row = $database->fetchRow($database->query("SHOW TABLES LIKE '{$tablePrefix}{$tableName}'"));
                return !empty($row);
            }
        } catch (\Exception $e) {
        }
        return false;
    }

    public static function ensureTable()
    {
        if (self::$tableChecked) {
            return;
        }
        $database = \Typecho\Db::get();
        $tablePrefix = $database->getPrefix();
        $databaseAdapter = $database->getAdapterName();
        $isSQLiteDatabase = stripos($databaseAdapter, 'SQLite') !== false;

        $summariesTableExists = self::tableExists('dear_ai_summaries');

        if (!$summariesTableExists) {
            if ($isSQLiteDatabase) {
                $database->query("CREATE TABLE IF NOT EXISTS \"{$tablePrefix}dear_ai_summaries\" (
                    \"id\" INTEGER PRIMARY KEY AUTOINCREMENT,
                    \"cid\" INTEGER NOT NULL,
                    \"model_id\" TEXT NOT NULL DEFAULT '',
                    \"model_name\" TEXT NOT NULL DEFAULT '',
                    \"model_display_name\" TEXT DEFAULT '',
                    \"summary\" TEXT DEFAULT '',
                    \"status\" TEXT DEFAULT 'completed',
                    \"error_message\" TEXT DEFAULT '',
                    \"created_at\" INTEGER DEFAULT 0,
                    \"updated_at\" INTEGER DEFAULT 0,
                    UNIQUE(\"cid\", \"model_id\")
                )");
            } else {
                $database->query("CREATE TABLE IF NOT EXISTS `{$tablePrefix}dear_ai_summaries` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `cid` INT UNSIGNED NOT NULL,
                    `model_id` VARCHAR(100) NOT NULL DEFAULT '',
                    `model_name` VARCHAR(100) NOT NULL DEFAULT '',
                    `model_display_name` VARCHAR(100) DEFAULT '',
                    `summary` TEXT,
                    `status` VARCHAR(20) DEFAULT 'completed',
                    `error_message` TEXT,
                    `created_at` INT UNSIGNED DEFAULT 0,
                    `updated_at` INT UNSIGNED DEFAULT 0,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_cid_model_id` (`cid`, `model_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            }
        } else {
            // Existing table, check and add model_id if missing
            if (!self::hasColumn('dear_ai_summaries', 'model_id')) {
                try {
                    if ($isSQLiteDatabase) {
                        $database->query("ALTER TABLE \"{$tablePrefix}dear_ai_summaries\" ADD COLUMN \"model_id\" TEXT NOT NULL DEFAULT ''");
                        try {
                            $database->query("UPDATE \"{$tablePrefix}dear_ai_summaries\" SET \"model_id\" = \"model_name\" WHERE \"model_id\" = '' OR \"model_id\" IS NULL");
                        } catch (\Exception $e) {}
                        try {
                            $database->query("CREATE UNIQUE INDEX IF NOT EXISTS \"{$tablePrefix}idx_cid_model_id\" ON \"{$tablePrefix}dear_ai_summaries\" (\"cid\", \"model_id\")");
                        } catch (\Exception $e) {}
                    } else {
                        $database->query("ALTER TABLE `{$tablePrefix}dear_ai_summaries` ADD COLUMN `model_id` VARCHAR(100) NOT NULL DEFAULT '' AFTER `cid`");
                        try {
                            $database->query("UPDATE `{$tablePrefix}dear_ai_summaries` SET `model_id` = `model_name` WHERE `model_id` = '' OR `model_id` IS NULL");
                        } catch (\Exception $e) {}
                        try {
                            $database->query("ALTER TABLE `{$tablePrefix}dear_ai_summaries` DROP INDEX `uk_cid_model`");
                        } catch (\Exception $e) {}
                        try {
                            $database->query("ALTER TABLE `{$tablePrefix}dear_ai_summaries` DROP INDEX `uk_cid`");
                        } catch (\Exception $e) {}
                        try {
                            $database->query("ALTER TABLE `{$tablePrefix}dear_ai_summaries` ADD UNIQUE KEY `uk_cid_model_id` (`cid`, `model_id`)");
                        } catch (\Exception $e) {}
                    }
                } catch (\Exception $e) {
                }
            }

            // Sync empty model_ids with model_name
            try {
                if ($isSQLiteDatabase) {
                    $database->query("UPDATE \"{$tablePrefix}dear_ai_summaries\" SET \"model_id\" = \"model_name\" WHERE (\"model_id\" = '' OR \"model_id\" IS NULL) AND \"model_name\" != ''");
                } else {
                    $database->query("UPDATE `{$tablePrefix}dear_ai_summaries` SET `model_id` = `model_name` WHERE (`model_id` = '' OR `model_id` IS NULL) AND `model_name` != ''");
                }
            } catch (\Exception $e) {
            }
        }

        // Rate log table check
        $rateLogTableExists = self::tableExists('dear_ai_rate_log');
        if (!$rateLogTableExists) {
            if ($isSQLiteDatabase) {
                $database->query("CREATE TABLE IF NOT EXISTS \"{$tablePrefix}dear_ai_rate_log\" (
                    \"id\" INTEGER PRIMARY KEY AUTOINCREMENT,
                    \"cid\" INTEGER NOT NULL DEFAULT 0,
                    \"request_time\" INTEGER NOT NULL
                )");
            } else {
                $database->query("CREATE TABLE IF NOT EXISTS `{$tablePrefix}dear_ai_rate_log` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `cid` INT UNSIGNED NOT NULL DEFAULT 0,
                    `request_time` INT UNSIGNED NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_time` (`request_time`),
                    KEY `idx_cid_time` (`cid`, `request_time`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            }
        }

        self::$tableChecked = true;
    }

    private static function actionGet()
    {
        $contentId = intval($_GET['cid'] ?? 0);
        $targetModelId = isset($_GET['model_id']) ? trim($_GET['model_id']) : '';
        $targetModelName = isset($_GET['model_name']) ? trim($_GET['model_name']) : '';
        if ($contentId <= 0) {
            self::error('无效的 CID', 400);
            return;
        }

        self::ensureTable();
        $database = \Typecho\Db::get();

        $databaseQuery = $database->select()->from('table.dear_ai_summaries')->where('cid = ?', $contentId);
        if ($targetModelId !== '') {
            $databaseQuery->where('model_id = ? OR (model_id = \'\' AND model_name = ?)', $targetModelId, $targetModelId);
        } elseif ($targetModelName !== '') {
            $databaseQuery->where('model_name = ? OR model_id = ?', $targetModelName, $targetModelName);
        }
        $databaseQuery->order('updated_at', \Typecho\Db::SORT_DESC)->limit(1);
        $queryResultRow = $database->fetchRow($databaseQuery);

        if ($queryResultRow) {
            self::success([
                'exists'     => true,
                'summary'    => $queryResultRow['summary'],
                'model'      => $queryResultRow['model_display_name'],
                'model_id'   => $queryResultRow['model_id'] ?: $queryResultRow['model_name'],
                'model_name' => $queryResultRow['model_name'],
                'status'     => $queryResultRow['status'],
                'error'      => $queryResultRow['error_message'] ?: null,
                'updated_at' => intval($queryResultRow['updated_at'])
            ]);
        } else {
            self::success(['exists' => false]);
        }
    }

    private static function actionModels()
    {
        $themeOptions = \Widget\Options::alloc();
        $parsedModelsArray  = self::parseModels($themeOptions->Dear_aiModels);
        $modelsResponseList = [];
        foreach ($parsedModelsArray as $modelIndex => $modelConfiguration) {
            $modelsResponseList[] = [
                'index'   => $modelIndex,
                'id'      => $modelConfiguration['id'],
                'display' => $modelConfiguration['model_display'],
                'name'    => $modelConfiguration['model_name']
            ];
        }
        self::success(['models' => $modelsResponseList]);
    }

    private static function actionGenerate()
    {
        $contentId = isset($_GET['cid']) ? intval($_GET['cid']) : 0;
        $targetModelIndex = isset($_GET['model_index']) ? intval($_GET['model_index']) : 0;
        $targetModelId = isset($_GET['model_id']) ? trim($_GET['model_id']) : '';

        if ($contentId <= 0) {
            self::error('无效的 CID', 400);
            return;
        }

        self::ensureTable();
        $databaseConnection = \Typecho\Db::get();
        $themeOptions = \Widget\Options::alloc();

        $requestTimeoutLimit = $themeOptions->Dear_aiTimeout ? intval($themeOptions->Dear_aiTimeout) : 15;

        if ($themeOptions->Dear_aiEnabled != '1') {
            self::error('AI摘要功能已关闭', 403);
            return;
        }

        $parsedModelsArray = self::parseModels($themeOptions->Dear_aiModels);
        if (empty($parsedModelsArray)) {
            self::error('未配置AI模型', 400);
            return;
        }

        $selectedModelConfiguration = null;
        if ($targetModelId !== '') {
            foreach ($parsedModelsArray as $mConfig) {
                if ($mConfig['id'] === $targetModelId) {
                    $selectedModelConfiguration = $mConfig;
                    break;
                }
            }
        }
        if (!$selectedModelConfiguration) {
            if (isset($parsedModelsArray[$targetModelIndex])) {
                $selectedModelConfiguration = $parsedModelsArray[$targetModelIndex];
            } else {
                $selectedModelConfiguration = $parsedModelsArray[0];
            }
        }

        $existingSummaryRecord = $databaseConnection->fetchRow(
            $databaseConnection->select()->from('table.dear_ai_summaries')
                ->where('cid = ?', $contentId)
                ->where('model_id = ? OR (model_id = \'\' AND model_name = ?)', $selectedModelConfiguration['id'], $selectedModelConfiguration['id'])
        );

        if ($existingSummaryRecord) {
            if ($existingSummaryRecord['status'] === 'generating') {
                $timeElapsedSinceUpdate = time() - intval($existingSummaryRecord['updated_at']);
                if ($timeElapsedSinceUpdate < $requestTimeoutLimit) {
                    self::success([
                        'exists'     => true,
                        'status'     => 'generating',
                        'summary'    => '',
                        'model'      => $selectedModelConfiguration['model_display'],
                        'model_id'   => $selectedModelConfiguration['id'],
                        'model_name' => $selectedModelConfiguration['model_name'],
                        'message'    => '该文章正在生成摘要，请稍候...'
                    ]);
                    return;
                }
            }
        }

        $rateLimitCheckResult = self::checkRateLimit($contentId, $themeOptions);
        if ($rateLimitCheckResult !== true) {
            self::error($rateLimitCheckResult, 429);
            return;
        }

        $articleDataRow = $databaseConnection->fetchRow(
            $databaseConnection->select('cid', 'title', 'text')->from('table.contents')->where('cid = ?', $contentId)
        );
        if (!$articleDataRow) {
            self::error('文章不存在', 404);
            return;
        }

        $currentTimestamp = time();
        $previousSummaryStatus = null;

        if ($existingSummaryRecord) {
            $previousSummaryStatus = $existingSummaryRecord['status'];
            $databaseConnection->query($databaseConnection->update('table.dear_ai_summaries')
                ->rows([
                    'model_id'           => $selectedModelConfiguration['id'],
                    'model_name'         => $selectedModelConfiguration['model_name'],
                    'model_display_name' => $selectedModelConfiguration['model_display'],
                    'status'             => 'generating',
                    'error_message'      => '',
                    'updated_at'         => $currentTimestamp
                ])
                ->where('id = ?', $existingSummaryRecord['id']));
        } else {
            $databaseConnection->query($databaseConnection->insert('table.dear_ai_summaries')->rows([
                'cid'                => $contentId,
                'model_id'           => $selectedModelConfiguration['id'],
                'model_name'         => $selectedModelConfiguration['model_name'],
                'model_display_name' => $selectedModelConfiguration['model_display'],
                'summary'            => '',
                'status'             => 'generating',
                'error_message'      => '',
                'created_at'         => $currentTimestamp,
                'updated_at'         => $currentTimestamp
            ]));
        }

        $databaseConnection->query($databaseConnection->insert('table.dear_ai_rate_log')->rows([
            'cid'          => $contentId,
            'request_time' => $currentTimestamp
        ]));

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $isAsynchronousExecutionSupported = function_exists('fastcgi_finish_request');

        if ($isAsynchronousExecutionSupported) {
            self::success([
                'exists'     => true,
                'model'      => $selectedModelConfiguration['model_display'],
                'model_id'   => $selectedModelConfiguration['id'],
                'model_name' => $selectedModelConfiguration['model_name'],
                'status'     => 'generating',
                'updated_at' => $currentTimestamp
            ]);
            fastcgi_finish_request();
        }

        $systemPromptText = $themeOptions->Dear_aiPrompt;
        if (empty($systemPromptText)) {
            $systemPromptText = self::defaultPrompt();
        }
        $strippedArticleText = preg_replace('/<!--.*?-->/s', '', $articleDataRow['text']);
        $combinedUserMessage = "文章标题：{$articleDataRow['title']}\n\n文章正文：\n{$strippedArticleText}";

        try {
            $generatedResultString = self::callOpenAI($selectedModelConfiguration, $systemPromptText, $combinedUserMessage, $requestTimeoutLimit);
            $databaseConnection->query($databaseConnection->update('table.dear_ai_summaries')->rows([
                'summary'            => $generatedResultString,
                'model_id'           => $selectedModelConfiguration['id'],
                'model_name'         => $selectedModelConfiguration['model_name'],
                'model_display_name' => $selectedModelConfiguration['model_display'],
                'status'             => 'completed',
                'error_message'      => '',
                'updated_at'         => time()
            ])->where('cid = ?', $contentId)->where('model_id = ?', $selectedModelConfiguration['id']));

            if ($isAsynchronousExecutionSupported === false) {
                self::success([
                    'exists'     => true,
                    'summary'    => $generatedResultString,
                    'model'      => $selectedModelConfiguration['model_display'],
                    'model_id'   => $selectedModelConfiguration['id'],
                    'model_name' => $selectedModelConfiguration['model_name'],
                    'status'     => 'completed',
                    'updated_at' => time()
                ]);
            }
        } catch (\Exception $exception) {
            if ($previousSummaryStatus === 'completed') {
                $databaseConnection->query($databaseConnection->update('table.dear_ai_summaries')->rows([
                    'status'        => 'completed',
                    'error_message' => '',
                    'updated_at'    => time()
                ])->where('cid = ?', $contentId)->where('model_id = ?', $selectedModelConfiguration['id']));
            } else {
                $databaseConnection->query($databaseConnection->update('table.dear_ai_summaries')->rows([
                    'status'        => 'error',
                    'error_message' => $exception->getMessage(),
                    'updated_at'    => time()
                ])->where('cid = ?', $contentId)->where('model_id = ?', $selectedModelConfiguration['id']));
            }

            if ($isAsynchronousExecutionSupported === false) {
                self::error('AI请求失败: ' . $exception->getMessage(), 502);
            }
        }
    }

    private static function callOpenAI($modelConfiguration, $systemPromptText, $combinedUserMessage, $requestTimeoutLimit = 15)
    {
        $apiEndpointUrl = rtrim($modelConfiguration['api_url'], '/');
        if (!preg_match('/\/chat\/completions\/?$/', $apiEndpointUrl)) {
            $apiEndpointUrl .= '/chat/completions';
        }

        $requestPayloadData = json_encode([
            'model'    => $modelConfiguration['model_name'],
            'messages' => [
                ['role' => 'system', 'content' => $systemPromptText],
                ['role' => 'user', 'content' => $combinedUserMessage]
            ],
            'temperature' => 0.6
        ], JSON_UNESCAPED_UNICODE);

        $cleanedApiKey = trim($modelConfiguration['api_key']);

        $curlHandler = curl_init($apiEndpointUrl);
        curl_setopt_array($curlHandler, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $requestPayloadData,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $cleanedApiKey,
                'Expect:'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $requestTimeoutLimit,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $curlResponseResult = curl_exec($curlHandler);
        $httpResponseCode = curl_getinfo($curlHandler, CURLINFO_HTTP_CODE);
        $curlErrorMessage  = curl_error($curlHandler);
        curl_close($curlHandler);

        if ($curlResponseResult === false) {
            throw new \Exception('连接超时或错误: ' . $curlErrorMessage);
        }

        $responseDataArray = json_decode($curlResponseResult, true);
        if ($httpResponseCode !== 200) {
            $apiErrorMessage = isset($responseDataArray['error']['message']) ? $responseDataArray['error']['message'] : '接口返回错误 ' . $httpResponseCode;
            throw new \Exception($apiErrorMessage);
        }

        if (isset($responseDataArray['choices'][0]['message']['content'])) {
            return trim($responseDataArray['choices'][0]['message']['content']);
        }
        throw new \Exception('AI 未返回有效内容');
    }

    private static function checkRateLimit($contentId, $themeOptions)
    {
        $database = \Typecho\Db::get();
        $currentTimestamp = time();

        $globalRateMinutesLimit = intval($themeOptions->Dear_aiGlobalRateMinutes ?: 60);
        $globalRateMaxRequests  = intval($themeOptions->Dear_aiGlobalRateMax ?: 1000);
        if ($globalRateMaxRequests > 0) {
            $globalSinceTimestamp = $currentTimestamp - $globalRateMinutesLimit * 60;
            $globalRequestCount = $database->fetchObject($database->select('COUNT(*) AS cnt')->from('table.dear_ai_rate_log')
                ->where('request_time > ?', $globalSinceTimestamp))->cnt;
            if ($globalRequestCount >= $globalRateMaxRequests) {
                return "全局请求限制：每 {$globalRateMinutesLimit} 分钟最多 {$globalRateMaxRequests} 次，已达上限";
            }
        }

        $articleRateMinutesLimit = intval($themeOptions->Dear_aiArticleRateMinutes ?: 1);
        $articleRateMaxRequests  = intval($themeOptions->Dear_aiArticleRateMax ?: 5);
        if ($articleRateMaxRequests > 0) {
            $articleSinceTimestamp = $currentTimestamp - $articleRateMinutesLimit * 60;
            $articleRequestCount = $database->fetchObject($database->select('COUNT(*) AS cnt')->from('table.dear_ai_rate_log')
                ->where('cid = ?', $contentId)->where('request_time > ?', $articleSinceTimestamp))->cnt;
            if ($articleRequestCount >= $articleRateMaxRequests) {
                return "该文章请求限制：每 {$articleRateMinutesLimit} 分钟最多 {$articleRateMaxRequests} 次，已达上限";
            }
        }

        return true;
    }

    public static function parseModels($jsonStringInput)
    {
        if (empty($jsonStringInput)) {
            return [];
        }
        $decodedJsonArray = json_decode($jsonStringInput, true);
        if (!is_array($decodedJsonArray)) {
            return [];
        }
        $validModelsArray = [];
        foreach ($decodedJsonArray as $modelConfiguration) {
            if (!empty($modelConfiguration['api_url']) && !empty($modelConfiguration['api_key']) && !empty($modelConfiguration['model_name'])) {
                $modelId = !empty($modelConfiguration['id']) ? trim($modelConfiguration['id']) : trim($modelConfiguration['model_name']);
                $validModelsArray[] = [
                    'id'            => $modelId,
                    'api_url'       => $modelConfiguration['api_url'],
                    'api_key'       => $modelConfiguration['api_key'],
                    'model_display' => !empty($modelConfiguration['model_display']) ? $modelConfiguration['model_display'] : $modelConfiguration['model_name'],
                    'model_name'    => $modelConfiguration['model_name']
                ];
            }
        }
        return $validModelsArray;
    }

    public static function defaultPrompt()
    {
        return '你是一个专业的文章摘要生成助手。请根据提供的文章内容生成一段简洁的中文摘要。要求：
1. 摘要长度最好控制在500字以内，如果能在小于500字的情况就将全文描述清楚的话，那字数越少越好，如果文章实在太长可适当放开限制，但是一定要尽可能控制
2. 准确概括文章的核心内容和关键要点，并且一定要完整
3. 使用流畅自然的中文表达
4. 可以用介绍的口吻来介绍本篇文章的内容，但是要尽量完整并保留文章原滋原味';
    }

    // ==========================================
    // Model ID Rename & Migration Utilities
    // ==========================================

    public static function renameModelId($oldId, $newId)
    {
        $oldId = trim($oldId);
        $newId = trim($newId);
        if ($oldId === '' || $newId === '' || $oldId === $newId) {
            return 0;
        }

        self::ensureTable();
        $database = \Typecho\Db::get();

        // Check for duplicates: if (cid, newId) already exists, remove conflicting (cid, oldId) row
        try {
            $existingNewRows = $database->fetchAll(
                $database->select('cid')->from('table.dear_ai_summaries')->where('model_id = ?', $newId)
            );
            $newCids = [];
            foreach ($existingNewRows as $row) {
                $newCids[] = intval($row['cid']);
            }
            if (!empty($newCids)) {
                $database->query(
                    $database->delete('table.dear_ai_summaries')
                        ->where('model_id = ?', $oldId)
                        ->where('cid IN ?', $newCids)
                );
            }
        } catch (\Exception $e) {
        }

        // Update model_id in dear_ai_summaries
        $updateQuery = $database->update('table.dear_ai_summaries')
            ->rows(['model_id' => $newId, 'updated_at' => time()])
            ->where('model_id = ?', $oldId);
        $affectedRows = $database->query($updateQuery);

        return intval($affectedRows);
    }

    public static function handleModelsSave($newModelsJson, $renamesJson = null)
    {
        if (!self::isAdmin()) {
            return;
        }
        self::ensureTable();

        // 1. Process explicit renames if provided
        if (!empty($renamesJson)) {
            $renames = is_array($renamesJson) ? $renamesJson : json_decode($renamesJson, true);
            if (is_array($renames)) {
                foreach ($renames as $oldId => $newId) {
                    if (!empty($oldId) && !empty($newId) && $oldId !== $newId) {
                        self::renameModelId($oldId, $newId);
                    }
                }
            }
        }

        // 2. Diff with previous models from options table
        try {
            $options = \Widget\Options::alloc();
            $oldModels = self::parseModels($options->Dear_aiModels);
            $newModels = self::parseModels($newModelsJson);

            $count = min(count($oldModels), count($newModels));
            for ($i = 0; $i < $count; $i++) {
                $oldId = $oldModels[$i]['id'];
                $newId = $newModels[$i]['id'];
                if (!empty($oldId) && !empty($newId) && $oldId !== $newId) {
                    self::renameModelId($oldId, $newId);
                }
            }
        } catch (\Exception $e) {
        }
    }

    // ==========================================
    // Admin Actions
    // ==========================================

    private static function actionAdminStats()
    {
        self::requireAdmin();
        self::ensureTable();
        $database = \Typecho\Db::get();
        $themeOptions = \Widget\Options::alloc();
        $activeModels = self::parseModels($themeOptions->Dear_aiModels);

        $activeModelIds = [];
        $modelsStats = [];
        foreach ($activeModels as $idx => $m) {
            $mid = $m['id'];
            $activeModelIds[] = $mid;
            $modelsStats[$mid] = [
                'index'             => $idx,
                'id'                => $mid,
                'name'              => $m['model_name'],
                'display'           => $m['model_display'],
                'total'             => 0,
                'completed'         => 0,
                'generating'        => 0,
                'error'             => 0,
                'other'             => 0,
                'latest_updated_at' => 0
            ];
        }

        $allSummaryRows = [];
        try {
            $allSummaryRows = $database->fetchAll(
                $database->select('model_id', 'status', 'COUNT(*) as count', 'MAX(updated_at) as latest_time')
                    ->from('table.dear_ai_summaries')
                    ->group('model_id', 'status')
            );
        } catch (\Exception $e) {
        }

        $orphanedCount = 0;
        $orphanedModels = [];
        $totalSummaries = 0;
        $totalCompleted = 0;
        $totalGenerating = 0;
        $totalError = 0;

        foreach ($allSummaryRows as $row) {
            $mid = $row['model_id'] ?: '(空ID)';
            $status = $row['status'] ?: 'completed';
            $count = intval($row['count']);
            $latestTime = intval($row['latest_time']);
            $totalSummaries += $count;

            if ($status === 'completed') $totalCompleted += $count;
            elseif ($status === 'generating') $totalGenerating += $count;
            elseif ($status === 'error') $totalError += $count;

            if (isset($modelsStats[$mid])) {
                $modelsStats[$mid]['total'] += $count;
                if (isset($modelsStats[$mid][$status])) {
                    $modelsStats[$mid][$status] += $count;
                } else {
                    $modelsStats[$mid]['other'] += $count;
                }
                if ($latestTime > $modelsStats[$mid]['latest_updated_at']) {
                    $modelsStats[$mid]['latest_updated_at'] = $latestTime;
                }
            } else {
                $orphanedCount += $count;
                if (!isset($orphanedModels[$mid])) {
                    $orphanedModels[$mid] = [
                        'id'         => $mid,
                        'count'      => 0,
                        'completed'  => 0,
                        'generating' => 0,
                        'error'      => 0
                    ];
                }
                $orphanedModels[$mid]['count'] += $count;
                if (isset($orphanedModels[$mid][$status])) {
                    $orphanedModels[$mid][$status] += $count;
                }
            }
        }

        $totalArticlesCount = 0;
        try {
            $totalArticlesCount = intval($database->fetchObject(
                $database->select('COUNT(*) as cnt')->from('table.contents')->where('type = ?', 'post')
            )->cnt);
        } catch (\Exception $e) {}

        $articlesWithSummaryCount = 0;
        try {
            $articlesWithSummaryCount = intval($database->fetchObject(
                $database->select('COUNT(DISTINCT cid) as cnt')->from('table.dear_ai_summaries')->where('status = ?', 'completed')
            )->cnt);
        } catch (\Exception $e) {}

        $rateLogsCount = 0;
        try {
            $rateLogsCount = intval($database->fetchObject(
                $database->select('COUNT(*) as cnt')->from('table.dear_ai_rate_log')
            )->cnt);
        } catch (\Exception $e) {}

        self::success([
            'models'                => array_values($modelsStats),
            'orphaned_count'        => $orphanedCount,
            'orphaned_models'       => array_values($orphanedModels),
            'total_summaries'       => $totalSummaries,
            'total_completed'       => $totalCompleted,
            'total_generating'      => $totalGenerating,
            'total_error'           => $totalError,
            'total_articles'        => $totalArticlesCount,
            'articles_with_summary' => $articlesWithSummaryCount,
            'rate_logs_count'       => $rateLogsCount
        ]);
    }

    private static function actionAdminCleanOrphaned()
    {
        self::requireAdmin();
        self::ensureTable();
        $database = \Typecho\Db::get();
        $themeOptions = \Widget\Options::alloc();
        $activeModels = self::parseModels($themeOptions->Dear_aiModels);
        $activeModelIds = [];
        foreach ($activeModels as $model) {
            if (!empty($model['id'])) {
                $activeModelIds[] = $model['id'];
            }
        }

        $deletedSummariesCount = 0;
        if (!empty($activeModelIds)) {
            $deleteSummariesQuery = $database->delete('table.dear_ai_summaries')
                ->where('model_id NOT IN ? OR model_id IS NULL OR model_id = ?', $activeModelIds, '');
            $deletedSummariesCount = intval($database->query($deleteSummariesQuery));
        } else {
            $deleteSummariesQuery = $database->delete('table.dear_ai_summaries');
            $deletedSummariesCount = intval($database->query($deleteSummariesQuery));
        }

        // Clean orphaned summaries where article cid no longer exists
        try {
            $existingPostCids = $database->fetchAll($database->select('cid')->from('table.contents'));
            $cids = array_map(function($r) { return intval($r['cid']); }, $existingPostCids);
            if (!empty($cids)) {
                $deletedDeadArticleSummaries = $database->query(
                    $database->delete('table.dear_ai_summaries')->where('cid NOT IN ?', $cids)
                );
                $deletedSummariesCount += intval($deletedDeadArticleSummaries);
            }
        } catch (\Exception $e) {}

        // Clean expired rate logs (> 24h)
        $expireTime = time() - 86400;
        $deletedLogsCount = 0;
        try {
            $deletedLogsCount = intval($database->query(
                $database->delete('table.dear_ai_rate_log')->where('request_time < ?', $expireTime)
            ));
        } catch (\Exception $e) {}

        self::success([
            'deleted_summaries' => $deletedSummariesCount,
            'deleted_rate_logs' => $deletedLogsCount,
            'message'           => "已清理 {$deletedSummariesCount} 条失效摘要及 {$deletedLogsCount} 条过期进度/频次记录"
        ]);
    }

    private static function actionAdminCleanUncompleted()
    {
        self::requireAdmin();
        self::ensureTable();
        $database = \Typecho\Db::get();
        $targetModelId = isset($_REQUEST['model_id']) ? trim($_REQUEST['model_id']) : '';

        $deleteQuery = $database->delete('table.dear_ai_summaries')->where('status != ?', 'completed');
        if ($targetModelId !== '' && $targetModelId !== 'all') {
            $deleteQuery->where('model_id = ?', $targetModelId);
        }
        $deletedCount = intval($database->query($deleteQuery));

        self::success([
            'deleted'  => $deletedCount,
            'model_id' => $targetModelId,
            'message'  => "已成功清理 {$deletedCount} 条未完成的摘要条目"
        ]);
    }

    private static function actionAdminCleanModel()
    {
        self::requireAdmin();
        self::ensureTable();
        $database = \Typecho\Db::get();
        $modelId = trim($_REQUEST['model_id'] ?? '');
        if ($modelId === '') {
            self::error('需要提供模型ID', 400);
            return;
        }
        $deleted = intval($database->query(
            $database->delete('table.dear_ai_summaries')->where('model_id = ?', $modelId)
        ));
        self::success([
            'deleted'  => $deleted,
            'model_id' => $modelId,
            'message'  => "已删除模型 [{$modelId}] 下的所有摘要（共 {$deleted} 条）"
        ]);
    }

    private static function actionAdminCleanRateLog()
    {
        self::requireAdmin();
        self::ensureTable();
        $database = \Typecho\Db::get();
        $deleted = intval($database->query($database->delete('table.dear_ai_rate_log')));
        self::success([
            'deleted' => $deleted,
            'message' => "已清空频次记录（共 {$deleted} 条）"
        ]);
    }

    private static function actionAdminCleanAll()
    {
        self::requireAdmin();
        self::ensureTable();
        $database = \Typecho\Db::get();
        $deleted = intval($database->query($database->delete('table.dear_ai_summaries')));
        self::success([
            'deleted' => $deleted,
            'message' => "已清空全部AI摘要缓存（共 {$deleted} 条）"
        ]);
    }

    private static function actionAdminRenameModelId()
    {
        self::requireAdmin();
        self::ensureTable();
        $oldId = trim($_REQUEST['old_id'] ?? '');
        $newId = trim($_REQUEST['new_id'] ?? '');
        if ($oldId === '' || $newId === '') {
            self::error('原模型ID和新模型ID均不能为空', 400);
            return;
        }
        $updated = self::renameModelId($oldId, $newId);
        self::success([
            'updated' => $updated,
            'old_id'  => $oldId,
            'new_id'  => $newId,
            'message' => "已成功将模型ID [{$oldId}] 关联的 {$updated} 条摘要更新为 [{$newId}]"
        ]);
    }

    private static function actionAdminListSummaries()
    {
        self::requireAdmin();
        self::ensureTable();
        $database = \Typecho\Db::get();

        $page = max(1, intval($_GET['page'] ?? 1));
        $pageSize = min(50, max(5, intval($_GET['pageSize'] ?? 10)));
        $targetModelId = isset($_GET['model_id']) ? trim($_GET['model_id']) : '';
        $targetStatus = isset($_GET['status']) ? trim($_GET['status']) : '';
        $searchKeyword = isset($_GET['search']) ? trim($_GET['search']) : '';

        try {
            $query = $database->select('s.*', 'c.title as article_title')
                ->from('table.dear_ai_summaries as s')
                ->join('table.contents as c', 's.cid = c.cid', \Typecho\Db::LEFT_JOIN);

            $countQuery = $database->select('COUNT(*) as cnt')
                ->from('table.dear_ai_summaries as s')
                ->join('table.contents as c', 's.cid = c.cid', \Typecho\Db::LEFT_JOIN);

            if ($targetModelId !== '') {
                $query->where('s.model_id = ?', $targetModelId);
                $countQuery->where('s.model_id = ?', $targetModelId);
            }
            if ($targetStatus !== '') {
                $query->where('s.status = ?', $targetStatus);
                $countQuery->where('s.status = ?', $targetStatus);
            }
            if ($searchKeyword !== '') {
                $query->where('c.title LIKE ? OR s.summary LIKE ? OR s.cid = ?', "%{$searchKeyword}%", "%{$searchKeyword}%", intval($searchKeyword));
                $countQuery->where('c.title LIKE ? OR s.summary LIKE ? OR s.cid = ?', "%{$searchKeyword}%", "%{$searchKeyword}%", intval($searchKeyword));
            }

            $total = intval($database->fetchObject($countQuery)->cnt);
            $totalPages = max(1, ceil($total / $pageSize));

            $query->order('s.updated_at', \Typecho\Db::SORT_DESC)
                ->page($page, $pageSize);

            $rows = $database->fetchAll($query);
        } catch (\Exception $e) {
            self::error('查询数据库摘要记录失败: ' . $e->getMessage(), 500);
            return;
        }

        $list = [];
        foreach ($rows as $row) {
            $summarySnippet = mb_substr(strip_tags($row['summary'] ?? ''), 0, 120, 'UTF-8');
            $list[] = [
                'id'                 => intval($row['id']),
                'cid'                => intval($row['cid']),
                'title'              => $row['article_title'] ?: ('[已删文章 CID: ' . $row['cid'] . ']'),
                'model_id'           => $row['model_id'],
                'model_name'         => $row['model_name'],
                'model_display_name' => $row['model_display_name'],
                'status'             => $row['status'],
                'error_message'      => $row['error_message'],
                'summary_snippet'    => $summarySnippet,
                'summary_full'       => $row['summary'],
                'created_at'         => intval($row['created_at']),
                'updated_at'         => intval($row['updated_at'])
            ];
        }

        self::success([
            'list'       => $list,
            'total'      => $total,
            'page'       => $page,
            'pageSize'   => $pageSize,
            'totalPages' => $totalPages
        ]);
    }

    private static function actionAdminDeleteSummary()
    {
        self::requireAdmin();
        self::ensureTable();
        $database = \Typecho\Db::get();
        $id = intval($_REQUEST['id'] ?? 0);
        if ($id <= 0) {
            self::error('无效的 ID', 400);
            return;
        }
        $database->query($database->delete('table.dear_ai_summaries')->where('id = ?', $id));
        self::success(['message' => '删除成功']);
    }

    private static function success($responsePayloadData)
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(array_merge(['ok' => true], $responsePayloadData), JSON_UNESCAPED_UNICODE);
    }

    private static function error($errorMessageText, $httpStatusCode = 400)
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code($httpStatusCode);
        }
        echo json_encode(['ok' => false, 'error' => $errorMessageText], JSON_UNESCAPED_UNICODE);
        exit;
    }
}