<?php
if (!defined('__TYPECHO_ROOT_DIR__'))
    exit;
global $showCategoryInArticlesList;
global $numArticleListContainsEachPage;
global $subPageInIndex;
global $enableComments;
global $enableCommentUrl;
global $linkDefaultImg;
global $linkDefaultImgFromUrl;
global $openLinksInNewWindow;
global $unGroupedLinksDefaultName;
global $isShowAllSlugInNavi;
global $slugInNavi;
global $isShowNavi;
global $isShowHomeInNavi;
global $articlesTitle;
global $enableLightbox;
global $rssUrl;

// 是否在文章列表中显示文章分类。
// 可选属性：true与false
$showCategoryInArticlesList = false;

// 文章列表每页包含条目数。
// 可选属性：整数
$numArticleListContainsEachPage = 8;

// 主页显示的嵌套独立页面类型。请填写独立页面的缩略名。"none"为不显示。
// 可选属性：字符串，独立页面缩略名
$subPageInIndex = "announcement";

// 是否启用评论区。（与typecho设置无关，如果为否则加载任何页面时将完全不加载评论系统）
// 可选属性：true与false
$enableComments = true;

// 是否启用评论时输入网址（用于减少广告）,如果设置为false，请将typecho设置中的评论需要填写网址选项关闭。
// 可选属性：true与false
$enableCommentUrl = true;

// 友情链接头像默认图片链接，用于友情链接图片加载失败时进行替换。
// 可选属性：字符串，图片链接，如果是本地链接请将原图放于本主题文件夹内
$linkDefaultImg = "/asset/imgs/defaultLinkImg.svg";

// 友情链接头像默认图片链接是否保存在主题文件夹内
// 如果$linkDefaultImg使用类似于https://xxx.com/img.png的形式，本项请设置为false
// 可选属性：true与false
$isDefaultImgLocal = true;

// 是否在新页面内打开友情链接
// 可选属性：true与false
$openLinksInNewWindow = true;

// 没有分类的友情链接的默认分类名
// 可选属性：字符串，用于未分类的友情链接的显示Title
$unGroupedLinksDefaultName = "未分类";

// 是否显示面包屑导航
// 可选属性：true与false
$isShowNavi = true;

// 是否在面包屑导航中显示首页
// 可选属性：true与false
$isShowHomeInNavi = true;

// 是否在面包屑导航中显示全部独立页面
// 可选属性：true与false
$isShowAllSlugInNavi = true;

// 如果不在面包屑导航中显示全部独立页面（showAllSlugInNavi = false）
// 请在本数组内填写所有要展示的独立页面入口，例如：
// $showAllSlugInNavi = ["about","tags"];
// 可选属性：字符串数组
$slugInNavi = [];

// 首页文章列表的标题
// 可选属性：字符串
$articlesTitle = "Articles";

// 是否为文章载入viewer图片灯箱1.11.7，如果为false则不会引入任何新增js与css
// https://github.com/fengyuanchen/viewerjs
// 可选属性：true与false
$enableLightbox = false;

// 博客底部添加RSS订阅链接，如果为"none"则为不添加
// 可选属性：字符串
$rssUrl = "none";

// 自定义CSS
// 请将自定义CSS填写入asset/css/custom.css中

// 自定义JS
// 请将自定义JS填写入asset/js/custom.js中