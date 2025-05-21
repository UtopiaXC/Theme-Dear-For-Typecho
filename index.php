<?php
/**
 * 这是一款基于<a target="_blank" href="https://github.com/imjeff/typecho-dear">Dear</a>二次开发的极简纯文字风格Typecho主题。
 * 支持评论、友情链接、代码高亮、灯箱、自适应黑暗模式等功能。 
 * 今天起，沉心写作。 <br/>
 * 更多详情请访问GitHub：<a href="https://github.com/UtopiaXC/Theme-Dear-For-Typecho" target="_blank">https://github.com/UtopiaXC/Theme-Dear-For-Typecho</a>
 *
 * @package Dear-For-Typecho
 * @author Jeff Chen & UtopiaXC
 * @version 2.0.3
 * @link https://github.com/UtopiaXC/Theme-Dear-For-Typecho
 */
if (!defined('__TYPECHO_ROOT_DIR__'))
    exit;
$this->need('header.php');
require('config.php'); ?>
<?php if ($this->is('index')) { ?>
    <?php $this->widget('Widget_Contents_Page_List')->to($pages); ?>
    <?php while ($pages->next()): ?>
        <?php if ($subPageInIndex == "none")
            break; ?>
        <?php if ($pages->slug == $subPageInIndex): ?>
            <p><?php $pages->content(); ?></p><br />
        <?php endif; ?>
    <?php endwhile; ?>
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
        <?php if ($this->is('post')): ?>
            <p><?php $this->category(','); ?> · <time datetime="<?php $this->date('c'); ?>"
                    itemprop="datePublished"><?php $this->date(); ?></time></p><?php endif; ?>
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