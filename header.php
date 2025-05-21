<?php if (!defined('__TYPECHO_ROOT_DIR__'))
    exit;
require('config.php'); ?>
<!DOCTYPE HTML>
<html>

<head>
    <meta charset="<?php $this->options->charset(); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        <?php $this->archiveTitle(['category' => _t('%s'), 'search' => _t('搜索结果：%s'), 'tag' => _t('标签：%s'), 'author' => _t('作者：%s')], '', ' - '); ?><?php $this->options->title(); ?>
    </title>
    <?php $this->header(); ?>
    <link rel="stylesheet" href="<?php $this->options->themeUrl('./asset/css/style.css'); ?>">
    <?php if ($enableHighlightjs): ?>
        <link id="hljs-theme" rel="stylesheet"
              href="<?php $this->options->themeUrl('/asset/css/highlightjs/default.min.css'); ?>">
        <script src="<?php $this->options->themeUrl('/asset/js/highlight.min.js'); ?>"></script>
    <?php endif ?>
    <?php if ($this->is('post') && $this->fields->isLatex == 1): ?>
        <link rel="stylesheet" href="<?php $this->options->themeUrl('./asset/css/KaTeX/katex.min.css'); ?>">
        <script defer type="text/javascript"
                src="<?php $this->options->themeUrl('/asset/js/KaTeX/katex.min.js'); ?>"></script>
        <script defer type="text/javascript"
                src="<?php $this->options->themeUrl('/asset/js/KaTeX/auto-render.min.js'); ?>"></script>
    <?php endif; ?>
</head>

<body>
<header>
    <?php $site_title_elem = $this->is('index') ? 'h1' : 'h2'; ?>
    <a class="title" href="<?php $this->options->siteUrl(); ?>"><<?php echo $site_title_elem; ?>
        ><?php $this->options->title() ?></<?php echo $site_title_elem; ?>></a>
    <?php if ($isShowNavi): ?>
        <nav>
            <p>
                <?php if ($isShowHomeInNavi): ?>
                    <a<?php if ($this->is('index')): ?> class="current" <?php endif; ?>
                            href="<?php $this->options->siteUrl(); ?>"><?php _e('首页'); ?></a>
                <?php endif; ?>
                <?php
                $this->widget('Widget_Contents_Page_List')->to($pages);
                if ($isShowAllSlugInNavi) {
                    while ($pages->next()) {
                        $isCurrent = "";
                        if ($this->is('page', $pages->slug)) {
                            $isCurrent = 'class="current "';
                        }
                        echo '<a ' . $isCurrent . 'href=';
                        $pages->permalink();
                        echo ' title="';
                        $pages->title();
                        echo '">';
                        $pages->title();
                        echo '</a>';
                    }
                } else {
                    foreach ($slugInNavi as $slug) {
                        while ($pages->next()) {
                            if ($pages->slug == $slug) {
                                $isCurrent = "";
                                if ($this->is('page', $pages->slug)) {
                                    $isCurrent = 'class="current "';
                                }
                                echo '<a ' . $isCurrent . 'href=';
                                $pages->permalink();
                                echo ' title="';
                                $pages->title();
                                echo '">';
                                $pages->title();
                                echo '</a>';
                                continue;
                            }
                        }
                    }
                }
                ?>
            </p>
        </nav>
    <?php endif; ?>
</header>
<main>