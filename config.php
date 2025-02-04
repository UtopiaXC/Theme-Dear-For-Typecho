<?php
if (!defined('__TYPECHO_ROOT_DIR__'))
    exit;
global $showCategoryInArticlesList;
global $numArticleListContainsEachPage;
global $subPageInIndex;
global $enableComments;
global $enableCommentUrl;
global $linkDefaultImg;
global $openLinksInNewWindow;
global $unGroupedLinksDefaultName;

//是否在文章列表中显示文章分类。
$showCategoryInArticlesList = false;

//文章列表每页包含条目数。
$numArticleListContainsEachPage = 8;

//主页显示的嵌套独立页面类型。请填写独立页面的缩略名。"none"为不显示。
$subPageInIndex = "announcement";

//是否启用评论区。（与typecho设置无关，如果为否则加载任何页面时将完全不加载评论系统）
$enableComments = true;

//是否启用评论时输入网址（用于减少广告）,如果设置为false，请将typecho设置中的评论需要填写网址选项关闭。
$enableCommentUrl = true;

//友情链接默认图片链接，用于友情链接图片加载失败时进行替换。
$linkDefaultImg = "/asset/imgs/defaultLinkImg.svg";

//是否在新页面内打开友情链接
$openLinksInNewWindow = true;

//没有分类的友情链接的默认分类名
$unGroupedLinksDefaultName = "未分类";