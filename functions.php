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
    $Dear_adminUi = new Typecho_Widget_Helper_Layout('div');
    $Dear_adminUi->html('
    <style>
    .dear-settings-toc {
        position: sticky;
        top: 100px;
        width: 180px;
        padding: 20px 0;
        z-index: 99;
    }
    .dear-settings-toc h3 {
        margin-top: 0;
        font-size: 13px;
        color: #999;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 15px;
        padding-bottom: 10px;
    }
    .dear-settings-toc ul { list-style: none; padding: 0; margin: 0; }
    .dear-settings-toc li { margin: 12px 0; }
    .dear-settings-toc a {
        color: #666;
        text-decoration: none;
        font-size: 14px;
        display: flex;
        align-items: center;
        transition: all 0.3s;
        position: relative;
        padding-left: 18px;
    }
    .dear-settings-toc a::before {
        content: "";
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #ddd;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    }
    .dear-settings-toc a:hover, .dear-settings-toc a.active {
        color: #467b96;
        font-weight: bold;
        transform: translateX(5px);
    }
    .dear-settings-toc a:hover::before, .dear-settings-toc a.active::before {
        background: #467b96;
        height: 16px;
        border-radius: 3px;
    }
    @media screen and (max-width: 1200px) {
        .dear-settings-toc-wrapper { display: none !important; }
    }
    </style>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const titles = document.querySelectorAll(".dear-group-title");
        if (titles.length === 0) return;
        
        const toc = document.createElement("div");
        toc.className = "dear-settings-toc";
        const header = document.createElement("h3");
        toc.appendChild(header);
        
        const ul = document.createElement("ul");
        toc.appendChild(ul);
        
        const links = [];
        
        titles.forEach((title, index) => {
            const id = "dear-group-" + index;
            const text = title.textContent.trim();
            
            const label = title.closest("li") || title;
            label.id = id;
            
            const li = document.createElement("li");
            const btn = document.createElement("a");
            btn.href = "#" + id;
            btn.textContent = text;
            btn.addEventListener("click", function(e) {
                e.preventDefault();
                const y = label.getBoundingClientRect().top + window.scrollY - 80;
                window.scrollTo({top: y, behavior: "smooth"});
            });
            
            links.push({btn: btn, label: label});
            li.appendChild(btn);
            ul.appendChild(li);
        });
        
        const form = document.querySelector("form");
        if (form) {
            form.style.position = "relative";
            const wrapper = document.createElement("div");
            wrapper.className = "dear-settings-toc-wrapper";
            wrapper.style.position = "absolute";
            wrapper.style.left = "-200px";
            wrapper.style.top = "0";
            wrapper.style.bottom = "0";
            wrapper.style.width = "180px";
            wrapper.appendChild(toc);
            form.appendChild(wrapper);
        }
        
        window.addEventListener("scroll", () => {
            let current = -1;
            const scrollY = window.scrollY + 100;
            
            for (let i = 0; i < links.length; i++) {
                if (links[i].label.offsetTop <= scrollY) {
                    current = i;
                } else {
                    break;
                }
            }
            
            links.forEach(l => l.btn.classList.remove("active"));
            if (current >= 0 && current < links.length) {
                links[current].btn.classList.add("active");
            }
        });
        
        window.dispatchEvent(new Event("scroll"));
    });
    </script>
    ');
    $form->addItem($Dear_adminUi);
    $Dear_showCategory = new Typecho_Widget_Helper_Form_Element_Radio('Dear_showCategory', array('1' => _t('显示'), '0' => _t('隐藏')), '0', _t('<div class="dear-group-title" style="font-size: 20px; font-weight: bold; color: #333; margin-top: 15px; margin-bottom: 15px; padding-bottom: 5px; border-bottom: 1px solid #eee;">通用与阅读</div>是否在文章列表中显示文章分类'), _t('文章列表中是否在最前面显示分类名称'));
    $form->addInput($Dear_showCategory);

    $Dear_themeModeBtn = new Typecho_Widget_Helper_Form_Element_Radio('Dear_themeModeBtn', array('1' => _t('显示'), '0' => _t('隐藏')), '1', _t('右上角显示深浅色模式切换开关'), _t('支持跟随系统、浅色、深色之间循环切换'));
    $form->addInput($Dear_themeModeBtn);

    $Dear_bgColorLight = new Typecho_Widget_Helper_Form_Element_Text('Dear_bgColorLight', NULL, '#FAF8F1', _t('浅色模式背景色'), _t('支持HEX色值，默认 #FAF8F1。如果输入无效格式，将自动回退到默认颜色。'));
    $form->addInput($Dear_bgColorLight);

    $Dear_bgColorDark = new Typecho_Widget_Helper_Form_Element_Text('Dear_bgColorDark', NULL, '#313131', _t('深色模式背景色'), _t('支持HEX色值，默认 #313131。如果输入无效格式，将自动回退到默认颜色。'));
    $form->addInput($Dear_bgColorDark);

    $Dear_numArticle = new Typecho_Widget_Helper_Form_Element_Text('Dear_numArticle', NULL, '8', _t('文章列表每页包含条目数'), _t('填写正整数，默认为0'));
    $form->addInput($Dear_numArticle);

    $Dear_subPage = new Typecho_Widget_Helper_Form_Element_Text('Dear_subPage', NULL, 'none', _t('主页显示的嵌套独立页面的URL缩略名'), _t('由于Typecho新版本修改，建议不要留空。填写创建过的独立页面的自定义URL缩略名，即slug，独立页面编辑页中标题下方的黄色框中的内容，填“none”则为不显示'));
    $form->addInput($Dear_subPage);

    $Dear_articlesTitle = new Typecho_Widget_Helper_Form_Element_Text('Dear_articlesTitle', NULL, 'Articles', _t('最近文章列表的标题名'), _t(''));
    $form->addInput($Dear_articlesTitle);

    $Dear_rssUrl = new Typecho_Widget_Helper_Form_Element_Text('Dear_rssUrl', NULL, 'none', _t('底部RSS订阅链接'), _t('填写后会在博客底部添加一个RSS按钮。如果不要则写“none”'));
    $form->addInput($Dear_rssUrl);

    $Dear_showNavi = new Typecho_Widget_Helper_Form_Element_Radio('Dear_showNavi', array('1' => _t('显示'), '0' => _t('隐藏')), '1', _t('<div class="dear-group-title" style="font-size: 20px; font-weight: bold; color: #333; margin-top: 35px; margin-bottom: 15px; padding-bottom: 5px; border-bottom: 1px solid #eee;">面包屑导航</div>是否显示面包屑导航'), _t(''));
    $form->addInput($Dear_showNavi);

    $Dear_showHomeInNavi = new Typecho_Widget_Helper_Form_Element_Radio('Dear_showHomeInNavi', array('1' => _t('显示'), '0' => _t('隐藏')), '1', _t('面包屑是否包含首页按钮'), _t(''));
    $form->addInput($Dear_showHomeInNavi);

    $Dear_showAllSlug = new Typecho_Widget_Helper_Form_Element_Radio('Dear_showAllSlug', array('1' => _t('是'), '0' => _t('否')), '1', _t('面包屑是否显示全部独立页面'), _t(''));
    $form->addInput($Dear_showAllSlug);

    $Dear_slugInNavi = new Typecho_Widget_Helper_Form_Element_Text('Dear_slugInNavi', NULL, '', _t('手动补充呈现页面入口'), _t('如果你在上面关闭了显示全部独立页面，可手动在此填写要展示的缩略名集合。英文逗号分隔。如: about,links'));
    $form->addInput($Dear_slugInNavi);

    $Dear_customNavi = new Typecho_Widget_Helper_Form_Element_Textarea('Dear_customNavi', NULL, '', _t('自定义导航链接'), _t('在这里填入自定义的一个或多个&lt;a&gt;标签，渲染时将直接按照原样输出到导航栏的末尾。'));
    $form->addInput($Dear_customNavi);

    $Dear_lightbox = new Typecho_Widget_Helper_Form_Element_Radio('Dear_lightbox', array('1' => _t('启用'), '0' => _t('关闭')), '1', _t('<div class="dear-group-title" style="font-size: 20px; font-weight: bold; color: #333; margin-top: 35px; margin-bottom: 15px; padding-bottom: 5px; border-bottom: 1px solid #eee;">阅读增强</div>启用图片灯箱特效'), _t('开启后文章内的图片点击可放大。会自动引入Viewerjs，略微增加网页加载时间'));
    $form->addInput($Dear_lightbox);

    $Dear_highlight = new Typecho_Widget_Helper_Form_Element_Radio('Dear_highlight', array('1' => _t('启用'), '0' => _t('关闭')), '1', _t('代码高亮'), _t('启用后代码块将拥有高亮。会自动引入Highlight.js，略微增加网页加载时间'));
    $form->addInput($Dear_highlight);

    $Dear_toc = new Typecho_Widget_Helper_Form_Element_Radio('Dear_toc', array('0' => _t('关闭'), 'left' => _t('左侧'), 'right' => _t('右侧')), 'right', _t('<div class="dear-group-title" style="font-size: 20px; font-weight: bold; color: #333; margin-top: 35px; margin-bottom: 15px; padding-bottom: 5px; border-bottom: 1px solid #eee;">目录</div>文章目录显示'), _t('选择左侧或右侧，如果当前文章有标题层级结构，则自动生成目录。'));
    $form->addInput($Dear_toc);

    $Dear_toc_depth = new Typecho_Widget_Helper_Form_Element_Text('Dear_toc_depth', NULL, '1', _t('目录默认展开层数'), _t('默认为1，0表示全部展开。超出该层级的子章节会被初始折叠，并显示可点击的展开箭头。'));
    $form->addInput($Dear_toc_depth);

    $Dear_toc_text_visible = new Typecho_Widget_Helper_Form_Element_Radio('Dear_toc_text_visible', array('1' => _t('是'), '0' => _t('否')), '0', _t('目录是否始终显示全部标题'), _t('默认为否，仅显示当前焦点所在章节的标题名，其他标题隐藏。反之则所有标题均常驻显示。'));
    $form->addInput($Dear_toc_text_visible);

    $Dear_toc_mobile_btn = new Typecho_Widget_Helper_Form_Element_Radio('Dear_toc_mobile_btn', array('1' => _t('显示'), '0' => _t('隐藏')), '1', _t('移动端是否显示目录按钮'), _t(''));
    $form->addInput($Dear_toc_mobile_btn);

    $Dear_comments = new Typecho_Widget_Helper_Form_Element_Radio('Dear_comments', array('1' => _t('启用'), '0' => _t('关闭')), '1', _t('<div class="dear-group-title" style="font-size: 20px; font-weight: bold; color: #333; margin-top: 35px; margin-bottom: 15px; padding-bottom: 5px; border-bottom: 1px solid #eee;">评论</div>是否启用评论区'), _t('如果为关闭，哪怕在Typecho发布文章时选择开启了评论也会完全屏蔽评论系统的加载'));
    $form->addInput($Dear_comments);

    $Dear_commentUrl = new Typecho_Widget_Helper_Form_Element_Radio('Dear_commentUrl', array('1' => _t('启用'), '0' => _t('关闭')), '1', _t('是否允许评论时填写自己的网站地址'), _t('关闭可减少垃圾链接。关闭前请同步将Typecho的设置->评论->“必须填写网址”关掉'));
    $form->addInput($Dear_commentUrl);

    $Dear_linkImg = new Typecho_Widget_Helper_Form_Element_Text('Dear_linkImg', NULL, '/asset/imgs/defaultLinkImg.svg', _t('<div class="dear-group-title" style="font-size: 20px; font-weight: bold; color: #333; margin-top: 35px; margin-bottom: 15px; padding-bottom: 5px; border-bottom: 1px solid #eee;">友情链接</div>友情链接默认图片'), _t('友链图片加载失败时调用的图片路径'));
    $form->addInput($Dear_linkImg);

    $Dear_linkImgLocal = new Typecho_Widget_Helper_Form_Element_Radio('Dear_linkImgLocal', array('1' => _t('是'), '0' => _t('否')), '1', _t('友情链接默认图片是否在主题本地'), _t('如果上方的路径是完整外部https://...链接，这里请选【否】'));
    $form->addInput($Dear_linkImgLocal);

    $Dear_newWindow = new Typecho_Widget_Helper_Form_Element_Radio('Dear_newWindow', array('1' => _t('是'), '0' => _t('否')), '1', _t('友链能否在新页面打开'), _t(''));
    $form->addInput($Dear_newWindow);

    $Dear_unGroupedLinks = new Typecho_Widget_Helper_Form_Element_Text('Dear_unGroupedLinks', NULL, '未分类', _t('默认友情链接分组名'), _t('如果没有正确指定type的友链将被统一放置到此名称下'));
    $form->addInput($Dear_unGroupedLinks);

    $Dear_customCss = new Typecho_Widget_Helper_Form_Element_Textarea('Dear_customCss', NULL, '', _t('<div class="dear-group-title" style="font-size: 20px; font-weight: bold; color: #333; margin-top: 35px; margin-bottom: 15px; padding-bottom: 5px; border-bottom: 1px solid #eee;">高级自定义</div>自定义CSS'), _t('在这里填入自定义CSS代码，直接写入CSS即可，主题会自动加上&lt;style&gt;标签'));
    $form->addInput($Dear_customCss);

    $Dear_customJs = new Typecho_Widget_Helper_Form_Element_Textarea('Dear_customJs', NULL, '', _t('自定义JS'), _t('在这里填入自定义JS代码（如访问统计等），直接写入JS即可，主题会自动加上&lt;script&gt;标签'));
    $form->addInput($Dear_customJs);
}
