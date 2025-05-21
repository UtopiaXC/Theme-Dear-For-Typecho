<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
require('config.php');
// 文章数设置
function themeInit($archive)
{
    global $numArticleListContainsEachPage;
    if ($archive->is('index')) {
        $archive->parameter->pageSize = $numArticleListContainsEachPage; // 首页近期文章条数
    }
    if ($archive->is('category')) {
        $archive->parameter->pageSize = $numArticleListContainsEachPage; // 自定义分类页条数
    }
    if ($archive->is('tag')) {
        $archive->parameter->pageSize = $numArticleListContainsEachPage; // 自定义标签页条数
    }
}

function themeFields($layout)
{
    $isLatex = new Typecho_Widget_Helper_Form_Element_Radio('isLatex',
        array(1 => _t('启用'),
            0 => _t('关闭')),
        0, _t('LaTeX 渲染'), _t('默认关闭增加网页访问速度，如文章内存在LaTeX语法则需要启用'));
    $layout->addItem($isLatex);
}
