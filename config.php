<?php
if (!defined('__TYPECHO_ROOT_DIR__'))
    exit;
global $showCategoryInArticlesList;
global $numArticleListContainsEachPage;
global $subPageInIndex;
global $enableComments;
global $enableCommentUrl;

//是否在文章列表中显示文章分类
$showCategoryInArticlesList = false;

//文章列表每页包含条目数
$numArticleListContainsEachPage = 8;

//主页显示的嵌套独立页面类型。请填写独立页面的缩略名。"none"为不显示。
$subPageInIndex = "announcement";

//是否启用评论区
$enableComments = true;

//是否禁用评论时输入网址（用于减少广告）
$enableCommentUrl = true;