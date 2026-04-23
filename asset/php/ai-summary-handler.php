<?php
if (!defined('__TYPECHO_ROOT_DIR__'))
    exit;

class DearTheme_AiSummary
{
    private static $tableChecked = false;

    public static function handleRequest()
    {
        header('Content-Type: application/json; charset=utf-8');
        $actionParameter = isset($_GET['dear_ai_action']) ? $_GET['dear_ai_action'] : '';
        try {
            switch ($actionParameter) {
                case 'get':
                    self::actionGet();
                    break;
                case 'generate':
                    self::actionGenerate();
                    break;
                case 'models':
                    self::actionModels();
                    break;
                default:
                    self::error('Invalid action', 400);
            }
        } catch (\Exception $exception) {
            self::error($exception->getMessage(), 500);
        }
        exit;
    }

    private static function ensureTable()
    {
        if (self::$tableChecked) {
            return;
        }
        $database = \Typecho\Db::get();
        $tablePrefix = $database->getPrefix();
        $databaseAdapter = $database->getAdapterName();
        $isSQLiteDatabase = stripos($databaseAdapter, 'SQLite') !== false;

        try {
            $database->fetchRow($database->select('id', 'model_name')->from('table.dear_ai_summaries')->limit(1));
            self::$tableChecked = true;
            return;
        } catch (\Exception $exception) {
        }

        $oldTableExists = false;
        try {
            $database->fetchRow($database->select('id')->from('table.dear_ai_summaries')->limit(1));
            $oldTableExists = true;
        } catch (\Exception $exception) {
        }

        if ($oldTableExists) {
            try {
                if ($isSQLiteDatabase) {
                    $database->query("ALTER TABLE \"{$tablePrefix}dear_ai_summaries\" ADD COLUMN \"model_name\" TEXT NOT NULL DEFAULT ''");
                } else {
                    $database->query("ALTER TABLE `{$tablePrefix}dear_ai_summaries` ADD COLUMN `model_name` VARCHAR(100) NOT NULL DEFAULT '' AFTER `cid`");
                    try {
                        $database->query("ALTER TABLE `{$tablePrefix}dear_ai_summaries` DROP INDEX `uk_cid`");
                    } catch (\Exception $exception) {
                    }
                    $database->query("ALTER TABLE `{$tablePrefix}dear_ai_summaries` ADD UNIQUE KEY `uk_cid_model` (`cid`, `model_name`)");
                }
            } catch (\Exception $exception) {
            }
            if ($isSQLiteDatabase) {
                try {
                    $database->query("CREATE UNIQUE INDEX IF NOT EXISTS \"{$tablePrefix}idx_cid_model\" ON \"{$tablePrefix}dear_ai_summaries\" (\"cid\", \"model_name\")");
                } catch (\Exception $exception) {
                }
            }
        } else {
            if ($isSQLiteDatabase) {
                $database->query("CREATE TABLE IF NOT EXISTS \"{$tablePrefix}dear_ai_summaries\" (
                    \"id\" INTEGER PRIMARY KEY AUTOINCREMENT,
                    \"cid\" INTEGER NOT NULL,
                    \"model_name\" TEXT NOT NULL DEFAULT '',
                    \"model_display_name\" TEXT DEFAULT '',
                    \"summary\" TEXT DEFAULT '',
                    \"status\" TEXT DEFAULT 'completed',
                    \"error_message\" TEXT DEFAULT '',
                    \"created_at\" INTEGER DEFAULT 0,
                    \"updated_at\" INTEGER DEFAULT 0,
                    UNIQUE(\"cid\", \"model_name\")
                )");
            } else {
                $database->query("CREATE TABLE IF NOT EXISTS `{$tablePrefix}dear_ai_summaries` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `cid` INT UNSIGNED NOT NULL,
                    `model_name` VARCHAR(100) NOT NULL DEFAULT '',
                    `model_display_name` VARCHAR(100) DEFAULT '',
                    `summary` TEXT,
                    `status` VARCHAR(20) DEFAULT 'completed',
                    `error_message` TEXT,
                    `created_at` INT UNSIGNED DEFAULT 0,
                    `updated_at` INT UNSIGNED DEFAULT 0,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_cid_model` (`cid`, `model_name`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            }
        }

        try {
            $database->fetchRow($database->select('id')->from('table.dear_ai_rate_log')->limit(1));
        } catch (\Exception $exception) {
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
        $targetModelName = isset($_GET['model_name']) ? trim($_GET['model_name']) : '';
        if ($contentId <= 0) {
            self::error('Invalid cid', 400);
            return;
        }

        self::ensureTable();
        $database = \Typecho\Db::get();

        $databaseQuery = $database->select()->from('table.dear_ai_summaries')->where('cid = ?', $contentId);
        if ($targetModelName !== '') {
            $databaseQuery->where('model_name = ?', $targetModelName);
        }
        $databaseQuery->order('updated_at', \Typecho\Db::SORT_DESC)->limit(1);
        $queryResultRow = $database->fetchRow($databaseQuery);

        if ($queryResultRow) {
            self::success([
                'exists'     => true,
                'summary'    => $queryResultRow['summary'],
                'model'      => $queryResultRow['model_display_name'],
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
                'index' => $modelIndex,
                'display' => $modelConfiguration['model_display'],
                'name' => $modelConfiguration['model_name']
            ];
        }
        self::success(['models' => $modelsResponseList]);
    }

    private static function actionGenerate()
    {
        $contentId = intval($_GET['cid'] ?? 0);
        $targetModelIndex = intval($_GET['model_index'] ?? 0);
        if ($contentId <= 0) {
            self::error('Invalid cid', 400);
            return;
        }

        self::ensureTable();
        $database = \Typecho\Db::get();
        $themeOptions = \Widget\Options::alloc();

        $requestTimeoutLimit = intval($themeOptions->Dear_aiTimeout ?: 15);

        if ($themeOptions->Dear_aiEnabled != '1') {
            self::error('AI摘要功能已关闭', 403);
            return;
        }

        $parsedModelsArray = self::parseModels($themeOptions->Dear_aiModels);
        if (empty($parsedModelsArray)) {
            self::error('未配置AI模型', 400);
            return;
        }
        if (!isset($parsedModelsArray[$targetModelIndex])) {
            $targetModelIndex = 0;
        }
        $selectedModelConfiguration = $parsedModelsArray[$targetModelIndex];

        $existingSummaryRecord = $database->fetchRow(
            $database->select()->from('table.dear_ai_summaries')
                ->where('cid = ?', $contentId)->where('model_name = ?', $selectedModelConfiguration['model_name'])
        );

        if ($existingSummaryRecord && $existingSummaryRecord['status'] === 'generating') {
            $timeElapsedSinceUpdate = time() - intval($existingSummaryRecord['updated_at']);
            if ($timeElapsedSinceUpdate < $requestTimeoutLimit) {
                self::success([
                    'exists' => true,
                    'status' => 'generating',
                    'summary' => '',
                    'model' => $selectedModelConfiguration['model_display'],
                    'model_name' => $selectedModelConfiguration['model_name'],
                    'message' => '该文章正在生成摘要，请稍候...'
                ]);
                return;
            }
        }

        $rateLimitCheckResult = self::checkRateLimit($contentId, $themeOptions);
        if ($rateLimitCheckResult !== true) {
            self::error($rateLimitCheckResult, 429);
            return;
        }

        $articleDataRow = $database->fetchRow($database->select('cid', 'title', 'text')->from('table.contents')->where('cid = ?', $contentId));
        if (!$articleDataRow) {
            self::error('文章不存在', 404);
            return;
        }

        $currentTimestamp = time();
        $previousSummaryStatus = null;

        if ($existingSummaryRecord) {
            $previousSummaryStatus = $existingSummaryRecord['status'];
            $database->query($database->update('table.dear_ai_summaries')
                ->rows(['status' => 'generating', 'error_message' => '', 'updated_at' => $currentTimestamp])
                ->where('cid = ?', $contentId)->where('model_name = ?', $selectedModelConfiguration['model_name']));
        } else {
            $database->query($database->insert('table.dear_ai_summaries')->rows([
                'cid' => $contentId,
                'model_name' => $selectedModelConfiguration['model_name'],
                'model_display_name' => $selectedModelConfiguration['model_display'],
                'summary' => '',
                'status' => 'generating',
                'error_message' => '',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp
            ]));
        }

        $database->query($database->insert('table.dear_ai_rate_log')->rows(['cid' => $contentId, 'request_time' => $currentTimestamp]));

        $systemPromptText = $themeOptions->Dear_aiPrompt;
        if (empty($systemPromptText)) {
            $systemPromptText = self::defaultPrompt();
        }
        $strippedArticleText = preg_replace('//s', '', $articleDataRow['text']);
        $combinedUserMessage = "文章标题：{$articleDataRow['title']}\n\n文章正文：\n{$strippedArticleText}";

        try {
            $generatedResultString = self::callOpenAI($selectedModelConfiguration, $systemPromptText, $combinedUserMessage, $requestTimeoutLimit);
            $database->query($database->update('table.dear_ai_summaries')->rows([
                'summary' => $generatedResultString,
                'model_display_name' => $selectedModelConfiguration['model_display'],
                'status' => 'completed',
                'error_message' => '',
                'updated_at' => time()
            ])->where('cid = ?', $contentId)->where('model_name = ?', $selectedModelConfiguration['model_name']));

            self::success([
                'exists' => true,
                'summary' => $generatedResultString,
                'model' => $selectedModelConfiguration['model_display'],
                'model_name' => $selectedModelConfiguration['model_name'],
                'status' => 'completed',
                'updated_at' => time()
            ]);
        } catch (\Exception $exception) {
            if ($previousSummaryStatus === 'completed') {
                $database->query($database->update('table.dear_ai_summaries')->rows([
                    'status' => 'completed',
                    'error_message' => '',
                    'updated_at' => time()
                ])->where('cid = ?', $contentId)->where('model_name = ?', $selectedModelConfiguration['model_name']));
            } else {
                $database->query($database->update('table.dear_ai_summaries')->rows([
                    'status' => 'error',
                    'error_message' => $exception->getMessage(),
                    'updated_at' => time()
                ])->where('cid = ?', $contentId)->where('model_name = ?', $selectedModelConfiguration['model_name']));
            }
            self::error('AI请求失败: ' . $exception->getMessage(), 502);
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

        $curlHandler = curl_init($apiEndpointUrl);
        curl_setopt_array($curlHandler, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $requestPayloadData,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $modelConfiguration['api_key'],
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

    private static function parseModels($jsonStringInput)
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
                $validModelsArray[] = [
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

    private static function success($responsePayloadData) {
        echo json_encode(array_merge(['ok' => true], $responsePayloadData), JSON_UNESCAPED_UNICODE);
    }

    private static function error($errorMessageText, $httpStatusCode = 400) {
        http_response_code($httpStatusCode);
        echo json_encode(['ok' => false, 'error' => $errorMessageText], JSON_UNESCAPED_UNICODE);
    }
}