<?php if (!defined('__TYPECHO_ROOT_DIR__'))
    exit; ?>
</main>
<footer>
    <p><a href="#">返回顶部 ↑</a></p>
    <span class="intro">&copy; <?php echo date('Y'); ?> <a href="<?php $this->options->siteUrl(); ?>"
            title="<?php $this->options->title(); ?>"><?php $this->options->title(); ?></a></br><a
            href="https://typecho.org/" title="Typecho">Typecho </a>Theme <a
            href="https://github.com/imjeff/typecho-dear" title="Dear">Dear</a> by <a href="https://yayu.net"
            title="雅余">YAYU</a> and customized by <a href="https://github.com/UtopiaXC/Theme-Dear-For-Typecho"
            title="UtopiaXC">UtopiaXC</a></span>
</footer>
<?php $this->footer(); ?>
</body>

</html>