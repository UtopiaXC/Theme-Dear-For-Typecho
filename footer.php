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
<?php
$aiEnabledFooter = !is_null($this->options->Dear_aiEnabled) && $this->options->Dear_aiEnabled == '1';
$aiDefaultHidden = !is_null($this->options->Dear_aiDefaultHidden) && $this->options->Dear_aiDefaultHidden == '1';
if (($this->is('post')) && $aiEnabledFooter):
    ?>
    <script src="<?php $this->options->themeUrl('./asset/js/marked.min.js'); ?>"></script>
    <script type="text/javascript">
        (function () {
            const box = document.getElementById("ai-summary-box");
            if (!box) return;
            const defaultHidden = <?php echo $aiDefaultHidden ? 'true' : 'false'; ?>;
            if (defaultHidden) {
                box.style.display = "none";
            }

            const cid = box.dataset.cid;
            const textEl = document.getElementById("ai-summary-text");
            const contentEl = document.getElementById("ai-summary-content");
            const expandBtn = document.getElementById("ai-summary-expand-btn");
            const toggleBtn = document.getElementById("ai-summary-toggle-btn");
            const closeBtn = document.getElementById("ai-close-btn");
            const copyBtn = document.getElementById("ai-copy-btn");
            const regenBtn = document.getElementById("ai-regenerate-btn");
            const modelBtn = document.getElementById("ai-model-select-btn");
            const modelCurrent = document.getElementById("ai-model-current");
            const modelDropdown = document.getElementById("ai-model-dropdown");
            const timeLabel = document.getElementById("ai-summary-time");
            if (toggleBtn) {
                toggleBtn.querySelector("span").textContent = (box.style.display === "none") ? "AI摘要" : "隐藏AI摘要";
            }

            let currentSummary = "";
            let isExpanded = false;
            let modelsList = [];
            let selectedModelIndex = 0;

            function api(action, extra) {
                let u = "/?dear_ai_action=" + action + "&cid=" + cid;
                if (extra) u += "&" + extra;
                return u;
            }

            function setStatus(msg, isError) {
                textEl.textContent = msg;
                textEl.style.color = isError ? "var(--gray-color)" : "";
                contentEl.classList.remove("ai-summary-collapsed");
                if (expandBtn) expandBtn.style.display = "none";
                isExpanded = true;
            }

            function setSummary(text, model, updatedAt) {
                currentSummary = text;
                if (typeof marked !== 'undefined') {
                    textEl.innerHTML = marked.parse(text);
                } else {
                    textEl.innerHTML = text;
                }
                textEl.style.color = "";
                if (model) modelCurrent.textContent = model;
                timeLabel.textContent = updatedAt ? "生成时间：" + formatTime(updatedAt) : "生成时间未知";

                requestAnimationFrame(function () {
                    const lineH = 22;
                    if (contentEl.scrollHeight > lineH * 4) {
                        contentEl.classList.add("ai-summary-collapsed");
                        if (expandBtn) {
                            expandBtn.style.display = "block";
                            expandBtn.textContent = "展开全文 ▼";
                        }
                        isExpanded = false;
                    } else {
                        contentEl.classList.remove("ai-summary-collapsed");
                        if (expandBtn) expandBtn.style.display = "none";
                        isExpanded = true;
                    }
                });
            }

            function formatTime(ts) {
                if (!ts) return "";
                const d = new Date(ts * 1000);
                const pad = n => String(n).padStart(2, "0");
                return d.getFullYear() + "-" + pad(d.getMonth() + 1) + "-" + pad(d.getDate()) + " " + pad(d.getHours()) + ":" + pad(d.getMinutes());
            }

            function fetchForModel(modelName, autoGenerate) {
                setStatus("正在加载摘要...", false);
                fetch(api("get", "model_name=" + encodeURIComponent(modelName)))
                    .then(r => r.json())
                    .then(data => {
                        if (data.ok && data.exists) {
                            if (data.status === "completed" && data.summary) {
                                setSummary(data.summary, data.model, data.updated_at);
                            } else if (data.status === "generating") {
                                setStatus("摘要生成中...", false);
                                setTimeout(() => pollStatus(modelName), 3000);
                            } else if (autoGenerate) {
                                generateSummary();
                            }
                        } else if (autoGenerate) {
                            generateSummary();
                        } else {
                            setStatus("暂无缓存，请点击重新生成", false);
                        }
                    })
                    .catch(() => setStatus("网络请求失败", true));
            }

            function renderDropdown() {
                if (!modelDropdown) {
                    return;
                }
                modelDropdown.innerHTML = "";
                modelsList.forEach(function (modelItem, index) {
                    const dropdownItem = document.createElement("div");
                    let classNameString = "ai-model-dropdown-item";
                    if (index === selectedModelIndex) {
                        classNameString += " active";
                    }
                    dropdownItem.className = classNameString;
                    dropdownItem.textContent = modelItem.display;

                    dropdownItem.addEventListener("click", function (event) {
                        event.stopPropagation();
                        modelDropdown.style.display = "none";
                        if (index === selectedModelIndex) {
                            return;
                        }
                        selectedModelIndex = index;
                        modelCurrent.textContent = modelItem.display;
                        fetchForModel(modelItem.name, false);
                    });

                    modelDropdown.appendChild(dropdownItem);
                });
            }

            function generateSummary() {
                setStatus("AI正在组织语言...", false);
                fetch(api("generate", "model_index=" + selectedModelIndex))
                    .then(r => r.json())
                    .then(data => {
                        if (data.ok) {
                            if (data.status === "completed" && data.summary) {
                                setSummary(data.summary, data.model, data.updated_at);
                            } else {
                                setTimeout(() => pollStatus(data.model_name), 3000);
                            }
                        } else {
                            setStatus(data.error || "请求失败", true);
                        }
                    })
                    .catch(() => setStatus("请求超时或连接错误", true));
            }

            function pollStatus(modelName) {
                fetch(api("get", "model_name=" + encodeURIComponent(modelName)))
                    .then(r => r.json())
                    .then(data => {
                        if (data.ok && data.exists && data.status === "completed") {
                            setSummary(data.summary, data.model, data.updated_at);
                        } else if (data.status === "generating") {
                            setTimeout(() => pollStatus(modelName), 3000);
                        }
                    });
            }

            fetch(api("models").replace("&cid=" + cid, ""))
                .then(function (response) {
                    return response.json();
                })
                .then(function (responseData) {
                    if (responseData.ok && responseData.models && responseData.models.length > 0) {
                        modelsList = responseData.models;
                        selectedModelIndex = 0;
                        modelCurrent.textContent = modelsList[0].display;
                        renderDropdown();
                        fetchForModel(modelsList[0].name, true);
                    }
                });

            if (toggleBtn) toggleBtn.addEventListener("click", function () {
                if (box.style.display === "none") {
                    box.style.display = "block";
                    toggleBtn.querySelector("span").textContent = "隐藏AI摘要";
                } else {
                    box.style.display = "none";
                    toggleBtn.querySelector("span").textContent = "AI摘要";
                }
            });

            if (closeBtn) closeBtn.addEventListener("click", () => {
                box.style.display = "none";
                if (toggleBtn) toggleBtn.querySelector("span").textContent = "AI摘要";
            });

            if (expandBtn) expandBtn.addEventListener("click", function () {
                if (isExpanded) {
                    contentEl.classList.add("ai-summary-collapsed");
                    expandBtn.textContent = "展开全文 ▼";
                } else {
                    contentEl.classList.remove("ai-summary-collapsed");
                    expandBtn.textContent = "收起 ▲";
                }
                isExpanded = !isExpanded;
            });

            const generationConfirmationModal = document.getElementById("ai-generation-confirmation-modal");
            const confirmationCancelButton = document.getElementById("ai-generation-cancel-button");
            const confirmationConfirmButton = document.getElementById("ai-generation-confirm-button");

            if (regenBtn) {
                regenBtn.addEventListener("click", function () {
                    if (generationConfirmationModal) {
                        generationConfirmationModal.style.display = "flex";
                    }
                });
            }

            if (confirmationCancelButton) {
                confirmationCancelButton.addEventListener("click", function () {
                    generationConfirmationModal.style.display = "none";
                });
            }

            if (confirmationConfirmButton) {
                confirmationConfirmButton.addEventListener("click", function () {
                    generationConfirmationModal.style.display = "none";
                    generateSummary();
                });
            }

            window.addEventListener("click", function (event) {
                if (event.target === generationConfirmationModal) {
                    generationConfirmationModal.style.display = "none";
                }
            });

            if (copyBtn) {
                copyBtn.addEventListener("click", function () {
                    if (currentSummary) {
                        navigator.clipboard.writeText(currentSummary).then(() => {
                            const oldTitle = copyBtn.title;
                            copyBtn.title = "已复制!";
                            setTimeout(() => { copyBtn.title = oldTitle; }, 2000);
                        });
                    }
                });
            }

            if (modelBtn) {
                modelBtn.addEventListener("click", function (e) {
                    e.stopPropagation();
                    if (modelDropdown) {
                        modelDropdown.style.display = modelDropdown.style.display === "none" ? "block" : "none";
                    }
                });
            }

            document.addEventListener("click", function () {
                if (modelDropdown) {
                    modelDropdown.style.display = "none";
                }
            });

        })();
    </script>
        })();
    </script>
<?php endif; ?>

</html>