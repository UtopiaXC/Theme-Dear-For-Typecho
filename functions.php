<?php
if (!defined('__TYPECHO_ROOT_DIR__'))
    exit;
// 文章数设置
function themeInit($archive)
{
    $options = Helper::options();
    $numArticleListContainsEachPage = is_null($options->Dear_numArticle) ? 8 : (int) $options->Dear_numArticle;
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
    $isLatex = new Typecho_Widget_Helper_Form_Element_Radio(
        'isLatex',
        array(
            1 => _t('启用'),
            0 => _t('关闭')
        ),
        0,
        _t('LaTeX 渲染'),
        _t('默认关闭增加网页访问速度，如文章内存在LaTeX语法则需要启用')
    );
    $layout->addItem($isLatex);
}

Typecho_Plugin::factory('admin/menu.php')->navBar = array('DearTheme_Menu', 'render');
class DearTheme_Menu
{
    public static function render()
    {
        echo '<a href="' . Helper::options()->adminUrl . 'options-theme.php">Dear主题设置</a>';
    }
}

function themeConfig($form)
{
    $Dear_showCategory = new Typecho_Widget_Helper_Form_Element_Radio('Dear_showCategory', array('1' => _t('显示'), '0' => _t('隐藏')), '0', _t('是否在文章列表中显示文章分类'), _t('文章列表中是否在最前面显示分类名称'));
    $form->addInput($Dear_showCategory);

    $Dear_numArticle = new Typecho_Widget_Helper_Form_Element_Text('Dear_numArticle', NULL, '8', _t('文章列表每页包含条目数'), _t('填写正整数，默认为0'));
    $form->addInput($Dear_numArticle);

    $Dear_subPage = new Typecho_Widget_Helper_Form_Element_Text('Dear_subPage', NULL, 'none', _t('主页显示的嵌套独立页面的URL缩略名'), _t('由于Typecho新版本修改，建议不要留空。填写创建过的独立页面的自定义URL缩略名，即slug，独立页面编辑页中标题下方的黄色框中的内容，填“none”则为不显示'));
    $form->addInput($Dear_subPage);

    $Dear_comments = new Typecho_Widget_Helper_Form_Element_Radio('Dear_comments', array('1' => _t('启用'), '0' => _t('关闭')), '0', _t('是否启用评论区'), _t('如果为关闭，哪怕在Typecho发布文章时选择开启了评论也会完全屏蔽评论系统的加载'));
    $form->addInput($Dear_comments);

    $Dear_commentUrl = new Typecho_Widget_Helper_Form_Element_Radio('Dear_commentUrl', array('1' => _t('启用'), '0' => _t('关闭')), '0', _t('是否允许评论时填写自己的网站地址'), _t('关闭可减少垃圾链接。关闭前请同步将Typecho的设置->评论->“必须填写网址”关掉'));
    $form->addInput($Dear_commentUrl);

    $Dear_linkImg = new Typecho_Widget_Helper_Form_Element_Text('Dear_linkImg', NULL, '/asset/imgs/defaultLinkImg.svg', _t('友情链接默认图片'), _t('友链图片加载失败时调用的图片路径'));
    $form->addInput($Dear_linkImg);

    $Dear_linkImgLocal = new Typecho_Widget_Helper_Form_Element_Radio('Dear_linkImgLocal', array('1' => _t('是'), '0' => _t('否')), '1', _t('友情链接默认图片是否在主题本地'), _t('如果上方的路径是完整外部https://...链接，这里请选【否】'));
    $form->addInput($Dear_linkImgLocal);

    $Dear_newWindow = new Typecho_Widget_Helper_Form_Element_Radio('Dear_newWindow', array('1' => _t('是'), '0' => _t('否')), '1', _t('友链能否在新页面打开'), _t(''));
    $form->addInput($Dear_newWindow);

    $Dear_unGroupedLinks = new Typecho_Widget_Helper_Form_Element_Text('Dear_unGroupedLinks', NULL, '未分类', _t('默认友情链接分组名'), _t('如果没有正确指定type的友链将被统一放置到此名称下'));
    $form->addInput($Dear_unGroupedLinks);

    $Dear_showNavi = new Typecho_Widget_Helper_Form_Element_Radio('Dear_showNavi', array('1' => _t('显示'), '0' => _t('隐藏')), '1', _t('是否显示面包屑导航'), _t(''));
    $form->addInput($Dear_showNavi);

    $Dear_showHomeInNavi = new Typecho_Widget_Helper_Form_Element_Radio('Dear_showHomeInNavi', array('1' => _t('显示'), '0' => _t('隐藏')), '1', _t('面包屑是否包含首页'), _t(''));
    $form->addInput($Dear_showHomeInNavi);

    $Dear_showAllSlug = new Typecho_Widget_Helper_Form_Element_Radio('Dear_showAllSlug', array('1' => _t('显示'), '0' => _t('隐藏')), '1', _t('面包屑是否显示全部独立页面'), _t(''));
    $form->addInput($Dear_showAllSlug);

    $Dear_slugInNavi = new Typecho_Widget_Helper_Form_Element_Text('Dear_slugInNavi', NULL, '', _t('手动补充呈现页面入口'), _t('如果你在上面关闭了显示全部独立页面，可手动在此填写要展示的缩略名集合。英文逗号分隔。如: about,links'));
    $form->addInput($Dear_slugInNavi);

    $Dear_articlesTitle = new Typecho_Widget_Helper_Form_Element_Text('Dear_articlesTitle', NULL, 'Articles', _t('最近文章列表的标题名'), _t(''));
    $form->addInput($Dear_articlesTitle);

    $Dear_lightbox = new Typecho_Widget_Helper_Form_Element_Radio('Dear_lightbox', array('1' => _t('启用'), '0' => _t('关闭')), '0', _t('启用图片灯箱特效'), _t('开启后文章内的图片点击可放大。会自动引入Viewerjs，略微增加网页加载时间'));
    $form->addInput($Dear_lightbox);

    $Dear_rssUrl = new Typecho_Widget_Helper_Form_Element_Text('Dear_rssUrl', NULL, 'none', _t('底部RSS订阅链接'), _t('填写后会在博客底部添加一个RSS按钮。如果不要则写“none”'));
    $form->addInput($Dear_rssUrl);

    $Dear_highlight = new Typecho_Widget_Helper_Form_Element_Radio('Dear_highlight', array('1' => _t('启用'), '0' => _t('关闭')), '0', _t('代码高亮'), _t('启用后代码块将拥有高亮。会自动引入Highlight.js，略微增加网页加载时间'));
    $form->addInput($Dear_highlight);

    $Dear_customCss = new Typecho_Widget_Helper_Form_Element_Textarea('Dear_customCss', NULL, '', _t('自定义 CSS'), _t('在这里填入自定义 CSS 代码，直接写入 CSS 即可，程序会自动加上&lt;style&gt;标签'));
    $form->addInput($Dear_customCss);

    $Dear_customJs = new Typecho_Widget_Helper_Form_Element_Textarea('Dear_customJs', NULL, '', _t('自定义 JS'), _t('在这里填入自定义 JS 代码（如访问统计等），直接写入 JS 即可，程序会自动加上&lt;script&gt;标签'));
    $form->addInput($Dear_customJs);
}
