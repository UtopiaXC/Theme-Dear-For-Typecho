/**
 * Dear Theme — Article Badge & Copyright Selector
 *
 * Transforms custom fields textareas into visual interactive selectors on post/page edit page
 * and handles conditional visibility based on showBadges / showCopyright dropdown controls.
 */
(function () {
    "use strict";

    function esc(s) {
        if (!s && s !== 0) return "";
        var d = document.createElement("div");
        d.appendChild(document.createTextNode(String(s)));
        return d.innerHTML.replace(/"/g, "&quot;");
    }

    function parseJson(val) {
        if (!val || !val.trim()) return null;
        try { return JSON.parse(val); }
        catch (e) { return null; }
    }

    function parseJsonFromTA(textarea) {
        if (!textarea || !textarea.value) return null;
        return parseJson(textarea.value);
    }

    function parseJsonArr(textarea) {
        var r = parseJsonFromTA(textarea);
        return Array.isArray(r) ? r : [];
    }

    function badgePreviewHtml(badge) {
        var h = '<span style="display:inline-block;background:#333;color:#fff;padding:2px 6px;border-radius:3px 0 0 3px;font-size:11px;vertical-align:top;">' + esc(badge.label || "?") + '</span>';
        if (badge.value) {
            h += '<span style="display:inline-block;background:' + esc(badge.color || "#555") + ';color:#fff;padding:2px 6px;border-radius:0 3px 3px 0;font-size:11px;vertical-align:top;">' + esc(badge.value) + '</span>';
        }
        return h;
    }

    var BTN_SM_SECONDARY = "background:#999;color:#fff;border:none;border-radius:4px;padding:4px 12px;cursor:pointer;font-size:11px;";

    function findTextarea(fieldName) {
        return document.querySelector('textarea[name*="' + fieldName + '"]');
    }

    function findControl(fieldName) {
        return document.querySelector('select[name*="' + fieldName + '"]') ||
               document.querySelector('input[name*="' + fieldName + '"]:checked');
    }

    function getControlVal(fieldName) {
        var ctrl = findControl(fieldName);
        return ctrl ? ctrl.value : 'default';
    }

    function findFieldRow(element) {
        if (!element) return null;
        return element.closest('li.field') || element.closest('.field') || element.parentElement;
    }

    // ==================== Badge Selector ====================

    function initBadgeSelector() {
        var textarea = findTextarea('articleBadges');
        var groups = window.dearBadgeGroups;
        if (!textarea || !groups || !groups.length) return false;

        var badgesRow = findFieldRow(textarea);

        if (!textarea.dataset.dearInitialized) {
            textarea.dataset.dearInitialized = "true";

            var container = document.createElement("div");
            container.className = "dear-article-badges-container";
            container.style.cssText = "margin:6px 0;padding:12px;background:#f9f9f9;border-radius:8px;border:1px solid #e0e0e0;width:100%;box-sizing:border-box;";
            textarea.insertAdjacentElement('beforebegin', container);
            textarea.style.setProperty("display", "none", "important");

            function render() {
                var selected = parseJsonArr(textarea);
                container.innerHTML = "";

                groups.forEach(function (group) {
                    if (!group.badges || !group.badges.length) return;

                    var gDiv = document.createElement("div");
                    gDiv.style.cssText = "margin-bottom:10px;padding:12px;background:#fff;border:1px solid #ddd;border-radius:6px;";

                    var gTitle = document.createElement("div");
                    gTitle.style.cssText = "font-weight:600;margin-bottom:8px;font-size:14px;color:#333;";
                    gTitle.textContent = group.name + (group.mode === "single" ? " （单选）" : " （多选）");
                    gDiv.appendChild(gTitle);

                    var bDiv = document.createElement("div");
                    bDiv.style.cssText = "display:flex;flex-wrap:wrap;gap:8px;";

                    group.badges.forEach(function (badge) {
                        var isChecked = selected.indexOf(badge.id) !== -1;
                        var lbl = document.createElement("label");
                        lbl.style.cssText = "display:inline-flex;align-items:center;gap:6px;cursor:pointer;padding:6px 12px;border:2px solid " + (isChecked ? (badge.color || "#467b96") : "#e0e0e0") + ";border-radius:6px;font-size:13px;transition:all 0.15s;background:#fff;" + (isChecked ? "background:rgba(70,123,150,0.05);" : "");

                        var inp = document.createElement("input");
                        inp.type = group.mode === "single" ? "radio" : "checkbox";
                        inp.name = "dear_art_badge_" + group.id;
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
                btnRow.style.cssText = "display:flex;gap:6px;margin-top:6px;";

                var clearBtn = document.createElement("button");
                clearBtn.type = "button";
                clearBtn.textContent = "清除已选";
                clearBtn.style.cssText = BTN_SM_SECONDARY;
                clearBtn.addEventListener("click", function () {
                    textarea.value = "";
                    render();
                });
                btnRow.appendChild(clearBtn);

                var jsonBtn = document.createElement("button");
                jsonBtn.type = "button";
                jsonBtn.textContent = "显示/编辑 JSON";
                jsonBtn.style.cssText = BTN_SM_SECONDARY;
                jsonBtn.addEventListener("click", function () {
                    textarea.style.display = textarea.style.display === "none" ? "block" : "none";
                });
                btnRow.appendChild(jsonBtn);

                container.appendChild(btnRow);
            }

            function sync() {
                var sel = [];
                container.querySelectorAll("input:checked").forEach(function (inp) { sel.push(inp.value); });
                textarea.value = sel.length > 0 ? JSON.stringify(sel) : "";
            }

            render();
        }

        function updateVisibility() {
            var val = getControlVal('showBadges');
            if (badgesRow) {
                if (val === '1') {
                    badgesRow.style.setProperty('display', 'flex', 'important');
                } else {
                    badgesRow.style.setProperty('display', 'none', 'important');
                }
            }
        }

        var showCtrl = document.querySelector('select[name*="showBadges"]') || document.querySelector('input[name*="showBadges"]');
        if (showCtrl) {
            showCtrl.addEventListener('change', updateVisibility);
        }
        updateVisibility();

        return true;
    }

    // ==================== Copyright Selector ====================

    function initCopyrightSelector() {
        var textarea = findTextarea('articleCopyright');
        var presets = window.dearCopyrightPresets;
        if (!textarea || !presets) return false;

        var copyrightRow = findFieldRow(textarea);

        if (!textarea.dataset.dearInitialized) {
            textarea.dataset.dearInitialized = "true";

            var container = document.createElement("div");
            container.id = "dear-art-copyright-sel";
            container.className = "dear-article-copyright-container";
            container.style.cssText = "margin:6px 0;padding:12px;background:#f9f9f9;border-radius:8px;border:1px solid #e0e0e0;width:100%;box-sizing:border-box;";
            textarea.insertAdjacentElement('beforebegin', container);
            textarea.style.setProperty("display", "none", "important");

            var contentGroups = presets.contentGroups || [];
            var codePresets = presets.codePresets || [];

            function render() {
                var selected = parseJsonFromTA(textarea);
                if (!selected || typeof selected !== "object" || Array.isArray(selected)) {
                    selected = {};
                }
                container.innerHTML = "";

                var contentSel = selected.content || null;
                var codeSel = selected.code || null;

                // --- Content License Section ---
                if (contentGroups.length) {
                    var cDiv = document.createElement("div");
                    cDiv.style.cssText = "margin-bottom:10px;padding:12px;background:#fff;border:1px solid #ddd;border-radius:6px;";
                    var cTitle = document.createElement("div");
                    cTitle.style.cssText = "font-weight:600;margin-bottom:8px;font-size:14px;color:#333;";
                    cTitle.textContent = "内容许可协议（单选）";
                    cDiv.appendChild(cTitle);

                    var bDiv = document.createElement("div");
                    bDiv.style.cssText = "display:flex;flex-wrap:wrap;gap:8px;margin-bottom:8px;";

                    var isDefaultChecked = !contentSel || !contentSel.type;
                    var defLbl = document.createElement("label");
                    defLbl.style.cssText = "display:inline-flex;align-items:center;gap:6px;cursor:pointer;padding:5px 10px;border:2px solid " + (isDefaultChecked ? "#467b96" : "#e0e0e0") + ";border-radius:6px;font-size:13px;transition:all 0.15s;background:#fff;" + (isDefaultChecked ? "background:rgba(70,123,150,0.05);" : "");
                    var defInp = document.createElement("input");
                    defInp.type = "radio";
                    defInp.name = "dear_art_content_license";
                    defInp.value = "";
                    defInp.checked = isDefaultChecked;
                    defInp.style.cssText = "margin:0;width:auto;";
                    defInp.addEventListener("change", function () { syncContent(); render(); });
                    var defSpan = document.createElement("span");
                    defSpan.style.cssText = "font-weight:600;color:#666;font-size:13px;";
                    defSpan.textContent = "不设置 (无)";
                    defLbl.appendChild(defInp);
                    defLbl.appendChild(defSpan);
                    bDiv.appendChild(defLbl);

                    contentGroups.forEach(function (group) {
                        var isChecked = contentSel && contentSel.type === group.id;
                        var lbl = document.createElement("label");
                        lbl.style.cssText = "display:inline-flex;align-items:center;gap:6px;cursor:pointer;padding:5px 10px;border:2px solid " + (isChecked ? "#467b96" : "#e0e0e0") + ";border-radius:6px;font-size:13px;transition:all 0.15s;background:#fff;" + (isChecked ? "background:rgba(70,123,150,0.05);" : "");
                        var inp = document.createElement("input");
                        inp.type = "radio";
                        inp.name = "dear_art_content_license";
                        inp.value = group.id;
                        inp.checked = isChecked;
                        inp.style.cssText = "margin:0;width:auto;";
                        inp.addEventListener("change", function () { syncContent(); render(); });
                        var nameSpan = document.createElement("span");
                        nameSpan.style.cssText = "font-weight:600;font-size:13px;";
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
                                mLbl.style.cssText = "display:inline-flex;align-items:center;gap:6px;cursor:" + (isRequired ? "not-allowed" : "pointer") + ";padding:5px 12px;border:2px solid " + (isMarkChecked ? "#467b96" : "#e0e0e0") + ";border-radius:6px;font-size:13px;transition:all 0.15s;background:#fff;" + (isMarkChecked ? "background:rgba(70,123,150,0.08);" : "");
                                var mInp = document.createElement("input");
                                mInp.type = "checkbox";
                                mInp.value = mVal;
                                mInp.checked = isMarkChecked || isRequired;
                                mInp.disabled = isRequired;
                                mInp.className = "dear-art-mark-chk";
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
                                    var tipSpan = document.createElement("span");
                                    tipSpan.style.cssText = "font-size:11px;color:#888;font-weight:normal;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;";
                                    tipSpan.textContent = markDef.tooltip;
                                    mLbl.appendChild(tipSpan);
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
                    codeDiv.style.cssText = "margin-bottom:10px;padding:12px;background:#fff;border:1px solid #ddd;border-radius:6px;";
                    var codeTitle = document.createElement("div");
                    codeTitle.style.cssText = "font-weight:600;margin-bottom:8px;font-size:14px;color:#333;";
                    codeTitle.textContent = "代码许可协议（单选）";
                    codeDiv.appendChild(codeTitle);

                    var codeBDiv = document.createElement("div");
                    codeBDiv.style.cssText = "display:flex;flex-wrap:wrap;gap:8px;";

                    var isCodeDefaultChecked = !codeSel;
                    var codeDefLbl = document.createElement("label");
                    codeDefLbl.style.cssText = "display:inline-flex;align-items:center;gap:6px;cursor:pointer;padding:5px 10px;border:2px solid " + (isCodeDefaultChecked ? "#467b96" : "#e0e0e0") + ";border-radius:6px;font-size:13px;transition:all 0.15s;background:#fff;" + (isCodeDefaultChecked ? "background:rgba(70,123,150,0.05);" : "");
                    var codeDefInp = document.createElement("input");
                    codeDefInp.type = "radio";
                    codeDefInp.name = "dear_art_code_license";
                    codeDefInp.value = "";
                    codeDefInp.checked = isCodeDefaultChecked;
                    codeDefInp.style.cssText = "margin:0;width:auto;";
                    codeDefInp.addEventListener("change", function () { syncCode(); render(); });
                    var codeDefSpan = document.createElement("span");
                    codeDefSpan.style.cssText = "font-weight:600;color:#666;font-size:13px;";
                    codeDefSpan.textContent = "不设置 (无)";
                    codeDefLbl.appendChild(codeDefInp);
                    codeDefLbl.appendChild(codeDefSpan);
                    codeBDiv.appendChild(codeDefLbl);

                    codePresets.forEach(function (preset) {
                        var isChecked = codeSel === preset.id;
                        var lbl = document.createElement("label");
                        lbl.style.cssText = "display:inline-flex;align-items:center;gap:6px;cursor:pointer;padding:5px 10px;border:2px solid " + (isChecked ? "#467b96" : "#e0e0e0") + ";border-radius:6px;font-size:13px;transition:all 0.15s;background:#fff;" + (isChecked ? "background:rgba(70,123,150,0.05);" : "");
                        var inp = document.createElement("input");
                        inp.type = "radio";
                        inp.name = "dear_art_code_license";
                        inp.value = preset.id;
                        inp.checked = isChecked;
                        inp.style.cssText = "margin:0;width:auto;";
                        inp.addEventListener("change", function () { syncCode(); render(); });
                        var nameSpan = document.createElement("span");
                        nameSpan.style.cssText = "font-weight:600;font-size:13px;";
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

                var btnRow = document.createElement("div");
                btnRow.style.cssText = "display:flex;gap:6px;margin-top:6px;";
                var clearBtn = document.createElement("button");
                clearBtn.type = "button";
                clearBtn.textContent = "清除已选";
                clearBtn.style.cssText = BTN_SM_SECONDARY;
                clearBtn.addEventListener("click", function () { textarea.value = ""; render(); });
                btnRow.appendChild(clearBtn);
                var jsonBtn = document.createElement("button");
                jsonBtn.type = "button";
                jsonBtn.textContent = "显示/编辑 JSON";
                jsonBtn.style.cssText = BTN_SM_SECONDARY;
                jsonBtn.addEventListener("click", function () {
                    textarea.style.display = textarea.style.display === "none" ? "block" : "none";
                });
                btnRow.appendChild(jsonBtn);
                container.appendChild(btnRow);
            }

            function syncContent() {
                var selected = parseJsonFromTA(textarea) || {};
                if (Array.isArray(selected)) selected = {};

                var checkedRadio = container.querySelector('input[name="dear_art_content_license"]:checked');
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
                        container.querySelectorAll(".dear-art-mark-chk:checked").forEach(function (chk) {
                            var val = chk.value;
                            if (marks.indexOf(val) === -1) marks.push(val);
                        });
                        selected.content = { type: groupId, marks: marks };
                    } else {
                        selected.content = { type: groupId };
                    }
                }
                textarea.value = JSON.stringify(selected);
            }

            function syncCode() {
                var selected = parseJsonFromTA(textarea) || {};
                if (Array.isArray(selected)) selected = {};
                var checkedRadio = container.querySelector('input[name="dear_art_code_license"]:checked');
                if (!checkedRadio || !checkedRadio.value) {
                    delete selected.code;
                } else {
                    selected.code = checkedRadio.value;
                }
                textarea.value = JSON.stringify(selected);
            }

            render();
        }

        function updateVisibility() {
            var val = getControlVal('showCopyright');
            if (copyrightRow) {
                if (val === '1') {
                    copyrightRow.style.setProperty('display', 'flex', 'important');
                } else {
                    copyrightRow.style.setProperty('display', 'none', 'important');
                }
            }
        }

        var showCtrl = document.querySelector('select[name*="showCopyright"]') || document.querySelector('input[name*="showCopyright"]');
        if (showCtrl) {
            showCtrl.addEventListener('change', updateVisibility);
        }
        updateVisibility();

        return true;
    }

    function tryInit() {
        var res1 = initBadgeSelector();
        var res2 = initCopyrightSelector();
        return res1 && res2;
    }

    var attempts = 0;
    function pollInit() {
        attempts++;
        if (tryInit() || attempts > 40) return;
        setTimeout(pollInit, 100);
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", pollInit);
    } else {
        pollInit();
    }
})();
