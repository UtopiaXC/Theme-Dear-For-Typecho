<?php if (!defined('__TYPECHO_ROOT_DIR__'))
    exit;
?>
<?php
$rssUrl = is_null($this->options->Dear_rssUrl) ? "none" : $this->options->Dear_rssUrl;
$enableHighlightjs = is_null($this->options->Dear_highlight) ? false : (bool) $this->options->Dear_highlight;
$tocPosition = is_null($this->options->Dear_toc) ? '0' : $this->options->Dear_toc;
$tocDepth = is_null($this->options->Dear_toc_depth) ? '0' : $this->options->Dear_toc_depth;
$linkNewWindow = is_null($this->options->Dear_linkNewWindow) ? '1' : $this->options->Dear_linkNewWindow;
$headingToggle = is_null($this->options->Dear_headingToggle) ? '0' : $this->options->Dear_headingToggle;
?>
</main>
<footer>
    <p><a href="#">返回顶部 ↑</a></p>
    <span class="intro">&copy; <?php echo date('Y'); ?> <a href="<?php $this->options->siteUrl(); ?>"
            title="<?php $this->options->title(); ?>"><?php $this->options->title(); ?></a><?php if ($rssUrl != "none"): ?>
            | <a href="<?php echo $rssUrl; ?>" title="RSS">RSS</a><?php endif; ?></br><a href="https://typecho.org/"
            target="_blank" title="Typecho">Typecho </a>Theme <a
            href="https://github.com/UtopiaXC/Theme-Dear-For-Typecho" target="_blank" title="Dear">Dear</a> By <a
            href="https://yayu.net/" target="_blank" title="Jeff Chen">Jeff Chen</a> and <a
            href="https://www.utopiaxc.cn/" target="_blank" title="UtopiaXC">UtopiaXC</a>.</span>
</footer>
<?php $this->footer(); ?>
<?php if (!empty($this->options->Dear_customJs)): ?>
    <script>
        <?php echo $this->options->Dear_customJs; ?>
    </script>
<?php endif; ?>
</body>
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
<?php if ($this->is('post') && $tocPosition != '0'): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const gallery = document.getElementById("gallery");
            if (!gallery) return;
            const headings = gallery.querySelectorAll("h1, h2, h3, h4, h5, h6");
            if (headings.length === 0) return;

            const tocWrapper = document.createElement("div");
            tocWrapper.className = "post-toc-wrapper position-<?php echo $tocPosition; ?>";

            // const tocTitle = document.createElement("div");
            // tocTitle.className = "post-toc-title";
            // tocTitle.textContent = "Table of Content";
            // tocWrapper.appendChild(tocTitle);

            const tocContainer = document.createElement("div");
            tocContainer.className = "post-toc";

            let rootUl = document.createElement("ul");

            const topLi = document.createElement("li");
            topLi.className = "toc-level-0";
            const topContainer = document.createElement("div");
            topContainer.className = "toc-link-container";
            const topToggle = document.createElement("span");
            topToggle.className = "toc-toggle";
            const topA = document.createElement("a");
            topA.className = "toc-link";
            topA.href = "#top";
            const topDash = document.createElement("span");
            topDash.className = "toc-dash";
            const topTextSpan = document.createElement("span");
            topTextSpan.className = "toc-text";
            topTextSpan.textContent = "Top";
            topA.appendChild(topDash);
            topA.appendChild(topTextSpan);
            topContainer.appendChild(topToggle);
            topContainer.appendChild(topA);
            topLi.appendChild(topContainer);
            rootUl.appendChild(topLi);

            topA.addEventListener("click", function (e) {
                e.preventDefault();
                requestAnimationFrame(() => {
                    window.scrollTo({ top: 0, behavior: "smooth" });
                });
            });

            let lastLiForLevel = {};

            let depthSetting = parseInt("<?php echo $tocDepth; ?>") || 0;
            if (window.innerWidth <= 1250) {
                depthSetting = 0;
            }

            let minLevel = 6;
            headings.forEach(h => {
                let lvl = parseInt(h.tagName.substring(1));
                if (lvl < minLevel) minLevel = lvl;
            });
            const levelOffset = minLevel - 1;

            headings.forEach((heading, index) => {
                const id = "heading-" + index;
                if (!heading.id) heading.id = id;
                const rawLevel = parseInt(heading.tagName.substring(1));
                const level = rawLevel - levelOffset;

                const li = document.createElement("li");
                li.className = "toc-level-" + level;

                const container = document.createElement("div");
                container.className = "toc-link-container";

                const toggle = document.createElement("span");
                toggle.className = "toc-toggle";
                toggle.innerHTML = "▼";

                const a = document.createElement("a");
                a.className = "toc-link";
                a.href = "#" + heading.id;

                a.addEventListener("click", function (e) {
                    e.preventDefault();
                    const targetEl = document.getElementById(heading.id);
                    if (targetEl) {
                        requestAnimationFrame(() => {
                            const rect = targetEl.getBoundingClientRect();
                            window.scrollTo({
                                top: window.scrollY + rect.top - 60,
                                behavior: "smooth"
                            });
                        });
                    }
                });

                const dash = document.createElement("span");
                dash.className = "toc-dash";

                const textSpan = document.createElement("span");
                textSpan.className = "toc-text";
                textSpan.textContent = heading.textContent;

                a.appendChild(dash);
                a.appendChild(textSpan);
                container.appendChild(toggle);
                container.appendChild(a);
                li.appendChild(container);

                toggle.addEventListener("click", (e) => {
                    e.stopPropagation();
                    li.classList.toggle("collapsed");
                });

                let parentLevel = level - 1;
                while (parentLevel >= 1 && !lastLiForLevel[parentLevel]) {
                    parentLevel--;
                }

                if (parentLevel >= 1) {
                    let parentLi = lastLiForLevel[parentLevel];
                    parentLi.classList.add("has-children");

                    if (depthSetting > 0 && parentLevel >= depthSetting) {
                        parentLi.classList.add("collapsed");
                    }

                    let ul = parentLi.querySelector(":scope > ul");
                    if (!ul) {
                        ul = document.createElement("ul");
                        parentLi.appendChild(ul);
                    }
                    ul.appendChild(li);
                } else {
                    rootUl.appendChild(li);
                }

                lastLiForLevel[level] = li;
                for (let i = level + 1; i <= 6; i++) {
                    lastLiForLevel[i] = null;
                }
            });
            tocContainer.appendChild(rootUl);
            tocWrapper.appendChild(tocContainer);
            document.body.appendChild(tocWrapper);

            const overlay = document.createElement("div");
            overlay.className = "post-toc-overlay";
            document.body.appendChild(overlay);

            const toggleBtn = document.createElement("div");
            toggleBtn.className = "toc-toggle-btn";
            toggleBtn.innerHTML = "☰";
            document.body.appendChild(toggleBtn);

            toggleBtn.addEventListener("click", () => {
                tocWrapper.classList.toggle("drawer-open");
                overlay.classList.toggle("drawer-open");
            });
            overlay.addEventListener("click", () => {
                tocWrapper.classList.remove("drawer-open");
                overlay.classList.remove("drawer-open");
            });
            document.querySelectorAll(".post-toc a").forEach(link => {
                link.addEventListener("click", () => {
                    if (window.innerWidth <= 1250) {
                        tocWrapper.classList.remove("drawer-open");
                        overlay.classList.remove("drawer-open");
                    }
                });
            });

            let activeHeadingId = null;

            window.addEventListener("scroll", () => {
                let currentId = "top";
                for (let i = headings.length - 1; i >= 0; i--) {
                    const rect = headings[i].getBoundingClientRect();
                    if (rect.top <= window.innerHeight * 0.3) {
                        currentId = headings[i].id;
                        break;
                    }
                }
                if (currentId && currentId !== activeHeadingId) {
                    setActive(currentId);
                }
            });

            function setActive(id) {
                activeHeadingId = id;
                document.querySelectorAll(".post-toc li").forEach(li => {
                    li.classList.remove("active", "active-parent");
                    if (li.classList.contains("has-children") && depthSetting > 0) {
                        let lvlMatch = li.className.match(/toc-level-(\d+)/);
                        if (lvlMatch) {
                            let lvl = parseInt(lvlMatch[1]);
                            if (lvl >= depthSetting) {
                                li.classList.add("collapsed");
                            }
                        }
                    }
                });
                const activeLink = document.querySelector('.post-toc a[href="#' + id + '"]');
                if (activeLink) {
                    let currentLi = activeLink.closest("li");
                    currentLi.classList.add("active");

                    let parentUl = currentLi.parentElement;
                    while (parentUl && parentUl.parentElement && parentUl.parentElement.tagName === "LI") {
                        const parentLi = parentUl.parentElement;
                        parentLi.classList.add("active-parent");
                        parentLi.classList.remove("collapsed");
                        parentUl = parentLi.parentElement;
                    }
                }
            }

            setTimeout(() => {
                window.dispatchEvent(new Event('scroll'));
            }, 100);
        });
    </script>
