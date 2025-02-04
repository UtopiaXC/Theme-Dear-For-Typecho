<?php
/**
 * 友情链接
 *
 * @package custom
 */
if (!defined('__TYPECHO_ROOT_DIR__'))
    exit; 
require("config.php");?>

<div class="friend">


</div>
<?php $this->need('header.php'); ?>

<?php
try {
    $db = Typecho_Db::get();
    $prefix = $db->getPrefix();
    @$links = $db->fetchAll($db->select()->from($prefix . 'links'));
    $types = [];
    foreach ($links as $link) {
        $type = $link['sort'];
        if ($type == '') {
            $type = $unGroupedLinksDefaultName;
        }
        array_push($types, $type);
    }
    $types = array_unique($types);
    sort($types);
    foreach ($types as $type) {
        $target="";
        if($openLinksInNewWindow){
            $target = "_blank";
        }else{
            $target = "_self";
        }
        echo '<h3># '.$type . '</h3><div class="links">';
        foreach ($links as $link) {
            if ($link["sort"] == $type || ($type == $unGroupedLinksDefaultName && $link["sort"] == "")) {
                echo '
                <a target="'.$target.'" href="' . $link["url"] . '" title="' . $link["name"] . '" class="link-card-url">
          <div class="link-card">
            <div class="link-card-left">
              <img class="link-card-avatar" src="' . $link["image"] . '" notFoundSrc="';$this->options->themeUrl($linkDefaultImg);
              echo'">
            </div>
            <div class="link-card-right">
              <div class="link-card-title">' . $link["name"] . '</div>
              <div class="link-card-description">' . $link["description"] . '</div>
            </div>
          </div>
        </a>
                ';

                echo "</br>";
            }
        }
        echo "</div>";
    }

} catch (Typecho_Db_Exception $e) {
    echo '未检测到友情链接数据库，请检查是否已经启用<a target="_blank" href="https://github.com/Mejituu/Links?tab=readme-ov-file">Links</a>插件。</br>如果已经启用，则可能是PHP版本或插件版本问题。</br>如果均已排除请前往<a target="_blank" href="https://github.com/UtopiaXC/Theme-Dear-For-Typecho">主题GitHub</a>仓库提交issue。';
}
?>
</div? <?php $this->need('comments.php'); ?> <script>
    document.addEventListener("error", function (e) {
        var elem = e.target;
        if (elem.tagName.toLowerCase() === 'img') {
            var notFoundImgSrt = elem.getAttribute("notFoundSrc")
            if (notFoundImgSrt) {
                elem.src = notFoundImgSrt;
            }
        }
    }, true);
</script>
<?php $this->need('footer.php'); ?>