<?php
/**
 * 全部分类与标签
 *
 * @package custom
 */

 if (!defined('__TYPECHO_ROOT_DIR__'))
 exit;
require("config.php"); ?>
<?php $this->need('header.php'); ?>
<div><?php $this->content(); ?></div>
<h1>» 分类</h1>
<ul class="tag_list">
    <?php $this->widget('Widget_Metas_Category_List')
               ->parse('<li class="tag_item"><a href="{permalink}">{name}</a> ({count})</li>'); ?>
</ul>
<h1>» 标签</h1>
<ul class="tag_list">
<?php $this->widget('Widget_Metas_Tag_Cloud')->parse('<li class="tag_item"><a href="{permalink}">{name}</a> ({count})</li>'); ?>
</ul>           
<?php $this->need('footer.php'); ?>