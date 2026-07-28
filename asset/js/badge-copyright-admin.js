/**
 * Dear Theme — Badge & Copyright Settings Admin Editor
 *
 * Handles the visual editors on the theme settings page:
 * - Badge groups CRUD editor
 * - Copyright presets CRUD editor (with custom mark entries for groups with hasMarks)
 * - Default badges selector
 * - Default copyright selector
 */
(function () {
    "use strict";

    // ==================== Utility Functions ====================

    function esc(s) {
        if (!s && s !== 0) return "";
        var d = document.createElement("div");
        d.appendChild(document.createTextNode(String(s)));
        return d.innerHTML.replace(/"/g, "&quot;");
    }

    function parseJson(textarea) {
        if (!textarea || !textarea.value || !textarea.value.trim()) return null;
        try { return JSON.parse(textarea.value); }
        catch (e) { return null; }
    }

    function parseJsonArr(textarea) {
        var r = parseJson(textarea);
        return Array.isArray(r) ? r : [];
    }

    function saveJson(textarea, data) {
        textarea.value = JSON.stringify(data, null, 2);
    }

    function createContainer(textarea) {
        var c = document.createElement("div");
        c.style.cssText = "margin:10px 0;padding:15px;background:#f9f9f9;border-radius:8px;border:1px solid #e0e0e0;";
        textarea.parentElement.insertBefore(c, textarea);
        textarea.style.display = "none";
        return c;
    }

    function createButton(text, style, onClick) {
        var btn = document.createElement("button");
        btn.type = "button";
        btn.textContent = text;
        btn.style.cssText = style;
        btn.addEventListener("click", onClick);
        return btn;
    }

    var BTN_PRIMARY = "background:#467b96;color:#fff;border:none;border-radius:4px;padding:6px 16px;cursor:pointer;font-size:13px;";
    var BTN_DANGER = "background:#e74c3c;color:#fff;border:none;border-radius:4px;padding:4px 12px;cursor:pointer;font-size:12px;";
    var BTN_SECONDARY = "background:#999;color:#fff;border:none;border-radius:4px;padding:6px 16px;cursor:pointer;font-size:13px;";
    var BTN_SM_DANGER = "background:#e74c3c;color:#fff;border:none;border-radius:3px;padding:2px 8px;cursor:pointer;font-size:11px;";
    var BTN_SM_PRIMARY = "background:#467b96;color:#fff;border:none;border-radius:4px;padding:4px 12px;cursor:pointer;font-size:12px;";
    var BTN_SM_SECONDARY = "background:#999;color:#fff;border:none;border-radius:4px;padding:4px 12px;cursor:pointer;font-size:12px;";
    var INPUT_S = "width:100%;padding:5px;border:1px solid #ccc;border-radius:4px;margin-top:2px;box-sizing:border-box;font-size:12px;";
    var CARD_S = "background:#fff;border:1px solid #ddd;border-radius:6px;padding:14px;margin-bottom:12px;";
    var SUBCARD_S = "background:#f5f5f5;border:1px solid #e0e0e0;border-radius:4px;padding:10px;margin-bottom:8px;";

    function badgePreviewHtml(badge) {
        var h = '<span style="display:inline-block;background:#333;color:#fff;padding:2px 6px;border-radius:3px 0 0 3px;font-size:11px;vertical-align:top;">' + esc(badge.label || "?") + '</span>';
        if (badge.value) {
            h += '<span style="display:inline-block;background:' + esc(badge.color || "#555") + ';color:#fff;padding:2px 6px;border-radius:0 3px 3px 0;font-size:11px;vertical-align:top;">' + esc(badge.value) + '</span>';
        }
        return h;
    }

    // ==================== Badge Groups Editor ====================

    function initBadgeGroupsEditor() {
        var textarea = document.querySelector("textarea[name=Dear_badgeGroups]");
        if (!textarea) return;
        var container = createContainer(textarea);

        function render() {
            var groups = parseJsonArr(textarea);
            container.innerHTML = "";

            groups.forEach(function (group, gi) {
                var card = document.createElement("div");
                card.style.cssText = CARD_S;

                var header = document.createElement("div");
                header.style.cssText = "display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;";
                header.innerHTML = '<strong style="font-size:15px;">徽章组 #' + (gi + 1) + '</strong>';
                header.appendChild(createButton("删除组", BTN_DANGER, function () {
                    groups.splice(gi, 1); saveJson(textarea, groups); render();
                }));
                card.appendChild(header);

                var gRow = document.createElement("div");
                gRow.style.cssText = "display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:12px;";
                gRow.innerHTML =
                    '<label style="font-size:12px;color:#666;">组ID<input type="text" data-gi="' + gi + '" data-key="id" value="' + esc(group.id) + '" class="dear-gf" style="' + INPUT_S + '"></label>' +
                    '<label style="font-size:12px;color:#666;">组名<input type="text" data-gi="' + gi + '" data-key="name" value="' + esc(group.name) + '" class="dear-gf" style="' + INPUT_S + '"></label>' +
                    '<label style="font-size:12px;color:#666;">选择模式<select data-gi="' + gi + '" data-key="mode" class="dear-gf" style="' + INPUT_S + '"><option value="single"' + (group.mode === "single" ? " selected" : "") + '>单选</option><option value="multi"' + (group.mode === "multi" ? " selected" : "") + '>多选</option></select></label>';
                card.appendChild(gRow);

                var badges = group.badges || [];
                badges.forEach(function (badge, bi) {
                    var bCard = document.createElement("div");
                    bCard.style.cssText = SUBCARD_S;

                    var bHeader = document.createElement("div");
                    bHeader.style.cssText = "display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;";
                    var leftDiv = document.createElement("div");
                    leftDiv.style.cssText = "display:flex;align-items:center;gap:8px;";
                    leftDiv.innerHTML = '<span style="font-size:12px;font-weight:600;color:#555;">徽章 #' + (bi + 1) + '</span><span style="display:inline-flex;border-radius:3px;font-size:0;font-weight:700;letter-spacing:0.5px;box-shadow:0 1px 2px rgba(0,0,0,0.1);">' + badgePreviewHtml(badge) + '</span>';
                    bHeader.appendChild(leftDiv);
                    bHeader.appendChild(createButton("删除", BTN_SM_DANGER, function () {
                        groups[gi].badges.splice(bi, 1); saveJson(textarea, groups); render();
                    }));
                    bCard.appendChild(bHeader);

                    var bRow1 = document.createElement("div");
                    bRow1.style.cssText = "display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:6px;margin-bottom:6px;";
                    bRow1.innerHTML =
                        '<label style="font-size:11px;color:#666;">ID<input type="text" data-gi="' + gi + '" data-bi="' + bi + '" data-key="id" value="' + esc(badge.id) + '" class="dear-bf" style="' + INPUT_S + '"></label>' +
                        '<label style="font-size:11px;color:#666;">标签<input type="text" data-gi="' + gi + '" data-bi="' + bi + '" data-key="label" value="' + esc(badge.label) + '" class="dear-bf" style="' + INPUT_S + '"></label>' +
                        '<label style="font-size:11px;color:#666;">值<input type="text" data-gi="' + gi + '" data-bi="' + bi + '" data-key="value" value="' + esc(badge.value) + '" class="dear-bf" style="' + INPUT_S + '"></label>' +
                        '<label style="font-size:11px;color:#666;">颜色<div style="display:flex;gap:4px;margin-top:2px;"><input type="color" data-gi="' + gi + '" data-bi="' + bi + '" class="dear-bf-color" value="' + esc(badge.color || "#555555") + '" style="width:30px;height:28px;padding:0;border:1px solid #ccc;border-radius:4px;cursor:pointer;"><input type="text" data-gi="' + gi + '" data-bi="' + bi + '" data-key="color" value="' + esc(badge.color || "#555555") + '" class="dear-bf" style="flex:1;padding:5px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;font-size:12px;"></div></label>';
                    bCard.appendChild(bRow1);

                    var bRow2 = document.createElement("div");
                    bRow2.style.cssText = "display:grid;grid-template-columns:1fr 1fr;gap:6px;";
                    bRow2.innerHTML =
                        '<label style="font-size:11px;color:#666;">提示 (Tooltip)<input type="text" data-gi="' + gi + '" data-bi="' + bi + '" data-key="tooltip" value="' + esc(badge.tooltip) + '" class="dear-bf" style="' + INPUT_S + '"></label>' +
                        '<label style="font-size:11px;color:#666;">链接 (URL)<input type="text" data-gi="' + gi + '" data-bi="' + bi + '" data-key="link" value="' + esc(badge.link) + '" class="dear-bf" style="' + INPUT_S + '"></label>';
                    bCard.appendChild(bRow2);
                    card.appendChild(bCard);
                });

                card.appendChild(createButton("+ 添加徽章", BTN_SM_PRIMARY, function () {
                    if (!groups[gi].badges) groups[gi].badges = [];
                    groups[gi].badges.push({ id: "badge_" + Date.now(), label: "", value: "", color: "#555555", tooltip: "", link: "" });
                    saveJson(textarea, groups); render();
                }));
                container.appendChild(card);
            });

            var btnRow = document.createElement("div");
            btnRow.style.cssText = "display:flex;gap:8px;";
            btnRow.appendChild(createButton("+ 添加徽章组", BTN_PRIMARY, function () {
                groups.push({ id: "group_" + Date.now(), name: "新组", mode: "multi", badges: [] });
                saveJson(textarea, groups); render();
            }));
            btnRow.appendChild(createButton("显示/编辑 JSON", BTN_SECONDARY, function () {
                textarea.style.display = textarea.style.display === "none" ? "block" : "none";
            }));
            container.appendChild(btnRow);

            container.querySelectorAll(".dear-gf").forEach(function (inp) {
                var handler = function () {
                    var g = parseJsonArr(textarea);
                    var i = parseInt(this.dataset.gi);
                    if (g[i]) { g[i][this.dataset.key] = this.value; saveJson(textarea, g); }
                };
                inp.addEventListener("input", handler);
                inp.addEventListener("change", handler);
            });

            container.querySelectorAll(".dear-bf").forEach(function (inp) {
                inp.addEventListener("input", function () {
                    var g = parseJsonArr(textarea);
                    var gi = parseInt(this.dataset.gi), bi = parseInt(this.dataset.bi);
                    if (g[gi] && g[gi].badges && g[gi].badges[bi]) {
                        g[gi].badges[bi][this.dataset.key] = this.value;
                        saveJson(textarea, g);
                        if (this.dataset.key === "color") {
                            var cp = container.querySelector('.dear-bf-color[data-gi="' + gi + '"][data-bi="' + bi + '"]');
                            if (cp && /^#[0-9A-Fa-f]{6}$/.test(this.value)) cp.value = this.value;
                        }
                    }
                });
            });

            container.querySelectorAll(".dear-bf-color").forEach(function (inp) {
                inp.addEventListener("input", function () {
                    var g = parseJsonArr(textarea);
                    var gi = parseInt(this.dataset.gi), bi = parseInt(this.dataset.bi);
                    if (g[gi] && g[gi].badges && g[gi].badges[bi]) {
                        g[gi].badges[bi].color = this.value;
                        saveJson(textarea, g);
                        var ti = container.querySelector('.dear-bf[data-gi="' + gi + '"][data-bi="' + bi + '"][data-key="color"]');
                        if (ti) ti.value = this.value;
                    }
                });
            });
        }
        render();
    }

    // ==================== Copyright Presets Editor ====================

    function initCopyrightPresetsEditor() {
        var textarea = document.querySelector("textarea[name=Dear_copyrightPresets]");
        if (!textarea) return;
        var container = createContainer(textarea);

        function render() {
            var presets = parseJson(textarea);
            if (!presets || typeof presets !== "object") {
                presets = { contentGroups: [], codePresets: [] };
            }
            var contentGroups = presets.contentGroups || [];
            var codePresets = presets.codePresets || [];

            container.innerHTML = "";

            // --- Content License Groups ---
            var contentTitle = document.createElement("div");
            contentTitle.style.cssText = "font-size:16px;font-weight:bold;color:#333;margin-bottom:10px;padding-bottom:6px;border-bottom:1px solid #eee;";
            contentTitle.textContent = "内容许可协议组";
            container.appendChild(contentTitle);

            contentGroups.forEach(function (group, gi) {
                var card = document.createElement("div");
                card.style.cssText = CARD_S;

                var header = document.createElement("div");
                header.style.cssText = "display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;";
                header.innerHTML = '<strong style="font-size:14px;">协议组 #' + (gi + 1) + (group.name ? ' — ' + esc(group.name) : '') + '</strong>';
                header.appendChild(createButton("删除", BTN_DANGER, function () {
                    contentGroups.splice(gi, 1); presets.contentGroups = contentGroups; saveJson(textarea, presets); render();
                }));
                card.appendChild(header);

                // Row 1: ID, Name, Description
                var row1 = document.createElement("div");
                row1.style.cssText = "display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:8px;";
                row1.innerHTML =
                    '<label style="font-size:11px;color:#666;">ID<input type="text" data-gi="' + gi + '" data-key="id" value="' + esc(group.id) + '" class="dear-cpf" style="' + INPUT_S + '"></label>' +
                    '<label style="font-size:11px;color:#666;">名称<input type="text" data-gi="' + gi + '" data-key="name" value="' + esc(group.name) + '" class="dear-cpf" style="' + INPUT_S + '"></label>' +
                    '<label style="font-size:11px;color:#666;">描述<input type="text" data-gi="' + gi + '" data-key="description" value="' + esc(group.description) + '" class="dear-cpf" style="' + INPUT_S + '"></label>';
                card.appendChild(row1);

                // hasMarks toggle
                var marksToggleRow = document.createElement("div");
                marksToggleRow.style.cssText = "margin-bottom:8px;";
                var hasMarksLabel = document.createElement("label");
                hasMarksLabel.style.cssText = "font-size:12px;color:#666;display:flex;align-items:center;gap:6px;";
                var hasMarksChk = document.createElement("input");
                hasMarksChk.type = "checkbox";
                hasMarksChk.checked = !!group.hasMarks;
                hasMarksChk.addEventListener("change", function () {
                    contentGroups[gi].hasMarks = this.checked;
                    if (this.checked && (!contentGroups[gi].marks || !contentGroups[gi].marks.length)) {
                        contentGroups[gi].marks = [
                            { id: "BY", text: "BY", tooltip: "署名：引用或使用本文内容时必须注明明确出处", required: true },
                            { id: "NC", text: "NC", tooltip: "非商业性使用：您不能利用本文内容进行商业化行为", required: false },
                            { id: "SA", text: "SA", tooltip: "相同方式共享：二次创作必须在发布时使用同样的CC协议", required: false },
                            { id: "ND", text: "ND", tooltip: "禁止演绎：您不能对本文内容进行任何形式的修改或二次创作", required: false }
                        ];
                    }
                    presets.contentGroups = contentGroups;
                    saveJson(textarea, presets); render();
                });
                hasMarksLabel.appendChild(hasMarksChk);
                hasMarksLabel.appendChild(document.createTextNode("此协议组包含可选许可标记"));
                marksToggleRow.appendChild(hasMarksLabel);
                card.appendChild(marksToggleRow);

                if (group.hasMarks) {
                    // Custom Marks List Editor
                    var marksBox = document.createElement("div");
                    marksBox.style.cssText = "padding:10px;background:#eef5fa;border:1px solid #d0e3f0;border-radius:4px;margin-bottom:8px;";
                    marksBox.innerHTML = '<div style="font-size:12px;color:#333;margin-bottom:8px;font-weight:600;">许可标记配置列表</div>';

                    var marksList = group.marks || [];
                    marksList.forEach(function (mark, mi) {
                        var mCard = document.createElement("div");
                        mCard.style.cssText = "display:flex;gap:6px;align-items:center;margin-bottom:6px;background:#fff;padding:6px 8px;border-radius:4px;border:1px solid #ccc;";
                        mCard.innerHTML =
                            '<label style="font-size:11px;color:#666;width:80px;">标记/ID<input type="text" data-gi="' + gi + '" data-mi="' + mi + '" data-key="text" value="' + esc(mark.text || mark.id) + '" class="dear-mf" style="' + INPUT_S + '"></label>' +
                            '<label style="font-size:11px;color:#666;flex:1;">提示 (Tooltip)<input type="text" data-gi="' + gi + '" data-mi="' + mi + '" data-key="tooltip" value="' + esc(mark.tooltip) + '" class="dear-mf" style="' + INPUT_S + '"></label>' +
                            '<label style="font-size:11px;color:#666;display:flex;align-items:center;gap:4px;margin-top:14px;cursor:pointer;"><input type="checkbox" data-gi="' + gi + '" data-mi="' + mi + '" class="dear-mf-req"' + (mark.required ? " checked" : "") + '>必选</label>';

                        mCard.appendChild(createButton("删除", BTN_SM_DANGER, function () {
                            contentGroups[gi].marks.splice(mi, 1);
                            presets.contentGroups = contentGroups;
                            saveJson(textarea, presets); render();
                        }));
                        marksBox.appendChild(mCard);
                    });

                    marksBox.appendChild(createButton("+ 添加许可标记", BTN_SM_PRIMARY, function () {
                        if (!contentGroups[gi].marks) contentGroups[gi].marks = [];
                        contentGroups[gi].marks.push({ id: "MARK_" + Date.now(), text: "", tooltip: "", required: false });
                        presets.contentGroups = contentGroups;
                        saveJson(textarea, presets); render();
                    }));
                    card.appendChild(marksBox);
                } else {
                    var row2 = document.createElement("div");
                    row2.style.cssText = "display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px;";
                    var badgeObj = group.badge || { text: "", tooltip: "" };
                    row2.innerHTML =
                        '<label style="font-size:11px;color:#666;">链接<input type="text" data-gi="' + gi + '" data-key="link" value="' + esc(group.link || "") + '" class="dear-cpf" style="' + INPUT_S + '"></label>' +
                        '<label style="font-size:11px;color:#666;">徽章文字<input type="text" data-gi="' + gi + '" data-key="text" value="' + esc(badgeObj.text) + '" class="dear-cpbf" style="' + INPUT_S + '"></label>';
                    card.appendChild(row2);
                    var row3 = document.createElement("div");
                    row3.style.cssText = "margin-bottom:8px;";
                    row3.innerHTML =
                        '<label style="font-size:11px;color:#666;">徽章提示<input type="text" data-gi="' + gi + '" data-key="tooltip" value="' + esc(badgeObj.tooltip) + '" class="dear-cpbf" style="' + INPUT_S + '"></label>';
                    card.appendChild(row3);
                }

                container.appendChild(card);
            });

            container.appendChild(createButton("+ 添加内容协议组", BTN_SM_PRIMARY, function () {
                contentGroups.push({ id: "content_" + Date.now(), name: "", description: "", hasMarks: false, link: "", badge: { text: "", tooltip: "" } });
                presets.contentGroups = contentGroups;
                saveJson(textarea, presets); render();
            }));

            // --- Code License Presets ---
            var codeTitle = document.createElement("div");
            codeTitle.style.cssText = "font-size:16px;font-weight:bold;color:#333;margin:20px 0 10px;padding-bottom:6px;border-bottom:1px solid #eee;";
            codeTitle.textContent = "代码许可协议预设";
            container.appendChild(codeTitle);

            codePresets.forEach(function (preset, pi) {
                var card = document.createElement("div");
                card.style.cssText = CARD_S;

                var header = document.createElement("div");
                header.style.cssText = "display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;";
                header.innerHTML = '<strong style="font-size:14px;">预设 #' + (pi + 1) + (preset.name ? ' — ' + esc(preset.name) : '') + '</strong>';
                header.appendChild(createButton("删除", BTN_DANGER, function () {
                    codePresets.splice(pi, 1); presets.codePresets = codePresets; saveJson(textarea, presets); render();
                }));
                card.appendChild(header);

                var row1 = document.createElement("div");
                row1.style.cssText = "display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:8px;";
                row1.innerHTML =
                    '<label style="font-size:11px;color:#666;">ID<input type="text" data-pi="' + pi + '" data-key="id" value="' + esc(preset.id) + '" class="dear-codef" style="' + INPUT_S + '"></label>' +
                    '<label style="font-size:11px;color:#666;">名称<input type="text" data-pi="' + pi + '" data-key="name" value="' + esc(preset.name) + '" class="dear-codef" style="' + INPUT_S + '"></label>' +
                    '<label style="font-size:11px;color:#666;">描述<input type="text" data-pi="' + pi + '" data-key="description" value="' + esc(preset.description) + '" class="dear-codef" style="' + INPUT_S + '"></label>';
                card.appendChild(row1);

                var row2 = document.createElement("div");
                row2.style.cssText = "display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:8px;margin-bottom:8px;";
                row2.innerHTML =
                    '<label style="font-size:11px;color:#666;">链接<input type="text" data-pi="' + pi + '" data-key="link" value="' + esc(preset.link) + '" class="dear-codef" style="' + INPUT_S + '"></label>' +
                    '<label style="font-size:11px;color:#666;">徽章标签<input type="text" data-pi="' + pi + '" data-key="badgeLabel" value="' + esc(preset.badgeLabel || "OPEN SOURCED") + '" class="dear-codef" style="' + INPUT_S + '"></label>' +
                    '<label style="font-size:11px;color:#666;">徽章值<input type="text" data-pi="' + pi + '" data-key="badgeValue" value="' + esc(preset.badgeValue) + '" class="dear-codef" style="' + INPUT_S + '"></label>' +
                    '<label style="font-size:11px;color:#666;">徽章颜色<input type="text" data-pi="' + pi + '" data-key="badgeColor" value="' + esc(preset.badgeColor || "#555555") + '" class="dear-codef" style="' + INPUT_S + '"></label>';
                card.appendChild(row2);
                container.appendChild(card);
            });

            var codeBtnRow = document.createElement("div");
            codeBtnRow.style.cssText = "display:flex;gap:8px;margin-top:8px;";
            codeBtnRow.appendChild(createButton("+ 添加代码协议", BTN_SM_PRIMARY, function () {
                codePresets.push({ id: "code_" + Date.now(), name: "", description: "", link: "", badgeLabel: "OPEN SOURCED", badgeValue: "", badgeColor: "#555555" });
                presets.codePresets = codePresets;
                saveJson(textarea, presets); render();
            }));
            codeBtnRow.appendChild(createButton("显示/编辑 JSON", BTN_SECONDARY, function () {
                textarea.style.display = textarea.style.display === "none" ? "block" : "none";
            }));
            container.appendChild(codeBtnRow);

            // Bind events for group fields
            container.querySelectorAll(".dear-cpf").forEach(function (inp) {
                inp.addEventListener("input", function () {
                    var p = parseJson(textarea);
                    if (!p) return;
                    var gi = parseInt(this.dataset.gi);
                    if (p.contentGroups && p.contentGroups[gi]) {
                        p.contentGroups[gi][this.dataset.key] = this.value;
                        saveJson(textarea, p);
                    }
                });
            });

            // Bind events for group badge fields
            container.querySelectorAll(".dear-cpbf").forEach(function (inp) {
                inp.addEventListener("input", function () {
                    var p = parseJson(textarea);
                    if (!p) return;
                    var gi = parseInt(this.dataset.gi);
                    if (p.contentGroups && p.contentGroups[gi]) {
                        if (!p.contentGroups[gi].badge) p.contentGroups[gi].badge = {};
                        p.contentGroups[gi].badge[this.dataset.key] = this.value;
                        saveJson(textarea, p);
                    }
                });
            });

            // Bind events for mark fields
            container.querySelectorAll(".dear-mf").forEach(function (inp) {
                inp.addEventListener("input", function () {
                    var p = parseJson(textarea);
                    if (!p) return;
                    var gi = parseInt(this.dataset.gi), mi = parseInt(this.dataset.mi);
                    if (p.contentGroups && p.contentGroups[gi] && p.contentGroups[gi].marks && p.contentGroups[gi].marks[mi]) {
                        p.contentGroups[gi].marks[mi][this.dataset.key] = this.value;
                        if (this.dataset.key === "text") {
                            p.contentGroups[gi].marks[mi].id = this.value;
                        }
                        saveJson(textarea, p);
                    }
                });
            });

            container.querySelectorAll(".dear-mf-req").forEach(function (inp) {
                inp.addEventListener("change", function () {
                    var p = parseJson(textarea);
                    if (!p) return;
                    var gi = parseInt(this.dataset.gi), mi = parseInt(this.dataset.mi);
                    if (p.contentGroups && p.contentGroups[gi] && p.contentGroups[gi].marks && p.contentGroups[gi].marks[mi]) {
                        p.contentGroups[gi].marks[mi].required = this.checked;
                        saveJson(textarea, p);
                    }
                });
            });

            // Bind events for code preset fields
            container.querySelectorAll(".dear-codef").forEach(function (inp) {
                inp.addEventListener("input", function () {
                    var p = parseJson(textarea);
                    if (!p) return;
                    var pi = parseInt(this.dataset.pi);
                    if (p.codePresets && p.codePresets[pi]) {
                        p.codePresets[pi][this.dataset.key] = this.value;
                        saveJson(textarea, p);
                    }
                });
            });
        }
        render();
    }

    // ==================== Selector Builder (shared logic) ====================

    function buildBadgeSelector(container, groupsTextarea, selectedTextarea) {
        function render() {
            var groups = parseJsonArr(groupsTextarea);
            var selected = parseJsonArr(selectedTextarea);
            container.innerHTML = "";

            if (!groups.length) {
                container.innerHTML = '<div style="color:#999;font-size:13px;">请先在上方配置徽章组后保存设置。</div>';
                return;
            }

            groups.forEach(function (group) {
                if (!group.badges || !group.badges.length) return;
                var gDiv = document.createElement("div");
                gDiv.style.cssText = "margin-bottom:12px;padding:12px;background:#fff;border:1px solid #ddd;border-radius:6px;";
                var gTitle = document.createElement("div");
                gTitle.style.cssText = "font-weight:600;margin-bottom:8px;font-size:14px;color:#333;";
                gTitle.textContent = group.name + (group.mode === "single" ? " （单选）" : " （多选）");
                gDiv.appendChild(gTitle);

                var bDiv = document.createElement("div");
                bDiv.style.cssText = "display:flex;flex-wrap:wrap;gap:8px;";
                group.badges.forEach(function (badge) {
                    var isChecked = selected.indexOf(badge.id) !== -1;
                    var lbl = document.createElement("label");
                    lbl.style.cssText = "display:inline-flex;align-items:center;gap:6px;cursor:pointer;padding:5px 10px;border:2px solid " + (isChecked ? (badge.color || "#467b96") : "#e0e0e0") + ";border-radius:6px;font-size:13px;transition:all 0.15s;" + (isChecked ? "background:rgba(70,123,150,0.05);" : "");
                    var inp = document.createElement("input");
                    inp.type = group.mode === "single" ? "radio" : "checkbox";
                    inp.name = "dear_sel_badge_" + group.id + "_" + container.id;
                    inp.value = badge.id;
                    inp.checked = isChecked;
                    inp.style.cssText = "margin:0;width:auto;";
                    inp.addEventListener("change", function () { sync(); render(); });
                    var prev = document.createElement("span");
                    prev.style.cssText = "display:inline-flex;border-radius:3px;font-size:0;font-weight:700;letter-spacing:0.5px;box-shadow:0 1px 2px rgba(0,0,0,0.1);";
                    prev.innerHTML = badgePreviewHtml(badge);
                    lbl.appendChild(inp);
                    lbl.appendChild(prev);
                    bDiv.appendChild(lbl);
                });
                gDiv.appendChild(bDiv);
                container.appendChild(gDiv);
            });

            var btnRow = document.createElement("div");
            btnRow.style.cssText = "display:flex;gap:8px;margin-top:8px;";
            btnRow.appendChild(createButton("刷新选项", BTN_SM_PRIMARY, render));
            btnRow.appendChild(createButton("清除全部", BTN_SM_SECONDARY, function () {
                selectedTextarea.value = "[]"; render();
            }));
            btnRow.appendChild(createButton("显示/编辑 JSON", BTN_SM_SECONDARY, function () {
                selectedTextarea.style.display = selectedTextarea.style.display === "none" ? "block" : "none";
            }));
            container.appendChild(btnRow);
        }

        function sync() {
            var sel = [];
            container.querySelectorAll("input:checked").forEach(function (inp) { sel.push(inp.value); });
            selectedTextarea.value = JSON.stringify(sel);
        }

        render();
    }

    // ==================== Copyright Selector ====================

    function buildCopyrightSelector(container, presetsTextarea, selectedTextarea) {
        function render() {
            var presets = parseJson(presetsTextarea);
            var selected = parseJson(selectedTextarea);
            if (!selected || typeof selected !== "object" || Array.isArray(selected)) {
                selected = {};
            }
            container.innerHTML = "";

            if (!presets || typeof presets !== "object") {
                container.innerHTML = '<div style="color:#999;font-size:13px;">请先在上方配置版权预设后保存设置。</div>';
                return;
            }

            var contentGroups = presets.contentGroups || [];
            var codePresets = presets.codePresets || [];

            var contentSel = selected.content || null;
            var codeSel = selected.code || null;

            // --- Content License Section ---
            if (contentGroups.length) {
                var cDiv = document.createElement("div");
                cDiv.style.cssText = "margin-bottom:12px;padding:12px;background:#fff;border:1px solid #ddd;border-radius:6px;";
                var cTitle = document.createElement("div");
                cTitle.style.cssText = "font-weight:600;margin-bottom:8px;font-size:14px;color:#333;";
                cTitle.textContent = "内容许可协议（单选）";
                cDiv.appendChild(cTitle);

                var bDiv = document.createElement("div");
                bDiv.style.cssText = "display:flex;flex-wrap:wrap;gap:8px;margin-bottom:8px;";

                // Option: None / Clear
                var isNoneChecked = !contentSel || !contentSel.type;
                var noneLbl = document.createElement("label");
                noneLbl.style.cssText = "display:inline-flex;align-items:center;gap:6px;cursor:pointer;padding:5px 10px;border:2px solid " + (isNoneChecked ? "#999" : "#e0e0e0") + ";border-radius:6px;font-size:13px;transition:all 0.15s;" + (isNoneChecked ? "background:#f0f0f0;" : "");
                var noneInp = document.createElement("input");
                noneInp.type = "radio";
                noneInp.name = "dear_sel_content_" + container.id;
                noneInp.value = "";
                noneInp.checked = isNoneChecked;
                noneInp.style.cssText = "margin:0;width:auto;";
                noneInp.addEventListener("change", function () { syncContent(); render(); });
                var noneSpan = document.createElement("span");
                noneSpan.style.cssText = "font-weight:600;color:#666;";
                noneSpan.textContent = "不设置 (无)";
                noneLbl.appendChild(noneInp);
                noneLbl.appendChild(noneSpan);
                bDiv.appendChild(noneLbl);

                contentGroups.forEach(function (group) {
                    var isChecked = contentSel && contentSel.type === group.id;
                    var lbl = document.createElement("label");
                    lbl.style.cssText = "display:inline-flex;align-items:center;gap:6px;cursor:pointer;padding:5px 10px;border:2px solid " + (isChecked ? "#467b96" : "#e0e0e0") + ";border-radius:6px;font-size:13px;transition:all 0.15s;" + (isChecked ? "background:rgba(70,123,150,0.05);" : "");
                    var inp = document.createElement("input");
                    inp.type = "radio";
                    inp.name = "dear_sel_content_" + container.id;
                    inp.value = group.id;
                    inp.checked = isChecked;
                    inp.style.cssText = "margin:0;width:auto;";
                    inp.addEventListener("change", function () { syncContent(); render(); });
                    var nameSpan = document.createElement("span");
                    nameSpan.style.cssText = "font-weight:600;";
                    nameSpan.textContent = group.name;
                    lbl.appendChild(inp);
                    lbl.appendChild(nameSpan);
                    if (group.description) {
                        var desc = document.createElement("span");
                        desc.style.cssText = "font-size:11px;color:#888;font-weight:normal;";
                        desc.textContent = group.description;
                        lbl.appendChild(desc);
                    }
                    bDiv.appendChild(lbl);
                });

                cDiv.appendChild(bDiv);

                // Show marks selector if selected group has marks
                if (contentSel && contentSel.type) {
                    var activeGroup = null;
                    contentGroups.forEach(function (g) { if (g.id === contentSel.type) activeGroup = g; });
                    if (activeGroup && activeGroup.hasMarks && activeGroup.marks && activeGroup.marks.length) {
                        var marksDiv = document.createElement("div");
                        marksDiv.style.cssText = "padding:10px;background:#f0f7fb;border:1px solid #d0e3f0;border-radius:6px;";
                        var marksTitle = document.createElement("div");
                        marksTitle.style.cssText = "font-size:12px;color:#555;margin-bottom:8px;font-weight:600;";
                        marksTitle.textContent = "选择许可标记";
                        marksDiv.appendChild(marksTitle);

                        var mDiv = document.createElement("div");
                        mDiv.style.cssText = "display:flex;flex-wrap:wrap;gap:8px;";

                        var currentMarks = contentSel.marks || [];

                        activeGroup.marks.forEach(function (markDef) {
                            var mVal = markDef.id || markDef.text;
                            var isMarkChecked = currentMarks.indexOf(mVal) !== -1;
                            var isRequired = !!markDef.required;
                            var mLbl = document.createElement("label");
                            mLbl.style.cssText = "display:inline-flex;align-items:center;gap:6px;cursor:" + (isRequired ? "not-allowed" : "pointer") + ";padding:5px 12px;border:2px solid " + (isMarkChecked ? "#467b96" : "#e0e0e0") + ";border-radius:6px;font-size:13px;transition:all 0.15s;" + (isMarkChecked ? "background:rgba(70,123,150,0.08);" : "");
                            var mInp = document.createElement("input");
                            mInp.type = "checkbox";
                            mInp.value = mVal;
                            mInp.checked = isMarkChecked || isRequired;
                            mInp.disabled = isRequired;
                            mInp.className = "dear-sel-mark-chk";
                            mInp.style.cssText = "margin:0;width:auto;";
                            mInp.addEventListener("change", function () { syncContent(); render(); });
                            var mNameSpan = document.createElement("span");
                            mNameSpan.style.cssText = "font-weight:600;font-size:13px;";
                            mNameSpan.textContent = markDef.text || mVal;
                            mLbl.appendChild(mInp);
                            mLbl.appendChild(mNameSpan);
                            if (isRequired) {
                                var reqTag = document.createElement("span");
                                reqTag.style.cssText = "font-size:10px;color:#999;font-weight:normal;";
                                reqTag.textContent = "(必选)";
                                mLbl.appendChild(reqTag);
                            }
                            if (markDef.tooltip) {
                                var tip = document.createElement("span");
                                tip.style.cssText = "font-size:11px;color:#888;font-weight:normal;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;";
                                tip.textContent = markDef.tooltip;
                                mLbl.appendChild(tip);
                            }
                            mDiv.appendChild(mLbl);
                        });

                        marksDiv.appendChild(mDiv);
                        cDiv.appendChild(marksDiv);
                    }
                }

                container.appendChild(cDiv);
            }

            // --- Code License Section ---
            if (codePresets.length) {
                var codeDiv = document.createElement("div");
                codeDiv.style.cssText = "margin-bottom:12px;padding:12px;background:#fff;border:1px solid #ddd;border-radius:6px;";
                var codeTitle = document.createElement("div");
                codeTitle.style.cssText = "font-weight:600;margin-bottom:8px;font-size:14px;color:#333;";
                codeTitle.textContent = "代码许可协议（单选）";
                codeDiv.appendChild(codeTitle);

                var codeBDiv = document.createElement("div");
                codeBDiv.style.cssText = "display:flex;flex-wrap:wrap;gap:8px;";

                // Option: None / Clear
                var isCodeNoneChecked = !codeSel;
                var codeNoneLbl = document.createElement("label");
                codeNoneLbl.style.cssText = "display:inline-flex;align-items:center;gap:6px;cursor:pointer;padding:5px 10px;border:2px solid " + (isCodeNoneChecked ? "#999" : "#e0e0e0") + ";border-radius:6px;font-size:13px;transition:all 0.15s;" + (isCodeNoneChecked ? "background:#f0f0f0;" : "");
                var codeNoneInp = document.createElement("input");
                codeNoneInp.type = "radio";
                codeNoneInp.name = "dear_sel_code_" + container.id;
                codeNoneInp.value = "";
                codeNoneInp.checked = isCodeNoneChecked;
                codeNoneInp.style.cssText = "margin:0;width:auto;";
                codeNoneInp.addEventListener("change", function () { syncCode(); render(); });
                var codeNoneSpan = document.createElement("span");
                codeNoneSpan.style.cssText = "font-weight:600;color:#666;";
                codeNoneSpan.textContent = "不设置 (无)";
                codeNoneLbl.appendChild(codeNoneInp);
                codeNoneLbl.appendChild(codeNoneSpan);
                codeBDiv.appendChild(codeNoneLbl);

                codePresets.forEach(function (preset) {
                    var isChecked = codeSel === preset.id;
                    var lbl = document.createElement("label");
                    lbl.style.cssText = "display:inline-flex;align-items:center;gap:6px;cursor:pointer;padding:5px 10px;border:2px solid " + (isChecked ? "#467b96" : "#e0e0e0") + ";border-radius:6px;font-size:13px;transition:all 0.15s;" + (isChecked ? "background:rgba(70,123,150,0.05);" : "");
                    var inp = document.createElement("input");
                    inp.type = "radio";
                    inp.name = "dear_sel_code_" + container.id;
                    inp.value = preset.id;
                    inp.checked = isChecked;
                    inp.style.cssText = "margin:0;width:auto;";
                    inp.addEventListener("change", function () { syncCode(); render(); });
                    var nameSpan = document.createElement("span");
                    nameSpan.style.cssText = "font-weight:600;";
                    nameSpan.textContent = preset.name;
                    lbl.appendChild(inp);
                    lbl.appendChild(nameSpan);
                    if (preset.description) {
                        var desc = document.createElement("span");
                        desc.style.cssText = "font-size:11px;color:#888;font-weight:normal;";
                        desc.textContent = preset.description;
                        lbl.appendChild(desc);
                    }
                    codeBDiv.appendChild(lbl);
                });
                codeDiv.appendChild(codeBDiv);
                container.appendChild(codeDiv);
            }

            // Buttons
            var btnRow = document.createElement("div");
            btnRow.style.cssText = "display:flex;gap:8px;margin-top:8px;";
            btnRow.appendChild(createButton("刷新选项", BTN_SM_PRIMARY, render));
            btnRow.appendChild(createButton("清除全部", BTN_SM_SECONDARY, function () {
                selectedTextarea.value = "{}"; render();
            }));
            btnRow.appendChild(createButton("显示/编辑 JSON", BTN_SM_SECONDARY, function () {
                selectedTextarea.style.display = selectedTextarea.style.display === "none" ? "block" : "none";
            }));
            container.appendChild(btnRow);
        }

        function syncContent() {
            var presets = parseJson(presetsTextarea);
            if (!presets) return;
            var selected = parseJson(selectedTextarea) || {};
            if (Array.isArray(selected)) selected = {};

            var contentGroups = presets.contentGroups || [];
            var checkedRadio = container.querySelector('input[name="dear_sel_content_' + container.id + '"]:checked');

            if (!checkedRadio || !checkedRadio.value) {
                delete selected.content;
            } else {
                var groupId = checkedRadio.value;
                var activeGroup = null;
                contentGroups.forEach(function (g) { if (g.id === groupId) activeGroup = g; });

                if (activeGroup && activeGroup.hasMarks && activeGroup.marks) {
                    var marks = [];
                    activeGroup.marks.forEach(function (m) {
                        if (m.required) marks.push(m.id || m.text);
                    });
                    container.querySelectorAll(".dear-sel-mark-chk:checked").forEach(function (chk) {
                        var val = chk.value;
                        if (marks.indexOf(val) === -1) marks.push(val);
                    });
                    selected.content = { type: groupId, marks: marks };
                } else {
                    selected.content = { type: groupId };
                }
            }

            selectedTextarea.value = JSON.stringify(selected);
        }

        function syncCode() {
            var selected = parseJson(selectedTextarea) || {};
            if (Array.isArray(selected)) selected = {};
            var checkedRadio = container.querySelector('input[name="dear_sel_code_' + container.id + '"]:checked');
            if (!checkedRadio || !checkedRadio.value) {
                delete selected.code;
            } else {
                selected.code = checkedRadio.value;
            }
            selectedTextarea.value = JSON.stringify(selected);
        }

        render();
    }

    // ==================== Default Selectors ====================

    function initDefaultBadgesSelector() {
        var defaultTA = document.querySelector("textarea[name=Dear_badgesDefault]");
        var groupsTA = document.querySelector("textarea[name=Dear_badgeGroups]");
        if (!defaultTA || !groupsTA) return;
        var container = createContainer(defaultTA);
        container.id = "dear-default-badges-sel";
        buildBadgeSelector(container, groupsTA, defaultTA);
    }

    function initDefaultCopyrightSelector() {
        var defaultTA = document.querySelector("textarea[name=Dear_copyrightDefault]");
        var presetsTA = document.querySelector("textarea[name=Dear_copyrightPresets]");
        if (!defaultTA || !presetsTA) return;
        var container = createContainer(defaultTA);
        container.id = "dear-default-copyright-sel";
        buildCopyrightSelector(container, presetsTA, defaultTA);
    }

    // ==================== Init ====================

    document.addEventListener("DOMContentLoaded", function () {
        initBadgeGroupsEditor();
        initCopyrightPresetsEditor();
        initDefaultBadgesSelector();
        initDefaultCopyrightSelector();
    });
})();
