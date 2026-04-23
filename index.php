<?php
/**
 * 这是一款基于<a target="_blank" href="https://github.com/imjeff/typecho-dear">Dear</a>二次开发的极简纯文字风格Typecho主题。
 * 支持评论、友情链接、目录、代码高亮、灯箱、自适应黑暗模式、AI摘要等功能。
 * 今天起，沉心创作。 <br/>
 * 更多详情请访问GitHub：<a href="https://github.com/UtopiaXC/Theme-Dear-For-Typecho" target="_blank">https://github.com/UtopiaXC/Theme-Dear-For-Typecho</a>
 *
 * @package Dear-For-Typecho
 * @author Jeff Chen & UtopiaXC
 * @version 3.2.0
 * @link https://github.com/UtopiaXC/Theme-Dear-For-Typecho
 */
if (!defined('__TYPECHO_ROOT_DIR__'))
    exit;

$subPageInIndex = is_null($this->options->Dear_subPage) ? "none" : $this->options->Dear_subPage;
$articlesTitle = is_null($this->options->Dear_articlesTitle) ? "Articles" : $this->options->Dear_articlesTitle;
$showCategoryInArticlesList = is_null($this->options->Dear_showCategory) ? true : (bool) $this->options->Dear_showCategory;
$enableComments = is_null($this->options->Dear_comments) ? false : (bool) $this->options->Dear_comments;
$enableLightbox = is_null($this->options->Dear_lightbox) ? false : (bool) $this->options->Dear_lightbox;

