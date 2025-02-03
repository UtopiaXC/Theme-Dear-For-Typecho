<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
require('config.php');
// 文章数设置
function themeInit($archive) {
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