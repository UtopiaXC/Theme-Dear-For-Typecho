<?php if (!defined('__TYPECHO_ROOT_DIR__'))
    exit;
$enableHighlightjs = is_null($this->options->Dear_highlight) ? false : (bool) $this->options->Dear_highlight;
$isShowNavi = is_null($this->options->Dear_showNavi) ? true : (bool) $this->options->Dear_showNavi;
$isShowHomeInNavi = is_null($this->options->Dear_showHomeInNavi) ? true : (bool) $this->options->Dear_showHomeInNavi;
$isShowAllSlugInNavi = is_null($this->options->Dear_showAllSlug) ? true : (bool) $this->options->Dear_showAllSlug;
$rawSlug = is_null($this->options->Dear_slugInNavi) ? "" : $this->options->Dear_slugInNavi;
$customNavi = is_null($this->options->Dear_customNavi) ? "" : $this->options->Dear_customNavi;
$slugInNavi = empty(trim($rawSlug)) ? [] : array_map('trim', explode(',', $rawSlug));
$tocPosition = is_null($this->options->Dear_toc) ? '0' : $this->options->Dear_toc;
$tocTextVisible = is_null($this->options->Dear_toc_text_visible) ? '0' : $this->options->Dear_toc_text_visible;
$tocMobileBtn = is_null($this->options->Dear_toc_mobile_btn) ? '1' : $this->options->Dear_toc_mobile_btn;
$themeModeBtn = is_null($this->options->Dear_themeModeBtn) ? '1' : $this->options->Dear_themeModeBtn;
$bgColorLight = is_null($this->options->Dear_bgColorLight) ? '#FAF8F1' : trim($this->options->Dear_bgColorLight);
if (!preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $bgColorLight)) {
    $bgColorLight = '#FAF8F1';
}
$bgColorDark = is_null($this->options->Dear_bgColorDark) ? '#313131' : trim($this->options->Dear_bgColorDark);
if (!preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $bgColorDark)) {
    $bgColorDark = '#313131';
}
?>
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
    <?php
    $isAiSummaryFeatureEnabled = false;
    if ($this->is('post')) {
        if (!is_null($this->options->Dear_aiEnabled)) {
            if ($this->options->Dear_aiEnabled == '1') {
                $isAiSummaryFeatureEnabled = true;
            }
        }
    }
    ?>
    <?php if ($isAiSummaryFeatureEnabled): ?>
        <link rel="stylesheet" href="<?php $this->options->themeUrl('./asset/css/ai-summary.css'); ?>">
    <?php endif; ?>
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
    <?php if (!empty($this->options->Dear_customCss)): ?>
        <style>
            <?php echo $this->options->Dear_customCss; ?>
        </style>
    <?php endif; ?>
    <?php if ($this->is('post') && $tocPosition != '0'): ?>
        <style>
            .post-toc-wrapper {
                position: fixed;
                top: 120px;
                width: 250px;
                max-height: calc(100vh - 160px);
                overflow-y: auto;
                z-index: 99;
                padding: 10px 0;
                scrollbar-width: none;
            }

            .post-toc-wrapper:hover {
                scrollbar-width: thin;
            }

            .post-toc-wrapper.position-right {
                left: 50%;
                margin-left: 360px;
            }

            .post-toc-wrapper.position-left {
                right: 50%;
                margin-right: 360px;
            }

            .post-toc-title {
                font-size: 1.2em;
                font-weight: bold;
                margin-bottom: 15px;
                padding-left: 15px;
                color: var(--heading-color);
                opacity: 0;
                transition: opacity 0.3s;
            }

            .post-toc-wrapper:hover .post-toc-title {
                opacity: 1;
            }

            .post-toc ul {
                list-style: none;
                padding: 0;
                margin: 0;
            }

            .post-toc ul ul {
                padding-left: 15px;
            }

            .post-toc li {
                line-height: 1.6;
                margin: 4px 0;
            }

            .post-toc li .toc-link-container {
                display: flex;
                align-items: center;
                position: relative;
                padding: 2px 0;
            }

            .post-toc li .toc-toggle {
                display: inline-block;
                width: 16px;
                height: 16px;
                line-height: 16px;
                text-align: center;
                font-size: 10px;
                color: var(--gray-color);
                cursor: pointer;
                transition: opacity 0.3s ease, transform 0.3s ease;
                flex-shrink: 0;
                user-select: none;
                margin-right: 5px;
            }

            .post-toc li:not(.has-children)>.toc-link-container .toc-toggle {
                opacity: 0;
                pointer-events: none;
            }

            .post-toc li.collapsed>.toc-link-container .toc-toggle {
                transform: rotate(-90deg);
            }

            .post-toc li.collapsed>ul {
                display: none;
            }

            .post-toc li .toc-link {
                display: flex;
                align-items: center;
                flex: 1;
                text-decoration: none;
                color: var(--text-color);
                overflow: hidden;
                border-bottom: none !important;
            }

            .post-toc li .toc-link:hover {
                border-bottom: none !important;
            }

            .post-toc li .toc-dash {
                width: 12px;
                height: 4px;
                border-radius: 2px;
                background: var(--gray-color);
                <?php if ($tocTextVisible == '1'): ?>
                opacity: 0.3;
                <?php else: ?>
                opacity: 0;
                <?php endif; ?>
                margin-right: 10px;
                flex-shrink: 0;
                transition: all 0.3s ease;
            }

            .post-toc li .toc-text {
                <?php if ($tocTextVisible == '1'): ?>
                opacity: 1;
                <?php else: ?>
                opacity: 0;
                <?php endif; ?>
                transition: opacity 0.3s ease;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                font-size: 0.9em;
            }

            <?php if ($tocTextVisible == '0'): ?>
            .post-toc li .toc-toggle {
                opacity: 0;
            }

            .post-toc-wrapper:hover li .toc-text,
            .post-toc li.active>.toc-link-container .toc-text,
            .post-toc-wrapper:hover li.has-children>.toc-link-container .toc-toggle,
            .post-toc li.active.has-children>.toc-link-container .toc-toggle {
                opacity: 1;
            }

            .post-toc-wrapper:hover li .toc-dash {
                opacity: 0.3;
            }
            <?php endif; ?>
            <?php if ($tocTextVisible == '1'): ?>
                .post-toc-title {
                    opacity: 1 !important;
                }

                .post-toc-wrapper {
                    scrollbar-width: thin !important;
                }

            <?php endif; ?>
            .post-toc li .toc-link:hover .toc-dash {
                background: var(--link-color);
                opacity: 0.8;
            }

            .post-toc li .toc-link-container:hover .toc-toggle,
            .post-toc li.active>.toc-link-container .toc-toggle {
                color: var(--link-color);
            }

            .post-toc li.active>.toc-link-container .toc-dash {
                background: var(--link-color);
                opacity: 1;
                width: 18px;
            }

            .post-toc li.active>.toc-link-container .toc-text {
                color: var(--link-color);
                font-weight: bold;
            }

            .toc-toggle-btn {
                display: none;
                position: fixed;
                right: 20px;
                bottom: 20px;
                width: 45px;
                height: 45px;
                border-radius: 50%;
                background-color: var(--background-color);
                border: 1px solid rgba(128, 128, 128, 0.2);
                color: var(--text-color);
                text-align: center;
                line-height: 45px;
                font-size: 20px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                z-index: 1000;
                cursor: pointer;
            }

            .post-toc-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 998;
            }

            @media screen and (max-width: 1250px) {
                .post-toc li .toc-text {
                    opacity: 1 !important;
                }

                .post-toc-title {
                    opacity: 1 !important;
                }

                .post-toc-wrapper {
                    scrollbar-width: thin !important;
                }

                <?php if ($tocMobileBtn == '0'): ?>
                    .post-toc-wrapper {
                        display: none !important;
                    }

                <?php else: ?>
                    .toc-toggle-btn {
                        display: block;
                    }

                    .post-toc-wrapper {
                        position: fixed;
                        left: auto !important;
                        right: 0 !important;
                        top: 0;
                        bottom: 0;
                        width: 280px;
                        height: 100vh;
                        max-height: 100vh;
                        background: var(--background-color);
                        margin: 0 !important;
                        padding: 40px 20px;
                        box-sizing: border-box;
                        box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
                        transform: translateX(110%);
                        transition: transform 0.3s ease;
                        z-index: 999;
                    }

                    .post-toc-wrapper.drawer-open {
                        transform: translateX(0);
                    }

                    .post-toc-overlay.drawer-open {
                        display: block;
                    }

                <?php endif; ?>
            }
        </style>
    <?php endif; ?>

    <style>
        :root {
            --background-color: <?php echo $bgColorLight; ?> !important;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --background-color: <?php echo $bgColorDark; ?> !important;
            }
        }
    </style>
    <?php if ($themeModeBtn == '1'): ?>
        <style>
            html[data-theme="dark"] {
                --background-color: <?php echo $bgColorDark; ?> !important;
                --heading-color: #eee;
                --text-color: #ddd;
                --link-color: #6ab0e5;
                --visited-color: #8b6fcb;
                --code-background-color: #313131;
                --code-color: #ddd;
                --blockquote-color: #ccc;
                --gray-color: #999;
                --current-page-color: #a4d8ff;
                --comment-author-color: #606060;
                --image-background-color: #c0c0c0;
            }
            html[data-theme="light"] {
                --background-color: <?php echo $bgColorLight; ?> !important;
                --heading-color: #222;
                --text-color: #444;
                --link-color: #3273dc;
                --visited-color: #8b6fcb;
                --code-background-color: #fff;
                --code-color: #222;
                --blockquote-color: #222;
                --gray-color: #999;
                --current-page-color: #88b6ff;
                --comment-author-color: #eee;
                --image-background-color: #ffffff;
            }
            .theme-mode-btn {
                position: absolute;
                top: 25px;
                right: 0;
                background: none;
                border: none;
                cursor: pointer;
                color: var(--text-color);
                width: 20px;
                height: 20px;
                padding: 0;
                outline: none;
                opacity: 0.5;
                transition: opacity 0.3s;
                z-index: 99;
            }
            .theme-mode-btn:hover {
                opacity: 1;
            }
            header { position: relative; }
        </style>
        <script>
            (function() {
                var theme = localStorage.getItem('dear_theme') || 'auto';
                if (theme !== 'auto') {
                    document.documentElement.setAttribute('data-theme', theme);
                }
            })();
        </script>
    <?php endif; ?>
