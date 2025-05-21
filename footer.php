<?php if (!defined('__TYPECHO_ROOT_DIR__'))
    exit;
require('config.php'); ?>
</main>
<footer>
    <p><a href="#">返回顶部 ↑</a></p>
    <span class="intro">&copy; <?php echo date('Y'); ?> <a href="<?php $this->options->siteUrl(); ?>"
                                                           title="<?php $this->options->title(); ?>"><?php $this->options->title(); ?></a><?php if ($rssUrl != "none"): ?>
            | <a href="<?php echo $rssUrl; ?>" title="RSS">RSS</a><?php endif; ?></br><a href="https://typecho.org/"
                                                                                         target="_blank"
                                                                                         title="Typecho">Typecho </a>Theme <a
                href="https://github.com/UtopiaXC/Theme-Dear-For-Typecho" target="_blank" title="Dear">Dear</a> By <a
                href="https://yayu.net/" target="_blank" title="Jeff Chen">Jeff Chen</a> and <a
                href="https://www.utopiaxc.cn/" target="_blank" title="UtopiaXC">UtopiaXC</a>.</span>
</footer>
<?php $this->footer(); ?>
</body>
<script src="<?php $this->options->themeUrl('/asset/js/custom.js'); ?>"></script>
<?php if ($enableHighlightjs): ?>
    <script>
        const themeLink = document.getElementById("hljs-theme");
        const LIGHT_THEME = "<?php $this->options->themeUrl('/asset/css/highlightjs/default.min.css'); ?>";
        const DARK_THEME = "<?php $this->options->themeUrl('/asset/css/highlightjs/dark.min.css'); ?>";

        function isDarkMode() {
            return window.matchMedia("(prefers-color-scheme: dark)").matches;
        }

        function updateHighlightTheme() {
            themeLink.href = isDarkMode() ? DARK_THEME : LIGHT_THEME;
        }

        window.matchMedia("(prefers-color-scheme: dark)").addEventListener("change", () => {
            updateHighlightTheme();
        });
        document.addEventListener("DOMContentLoaded", () => {
            updateHighlightTheme();
            const supportedLanguages = hljs.listLanguages();
            document.querySelectorAll("pre code").forEach((block) => {
                const classes = block.className.split(" ");
                const langClass = classes.find(cls => cls.startsWith("lang-"));
                let lang = "";
                if (langClass) {
                    lang = langClass.replace("lang-", "");
                    if (!supportedLanguages.includes(lang)) {
                        block.classList.remove(...block.classList);
                        block.classList.add("lang-plaintext");
                    }
                } else {
                    lang = "PLAINTEXT"
                    block.classList.add("lang-plaintext");
                }
                const langLabel = document.createElement("span");
                langLabel.className = "code-lang";
                langLabel.textContent = lang.toUpperCase();
                const copyBtn = document.createElement("button");
                copyBtn.className = "copy-btn";
                copyBtn.textContent = "Copy";
                copyBtn.addEventListener("click", () => {
                    const code = block.textContent || block.innerText;
                    navigator.clipboard.writeText(code).then(() => {
                        copyBtn.textContent = "Copied!";
                        setTimeout(() => {
                            copyBtn.textContent = "Copy";
                        }, 2000);
                    });
                });
                const langContainer = document.createElement("div");
                langContainer.className = "code-lang-container";
                langContainer.appendChild(copyBtn);
                langContainer.appendChild(langLabel);
                block.parentElement.appendChild(langContainer);
                hljs.highlightElement(block);
            });
        });
    </script>
<?php endif ?>
<?php if ($this->is('post') && $this->fields->isLatex == 1): ?>
    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function () {
            renderMathInElement(document.body, {
                delimiters: [{
                    left: "$$",
                    right: "$$",
                    display: true
                }, {
                    left: "$",
                    right: "$",
                    display: false
                }],
                ignoredTags: ["script", "noscript", "style", "textarea", "pre", "code"],
                ignoredClasses: ["nokatex"]
            });
        });
    </script>
<?php endif; ?>
</html>