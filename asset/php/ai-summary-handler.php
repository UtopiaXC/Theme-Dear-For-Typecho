<?php
if (!defined('__TYPECHO_ROOT_DIR__'))
    exit;

class DearTheme_AiSummary
{
    private static $tableChecked = false;

    public static function handleRequest()
    {
        header('Content-Type: application/json; charset=utf-8');
        $action = isset($_GET['dear_ai_action']) ? $_GET['dear_ai_action'] : '';
        try {
            switch ($action) {
                case 'get':     self::actionGet(); break;
                case 'generate': self::actionGenerate(); break;
                case 'models':  self::actionModels(); break;
                default: self::error('Invalid action', 400);
            }
        } catch (\Exception $e) {
            self::error($e->getMessage(), 500);
        }
        exit;
    }

    private static function ensureTable()
    {
        if (self::$tableChecked) return;
        $db  = \Typecho\Db::get();
        $pre = $db->getPrefix();
        $adapter = $db->getAdapterName();
        $isSQLite = stripos($adapter, 'SQLite') !== false;

        try {
            $db->fetchRow($db->select('id', 'model_name')->from('table.dear_ai_summaries')->limit(1));
            self::$tableChecked = true;
            return;
        } catch (\Exception $e) {}

        $oldExists = false;
        try {
            $db->fetchRow($db->select('id')->from('table.dear_ai_summaries')->limit(1));
            $oldExists = true;
        } catch (\Exception $e) {}

        if ($oldExists) {
            try {
                if ($isSQLite) {
                    $db->query("ALTER TABLE \"{$pre}dear_ai_summaries\" ADD COLUMN \"model_name\" TEXT NOT NULL DEFAULT ''");
                } else {
                    $db->query("ALTER TABLE `{$pre}dear_ai_summaries` ADD COLUMN `model_name` VARCHAR(100) NOT NULL DEFAULT '' AFTER `cid`");
                    try { $db->query("ALTER TABLE `{$pre}dear_ai_summaries` DROP INDEX `uk_cid`"); } catch (\Exception $e) {}
                    $db->query("ALTER TABLE `{$pre}dear_ai_summaries` ADD UNIQUE KEY `uk_cid_model` (`cid`, `model_name`)");
                }
            } catch (\Exception $e) {}
            if ($isSQLite) {
                try { $db->query("CREATE UNIQUE INDEX IF NOT EXISTS \"{$pre}idx_cid_model\" ON \"{$pre}dear_ai_summaries\" (\"cid\", \"model_name\")"); } catch (\Exception $e) {}
            }
        } else {
            if ($isSQLite) {
                $db->query("CREATE TABLE IF NOT EXISTS \"{$pre}dear_ai_summaries\" (
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
                $db->query("CREATE TABLE IF NOT EXISTS `{$pre}dear_ai_summaries` (
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
            $db->fetchRow($db->select('id')->from('table.dear_ai_rate_log')->limit(1));
        } catch (\Exception $e) {
            if ($isSQLite) {
                $db->query("CREATE TABLE IF NOT EXISTS \"{$pre}dear_ai_rate_log\" (
                    \"id\" INTEGER PRIMARY KEY AUTOINCREMENT,
                    \"cid\" INTEGER NOT NULL DEFAULT 0,
                    \"request_time\" INTEGER NOT NULL
                )");
            } else {
                $db->query("CREATE TABLE IF NOT EXISTS `{$pre}dear_ai_rate_log` (
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
        $cid = intval($_GET['cid'] ?? 0);
        $modelName = isset($_GET['model_name']) ? trim($_GET['model_name']) : '';
        if ($cid <= 0) { self::error('Invalid cid', 400); return; }

        self::ensureTable();
        $db = \Typecho\Db::get();

        $query = $db->select()->from('table.dear_ai_summaries')->where('cid = ?', $cid);
        if ($modelName !== '') {
            $query->where('model_name = ?', $modelName);
        }
        $query->order('updated_at', \Typecho\Db::SORT_DESC)->limit(1);
        $row = $db->fetchRow($query);

        if ($row) {
            self::success([
                'exists'     => true,
                'summary'    => $row['summary'],
                'model'      => $row['model_display_name'],
                'model_name' => $row['model_name'],
                'status'     => $row['status'],
                'error'      => $row['error_message'] ?: null,
                'updated_at' => intval($row['updated_at'])
            ]);
        } else {
            self::success(['exists' => false]);
        }
    }

    private static function actionModels()
    {
        $options = \Widget\Options::alloc();
        $models  = self::parseModels($options->Dear_aiModels);
        $list = [];
        foreach ($models as $i => $m) {
            $list[] = ['index' => $i, 'display' => $m['model_display'], 'name' => $m['model_name']];
        }
        self::success(['models' => $list]);
    }

    private static function actionGenerate()
    {
        $cid        = intval($_GET['cid'] ?? 0);
        $modelIndex = intval($_GET['model_index'] ?? 0);
        if ($cid <= 0) { self::error('Invalid cid', 400); return; }

        self::ensureTable();
        $db      = \Typecho\Db::get();
        $options = \Widget\Options::alloc();

        $timeout = intval($options->Dear_aiTimeout ?: 15);

        if ($options->Dear_aiEnabled != '1') {
            self::error('AI 摘要功能已关闭', 403);
            return;
        }

        $models = self::parseModels($options->Dear_aiModels);
        if (empty($models)) { self::error('未配置 AI 模型', 400); return; }
        if (!isset($models[$modelIndex])) $modelIndex = 0;
        $model = $models[$modelIndex];

        $existing = $db->fetchRow(
            $db->select()->from('table.dear_ai_summaries')
                ->where('cid = ?', $cid)->where('model_name = ?', $model['model_name'])
        );

        if ($existing && $existing['status'] === 'generating') {
            $elapsed = time() - intval($existing['updated_at']);
            if ($elapsed < $timeout) {
                self::success([
                    'exists' => true, 'status' => 'generating',
                    'summary' => '', 'model' => $model['model_display'],
                    'model_name' => $model['model_name'],
                    'message' => '该文章正在生成摘要，请稍候...'
                ]);
                return;
            }
        }

        $rateLimitResult = self::checkRateLimit($cid, $options);
        if ($rateLimitResult !== true) { self::error($rateLimitResult, 429); return; }

        $post = $db->fetchRow($db->select('cid', 'title', 'text')->from('table.contents')->where('cid = ?', $cid));
        if (!$post) { self::error('文章不存在', 404); return; }

        $now = time();
        if ($existing) {
            $db->query($db->update('table.dear_ai_summaries')
                ->rows(['status' => 'generating', 'error_message' => '', 'updated_at' => $now])
                ->where('cid = ?', $cid)->where('model_name = ?', $model['model_name']));
        } else {
            $db->query($db->insert('table.dear_ai_summaries')->rows([
                'cid' => $cid, 'model_name' => $model['model_name'],
                'model_display_name' => $model['model_display'],
                'summary' => '', 'status' => 'generating',
                'error_message' => '', 'created_at' => $now, 'updated_at' => $now
            ]));
        }

        $db->query($db->insert('table.dear_ai_rate_log')->rows(['cid' => $cid, 'request_time' => $now]));

        $prompt = $options->Dear_aiPrompt;
        if (empty($prompt)) $prompt = self::defaultPrompt();
        $articleText = preg_replace('//s', '', $post['text']);
        $userMessage = "文章标题：{$post['title']}\n\n文章正文：\n{$articleText}";

        try {
            $result = self::callOpenAI($model, $prompt, $userMessage, $timeout);
            $db->query($db->update('table.dear_ai_summaries')->rows([
                'summary' => $result, 'model_display_name' => $model['model_display'],
                'status' => 'completed', 'error_message' => '', 'updated_at' => time()
            ])->where('cid = ?', $cid)->where('model_name = ?', $model['model_name']));

            self::success([
                'exists' => true, 'summary' => $result, 'model' => $model['model_display'],
                'model_name' => $model['model_name'], 'status' => 'completed', 'updated_at' => time()
            ]);
        } catch (\Exception $e) {
            $db->query($db->update('table.dear_ai_summaries')->rows([
                'status' => 'error', 'error_message' => $e->getMessage(), 'updated_at' => time()
            ])->where('cid = ?', $cid)->where('model_name = ?', $model['model_name']));
            self::error('AI 请求失败: ' . $e->getMessage(), 502);
        }
    }

    private static function callOpenAI($model, $systemPrompt, $userMessage, $timeout = 15)
    {
        $url = rtrim($model['api_url'], '/');
        if (!preg_match('/\/chat\/completions\/?$/', $url)) $url .= '/chat/completions';

        $payload = json_encode([
            'model'    => $model['model_name'],
            'messages' => [['role' => 'system', 'content' => $systemPrompt], ['role' => 'user', 'content' => $userMessage]],
            'temperature' => 0.6
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Authorization: Bearer ' . $model['api_key']],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($response === false) throw new \Exception('连接超时或错误: ' . $curlErr);

        $data = json_decode($response, true);
        if ($httpCode !== 200) {
            $errMsg = isset($data['error']['message']) ? $data['error']['message'] : '接口返回错误 ' . $httpCode;
            throw new \Exception($errMsg);
        }

        if (isset($data['choices'][0]['message']['content'])) {
            return trim($data['choices'][0]['message']['content']);
        }
        throw new \Exception('AI 未返回有效内容');
    }

    private static function checkRateLimit($cid, $options)
    {
        $db = \Typecho\Db::get();
        $now = time();

        $globalMinutes = intval($options->Dear_aiGlobalRateMinutes ?: 60);
        $globalMax     = intval($options->Dear_aiGlobalRateMax ?: 1000);
        if ($globalMax > 0) {
            $since = $now - $globalMinutes * 60;
            $cnt = $db->fetchObject($db->select('COUNT(*) AS cnt')->from('table.dear_ai_rate_log')
                ->where('request_time > ?', $since))->cnt;
            if ($cnt >= $globalMax) return "全局请求限制：每 {$globalMinutes} 分钟最多 {$globalMax} 次，已达上限";
        }

        $articleMinutes = intval($options->Dear_aiArticleRateMinutes ?: 1);
        $articleMax     = intval($options->Dear_aiArticleRateMax ?: 5);
        if ($articleMax > 0) {
            $since = $now - $articleMinutes * 60;
            $cnt = $db->fetchObject($db->select('COUNT(*) AS cnt')->from('table.dear_ai_rate_log')
                ->where('cid = ?', $cid)->where('request_time > ?', $since))->cnt;
            if ($cnt >= $articleMax) return "该文章请求限制：每 {$articleMinutes} 分钟最多 {$articleMax} 次，已达上限";
        }

        return true;
    }


    private static function parseModels($json)
    {
        if (empty($json)) return [];
        $arr = json_decode($json, true);
        if (!is_array($arr)) return [];
        $valid = [];
        foreach ($arr as $m) {
            if (!empty($m['api_url']) && !empty($m['api_key']) && !empty($m['model_name'])) {
                $valid[] = [
                    'api_url'       => $m['api_url'],
                    'api_key'       => $m['api_key'],
                    'model_display' => !empty($m['model_display']) ? $m['model_display'] : $m['model_name'],
                    'model_name'    => $m['model_name']
                ];
            }
        }
        return $valid;
    }

    public static function defaultPrompt()
    {
        return '你是一个专业的文章摘要生成助手。请根据提供的文章内容生成一段简洁的中文摘要。要求：
1. 摘要长度最好控制在500字以内，如果能在小于500字的情况就将全文描述清楚的话，那字数越少越好，如果文章实在太长可适当放开限制，但是一定要尽可能控制
2. 准确概括文章的核心内容和关键要点，并且一定要完整
3. 使用流畅自然的中文表达
4. 可以用介绍的口吻来介绍本篇文章的内容，但是要尽量完整并保留文章原滋原味';
    }

    private static function success($data) {
        echo json_encode(array_merge(['ok' => true], $data), JSON_UNESCAPED_UNICODE);
    }
    private static function error($msg, $code = 400) {
        http_response_code($code);
        echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    }
}