</head>

<body>
    <header>
        <?php if ($themeModeBtn == '1'): ?>
            <button id="theme-mode-btn" class="theme-mode-btn" title="切换深浅色模式">
                <svg id="theme-icon-auto" viewBox="0 0 1024 1024" fill="currentColor" style="display:none; width: 100%; height: 100%;">
                    <path d="M485.376 539.648c33.28 33.28 70.656 61.952 111.616 85.504 40.96 24.064 84.992 42.496 132.096 55.296-26.112 33.28-57.856 59.392-95.744 77.824-37.888 18.432-77.824 28.16-120.32 28.16-76.288 0-141.312-26.624-194.56-79.872s-79.872-117.76-79.872-194.56c0-42.496 9.216-82.432 28.16-120.32 18.432-37.888 44.544-69.632 77.824-95.744 13.312 47.104 31.744 91.136 55.296 132.096 24.064 40.96 52.224 78.336 85.504 111.616z m284.16 70.144c-13.312-3.072-26.112-6.656-38.912-10.752s-25.088-8.704-37.888-13.824c5.12-11.776 9.216-23.552 11.264-35.84s3.584-24.576 3.584-37.888c0-54.272-18.944-100.352-57.344-138.752-38.4-38.4-84.48-57.344-138.752-57.344-13.312 0-25.6 1.024-37.888 3.584-12.288 2.048-24.064 6.144-35.84 11.264-5.12-12.288-9.728-24.576-13.312-37.376-3.584-12.288-7.168-25.088-10.24-38.4 15.872-5.632 31.744-10.24 48.128-13.312 16.384-3.072 32.768-4.608 50.176-4.608 76.288 0 141.312 26.624 194.56 79.872s79.872 117.76 79.872 194.56c0 16.896-1.536 33.792-4.608 50.176-2.56 16.896-7.168 33.28-12.8 48.64z m-296.96-450.56v-117.76h78.336v117.76H472.576z m0 822.784v-117.76h78.336v117.76H472.576z m316.416-691.2l-55.808-55.808 83.456-82.432 55.808 54.784-83.456 83.456zM207.36 871.424l-55.808-54.784 83.456-83.456 55.808 55.808-83.456 82.432z m657.408-320V472.576h117.76v78.336h-117.76z m-822.784 0V472.576h117.76v78.336h-117.76z m774.656 321.024l-83.456-83.456 55.808-55.808 82.432 83.456-54.784 55.808zM235.008 290.816L152.576 207.36l54.784-55.808 83.456 83.456-55.808 55.808z"></path>
                </svg>
                <svg id="theme-icon-light" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none; width: 100%; height: 100%;">
                    <circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                </svg>
                <svg id="theme-icon-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none; width: 100%; height: 100%;">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                </svg>
            </button>
            <script>
                (function() {
                    const btn = document.getElementById('theme-mode-btn');
                    if(!btn) return;
                    const icons = {
                        'auto': document.getElementById('theme-icon-auto'),
                        'light': document.getElementById('theme-icon-light'),
                        'dark': document.getElementById('theme-icon-dark')
                    };
                    let currentTheme = localStorage.getItem('dear_theme') || 'auto';
                    function updateIcon() {
                        Object.values(icons).forEach(icon => icon.style.display = 'none');
                        icons[currentTheme].style.display = 'block';
                    }
                    updateIcon();
                    btn.addEventListener('click', function() {
                        if (currentTheme === 'auto') currentTheme = 'light';
                        else if (currentTheme === 'light') currentTheme = 'dark';
                        else currentTheme = 'auto';
                        
                        localStorage.setItem('dear_theme', currentTheme);
                        updateIcon();
                        
                        if (currentTheme === 'auto') {
                            document.documentElement.removeAttribute('data-theme');
                        } else {
                            document.documentElement.setAttribute('data-theme', currentTheme);
                        }
                    });
                })();
            </script>
        <?php endif; ?>
        <?php $site_title_elem = $this->is('index') ? 'h1' : 'h2'; ?>
        <a class="title" href="<?php $this->options->siteUrl(); ?>"><<?php echo $site_title_elem; ?>
                ><?php $this->options->title() ?></<?php echo $site_title_elem; ?>></a>
        <?php if ($isShowNavi): ?>
            <nav>
                <p>
                    <?php if ($isShowHomeInNavi): ?>
                        <a<?php if ($this->is('index')): ?> class="current" <?php endif; ?> href="<?php $this->options->siteUrl(); ?>"><?php _e('首页'); ?></a><?php endif; ?><?php
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
                        if (!empty(trim($customNavi))) {
                            echo $customNavi;
                        }
                        ?>
                </p>
            </nav>
        <?php endif; ?>
    </header>
    <main>