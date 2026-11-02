/**
 * Dear Theme - Updater Client Script
 * Handles checking latest releases via GitHub API and triggering automatic updates.
 */

(function () {
    "use strict";

    const GITHUB_REPO = "UtopiaXC/Theme-Dear-For-Typecho";
    const GITHUB_API_LATEST = "https://api.github.com/repos/" + GITHUB_REPO + "/releases/latest";

    /**
     * Semantic version comparison helper
     * Strips leading 'v' / 'V' and compares segments
     * Returns: 1 if v1 > v2, -1 if v1 < v2, 0 if v1 == v2
     */
    function compareVersions(v1, v2) {
        const clean1 = (v1 || "").replace(/^[vV]/, "").trim();
        const clean2 = (v2 || "").replace(/^[vV]/, "").trim();

        if (clean1 === clean2) return 0;

        const parts1 = clean1.split(/[\.-]/).map(p => isNaN(p) ? p : parseInt(p, 10));
        const parts2 = clean2.split(/[\.-]/).map(p => isNaN(p) ? p : parseInt(p, 10));
        const maxLen = Math.max(parts1.length, parts2.length);

        for (let i = 0; i < maxLen; i++) {
            const p1 = parts1[i] !== undefined ? parts1[i] : 0;
            const p2 = parts2[i] !== undefined ? parts2[i] : 0;

            if (typeof p1 === "number" && typeof p2 === "number") {
                if (p1 > p2) return 1;
                if (p1 < p2) return -1;
            } else {
                const s1 = String(p1);
                const s2 = String(p2);
                if (s1 > s2) return 1;
                if (s1 < s2) return -1;
            }
        }
        return 0;
    }

    /**
     * Format ISO date string into readable YYYY-MM-DD
     */
    function formatDate(isoStr) {
        if (!isoStr) return "";
        try {
            const d = new Date(isoStr);
            if (isNaN(d.getTime())) return "";
            return d.getFullYear() + "-" +
                String(d.getMonth() + 1).padStart(2, "0") + "-" +
                String(d.getDate()).padStart(2, "0");
        } catch (e) {
            return "";
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        const checkBtn = document.getElementById("dear-check-update-btn");
        const statusSpan = document.getElementById("dear-check-update-status");
        const modalOverlay = document.getElementById("dear-update-modal-overlay");
        if (!checkBtn || !modalOverlay) return;

        const modalCloseBtn = document.getElementById("dear-update-modal-close");
        const modalCancelBtn = document.getElementById("dear-update-btn-cancel");
        const modalConfirmBtn = document.getElementById("dear-update-btn-confirm");
        const oldBadge = document.getElementById("dear-update-old-badge");
        const newBadge = document.getElementById("dear-update-new-badge");
        const releaseTitleEl = document.getElementById("dear-update-release-title");
        const releaseDateEl = document.getElementById("dear-update-release-date");
        const changelogEl = document.getElementById("dear-update-changelog");
        const progressArea = document.getElementById("dear-update-progress-area");
        const statusText = document.getElementById("dear-update-status-text");

        const config = window.DearUpdateConfig || {};
        const currentVersion = config.currentVersion || "1.0.0";
        const cleanCurrent = currentVersion.replace(/^[vV]/, "").trim();
        const ajaxBaseUrl = config.ajaxUrl || window.location.href.split("#")[0];

        let pendingRelease = null;

        function openModal(release, downloadUrl) {
            pendingRelease = {
                release: release,
                downloadUrl: downloadUrl
            };

            const tag = release.tag_name || "";
            const cleanLatest = tag.replace(/^[vV]/, "").trim();

            if (oldBadge) oldBadge.textContent = "当前版本: v" + cleanCurrent;
            if (newBadge) newBadge.textContent = "最新版本: v" + cleanLatest;

            if (releaseTitleEl) {
                releaseTitleEl.textContent = release.name || tag || "新版本发布";
            }

            if (releaseDateEl) {
                const dateStr = formatDate(release.published_at);
                releaseDateEl.textContent = dateStr ? "发布时间: " + dateStr : "";
            }

            if (changelogEl) {
                const body = release.body ? release.body.trim() : "";
                if (body) {
                    if (window.marked && typeof window.marked.parse === "function") {
                        changelogEl.innerHTML = window.marked.parse(body);
                    } else {
                        changelogEl.textContent = body;
                    }
                } else {
                    changelogEl.textContent = "暂无详细更新日志说明。";
                }
            }

            if (progressArea) progressArea.style.display = "none";
            if (statusText) statusText.innerHTML = "";

            modalConfirmBtn.disabled = false;
            modalCancelBtn.disabled = false;
            modalCloseBtn.disabled = false;
            modalConfirmBtn.textContent = "立即更新";

            modalOverlay.style.display = "flex";
        }

        function closeModal() {
            if (modalConfirmBtn && modalConfirmBtn.disabled) {
                // If update is running, prevent closing
                return;
            }
            modalOverlay.style.display = "none";
            pendingRelease = null;
        }

        modalCloseBtn.addEventListener("click", closeModal);
        modalCancelBtn.addEventListener("click", closeModal);
        modalOverlay.addEventListener("click", function (e) {
            if (e.target === modalOverlay) closeModal();
        });

        // ==========================================
        // Check for updates
        // ==========================================
        async function fetchLatestRelease() {
            // First attempt direct GitHub API request
            try {
                const resp = await fetch(GITHUB_API_LATEST, {
                    headers: { "Accept": "application/vnd.github.v3+json" }
                });
                if (resp.ok) {
                    const data = await resp.json();
                    if (data && data.tag_name) {
                        return data;
                    }
                }
            } catch (err) {
                console.warn("Direct GitHub API fetch failed, falling back to server check...", err);
            }

            // Fallback: Query via server-side PHP endpoint
            const fallbackUrl = ajaxBaseUrl + (ajaxBaseUrl.includes("?") ? "&" : "?") + "dear_update_action=check";
            const resp = await fetch(fallbackUrl, { credentials: "same-origin" });
            const result = await resp.json();
            if (!result || !result.success) {
                throw new Error(result && result.message ? result.message : "无法获取版本信息");
            }
            return {
                tag_name: result.latest_tag,
                name: result.release_name,
                body: result.release_body,
                published_at: result.published_at,
                assets: result.download_url ? [{
                    name: "Dear-For-Typecho.zip",
                    browser_download_url: result.download_url
                }] : [],
                zipball_url: result.download_url
            };
        }

        checkBtn.addEventListener("click", async function () {
            checkBtn.disabled = true;
            const originalText = checkBtn.textContent;
            checkBtn.textContent = "正在检查...";
            if (statusSpan) {
                statusSpan.innerHTML = '<span class="dear-update-spinner"></span> 正在连接 GitHub 检查最新版本...';
            }

            try {
                const release = await fetchLatestRelease();
                const latestTag = release.tag_name || "";
                const cleanLatest = latestTag.replace(/^[vV]/, "").trim();

                // Find download URL
                let downloadUrl = "";
                if (Array.isArray(release.assets)) {
                    for (let i = 0; i < release.assets.length; i++) {
                        const asset = release.assets[i];
                        if (asset && asset.name && /\.zip$/i.test(asset.name)) {
                            downloadUrl = asset.browser_download_url;
                            break;
                        }
                    }
                }
                if (!downloadUrl && release.zipball_url) {
                    downloadUrl = release.zipball_url;
                }

                const cmp = compareVersions(cleanLatest, cleanCurrent);

                if (cmp > 0 || (cleanLatest !== cleanCurrent && cleanLatest !== "")) {
                    // Update available!
                    if (statusSpan) {
                        statusSpan.innerHTML = '<span style="color:#d35400; font-weight:bold;">发现新版本 v' + cleanLatest + '</span>';
                    }
                    openModal(release, downloadUrl);
                } else {
                    // Already up-to-date
                    if (statusSpan) {
                        statusSpan.innerHTML = '<span style="color:#27ae60; font-weight:bold;">✓ 当前已是最新版本 (v' + cleanCurrent + ')</span>';
                    }
                    if (window.dearAlert) {
                        await window.dearAlert("当前已是最新版本 (v" + cleanCurrent + ")，无需更新。", "版本检查");
                    }
                }
            } catch (err) {
                console.error("Check update error:", err);
                if (statusSpan) {
                    statusSpan.innerHTML = '<span style="color:#c0392b;">检查更新失败：' + (err.message || "网络异常") + '</span>';
                }
                if (window.dearAlert) {
                    await window.dearAlert("检查更新失败：\n" + (err.message || "无法连接至 GitHub，请检查服务器或本地网络。"), "检查失败");
                }
            } finally {
                checkBtn.disabled = false;
                checkBtn.textContent = originalText;
            }
        });

        // ==========================================
        // Execute Update
        // ==========================================
        modalConfirmBtn.addEventListener("click", async function () {
            if (!pendingRelease) return;

            // Confirm again
            modalConfirmBtn.disabled = true;
            modalCancelBtn.disabled = true;
            modalCloseBtn.disabled = true;

            if (progressArea) progressArea.style.display = "block";
            if (statusText) {
                statusText.innerHTML = '<span class="dear-update-spinner"></span> 正在下载并解压更新包，请稍候（请勿刷新或关闭页面）...';
                statusText.style.color = "#467b96";
            }

            try {
                const formData = new FormData();
                if (pendingRelease.downloadUrl) {
                    formData.append("download_url", pendingRelease.downloadUrl);
                }
                formData.append("version", pendingRelease.release.tag_name || "");

                const updateEndpoint = ajaxBaseUrl + (ajaxBaseUrl.includes("?") ? "&" : "?") + "dear_update_action=do_update";
                const response = await fetch(updateEndpoint, {
                    method: "POST",
                    body: formData,
                    credentials: "same-origin"
                });

                const result = await response.json();
                if (!result || !result.success) {
                    throw new Error(result && result.message ? result.message : "更新过程返回未知异常");
                }

                if (statusText) {
                    statusText.innerHTML = '<span style="color:#27ae60; font-weight:bold;">✓ ' + (result.message || "主题更新成功！") + ' 页面即将自动刷新...</span>';
                }

                setTimeout(function () {
                    window.location.reload();
                }, 1800);
            } catch (err) {
                console.error("Update execution error:", err);
                if (statusText) {
                    statusText.innerHTML = '<span style="color:#c0392b; font-weight:bold;">✕ 更新失败：' + (err.message || "网络异常或权限不足") + '</span>';
                }
                modalConfirmBtn.disabled = false;
                modalCancelBtn.disabled = false;
                modalCloseBtn.disabled = false;
                modalConfirmBtn.textContent = "重试更新";
            }
        });
    });
})();

