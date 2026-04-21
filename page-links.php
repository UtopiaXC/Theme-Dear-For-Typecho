<?php
/**
 * 友情链接
 *
 * @package custom
 */
if (!defined('__TYPECHO_ROOT_DIR__'))
    exit;

$unGroupedLinksDefaultName = is_null($this->options->Dear_unGroupedLinks) ? "未分类" : $this->options->Dear_unGroupedLinks;
$openLinksInNewWindow = is_null($this->options->Dear_newWindow) ? true : (bool)$this->options->Dear_newWindow;
$linkDefaultImg = is_null($this->options->Dear_linkImg) ? "/asset/imgs/defaultLinkImg.svg" : $this->options->Dear_linkImg;
$isDefaultImgLocal = is_null($this->options->Dear_linkImgLocal) ? true : (bool)$this->options->Dear_linkImgLocal;
?>
<?php $this->need('header.php'); ?>
<link rel="stylesheet" href="<?php $this->options->themeUrl('./asset/css/links.css'); ?>">
<?php
$contentHTML = $this->content;
$contentHTML = preg_replace('/<dear-links>.*?<\/dear-links>/is', '', $contentHTML);
?>
<div><?php echo $contentHTML; ?></div>
<?php
$links = [];
$types = [];

$rawText = $this->text;
if (preg_match('/<dear-links>(.*?)<\/dear-links>/is', $rawText, $matches)) {
    $jsonObj = json_decode(trim($matches[1]), true);
    if (is_array($jsonObj)) {
        foreach ($jsonObj as $item) {
            $name = isset($item['name']) ? trim($item['name']) : '';
            if (!$name)
                continue;
            $url = isset($item['url']) ? trim($item['url']) : '';
            $desc = isset($item['description']) ? trim($item['description']) : '';
            $avatar = isset($item['image']) ? trim($item['image']) : '';
            $email = isset($item['email']) ? trim($item['email']) : '';
            $display = isset($item['display']) ? (bool) $item['display'] : true;
            $currentSort = isset($item['type']) && $item['type'] !== '' ? trim($item['type']) : $unGroupedLinksDefaultName;
            if (!$display)
                continue;
            if (!$avatar && $email) {
                $avatar = 'https://cravatar.cn/avatar/' . md5(strtolower(trim($email))) . '?s=100&d=mp';
            }
            $links[] = [
                'name' => $name,
                'url' => $url,
                'description' => $desc,
                'image' => $avatar,
                'sort' => $currentSort,
                'state' => $status
            ];
            if (!in_array($currentSort, $types)) {
                $types[] = $currentSort;
            }
        }
    }
}

$plugins = \Typecho\Plugin::export();
$isLinksActive = isset($plugins['activated']['Links']);

if ($isLinksActive) {
    try {
        $db = \Typecho\Db::get();
        $prefix = $db->getPrefix();
        @$dbLinks = $db->fetchAll($db->select()->from($prefix . 'links'));
        if ($dbLinks && count($dbLinks) > 0) {
            $dbTypes = [];
            foreach ($dbLinks as $link) {
                if ($link['state'] == 0)
                    continue;
                $type = $link['sort'];
                if ($type == '') {
                    $type = $unGroupedLinksDefaultName;
                }
                $links[] = $link;
                $dbTypes[] = $type;
            }
            $dbTypes = array_unique($dbTypes);
            sort($dbTypes);
            foreach ($dbTypes as $dt) {
                if (!in_array($dt, $types)) {
                    $types[] = $dt;
                }
            }
        }
    } catch (Typecho_Db_Exception $e) {
    }
}

if (empty($links)) {
    echo '未检测到友情链接内容。您可以安装并启用 <a target="_blank" href="https://github.com/Mejituu/Links?tab=readme-ov-file">Links</a> 插件，或者在当前页面加入 <code>&lt;dear-links&gt;...&lt;/dear-links&gt;</code> 标签构建友链。';
} else {
    foreach ($types as $type) {
        $target = $openLinksInNewWindow ? "_blank" : "_self";
        echo '<h3># ' . $type . '</h3><div class="links">';
        foreach ($links as $link) {
            if ($link["sort"] == $type || ($type == $unGroupedLinksDefaultName && $link["sort"] == "")) {
                echo '
                <a target="' . $target . '" href="' . $link["url"] . '" title="' . $link["name"] . '" class="link-card-url">
          <div class="link-card">
            <div class="link-card-left">
              <img class="link-card-avatar" src="' . $link["image"] . '" notFoundSrc="';
                if ($isDefaultImgLocal) {
                    $this->options->themeUrl($linkDefaultImg);
                } else {
                    echo $linkDefaultImg;
                }
                echo '">
            </div>
            <div class="link-card-right">
              <div class="link-card-title">' . $link["name"] . '</div>
              <div class="link-card-description">' . $link["description"] . '</div>
            </div>
          </div>
        </a>
                ';
                echo "</br>";
            }
        }
        echo "</div>";
    }
}
?>
</div>
<?php $this->need('comments.php'); ?>
<script>
    document.addEventListener("error", function (e) {
        var elem = e.target;
        if (elem.tagName.toLowerCase() === 'img') {
            var notFoundImgSrt = elem.getAttribute("notFoundSrc")
            if (notFoundImgSrt) {
                elem.src = notFoundImgSrt;
            }
        }
    }, true);
</script>
<?php $this->need('footer.php'); ?>