$this->need('header.php');
?>
<?php if ($this->is('index')) { ?>
    <?php if ($subPageInIndex != "none"): ?>
        <?php
        $db = \Typecho\Db::get();
        $pageRow = $db->fetchRow($db->select()->from('table.contents')
            ->where('type = ?', 'page')
            ->where('slug = ?', $subPageInIndex)
            ->limit(1));

        if ($pageRow) {
            $announcement = \Typecho\Widget::widget('Widget_Contents_Page_List');
            $announcement->push($pageRow);
            echo '<p>' . $announcement->content . '</p><br />';
        }
    ?>
    <?php endif; ?>
    <h3><?php echo $articlesTitle; ?></h3>
    <p>
    <ul class="posts">
        <?php while ($this->next()): ?>
            <li>
                <span><time datetime="<?php $this->date('c'); ?>" itemprop="datePublished"><?php $this->date(); ?></time></span>
                <div><?php if ($showCategoryInArticlesList) {
                    $this->category(',');
                    echo "&nbsp · &nbsp";
                } ?><a href="<?php $this->permalink() ?>"><?php $this->title() ?></a></div>
            </li>
        <?php endwhile; ?>
    </ul>
    </p>
<?php } elseif ($this->is('category') || $this->is('tag')) { ?>
    <h2><?php
    if ($this->is('category')) {
        echo "Category";
    } elseif ($this->is('tag')) {
        echo "Tag";
    }
    $this->archiveTitle(); ?></h2>
    <p>
    <ul class="posts">
        <?php while ($this->next()): ?>
            <li>
                <span><time datetime="<?php $this->date('c'); ?>" itemprop="datePublished"><?php $this->date(); ?></time></span>
                <div><?php if ($showCategoryInArticlesList) {
                    $this->category(',');
                    echo "&nbsp · &nbsp";
                } ?><a href="<?php $this->permalink() ?>"><?php $this->title() ?></a></div>
            </li>
        <?php endwhile; ?>
    </ul>
    </p>
<?php } else {
    if ($this->is('single')): ?>
        <h1><?php $this->title() ?></h1>
        <?php if ($this->is('post')):
            $aiEnabled = !is_null($this->options->Dear_aiEnabled) && $this->options->Dear_aiEnabled == '1';
        ?>
            <p class="post-meta-line">
                <?php $this->category(','); ?> · <time datetime="<?php $this->date('c'); ?>"
                    itemprop="datePublished"><?php $this->date(); ?></time>
                <?php if ($aiEnabled): ?>
                    <button type="button" id="ai-summary-toggle-btn" class="ai-summary-toggle-btn" title="显示/隐藏AI摘要">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;margin-right:3px;"><path d="M12 2a4 4 0 0 1 4 4v1a3 3 0 0 1 3 3v1a3 3 0 0 1-3 3H8a3 3 0 0 1-3-3v-1a3 3 0 0 1 3-3V6a4 4 0 0 1 4-4z"/><line x1="9" y1="18" x2="9" y2="22"/><line x1="15" y1="18" x2="15" y2="22"/><line x1="7" y1="22" x2="17" y2="22"/></svg>
                        <span>AI摘要</span>
                    </button>
                <?php endif; ?>
            </p>
            <?php if ($aiEnabled): ?>
            <div id="ai-summary-box" class="ai-summary-box" data-cid="<?php echo $this->cid; ?>">
                <div class="ai-summary-header">
                    <div class="ai-summary-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a4 4 0 0 1 4 4v1a3 3 0 0 1 3 3v1a3 3 0 0 1-3 3H8a3 3 0 0 1-3-3v-1a3 3 0 0 1 3-3V6a4 4 0 0 1 4-4z"/><line x1="9" y1="18" x2="9" y2="22"/><line x1="15" y1="18" x2="15" y2="22"/><line x1="7" y1="22" x2="17" y2="22"/></svg>
                        AI 摘要
                        <div class="ai-model-select-wrapper">
                            <button type="button" id="ai-model-select-btn" class="ai-model-select-btn">
                                <span id="ai-model-current"></span> ▾
                            </button>
                            <div id="ai-model-dropdown" class="ai-model-dropdown" style="display:none;"></div>
                        </div>
                        <span id="ai-summary-time" class="ai-summary-time-label"></span>
                    </div>
                    <div class="ai-summary-actions">
                        <button type="button" id="ai-regenerate-btn" class="ai-summary-action-btn" title="重新生成" style="width: auto; padding: 0 8px; font-size: 12px; line-height: 1;">
                            重新生成
                        </button>
                        <button type="button" id="ai-copy-btn" class="ai-summary-action-btn" title="复制摘要">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        </button>
                        <button type="button" id="ai-close-btn" class="ai-summary-action-btn" title="关闭">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                </div>
                <div id="ai-summary-content" class="ai-summary-content ai-summary-collapsed">
                    <div id="ai-summary-text"></div>
                </div>
                <button type="button" id="ai-summary-expand-btn" class="ai-summary-expand-btn" style="display:none;">展开全文 ▼</button>
                <div id="ai-generation-confirmation-modal" class="ai-summary-modal-container">
                    <div class="ai-summary-modal-card">
                        <div class="ai-summary-modal-title">确认</div>
                        <div class="ai-summary-modal-body">摘要生成请求速率已被限制，多次请求重新生成可能被拒绝，是否继续？</div>
                        <div class="ai-summary-modal-footer">
                            <button type="button" id="ai-generation-cancel-button" class="ai-summary-modal-button ai-summary-modal-cancel-text">取消</button>
                            <button type="button" id="ai-generation-confirm-button" class="ai-summary-modal-button ai-summary-modal-confirm-text">确认</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
        <div id="gallery"><?php $this->content(); ?></div>

        <?php if ($this->is('post')): ?>
            <p># <?php $this->tags(', ', true, '无标签'); ?></p><?php endif; ?>
        <p><br /><?php if ($enableComments) {
            $this->need('comments.php');
        } ?></p>
        <?php if ($enableLightbox): ?>
            <link href="<?php $this->options->themeUrl('/asset/css/viewer.min.css'); ?>" rel="stylesheet">
            <script src="<?php $this->options->themeUrl('/asset/js/viewer.min.js'); ?>"></script>
            <script>
                const gallery = new Viewer(document.getElementById('gallery'));
            </script>
        <?php endif; ?>
    <?php endif;
} ?>
<?php
if ($this->is('index') || $this->is('category') || $this->is('tag')) {
    $this->pageNav('&nbsp;←&nbsp;', '&nbsp;→&nbsp;', '3', '…');
}
?>

<?php $this->need('footer.php'); ?>