/**
 * Dear Theme — AI Summary Admin Editor
 *
 * Enhanced management system for Typecho Theme Settings:
 * - Model ID management with historical summary re-binding
 * - Custom modal dialogs (alert, confirm, prompt) with zero browser default alerts
 * - Per-model statistics & cleanup of uncompleted entries
 * - Global orphaned summary cleaner
 * - Summary browser & search modal
 * - System dashboard & overview metrics
 */

(function () {
    "use strict";

    // ==========================================
    // Custom Modal Dialogs (Alert, Confirm, Prompt)
    // ==========================================

    let dialogOverlay = null;

    function getDialogOverlay() {
        if (!dialogOverlay) {
            dialogOverlay = document.getElementById("dear-ai-modal-dialog");
        }
        if (!dialogOverlay) {
            dialogOverlay = document.createElement("div");
            dialogOverlay.id = "dear-ai-modal-dialog";
            dialogOverlay.className = "dear-ai-modal-overlay";
            dialogOverlay.style.zIndex = "100002";
            dialogOverlay.innerHTML = `
                <div class="dear-ai-modal-card dear-ai-dialog-card">
                    <div class="dear-ai-modal-header">
                        <div class="dear-ai-modal-title" id="dear-ai-dialog-title">提示</div>
                        <button type="button" class="dear-ai-modal-close" id="dear-ai-dialog-close">&times;</button>
                    </div>
                    <div class="dear-ai-modal-body">
                        <div id="dear-ai-dialog-message" class="dear-ai-dialog-message"></div>
                        <div id="dear-ai-dialog-input-wrapper" class="dear-ai-dialog-input-wrapper" style="display:none;">
                            <input type="text" id="dear-ai-dialog-input" class="dear-ai-input dear-ai-input-code">
                        </div>
                    </div>
                    <div class="dear-ai-modal-footer">
                        <button type="button" id="dear-ai-dialog-cancel" class="dear-ai-btn dear-ai-btn-secondary">取消</button>
                        <button type="button" id="dear-ai-dialog-confirm" class="dear-ai-btn dear-ai-btn-primary">确定</button>
                    </div>
                </div>
            `;
            document.body.appendChild(dialogOverlay);
        }
        return dialogOverlay;
    }

    function openCustomDialog(options) {
        const overlay = getDialogOverlay();
        const titleEl = document.getElementById("dear-ai-dialog-title");
        const msgEl = document.getElementById("dear-ai-dialog-message");
        const inputWrap = document.getElementById("dear-ai-dialog-input-wrapper");
        const inputEl = document.getElementById("dear-ai-dialog-input");
        const cancelBtn = document.getElementById("dear-ai-dialog-cancel");
        const confirmBtn = document.getElementById("dear-ai-dialog-confirm");
        const closeBtn = document.getElementById("dear-ai-dialog-close");

        titleEl.textContent = options.title || "提示";
        msgEl.textContent = options.message || "";

        if (options.showInput) {
            inputWrap.style.display = "block";
            inputEl.value = options.defaultValue || "";
            setTimeout(() => inputEl.focus(), 50);
        } else {
            inputWrap.style.display = "none";
        }

        cancelBtn.style.display = options.showCancel ? "inline-flex" : "none";
        cancelBtn.textContent = options.cancelText || "取消";

        confirmBtn.textContent = options.confirmText || "确定";
        confirmBtn.className = "dear-ai-btn " + (options.confirmClass || "dear-ai-btn-primary");

        overlay.classList.add("active");

        function cleanup() {
            overlay.classList.remove("active");
            confirmBtn.removeEventListener("click", onConfirmClick);
            cancelBtn.removeEventListener("click", onCancelClick);
            closeBtn.removeEventListener("click", onCancelClick);
            inputEl.removeEventListener("keydown", onInputKey);
            document.removeEventListener("keydown", onDocKey);
        }

        function onConfirmClick() {
            const val = options.showInput ? inputEl.value : true;
            cleanup();
            if (options.onConfirm) options.onConfirm(val);
        }

        function onCancelClick() {
            cleanup();
            if (options.onCancel) options.onCancel();
        }

        function onInputKey(e) {
            if (e.key === "Enter") {
                e.preventDefault();
                onConfirmClick();
            }
        }

        function onDocKey(e) {
            if (e.key === "Escape") {
                e.preventDefault();
                onCancelClick();
            }
        }

        confirmBtn.addEventListener("click", onConfirmClick);
        cancelBtn.addEventListener("click", onCancelClick);
        closeBtn.addEventListener("click", onCancelClick);
        inputEl.addEventListener("keydown", onInputKey);
        document.addEventListener("keydown", onDocKey);
    }

    window.dearAlert = function (message, title = "提示") {
        return new Promise(resolve => {
            openCustomDialog({
                title: title,
                message: message,
                showCancel: false,
                confirmText: "确定",
                confirmClass: "dear-ai-btn-primary",
                onConfirm: () => resolve(true),
                onCancel: () => resolve(true)
            });
        });
    };

    window.dearConfirm = function (message, title = "确认操作", isDanger = false) {
        return new Promise(resolve => {
            openCustomDialog({
                title: title,
                message: message,
                showCancel: true,
                cancelText: "取消",
                confirmText: "确定",
                confirmClass: isDanger ? "dear-ai-btn-danger" : "dear-ai-btn-primary",
                onConfirm: () => resolve(true),
                onCancel: () => resolve(false)
            });
        });
    };

    window.dearPrompt = function (message, defaultValue = "", title = "请输入") {
        return new Promise(resolve => {
            openCustomDialog({
                title: title,
                message: message,
                showCancel: true,
                showInput: true,
                defaultValue: defaultValue,
                cancelText: "取消",
                confirmText: "确定",
                confirmClass: "dear-ai-btn-primary",
                onConfirm: val => resolve(val),
                onCancel: () => resolve(null)
            });
        });
    };

    // ==========================================
    // Main Admin Logic
    // ==========================================

    document.addEventListener("DOMContentLoaded", function () {
        const textarea = document.querySelector("textarea[name=Dear_aiModels]");
        if (!textarea) return;

        const form = textarea.closest("form");
        const apiBaseUrl = (window.DearAiAdminConfig && window.DearAiAdminConfig.apiEndpoint) 
            ? window.DearAiAdminConfig.apiEndpoint.replace(/\/$/, '') + '/?dear_ai_action=' 
            : '/?dear_ai_action=';

        // Hidden input for tracking model ID renames on form submission
        let renameInput = form ? form.querySelector("input[name=Dear_aiModelRenames]") : null;
        if (!renameInput && form) {
            renameInput = document.createElement("input");
            renameInput.type = "hidden";
            renameInput.name = "Dear_aiModelRenames";
            renameInput.value = "{}";
            form.appendChild(renameInput);
        }

        // Create main UI Container
        const container = document.createElement("div");
        container.id = "dear-ai-admin-container";
        textarea.parentElement.insertBefore(container, textarea);
        textarea.style.display = "none";

        // Global stats cache
        let cachedStats = null;
        // Tracking original IDs map for cards: index -> originalId
        const originalIdsMap = {};
        // Renames pending sync: { [oldId]: newId }
        const pendingRenames = {};

        // Utility: API Helper with safe error parsing
        function callApi(action, params = {}, method = 'GET') {
            let url = apiBaseUrl + encodeURIComponent(action);
            const options = { 
                method: method,
                credentials: 'same-origin'
            };

            if (method === 'GET') {
                const searchParams = new URLSearchParams(params);
                const queryStr = searchParams.toString();
                if (queryStr) {
                    url += (url.includes('?') ? '&' : '?') + queryStr;
                }
            } else {
                const formData = new FormData();
                for (const key in params) {
                    formData.append(key, params[key]);
                }
                options.body = formData;
            }

            return fetch(url, options)
                .then(r => {
                    return r.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error("Non-JSON API response:", text);
                            const cleanText = text.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
                            return { 
                                ok: false, 
                                error: cleanText ? cleanText.slice(0, 200) : ("HTTP " + r.status + " " + r.statusText) 
                            };
                        }
                    });
                })
                .catch(err => {
                    console.error("AI Summary API Error:", err);
                    return { ok: false, error: "网络请求异常: " + err.message };
                });
        }

        // Utility: Toast Notification
        function showToast(message, type = 'success') {
            let toast = document.getElementById("dear-ai-toast");
            if (!toast) {
                toast = document.createElement("div");
                toast.id = "dear-ai-toast";
                toast.className = "dear-ai-toast";
                document.body.appendChild(toast);
            }
            toast.className = "dear-ai-toast " + type + " show";
            const prefix = type === 'success' ? '[成功] ' : (type === 'error' ? '[失败] ' : '[提示] ');
            toast.textContent = prefix + message;
            setTimeout(() => {
                toast.classList.remove("show");
            }, 3500);
        }

        // Utility: Format Date
        function formatDateTime(ts) {
            if (!ts) return "暂无";
            const d = new Date(ts * 1000);
            const pad = n => String(n).padStart(2, "0");
            return d.getFullYear() + "-" + pad(d.getMonth() + 1) + "-" + pad(d.getDate()) + " " +
                   pad(d.getHours()) + ":" + pad(d.getMinutes());
        }

        // Utility: Parse Models JSON
        function parseModels() {
            try {
                const arr = JSON.parse(textarea.value) || [];
                arr.forEach((m, idx) => {
                    if (!m.id) {
                        m.id = m.model_name || ("model-" + (idx + 1));
                    }
                    if (originalIdsMap[idx] === undefined) {
                        originalIdsMap[idx] = m.id;
                    }
                });
                return arr;
            } catch (e) {
                return [];
            }
        }

        // Utility: Save Models JSON
        function saveModels(models) {
            textarea.value = JSON.stringify(models, null, 2);
            if (renameInput) {
                renameInput.value = JSON.stringify(pendingRenames);
            }
        }

        // Refresh Statistics
        function refreshStats(callback) {
            callApi("admin_stats").then(res => {
                if (res.ok) {
                    cachedStats = res;
                    updateStatsBadges();
                    if (callback) callback(res);
                }
            });
        }

        // Update stats on rendered cards
        function updateStatsBadges() {
            if (!cachedStats || !cachedStats.models) return;
            const statsMap = {};
            cachedStats.models.forEach(m => {
                statsMap[m.id] = m;
            });

            const cards = container.querySelectorAll(".dear-ai-card");
            cards.forEach(card => {
                const mid = card.dataset.modelId;
                const statsEl = card.querySelector(".dear-ai-stats-row");
                if (!statsEl) return;

                const st = statsMap[mid] || { total: 0, completed: 0, generating: 0, error: 0 };
                statsEl.innerHTML = `
                    <span class="dear-ai-stat-pill total">总计: ${st.total}</span>
                    <span class="dear-ai-stat-pill completed">已完成: ${st.completed}</span>
                    <span class="dear-ai-stat-pill generating">生成中: ${st.generating}</span>
                    <span class="dear-ai-stat-pill error">失败: ${st.error}</span>
                    <button type="button" class="dear-ai-btn dear-ai-btn-secondary dear-ai-btn-sm card-refresh-stats" title="刷新统计">刷新</button>
                `;

                const refreshBtn = statsEl.querySelector(".card-refresh-stats");
                if (refreshBtn) {
                    refreshBtn.addEventListener("click", () => {
                        refreshStats(() => showToast("已刷新统计数据", "success"));
                    });
                }
            });

            const orphanBtn = container.querySelector("#dear-ai-btn-clean-orphan");
            if (orphanBtn && cachedStats.orphaned_count > 0) {
                orphanBtn.innerHTML = `清理失效条目 <span class="dear-ai-stat-pill error" style="margin-left:4px;padding:1px 5px;">${cachedStats.orphaned_count}</span>`;
            } else if (orphanBtn) {
                orphanBtn.innerHTML = `清理失效条目`;
            }
        }

        // Render Main Admin Interface
        function render() {
            const models = parseModels();
            container.innerHTML = "";

            // 1. Header
            const header = document.createElement("div");
            header.className = "dear-ai-admin-header";
            header.innerHTML = `
                <div class="dear-ai-admin-title">
                    <span>AI 摘要模型与数据管理</span>
                    <span class="dear-ai-admin-badge">${models.length} 个配置模型</span>
                </div>
            `;
            container.appendChild(header);

            // 2. Global Toolbar
            const toolbar = document.createElement("div");
            toolbar.className = "dear-ai-toolbar";
            toolbar.innerHTML = `
                <button type="button" id="dear-ai-btn-overview" class="dear-ai-btn dear-ai-btn-primary">数据总览看板</button>
                <button type="button" id="dear-ai-btn-records" class="dear-ai-btn dear-ai-btn-slate">摘要记录管理</button>
                <button type="button" id="dear-ai-btn-clean-orphan" class="dear-ai-btn dear-ai-btn-warning">清理失效条目</button>
                <button type="button" id="dear-ai-btn-clean-uncompleted-all" class="dear-ai-btn dear-ai-btn-amber">清理全部未完成</button>
                <button type="button" id="dear-ai-btn-clean-ratelog" class="dear-ai-btn dear-ai-btn-teal">重置频次限制</button>
                <button type="button" id="dear-ai-btn-migrate-tool" class="dear-ai-btn dear-ai-btn-purple">历史数据关联迁移</button>
                <button type="button" id="dear-ai-btn-clean-all" class="dear-ai-btn dear-ai-btn-danger">清空全部缓存</button>
            `;
            container.appendChild(toolbar);

            toolbar.querySelector("#dear-ai-btn-overview").addEventListener("click", openOverviewModal);
            toolbar.querySelector("#dear-ai-btn-records").addEventListener("click", openRecordsModal);
            toolbar.querySelector("#dear-ai-btn-clean-orphan").addEventListener("click", handleCleanOrphaned);
            toolbar.querySelector("#dear-ai-btn-clean-uncompleted-all").addEventListener("click", handleCleanUncompletedAll);
            toolbar.querySelector("#dear-ai-btn-clean-ratelog").addEventListener("click", handleCleanRateLog);
            toolbar.querySelector("#dear-ai-btn-migrate-tool").addEventListener("click", openMigrationModal);
            toolbar.querySelector("#dear-ai-btn-clean-all").addEventListener("click", handleCleanAll);

            // 3. Model Cards List
            const listContainer = document.createElement("div");
            listContainer.className = "dear-ai-cards-list";

            models.forEach((m, idx) => {
                const card = document.createElement("div");
                card.className = "dear-ai-card";
                card.dataset.idx = idx;
                card.dataset.modelId = m.id || "";

                const origId = originalIdsMap[idx] || m.id;
                const isIdChanged = origId && origId !== m.id;

                card.innerHTML = `
                    <div class="dear-ai-card-header">
                        <div class="dear-ai-card-title">
                            <span>模型 #${idx + 1}</span>
                            ${idx === 0 ? '<span class="dear-ai-card-tag-default">默认模型</span>' : ''}
                        </div>
                        <div class="dear-ai-card-actions">
                            ${idx > 0 ? `<button type="button" class="dear-ai-btn dear-ai-btn-secondary dear-ai-btn-sm card-move-up" data-idx="${idx}" title="上移">上移</button>` : ''}
                            ${idx < models.length - 1 ? `<button type="button" class="dear-ai-btn dear-ai-btn-secondary dear-ai-btn-sm card-move-down" data-idx="${idx}" title="下移">下移</button>` : ''}
                            <button type="button" class="dear-ai-btn dear-ai-btn-danger dear-ai-btn-sm card-delete" data-idx="${idx}">删除模型</button>
                        </div>
                    </div>

                    <div class="dear-ai-grid">
                        <div class="dear-ai-field-group">
                            <label class="dear-ai-field-label">
                                <span>模型唯一识别码 ID <strong style="color:#e11d48;">*</strong></span>
                            </label>
                            <input type="text" class="dear-ai-input dear-ai-input-code ai-field" data-idx="${idx}" data-key="id" value="${m.id || ''}" placeholder="例如: gemini-flash-lite">
                            <div class="dear-ai-field-help">数据库关联主键。模型版本升级时保持此ID不变即可保留历史摘要。</div>
                            ${isIdChanged ? `
                                <div class="dear-ai-id-changed-tag">
                                    <span>原ID: <code>${origId}</code>（保存时将自动关联迁移所有历史摘要）</span>
                                    <a class="sync-now-link" data-idx="${idx}" data-old="${origId}" data-new="${m.id}">[立即同步更新]</a>
                                </div>
                            ` : ''}
                        </div>

                        <div class="dear-ai-field-group">
                            <label class="dear-ai-field-label">
                                <span>模型调用名称 (model) <strong style="color:#e11d48;">*</strong></span>
                            </label>
                            <input type="text" class="dear-ai-input dear-ai-input-code ai-field" data-idx="${idx}" data-key="model_name" value="${m.model_name || ''}" placeholder="例如: gemini-3.5-flash-lite">
                            <div class="dear-ai-field-help">OpenAI API请求体中传递的真实模型名称。</div>
                        </div>

                        <div class="dear-ai-field-group">
                            <label class="dear-ai-field-label">
                                <span>前端显示名称</span>
                            </label>
                            <input type="text" class="dear-ai-input ai-field" data-idx="${idx}" data-key="model_display" value="${m.model_display || ''}" placeholder="例如: Gemini 3.5 Flash Lite">
                            <div class="dear-ai-field-help">在文章页面模型切换下拉菜单中向访客展示的名称。</div>
                        </div>

                        <div class="dear-ai-field-group">
                            <label class="dear-ai-field-label">
                                <span>API 接口地址 (URL) <strong style="color:#e11d48;">*</strong></span>
                            </label>
                            <input type="text" class="dear-ai-input dear-ai-input-code ai-field" data-idx="${idx}" data-key="api_url" value="${m.api_url || ''}" placeholder="https://api.openai.com/v1">
                            <div class="dear-ai-field-help">API端点，会自动补充 /chat/completions。</div>
                        </div>

                        <div class="dear-ai-field-group" style="grid-column: 1 / -1;">
                            <label class="dear-ai-field-label">
                                <span>API 密钥 (API Key) <strong style="color:#e11d48;">*</strong></span>
                            </label>
                            <div class="dear-ai-input-wrapper">
                                <input type="password" class="dear-ai-input dear-ai-input-code ai-field ai-pwd-field" data-idx="${idx}" data-key="api_key" value="${m.api_key || ''}" placeholder="sk-...">
                                <button type="button" class="dear-ai-toggle-pwd" title="显示/隐藏密钥">显示</button>
                            </div>
                        </div>
                    </div>

                    <div class="dear-ai-card-footer">
                        <div class="dear-ai-stats-row">
                            <span class="dear-ai-stat-pill total">加载统计中...</span>
                        </div>
                        <div class="dear-ai-card-buttons">
                            <button type="button" class="dear-ai-btn dear-ai-btn-warning dear-ai-btn-sm card-clean-uncompleted" data-mid="${m.id}">清理未完成条目</button>
                            <button type="button" class="dear-ai-btn dear-ai-btn-danger dear-ai-btn-sm card-clean-model" data-mid="${m.id}">清空该模型条目</button>
                            <button type="button" class="dear-ai-btn dear-ai-btn-purple dear-ai-btn-sm card-migrate-to-here" data-mid="${m.id}">迁移关联旧ID</button>
                        </div>
                    </div>
                `;

                listContainer.appendChild(card);
            });

            container.appendChild(listContainer);

            // 4. Bottom Actions
            const bottomBar = document.createElement("div");
            bottomBar.className = "dear-ai-bottom-bar";
            bottomBar.innerHTML = `
                <div>
                    <button type="button" id="dear-ai-btn-add" class="dear-ai-btn dear-ai-btn-primary">+ 添加新模型</button>
                    <button type="button" id="dear-ai-btn-toggle-json" class="dear-ai-btn dear-ai-btn-secondary" style="margin-left: 8px;">显示 / 编辑原始 JSON</button>
                </div>
                <div style="font-size: 12px; color: #64748b;">
                    提示：修改配置后请点击页面最下方的【保存设置】以生效。
                </div>
            `;
            container.appendChild(bottomBar);

            bottomBar.querySelector("#dear-ai-btn-add").addEventListener("click", () => {
                const current = parseModels();
                const newIdx = current.length + 1;
                current.push({
                    id: "model-" + newIdx,
                    model_name: "",
                    model_display: "",
                    api_url: "",
                    api_key: ""
                });
                originalIdsMap[current.length - 1] = "model-" + newIdx;
                saveModels(current);
                render();
            });

            bottomBar.querySelector("#dear-ai-btn-toggle-json").addEventListener("click", () => {
                textarea.style.display = (textarea.style.display === "none") ? "block" : "none";
            });

            bindCardEvents();
            refreshStats();
        }

        // Bind interactive events for card elements
        function bindCardEvents() {
            container.querySelectorAll(".ai-field").forEach(input => {
                input.addEventListener("input", function () {
                    const idx = parseInt(this.dataset.idx);
                    const key = this.dataset.key;
                    const models = parseModels();
                    if (!models[idx]) return;

                    models[idx][key] = this.value;

                    if (key === 'id') {
                        const origId = originalIdsMap[idx];
                        if (origId && origId !== this.value) {
                            pendingRenames[origId] = this.value;
                        } else if (origId && origId === this.value) {
                            delete pendingRenames[origId];
                        }
                    }

                    saveModels(models);

                    if (key === 'id') {
                        const card = this.closest(".dear-ai-card");
                        card.dataset.modelId = this.value;
                    }
                });

                if (input.dataset.key === 'id') {
                    input.addEventListener("change", function () {
                        render();
                    });
                }
            });

            container.querySelectorAll(".dear-ai-toggle-pwd").forEach(btn => {
                btn.addEventListener("click", function () {
                    const pwdInput = this.parentElement.querySelector(".ai-pwd-field");
                    if (pwdInput) {
                        const isPwd = (pwdInput.type === "password");
                        pwdInput.type = isPwd ? "text" : "password";
                        this.textContent = isPwd ? "隐藏" : "显示";
                    }
                });
            });

            container.querySelectorAll(".card-delete").forEach(btn => {
                btn.addEventListener("click", async function () {
                    const idx = parseInt(this.dataset.idx);
                    const models = parseModels();
                    const m = models[idx];
                    const confirmed = await window.dearConfirm(
                        `确定要删除模型 #${idx + 1} (${m.model_display || m.id}) 吗？\n注意：此操作仅在当前配置中移除该模型，其历史摘要条目不会被自动删除。如需清理历史摘要，请使用【清理失效条目】。`,
                        "删除模型确认",
                        true
                    );
                    if (!confirmed) return;

                    models.splice(idx, 1);
                    const newOrigMap = {};
                    models.forEach((item, i) => {
                        newOrigMap[i] = item.id;
                    });
                    Object.assign(originalIdsMap, newOrigMap);
                    saveModels(models);
                    render();
                    showToast("已移除模型配置", "success");
                });
            });

            container.querySelectorAll(".card-move-up").forEach(btn => {
                btn.addEventListener("click", function () {
                    const idx = parseInt(this.dataset.idx);
                    if (idx <= 0) return;
                    const models = parseModels();
                    const temp = models[idx];
                    models[idx] = models[idx - 1];
                    models[idx - 1] = temp;
                    saveModels(models);
                    render();
                });
            });

            container.querySelectorAll(".card-move-down").forEach(btn => {
                btn.addEventListener("click", function () {
                    const idx = parseInt(this.dataset.idx);
                    const models = parseModels();
                    if (idx >= models.length - 1) return;
                    const temp = models[idx];
                    models[idx] = models[idx + 1];
                    models[idx + 1] = temp;
                    saveModels(models);
                    render();
                });
            });

            container.querySelectorAll(".sync-now-link").forEach(link => {
                link.addEventListener("click", async function () {
                    const oldId = this.dataset.old;
                    const newId = this.dataset.new;
                    const idx = parseInt(this.dataset.idx);
                    if (!oldId || !newId || oldId === newId) return;

                    const confirmed = await window.dearConfirm(
                        `确定立即将数据库中所有关联模型ID [${oldId}] 的历史摘要更新为 [${newId}] 吗？`,
                        "同步更新关联摘要确认"
                    );
                    if (!confirmed) return;

                    callApi("admin_rename_model_id", { old_id: oldId, new_id: newId }, "POST").then(res => {
                        if (res.ok) {
                            showToast(res.message || "已成功同步更新数据库", "success");
                            originalIdsMap[idx] = newId;
                            delete pendingRenames[oldId];
                            saveModels(parseModels());
                            render();
                        } else {
                            showToast(res.error || "同步失败", "error");
                        }
                    });
                });
            });

            container.querySelectorAll(".card-clean-uncompleted").forEach(btn => {
                btn.addEventListener("click", async function () {
                    const mid = this.dataset.mid;
                    if (!mid) {
                        showToast("模型ID为空，无法清理", "warning");
                        return;
                    }
                    const confirmed = await window.dearConfirm(
                        `确定清理模型 [${mid}] 下全部状态为【生成中】或【失败】的未完成摘要条目吗？已完成的摘要不会受影响。`,
                        "清理未完成条目确认"
                    );
                    if (!confirmed) return;

                    callApi("admin_clean_uncompleted", { model_id: mid }, "POST").then(res => {
                        if (res.ok) {
                            showToast(res.message || "清理完成", "success");
                            refreshStats();
                        } else {
                            showToast(res.error || "清理失败", "error");
                        }
                    });
                });
            });

            container.querySelectorAll(".card-clean-model").forEach(btn => {
                btn.addEventListener("click", async function () {
                    const mid = this.dataset.mid;
                    if (!mid) return;
                    const confirmed = await window.dearConfirm(
                        `危险操作：确定要清空模型 [${mid}] 下的所有摘要（包含已完成的条目）吗？此操作无法撤销！`,
                        "清空模型全部摘要确认",
                        true
                    );
                    if (!confirmed) return;

                    callApi("admin_clean_model", { model_id: mid }, "POST").then(res => {
                        if (res.ok) {
                            showToast(res.message || "已清空该模型摘要", "success");
                            refreshStats();
                        } else {
                            showToast(res.error || "清空失败", "error");
                        }
                    });
                });
            });

            container.querySelectorAll(".card-migrate-to-here").forEach(btn => {
                btn.addEventListener("click", async function () {
                    const currentId = this.dataset.mid;
                    const oldId = await window.dearPrompt(
                        `请输入需要迁移至当前模型 [${currentId}] 的旧模型ID：`,
                        "",
                        "迁移历史摘要至此模型"
                    );
                    if (!oldId || !oldId.trim()) return;
                    const trimmedOldId = oldId.trim();
                    if (trimmedOldId === currentId) {
                        await window.dearAlert("新旧模型ID相同，无需迁移", "提示");
                        return;
                    }

                    const confirmed = await window.dearConfirm(
                        `确定将所有原属于 [${trimmedOldId}] 的历史摘要迁移并关联至 [${currentId}] 吗？`,
                        "确认数据迁移"
                    );
                    if (!confirmed) return;

                    callApi("admin_rename_model_id", { old_id: trimmedOldId, new_id: currentId }, "POST").then(res => {
                        if (res.ok) {
                            showToast(res.message || "迁移成功", "success");
                            refreshStats();
                        } else {
                            showToast(res.error || "迁移失败", "error");
                        }
                    });
                });
            });
        }

        // ==========================================
        // Toolbar Handlers
        // ==========================================

        async function handleCleanOrphaned() {
            const confirmed = await window.dearConfirm(
                "确定清理失效的摘要条目吗？\n这将清除当前模型列表中已不存在的模型对应的所有历史条目，以及已删除文章的孤立摘要和过期频次日志。",
                "清理失效条目确认"
            );
            if (!confirmed) return;

            callApi("admin_clean_orphaned", {}, "POST").then(res => {
                if (res.ok) {
                    showToast(res.message || "清理失效条目成功", "success");
                    refreshStats();
                } else {
                    showToast(res.error || "清理失败", "error");
                }
            });
        }

        async function handleCleanUncompletedAll() {
            const confirmed = await window.dearConfirm(
                "确定清理全部模型下所有状态非【已完成】（即生成中、失败等）的摘要条目吗？\n这可用于重置由于网络中断或超时卡住的任务队列。",
                "清理全站未完成条目确认"
            );
            if (!confirmed) return;

            callApi("admin_clean_uncompleted", { model_id: "all" }, "POST").then(res => {
                if (res.ok) {
                    showToast(res.message || "已清理全部未完成条目", "success");
                    refreshStats();
                } else {
                    showToast(res.error || "清理失败", "error");
                }
            });
        }

        async function handleCleanRateLog() {
            const confirmed = await window.dearConfirm(
                "确定清空全部请求频次限制记录吗？\n清空后将重置全局及单文章限流计数器。",
                "重置频次记录确认"
            );
            if (!confirmed) return;

            callApi("admin_clean_rate_log", {}, "POST").then(res => {
                if (res.ok) {
                    showToast(res.message || "频次记录已清空", "success");
                    refreshStats();
                } else {
                    showToast(res.error || "重置失败", "error");
                }
            });
        }

        async function handleCleanAll() {
            const step1 = await window.dearConfirm(
                "危险操作：确定要清空数据库中存储的【全部文章AI摘要缓存】吗？\n所有文章的历史摘要都将被彻底删除且不可恢复！\n\n是否继续？",
                "清空全部AI摘要缓存",
                true
            );
            if (!step1) return;

            const step2 = await window.dearConfirm(
                "再次确认：真的要清空全部摘要吗？请确认备份！",
                "二次确认",
                true
            );
            if (!step2) return;

            callApi("admin_clean_all", {}, "POST").then(res => {
                if (res.ok) {
                    showToast(res.message || "全站AI摘要已彻底清空", "success");
                    refreshStats();
                } else {
                    showToast(res.error || "清空失败", "error");
                }
            });
        }

        // ==========================================
        // Modal 1: Overview Dashboard
        // ==========================================

        function openOverviewModal() {
            let overlay = document.getElementById("dear-ai-modal-overview");
            if (!overlay) {
                overlay = document.createElement("div");
                overlay.id = "dear-ai-modal-overview";
                overlay.className = "dear-ai-modal-overlay";
                overlay.innerHTML = `
                    <div class="dear-ai-modal-card">
                        <div class="dear-ai-modal-header">
                            <div class="dear-ai-modal-title">AI 摘要数据总览与健康看板</div>
                            <button type="button" class="dear-ai-modal-close" data-close="overview">&times;</button>
                        </div>
                        <div class="dear-ai-modal-body" id="dear-ai-overview-body">
                            <div style="text-align:center;padding:30px;color:#94a3b8;">正在加载数据...</div>
                        </div>
                        <div class="dear-ai-modal-footer">
                            <button type="button" class="dear-ai-btn dear-ai-btn-secondary" data-close="overview">关闭</button>
                        </div>
                    </div>
                `;
                document.body.appendChild(overlay);

                overlay.querySelectorAll("[data-close=overview]").forEach(el => {
                    el.addEventListener("click", () => overlay.classList.remove("active"));
                });
                overlay.addEventListener("click", e => {
                    if (e.target === overlay) overlay.classList.remove("active");
                });
            }

            overlay.classList.add("active");
            const body = document.getElementById("dear-ai-overview-body");
            body.innerHTML = `<div style="text-align:center;padding:30px;color:#94a3b8;">正在加载数据...</div>`;

            callApi("admin_stats").then(res => {
                if (!res.ok) {
                    body.innerHTML = `<div style="color:#ef4444;padding:20px;">加载失败: ${res.error}</div>`;
                    return;
                }

                const covPct = res.total_articles > 0 
                    ? Math.round((res.articles_with_summary / res.total_articles) * 100) 
                    : 0;

                let modelsTableHtml = "";
                res.models.forEach(m => {
                    modelsTableHtml += `
                        <tr>
                            <td><strong>${m.id}</strong></td>
                            <td>${m.display || '-'}</td>
                            <td><code>${m.name || '-'}</code></td>
                            <td><span class="dear-ai-badge dear-ai-badge-success">${m.completed}</span></td>
                            <td><span class="dear-ai-badge dear-ai-badge-warning">${m.generating}</span></td>
                            <td><span class="dear-ai-badge dear-ai-badge-danger">${m.error}</span></td>
                            <td>${formatDateTime(m.latest_updated_at)}</td>
                        </tr>
                    `;
                });

                let orphanSectionHtml = "";
                if (res.orphaned_count > 0) {
                    let orphanListHtml = res.orphaned_models.map(om => 
                        `<span style="margin-right:10px;">ID: <code>${om.id}</code> (${om.count}条)</span>`
                    ).join("");

                    orphanSectionHtml = `
                        <div style="margin-top:20px;padding:14px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
                            <div>
                                <strong style="color:#b91c1c;">检测到 ${res.orphaned_count} 条失效历史摘要</strong>
                                <div style="font-size:12px;color:#7f1d1d;margin-top:4px;">这些摘要所属的模型ID在当前配置中已不存在：${orphanListHtml}</div>
                            </div>
                            <button type="button" class="dear-ai-btn dear-ai-btn-warning dear-ai-btn-sm" id="modal-clean-orphan-btn">立即清理失效条目</button>
                        </div>
                    `;
                }

                body.innerHTML = `
                    <div class="dear-ai-metric-grid">
                        <div class="dear-ai-metric-box">
                            <span class="dear-ai-metric-label">全站文章总数</span>
                            <span class="dear-ai-metric-value">${res.total_articles}</span>
                            <span class="dear-ai-metric-sub">已发布文章</span>
                        </div>
                        <div class="dear-ai-metric-box">
                            <span class="dear-ai-metric-label">已生成摘要文章</span>
                            <span class="dear-ai-metric-value">${res.articles_with_summary}</span>
                            <span class="dear-ai-metric-sub">覆盖率: ${covPct}%</span>
                        </div>
                        <div class="dear-ai-metric-box">
                            <span class="dear-ai-metric-label">数据库摘要总数</span>
                            <span class="dear-ai-metric-value">${res.total_summaries}</span>
                            <span class="dear-ai-metric-sub">完成:${res.total_completed} / 异常:${res.total_error + res.total_generating}</span>
                        </div>
                        <div class="dear-ai-metric-box">
                            <span class="dear-ai-metric-label">频次日志记录</span>
                            <span class="dear-ai-metric-value">${res.rate_logs_count}</span>
                            <span class="dear-ai-metric-sub">限流检查日志</span>
                        </div>
                    </div>

                    <div class="dear-ai-progress-wrapper">
                        <div class="dear-ai-progress-label">
                            <span>文章摘要覆盖进度</span>
                            <span>${res.articles_with_summary} / ${res.total_articles} (${covPct}%)</span>
                        </div>
                        <div class="dear-ai-progress-track">
                            <div class="dear-ai-progress-fill" style="width: ${covPct}%;"></div>
                        </div>
                    </div>

                    <div style="font-weight:700;font-size:13px;color:#1e293b;margin:18px 0 8px 0;">各模型数据统计明细</div>
                    <div class="dear-ai-table-container">
                        <table class="dear-ai-table">
                            <thead>
                                <tr>
                                    <th>模型唯一ID</th>
                                    <th>显示名称</th>
                                    <th>接口调用名</th>
                                    <th>已完成</th>
                                    <th>生成中</th>
                                    <th>失败</th>
                                    <th>最新更新时间</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${modelsTableHtml || '<tr><td colspan="7" style="text-align:center;color:#94a3b8;">暂无模型数据</td></tr>'}
                            </tbody>
                        </table>
                    </div>

                    ${orphanSectionHtml}
                `;

                const cleanOrphanBtn = body.querySelector("#modal-clean-orphan-btn");
                if (cleanOrphanBtn) {
                    cleanOrphanBtn.addEventListener("click", () => {
                        overlay.classList.remove("active");
                        handleCleanOrphaned();
                    });
                }
            });
        }

        // ==========================================
        // Modal 2: Summary Records Browser
        // ==========================================

        function openRecordsModal() {
            let overlay = document.getElementById("dear-ai-modal-records");
            if (!overlay) {
                overlay = document.createElement("div");
                overlay.id = "dear-ai-modal-records";
                overlay.className = "dear-ai-modal-overlay";
                overlay.innerHTML = `
                    <div class="dear-ai-modal-card" style="max-width: 980px;">
                        <div class="dear-ai-modal-header">
                            <div class="dear-ai-modal-title">摘要记录明细与管理</div>
                            <button type="button" class="dear-ai-modal-close" data-close="records">&times;</button>
                        </div>
                        <div class="dear-ai-modal-body">
                            <div class="dear-ai-filter-bar">
                                <select id="dear-ai-filter-model" class="dear-ai-select">
                                    <option value="">全部模型</option>
                                </select>
                                <select id="dear-ai-filter-status" class="dear-ai-select">
                                    <option value="">全部状态</option>
                                    <option value="completed">已完成 (completed)</option>
                                    <option value="generating">生成中 (generating)</option>
                                    <option value="error">失败 (error)</option>
                                </select>
                                <input type="text" id="dear-ai-filter-search" class="dear-ai-filter-input" placeholder="搜索文章标题 / CID / 摘要内容...">
                                <button type="button" id="dear-ai-filter-submit" class="dear-ai-btn dear-ai-btn-primary dear-ai-btn-sm">筛选</button>
                                <button type="button" id="dear-ai-filter-reset" class="dear-ai-btn dear-ai-btn-secondary dear-ai-btn-sm">重置</button>
                            </div>
                            <div class="dear-ai-table-container">
                                <table class="dear-ai-table">
                                    <thead>
                                        <tr>
                                            <th style="width:50px;">CID</th>
                                            <th>文章标题</th>
                                            <th>模型ID</th>
                                            <th>状态</th>
                                            <th>内容预览 / 错误</th>
                                            <th>更新时间</th>
                                            <th style="width:120px;">操作</th>
                                        </tr>
                                    </thead>
                                    <tbody id="dear-ai-records-tbody">
                                        <tr><td colspan="7" style="text-align:center;padding:20px;color:#94a3b8;">加载中...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="dear-ai-pagination">
                                <span id="dear-ai-page-info">共 0 条记录</span>
                                <div style="display:flex;gap:6px;">
                                    <button type="button" id="dear-ai-prev-page" class="dear-ai-btn dear-ai-btn-secondary dear-ai-btn-sm">上一页</button>
                                    <button type="button" id="dear-ai-next-page" class="dear-ai-btn dear-ai-btn-secondary dear-ai-btn-sm">下一页</button>
                                </div>
                            </div>
                        </div>
                        <div class="dear-ai-modal-footer">
                            <button type="button" class="dear-ai-btn dear-ai-btn-secondary" data-close="records">关闭</button>
                        </div>
                    </div>
                `;
                document.body.appendChild(overlay);

                overlay.querySelectorAll("[data-close=records]").forEach(el => {
                    el.addEventListener("click", () => overlay.classList.remove("active"));
                });
                overlay.addEventListener("click", e => {
                    if (e.target === overlay) overlay.classList.remove("active");
                });

                const modelSelect = overlay.querySelector("#dear-ai-filter-model");
                const models = parseModels();
                models.forEach(m => {
                    const opt = document.createElement("option");
                    opt.value = m.id;
                    opt.textContent = `${m.model_display || m.id} (${m.id})`;
                    modelSelect.appendChild(opt);
                });

                let currentPage = 1;

                function loadList(page = 1) {
                    currentPage = page;
                    const modelId = modelSelect.value;
                    const status = overlay.querySelector("#dear-ai-filter-status").value;
                    const search = overlay.querySelector("#dear-ai-filter-search").value.trim();
                    const tbody = overlay.querySelector("#dear-ai-records-tbody");
                    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:20px;color:#94a3b8;">正在加载...</td></tr>`;

                    callApi("admin_list_summaries", {
                        page: page,
                        pageSize: 10,
                        model_id: modelId,
                        status: status,
                        search: search
                    }).then(res => {
                        if (!res.ok) {
                            tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;color:#ef4444;padding:20px;">加载失败: ${res.error}</td></tr>`;
                            return;
                        }

                        overlay.querySelector("#dear-ai-page-info").textContent = `共 ${res.total} 条记录，当前第 ${res.page} / ${res.totalPages} 页`;
                        overlay.querySelector("#dear-ai-prev-page").disabled = (res.page <= 1);
                        overlay.querySelector("#dear-ai-next-page").disabled = (res.page >= res.totalPages);

                        if (!res.list || res.list.length === 0) {
                            tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:25px;color:#94a3b8;">没有找到符合条件的摘要记录</td></tr>`;
                            return;
                        }

                        tbody.innerHTML = "";
                        res.list.forEach(item => {
                            const tr = document.createElement("tr");

                            let statusBadge = "";
                            if (item.status === 'completed') {
                                statusBadge = `<span class="dear-ai-badge dear-ai-badge-success">已完成</span>`;
                            } else if (item.status === 'generating') {
                                statusBadge = `<span class="dear-ai-badge dear-ai-badge-warning">生成中</span>`;
                            } else if (item.status === 'error') {
                                statusBadge = `<span class="dear-ai-badge dear-ai-badge-danger">失败</span>`;
                            } else {
                                statusBadge = `<span class="dear-ai-badge dear-ai-badge-info">${item.status}</span>`;
                            }

                            const snippetText = item.status === 'error' 
                                ? `<span style="color:#ef4444;">错误: ${item.error_message || '未知错误'}</span>`
                                : (item.summary_snippet || '(空)');

                            tr.innerHTML = `
                                <td>${item.cid}</td>
                                <td><strong>${item.title}</strong></td>
                                <td><code>${item.model_id}</code></td>
                                <td>${statusBadge}</td>
                                <td style="max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${item.summary_snippet || ''}">${snippetText}</td>
                                <td>${formatDateTime(item.updated_at)}</td>
                                <td>
                                    <button type="button" class="dear-ai-btn dear-ai-btn-slate dear-ai-btn-sm item-preview" data-id="${item.id}">查看详情</button>
                                    <button type="button" class="dear-ai-btn dear-ai-btn-danger dear-ai-btn-sm item-delete" data-id="${item.id}" title="删除">删除</button>
                                </td>
                            `;

                            tr.querySelector(".item-preview").addEventListener("click", () => {
                                openDetailModal(item);
                            });

                            tr.querySelector(".item-delete").addEventListener("click", async () => {
                                const confirmed = await window.dearConfirm(
                                    `确定删除文章 [${item.title}] 在模型 [${item.model_id}] 下的这条摘要记录吗？`,
                                    "删除单条摘要确认",
                                    true
                                );
                                if (!confirmed) return;

                                callApi("admin_delete_summary", { id: item.id }, "POST").then(dRes => {
                                    if (dRes.ok) {
                                        showToast("已删除该摘要条目", "success");
                                        loadList(currentPage);
                                        refreshStats();
                                    } else {
                                        showToast(dRes.error || "删除失败", "error");
                                    }
                                });
                            });

                            tbody.appendChild(tr);
                        });
                    });
                }

                overlay.querySelector("#dear-ai-filter-submit").addEventListener("click", () => loadList(1));
                overlay.querySelector("#dear-ai-filter-reset").addEventListener("click", () => {
                    modelSelect.value = "";
                    overlay.querySelector("#dear-ai-filter-status").value = "";
                    overlay.querySelector("#dear-ai-filter-search").value = "";
                    loadList(1);
                });
                overlay.querySelector("#dear-ai-prev-page").addEventListener("click", () => {
                    if (currentPage > 1) loadList(currentPage - 1);
                });
                overlay.querySelector("#dear-ai-next-page").addEventListener("click", () => {
                    loadList(currentPage + 1);
                });

                overlay.loadList = loadList;
            }

            overlay.classList.add("active");
            overlay.loadList(1);
        }

        // Detail Preview Modal
        function openDetailModal(item) {
            let overlay = document.getElementById("dear-ai-modal-detail");
            if (!overlay) {
                overlay = document.createElement("div");
                overlay.id = "dear-ai-modal-detail";
                overlay.className = "dear-ai-modal-overlay";
                overlay.style.zIndex = "10001";
                overlay.innerHTML = `
                    <div class="dear-ai-modal-card" style="max-width: 680px;">
                        <div class="dear-ai-modal-header">
                            <div class="dear-ai-modal-title" id="dear-ai-detail-title">摘要详情</div>
                            <button type="button" class="dear-ai-modal-close" data-close="detail">&times;</button>
                        </div>
                        <div class="dear-ai-modal-body" id="dear-ai-detail-content"></div>
                        <div class="dear-ai-modal-footer">
                            <button type="button" class="dear-ai-btn dear-ai-btn-secondary" data-close="detail">关闭</button>
                        </div>
                    </div>
                `;
                document.body.appendChild(overlay);

                overlay.querySelectorAll("[data-close=detail]").forEach(el => {
                    el.addEventListener("click", () => overlay.classList.remove("active"));
                });
                overlay.addEventListener("click", e => {
                    if (e.target === overlay) overlay.classList.remove("active");
                });
            }

            document.getElementById("dear-ai-detail-title").textContent = `文章: ${item.title} (CID: ${item.cid})`;
            const contentBox = document.getElementById("dear-ai-detail-content");

            let errorHtml = "";
            if (item.error_message) {
                errorHtml = `<div style="padding:10px;background:#fee2e2;border:1px solid #fca5a5;border-radius:6px;color:#991b1b;margin-bottom:12px;font-size:12px;"><strong>错误原因：</strong> ${item.error_message}</div>`;
            }

            contentBox.innerHTML = `
                <div style="font-size:12px;color:#64748b;margin-bottom:12px;display:flex;gap:14px;flex-wrap:wrap;">
                    <span>模型ID: <code>${item.model_id}</code></span>
                    <span>接口模型: <code>${item.model_name}</code></span>
                    <span>状态: <strong>${item.status}</strong></span>
                    <span>更新时间: ${formatDateTime(item.updated_at)}</span>
                </div>
                ${errorHtml}
                <div style="font-size:12px;font-weight:700;color:#334155;margin-bottom:6px;">摘要正文：</div>
                <div style="padding:14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;line-height:1.6;white-space:pre-wrap;max-height:350px;overflow-y:auto;">${item.summary_full || '(暂无内容)'}</div>
            `;

            overlay.classList.add("active");
        }

        // ==========================================
        // Modal 3: Manual ID Migration Tool
        // ==========================================

        function openMigrationModal() {
            let overlay = document.getElementById("dear-ai-modal-migration");
            if (!overlay) {
                overlay = document.createElement("div");
                overlay.id = "dear-ai-modal-migration";
                overlay.className = "dear-ai-modal-overlay";
                overlay.innerHTML = `
                    <div class="dear-ai-modal-card" style="max-width: 540px;">
                        <div class="dear-ai-modal-header">
                            <div class="dear-ai-modal-title">历史数据模型ID关联迁移</div>
                            <button type="button" class="dear-ai-modal-close" data-close="migration">&times;</button>
                        </div>
                        <div class="dear-ai-modal-body">
                            <p style="font-size:13px;line-height:1.5;color:#475569;margin-bottom:16px;">
                                如果您之前修改了模型配置中的调用名称或识别码，导致原先生成的摘要丢失关联，可以通过此工具将旧ID下所有的历史摘要批量迁移并重新关联至指定的新ID。
                            </p>
                            <div class="dear-ai-field-group" style="margin-bottom:12px;">
                                <label class="dear-ai-field-label">原旧模型ID (Old ID) *</label>
                                <input type="text" id="migration-old-id" class="dear-ai-input dear-ai-input-code" placeholder="例如旧的调用名: gemini-3.5-flash-lite">
                            </div>
                            <div class="dear-ai-field-group" style="margin-bottom:16px;">
                                <label class="dear-ai-field-label">目标新模型ID (New ID) *</label>
                                <input type="text" id="migration-new-id" class="dear-ai-input dear-ai-input-code" placeholder="例如新的稳定识别码: gemini-flash-lite">
                            </div>
                            <div style="font-size:11px;color:#94a3b8;">
                                提示：迁移时如目标ID在同一文章下已有摘要，将保留较新的条目以防冲突。
                            </div>
                        </div>
                        <div class="dear-ai-modal-footer">
                            <button type="button" class="dear-ai-btn dear-ai-btn-secondary" data-close="migration">取消</button>
                            <button type="button" id="migration-confirm-btn" class="dear-ai-btn dear-ai-btn-purple">开始关联迁移</button>
                        </div>
                    </div>
                `;
                document.body.appendChild(overlay);

                overlay.querySelectorAll("[data-close=migration]").forEach(el => {
                    el.addEventListener("click", () => overlay.classList.remove("active"));
                });
                overlay.addEventListener("click", e => {
                    if (e.target === overlay) overlay.classList.remove("active");
                });

                overlay.querySelector("#migration-confirm-btn").addEventListener("click", async () => {
                    const oldId = overlay.querySelector("#migration-old-id").value.trim();
                    const newId = overlay.querySelector("#migration-new-id").value.trim();
                    if (!oldId || !newId) {
                        await window.dearAlert("请完整填写原模型ID和目标新模型ID", "填写提示");
                        return;
                    }
                    if (oldId === newId) {
                        await window.dearAlert("新旧模型ID相同，无需迁移", "提示");
                        return;
                    }

                    callApi("admin_rename_model_id", { old_id: oldId, new_id: newId }, "POST").then(res => {
                        if (res.ok) {
                            showToast(res.message || "迁移成功", "success");
                            overlay.classList.remove("active");
                            refreshStats();
                        } else {
                            showToast(res.error || "迁移失败", "error");
                        }
                    });
                });
            }

            overlay.querySelector("#migration-old-id").value = "";
            overlay.querySelector("#migration-new-id").value = "";
            overlay.classList.add("active");
        }

        // Initial Render
        render();
    });
})();
