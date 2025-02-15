<?php if (!defined('__TYPECHO_ROOT_DIR__'))
    exit; 
require('config.php'); ?>
</main>
<footer>
    <p><a href="#">返回顶部 ↑</a></p>
    <span class="intro">&copy; <?php echo date('Y'); ?> <a href="<?php $this->options->siteUrl(); ?>"
            title="<?php $this->options->title(); ?>"><?php $this->options->title(); ?></a><?php if($rssUrl != "none"): ?> | <a href="<?php echo $rssUrl; ?>" title="RSS">RSS</a><?php endif; ?></br><a
            href="https://typecho.org/" target="_blank" title="Typecho">Typecho </a>Theme <a
            href="https://github.com/UtopiaXC/Theme-Dear-For-Typecho" target="_blank" title="Dear">Dear</a> | A work By <a href="https://yayu.net/" target="_blank"
            title="雅余">YAYU</a> and <a href="https://www.utopiaxc.cn/" target="_blank"
            title="UtopiaXC">UtopiaXC</a>.</span>
</footer>
<?php $this->footer(); ?>
</body>

</html>