<?php endif; ?>
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
<?php if (($this->is('post') || $this->is('page')) && $linkNewWindow == '1'): ?>
    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function () {
            const gallery = document.getElementById("gallery");
            if (gallery) {
                const links = gallery.querySelectorAll("a");
                links.forEach(link => {
                    if (link.getAttribute("href") && !link.getAttribute("href").startsWith("#")) {
                        link.setAttribute("target", "_blank");
                    }
                });
            }
        });
    </script>
<?php endif; ?>
<?php if (($this->is('post') || $this->is('page')) && $headingToggle == '1'): ?>
    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function () {
            const gallery = document.getElementById("gallery");
            if (!gallery) return;

            const HEADING_TAGS = ['H1', 'H2', 'H3', 'H4', 'H5', 'H6'];

            function getLevel(el) {
                return parseInt(el.tagName.substring(1));
            }
            const children = Array.from(gallery.childNodes).filter(n =>
                n.nodeType === Node.ELEMENT_NODE || (n.nodeType === Node.TEXT_NODE && n.textContent.trim() !== '')
            );

            for (let i = 0; i < children.length; i++) {
                const el = children[i];
                if (el.nodeType !== Node.ELEMENT_NODE) continue;
                if (!HEADING_TAGS.includes(el.tagName)) continue;

                const level = getLevel(el);
                const contentNodes = [];
                for (let j = i + 1; j < children.length; j++) {
                    const sibling = children[j];
                    if (sibling.nodeType === Node.ELEMENT_NODE && HEADING_TAGS.includes(sibling.tagName)) {
                        if (getLevel(sibling) <= level) break;
                    }
                    contentNodes.push(sibling);
                }

                if (contentNodes.length === 0) continue;
                const wrapper = document.createElement('div');
                wrapper.className = 'heading-collapse-wrapper';
                el.after(wrapper);
                contentNodes.forEach(n => wrapper.appendChild(n));
                const arrow = document.createElement('span');
                arrow.className = 'heading-collapse-arrow';
                arrow.setAttribute('aria-label', '折叠/展开');
                arrow.innerHTML = '&#9660;';
                el.insertBefore(arrow, el.firstChild);
                arrow.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const collapsed = wrapper.classList.toggle('heading-collapsed');
                    arrow.innerHTML = collapsed ? '&#9654;' : '&#9660;';
                });
            }
        });
    </script>
<?php endif; ?>

</html>