/**
 * Dear Theme — Breadcrumb Navigation Admin Editor (Native Typecho Style)
 *
 * Visual manager for theme breadcrumb navigation:
 * - Query and select from existing system independent pages (slugs)
 * - Add and customize custom links
 * - Reorder, update, delete navigation items with instant sync
 * - Clean native Typecho admin styling
 * - Seamless backward compatibility with Dear_slugInNavi & Dear_customNavi
 */

(function () {
    "use strict";

    function esc(s) {
        if (!s && s !== 0) return "";
        var d = document.createElement("div");
        d.appendChild(document.createTextNode(String(s)));
        return d.innerHTML.replace(/"/g, "&quot;");
    }

    function getSystemPages() {
        if (window.DearSystemPages && Array.isArray(window.DearSystemPages)) {
            return window.DearSystemPages;
        }
        return [];
    }

    function parseJsonItems(textarea) {
        if (!textarea || !textarea.value || !textarea.value.trim()) return [];
        try {
            var res = JSON.parse(textarea.value);
            return Array.isArray(res) ? res : [];
        } catch (e) {
            return [];
        }
    }

    function initNaviAdmin() {
        var textarea = document.querySelector("textarea[name=Dear_navigationItems]");
        if (!textarea) return;

        var hiddenSlugInput = document.querySelector("input[name=Dear_slugInNavi]");
        var hiddenCustomInput = document.querySelector("input[name=Dear_customNavi], textarea[name=Dear_customNavi]");

        // Hide raw textarea
        textarea.style.display = "none";

        // Create main container
        var container = document.createElement("div");
        container.id = "dear-navi-admin-container";
        textarea.parentElement.insertBefore(container, textarea);

        var systemPages = getSystemPages();
        var items = parseJsonItems(textarea);

        // Auto-migrate legacy settings if items is empty
        if (items.length === 0) {
            var legacySlugs = hiddenSlugInput && hiddenSlugInput.value ? hiddenSlugInput.value.trim() : "";
            var legacyCustom = hiddenCustomInput && hiddenCustomInput.value ? hiddenCustomInput.value.trim() : "";

            var pageMap = {};
            systemPages.forEach(function (sp) {
                pageMap[sp.slug] = sp.title;
            });

            if (legacySlugs) {
                legacySlugs.split(",").forEach(function (slug) {
                    var s = slug.trim();
                    if (s) {
                        items.push({
                            type: "page",
                            slug: s,
                            title: pageMap[s] || s,
                            target: "_self"
                        });
                    }
                });
            }

            if (legacyCustom) {
                var div = document.createElement("div");
                div.innerHTML = legacyCustom;
                var links = div.querySelectorAll("a");
                links.forEach(function (a) {
                    var href = a.getAttribute("href") || "";
                    var text = a.textContent.trim();
                    var target = a.getAttribute("target") === "_blank" ? "_blank" : "_self";
                    if (href || text) {
                        items.push({
                            type: "custom",
                            title: text || href,
                            url: href,
                            target: target
                        });
                    }
                });
            }

            if (items.length > 0) {
                syncData();
            }
        }

        var activePanel = null; // 'page' | 'custom' | null

        function syncData() {
            textarea.value = JSON.stringify(items, null, 2);
            textarea.dispatchEvent(new Event("input", { bubbles: true }));
            textarea.dispatchEvent(new Event("change", { bubbles: true }));

            // Sync legacy hidden slug input
            if (hiddenSlugInput) {
                var pageSlugs = items
                    .filter(function (it) { return it.type === "page" && it.slug; })
                    .map(function (it) { return it.slug; });
                hiddenSlugInput.value = pageSlugs.join(",");
                hiddenSlugInput.dispatchEvent(new Event("change", { bubbles: true }));
            }

            // Sync legacy hidden custom navigation input
            if (hiddenCustomInput) {
                var customHtml = items
                    .filter(function (it) { return it.type === "custom" && it.url; })
                    .map(function (it) {
                        var targetAttr = it.target === "_blank" ? ' target="_blank"' : '';
                        return '<a href="' + esc(it.url) + '"' + targetAttr + '>' + esc(it.title || it.url) + '</a>';
                    })
                    .join("\n");
                hiddenCustomInput.value = customHtml;
                hiddenCustomInput.dispatchEvent(new Event("change", { bubbles: true }));
            }
        }

        function render() {
            container.innerHTML = "";

            // Toolbar
            var toolbar = document.createElement("div");
            toolbar.className = "dear-navi-toolbar";

            var addPageBtn = document.createElement("button");
            addPageBtn.type = "button";
            addPageBtn.className = "btn btn-s primary";
            addPageBtn.textContent = "选取独立页面";
            addPageBtn.addEventListener("click", function () {
                activePanel = activePanel === "page" ? null : "page";
                render();
            });
            toolbar.appendChild(addPageBtn);

            var addCustomBtn = document.createElement("button");
            addCustomBtn.type = "button";
            addCustomBtn.className = "btn btn-s";
            addCustomBtn.textContent = "添加自定义链接";
            addCustomBtn.addEventListener("click", function () {
                activePanel = activePanel === "custom" ? null : "custom";
                render();
            });
            toolbar.appendChild(addCustomBtn);

            if (systemPages.length > 0) {
                var importAllBtn = document.createElement("button");
                importAllBtn.type = "button";
                importAllBtn.className = "btn btn-s";
                importAllBtn.textContent = "导入全部系统页面";
                importAllBtn.addEventListener("click", function () {
                    var existingSlugs = {};
                    items.forEach(function (it) {
                        if (it.type === "page" && it.slug) existingSlugs[it.slug] = true;
                    });

                    systemPages.forEach(function (sp) {
                        if (!existingSlugs[sp.slug]) {
                            items.push({
                                type: "page",
                                slug: sp.slug,
                                title: sp.title || sp.slug,
                                target: "_self"
                            });
                        }
                    });

                    syncData();
                    activePanel = null;
                    render();
                });
                toolbar.appendChild(importAllBtn);
            }

            if (items.length > 0) {
                var clearBtn = document.createElement("button");
                clearBtn.type = "button";
                clearBtn.className = "btn btn-s btn-warn";
                clearBtn.textContent = "清空";
                clearBtn.addEventListener("click", function () {
                    if (confirm("确定要清空所有导航项配置吗？")) {
                        items = [];
                        syncData();
                        activePanel = null;
                        render();
                    }
                });
                toolbar.appendChild(clearBtn);
            }

            container.appendChild(toolbar);

            // Add Page Panel
            if (activePanel === "page") {
                var pagePanel = document.createElement("div");
                pagePanel.className = "dear-navi-panel";

                var panelTitle = document.createElement("div");
                panelTitle.className = "dear-navi-panel-title";
                panelTitle.textContent = "选取独立页面";
                pagePanel.appendChild(panelTitle);

                var formGrid = document.createElement("div");
                formGrid.className = "dear-navi-form-grid";

                // Page select
                var group1 = document.createElement("div");
                group1.className = "dear-navi-form-group";
                group1.innerHTML = "<label>独立页面</label>";

                var selectEl = document.createElement("select");
                if (systemPages.length === 0) {
                    var opt = document.createElement("option");
                    opt.disabled = true;
                    opt.selected = true;
                    opt.textContent = "暂无已发布的独立页面";
                    selectEl.appendChild(opt);
                } else {
                    systemPages.forEach(function (sp) {
                        var opt = document.createElement("option");
                        opt.value = sp.slug;
                        opt.textContent = sp.title + " - " + sp.slug;
                        opt.setAttribute("data-title", sp.title);
                        selectEl.appendChild(opt);
                    });
                }
                group1.appendChild(selectEl);
                formGrid.appendChild(group1);

                // Title input
                var group2 = document.createElement("div");
                group2.className = "dear-navi-form-group";
                group2.innerHTML = "<label>显示名称</label>";
                var titleInput = document.createElement("input");
                titleInput.type = "text";
                if (systemPages.length > 0) {
                    titleInput.value = systemPages[0].title || "";
                }
                group2.appendChild(titleInput);
                formGrid.appendChild(group2);

                selectEl.addEventListener("change", function () {
                    var opt = selectEl.options[selectEl.selectedIndex];
                    if (opt) {
                        titleInput.value = opt.getAttribute("data-title") || "";
                    }
                });

                // Target select
                var group3 = document.createElement("div");
                group3.className = "dear-navi-form-group";
                group3.innerHTML = "<label>打开方式</label>";
                var targetSelect = document.createElement("select");
                targetSelect.innerHTML =
                    '<option value="_self">当前窗口</option>' +
                    '<option value="_blank">新窗口</option>';
                group3.appendChild(targetSelect);
                formGrid.appendChild(group3);

                pagePanel.appendChild(formGrid);

                // Actions
                var actionsDiv = document.createElement("div");
                actionsDiv.className = "dear-navi-panel-actions";

                var confirmBtn = document.createElement("button");
                confirmBtn.type = "button";
                confirmBtn.className = "btn btn-s primary";
                confirmBtn.textContent = "添加";
                confirmBtn.disabled = systemPages.length === 0;
                confirmBtn.addEventListener("click", function () {
                    if (systemPages.length === 0) return;
                    var selectedSlug = selectEl.value;
                    var opt = selectEl.options[selectEl.selectedIndex];
                    var origTitle = opt ? opt.getAttribute("data-title") : selectedSlug;
                    var customTitle = titleInput.value.trim();

                    items.push({
                        type: "page",
                        slug: selectedSlug,
                        title: customTitle || origTitle,
                        target: targetSelect.value || "_self"
                    });

                    syncData();
                    activePanel = null;
                    render();
                });
                actionsDiv.appendChild(confirmBtn);

                var cancelBtn = document.createElement("button");
                cancelBtn.type = "button";
                cancelBtn.className = "btn btn-s";
                cancelBtn.textContent = "取消";
                cancelBtn.addEventListener("click", function () {
                    activePanel = null;
                    render();
                });
                actionsDiv.appendChild(cancelBtn);

                pagePanel.appendChild(actionsDiv);
                container.appendChild(pagePanel);
            }

            // Add Custom Link Panel
            if (activePanel === "custom") {
                var customPanel = document.createElement("div");
                customPanel.className = "dear-navi-panel";

                var customPanelTitle = document.createElement("div");
                customPanelTitle.className = "dear-navi-panel-title";
                customPanelTitle.textContent = "添加自定义链接";
                customPanel.appendChild(customPanelTitle);

                var cFormGrid = document.createElement("div");
                cFormGrid.className = "dear-navi-form-grid";

                var cGroup1 = document.createElement("div");
                cGroup1.className = "dear-navi-form-group";
                cGroup1.innerHTML = "<label>链接名称</label>";
                var cTitleInput = document.createElement("input");
                cTitleInput.type = "text";
                cGroup1.appendChild(cTitleInput);
                cFormGrid.appendChild(cGroup1);

                var cGroup2 = document.createElement("div");
                cGroup2.className = "dear-navi-form-group";
                cGroup2.innerHTML = "<label>链接地址</label>";
                var cUrlInput = document.createElement("input");
                cUrlInput.type = "text";
                cGroup2.appendChild(cUrlInput);
                cFormGrid.appendChild(cGroup2);

                var cGroup3 = document.createElement("div");
                cGroup3.className = "dear-navi-form-group";
                cGroup3.innerHTML = "<label>打开方式</label>";
                var cTargetSelect = document.createElement("select");
                cTargetSelect.innerHTML =
                    '<option value="_self">当前窗口</option>' +
                    '<option value="_blank">新窗口</option>';
                cGroup3.appendChild(cTargetSelect);
                cFormGrid.appendChild(cGroup3);

                customPanel.appendChild(cFormGrid);

                var cActionsDiv = document.createElement("div");
                cActionsDiv.className = "dear-navi-panel-actions";

                var cConfirmBtn = document.createElement("button");
                cConfirmBtn.type = "button";
                cConfirmBtn.className = "btn btn-s primary";
                cConfirmBtn.textContent = "添加";
                cConfirmBtn.addEventListener("click", function () {
                    var titleVal = cTitleInput.value.trim();
                    var urlVal = cUrlInput.value.trim();

                    if (!titleVal) {
                        cTitleInput.focus();
                        return;
                    }
                    if (!urlVal) {
                        cUrlInput.focus();
                        return;
                    }

                    items.push({
                        type: "custom",
                        title: titleVal,
                        url: urlVal,
                        target: cTargetSelect.value || "_self"
                    });

                    syncData();
                    activePanel = null;
                    render();
                });
                cActionsDiv.appendChild(cConfirmBtn);

                var cCancelBtn = document.createElement("button");
                cCancelBtn.type = "button";
                cCancelBtn.className = "btn btn-s";
                cCancelBtn.textContent = "取消";
                cCancelBtn.addEventListener("click", function () {
                    activePanel = null;
                    render();
                });
                cActionsDiv.appendChild(cCancelBtn);

                customPanel.appendChild(cActionsDiv);
                container.appendChild(customPanel);
            }

            // Items Table
            if (items.length === 0) {
                var emptyEl = document.createElement("div");
                emptyEl.className = "dear-navi-empty";
                emptyEl.textContent = "暂无导航项";
                container.appendChild(emptyEl);
            } else {
                var table = document.createElement("table");
                table.className = "dear-navi-table";

                var thead = document.createElement("thead");
                thead.innerHTML =
                    "<tr>" +
                    '<th style="width: 80px;">类型</th>' +
                    '<th style="width: 25%;">显示名称</th>' +
                    "<th>页面 / 链接</th>" +
                    '<th style="width: 100px;">打开方式</th>' +
                    '<th style="width: 120px; text-align: right;">操作</th>' +
                    "</tr>";
                table.appendChild(thead);

                var tbody = document.createElement("tbody");

                items.forEach(function (item, index) {
                    var tr = document.createElement("tr");

                    // 类型
                    var tdType = document.createElement("td");
                    tdType.innerHTML =
                        '<span class="dear-navi-type-tag">' +
                        (item.type === "page" ? "独立页面" : "自定义链接") +
                        "</span>";
                    tr.appendChild(tdType);

                    // 显示名称
                    var tdTitle = document.createElement("td");
                    var tInput = document.createElement("input");
                    tInput.type = "text";
                    tInput.value = item.title || "";
                    tInput.addEventListener("input", function () {
                        items[index].title = tInput.value;
                        syncData();
                    });
                    tInput.addEventListener("blur", function () {
                        tInput.value = tInput.value.trim();
                        items[index].title = tInput.value;
                        syncData();
                    });
                    tdTitle.appendChild(tInput);
                    tr.appendChild(tdTitle);

                    // 页面 / 链接
                    var tdContent = document.createElement("td");
                    if (item.type === "page") {
                        var spFound = systemPages.find(function (p) { return p.slug === item.slug; });
                        var slugHtml = '<span class="dear-navi-slug-text">' + esc(item.slug) + "</span>";
                        if (!spFound) {
                            slugHtml += ' <span class="dear-navi-missing-tag">未找到</span>';
                        }
                        tdContent.innerHTML = slugHtml;
                    } else {
                        var urlInput = document.createElement("input");
                        urlInput.type = "text";
                        urlInput.value = item.url || "";
                        urlInput.addEventListener("input", function () {
                            items[index].url = urlInput.value;
                            syncData();
                        });
                        urlInput.addEventListener("blur", function () {
                            urlInput.value = urlInput.value.trim();
                            items[index].url = urlInput.value;
                            syncData();
                        });
                        tdContent.appendChild(urlInput);
                    }
                    tr.appendChild(tdContent);

                    // 打开方式
                    var tdTarget = document.createElement("td");
                    var targetSel = document.createElement("select");
                    targetSel.innerHTML =
                        '<option value="_self"' + (item.target === "_blank" ? "" : " selected") + '>当前窗口</option>' +
                        '<option value="_blank"' + (item.target === "_blank" ? " selected" : "") + '>新窗口</option>';
                    targetSel.addEventListener("change", function () {
                        items[index].target = targetSel.value;
                        syncData();
                    });
                    tdTarget.appendChild(targetSel);
                    tr.appendChild(tdTarget);

                    // 操作
                    var tdActions = document.createElement("td");
                    tdActions.className = "dear-navi-actions-cell";

                    var upBtn = document.createElement("button");
                    upBtn.type = "button";
                    upBtn.className = "btn btn-xs";
                    upBtn.textContent = "上移";
                    upBtn.disabled = index === 0;
                    upBtn.addEventListener("click", function () {
                        if (index > 0) {
                            var temp = items[index];
                            items[index] = items[index - 1];
                            items[index - 1] = temp;
                            syncData();
                            render();
                        }
                    });
                    tdActions.appendChild(upBtn);

                    var downBtn = document.createElement("button");
                    downBtn.type = "button";
                    downBtn.className = "btn btn-xs";
                    downBtn.textContent = "下移";
                    downBtn.disabled = index === items.length - 1;
                    downBtn.addEventListener("click", function () {
                        if (index < items.length - 1) {
                            var temp = items[index];
                            items[index] = items[index + 1];
                            items[index + 1] = temp;
                            syncData();
                            render();
                        }
                    });
                    tdActions.appendChild(downBtn);

                    var delBtn = document.createElement("button");
                    delBtn.type = "button";
                    delBtn.className = "btn btn-xs btn-warn";
                    delBtn.textContent = "删除";
                    delBtn.addEventListener("click", function () {
                        items.splice(index, 1);
                        syncData();
                        render();
                    });
                    tdActions.appendChild(delBtn);

                    tr.appendChild(tdActions);
                    tbody.appendChild(tr);
                });

                table.appendChild(tbody);
                container.appendChild(table);
            }
        }

        // Listen for external textarea updates (e.g. JSON backup import)
        textarea.addEventListener("change", function () {
            var newItems = parseJsonItems(textarea);
            if (JSON.stringify(newItems) !== JSON.stringify(items)) {
                items = newItems;
                render();
            }
        });

        // Initial render
        render();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initNaviAdmin);
    } else {
        initNaviAdmin();
    }
})();
