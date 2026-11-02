/**
 * Dear Theme - Comments Enhancement Script
 * - Pure-text Kaomoji (颜文字) picker
 * - Markdown shortcut formatting buttons
 * - Ctrl + Enter (Cmd + Enter) quick submit
 * - Visitor details auto-persistence via localStorage
 * - Anti-duplicate submission UX
 */

(function () {
    "use strict";

    const KAOMOJIS = [
        "(*^▽^*)", "(｡･ω･｡)", "(ง •_•)ง", "(*/ω＼*)",
        "(ฅ´ω`ฅ)", "(๑•̀ㅂ•́)و✧", "(｡•́︿•̀｡)", "(￣▽￣)",
        "(oﾟvﾟ)ノ", "(｡･ω･｡)ﾉ♡", "(/≧▽≦)/", "(°ー°〃)",
        "(´∀`)", "(・ω・)", "(•̀ᴗ•́)و", "(づ｡◕‿‿◕｡)づ",
        "(>_<)", "o(*￣▽￣*)o", "(;¬_¬)", "٩(◕‿◕｡)۶"
    ];

    document.addEventListener("DOMContentLoaded", function () {
        const commentForm = document.getElementById("comment-form");
        const textarea = document.getElementById("textarea");
        if (!commentForm || !textarea) return;

        const kaomojiBtn = document.getElementById("dear-btn-kaomoji");
        const kaomojiDrawer = document.getElementById("dear-kaomoji-drawer");
        const submitBtn = document.getElementById("comment-submit-btn");

        const nameInput = document.getElementById("comment-name");
        const mailInput = document.getElementById("comment-mail");
        const urlInput = document.getElementById("comment-url");

        // 1. LocalStorage visitor info persistence
        const STORAGE_KEY = "dear_comment_author_info";
        try {
            if (nameInput && mailInput) {
                // If inputs are currently empty, restore from localStorage
                const saved = localStorage.getItem(STORAGE_KEY);
                if (saved) {
                    const info = JSON.parse(saved);
                    if (!nameInput.value && info.name) nameInput.value = info.name;
                    if (!mailInput.value && info.mail) mailInput.value = info.mail;
                    if (urlInput && !urlInput.value && info.url) urlInput.value = info.url;
                }

                // Save on change
                const saveInfo = function () {
                    const data = {
                        name: nameInput.value.trim(),
                        mail: mailInput.value.trim(),
                        url: urlInput ? urlInput.value.trim() : ""
                    };
                    localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
                };
                nameInput.addEventListener("blur", saveInfo);
                mailInput.addEventListener("blur", saveInfo);
                if (urlInput) urlInput.addEventListener("blur", saveInfo);
            }
        } catch (e) {
            console.warn("LocalStorage access failed:", e);
        }

        // 2. Insert text at cursor helper
        function insertTextAtCursor(before, after = "") {
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            const selected = text.substring(start, end);

            const replacement = before + (selected || "") + after;
            textarea.value = text.substring(0, start) + replacement + text.substring(end);

            textarea.focus();
            if (selected) {
                textarea.setSelectionRange(start + before.length, start + before.length + selected.length);
            } else {
                textarea.setSelectionRange(start + before.length, start + before.length);
            }
            // Trigger input event for auto-grow or validation
            textarea.dispatchEvent(new Event("input", { bubbles: true }));
        }

        // 3. Kaomoji Drawer
        if (kaomojiBtn && kaomojiDrawer) {
            // Populate Kaomoji buttons once
            kaomojiDrawer.innerHTML = KAOMOJIS.map(k => {
                return `<button type="button" class="kaomoji-item">${k}</button>`;
            }).join("");

            kaomojiBtn.addEventListener("click", function (e) {
                e.stopPropagation();
                const isHidden = kaomojiDrawer.style.display === "none";
                kaomojiDrawer.style.display = isHidden ? "flex" : "none";
                kaomojiBtn.classList.toggle("active", isHidden);
            });

            kaomojiDrawer.addEventListener("click", function (e) {
                const target = e.target.closest(".kaomoji-item");
                if (target) {
                    insertTextAtCursor(" " + target.textContent + " ");
                }
            });

            // Close drawer when clicking outside
            document.addEventListener("click", function (e) {
                if (!kaomojiDrawer.contains(e.target) && e.target !== kaomojiBtn) {
                    kaomojiDrawer.style.display = "none";
                    kaomojiBtn.classList.remove("active");
                }
            });
        }

        // 4. Markdown Shortcut Buttons
        const boldBtn = document.getElementById("dear-btn-bold");
        const italicBtn = document.getElementById("dear-btn-italic");
        const quoteBtn = document.getElementById("dear-btn-quote");
        const codeBtn = document.getElementById("dear-btn-code");
        const linkBtn = document.getElementById("dear-btn-link");

        if (boldBtn) boldBtn.addEventListener("click", () => insertTextAtCursor("**", "**"));
        if (italicBtn) italicBtn.addEventListener("click", () => insertTextAtCursor("*", "*"));
        if (quoteBtn) quoteBtn.addEventListener("click", () => insertTextAtCursor("> ", "\n"));
        if (codeBtn) codeBtn.addEventListener("click", () => insertTextAtCursor("`", "`"));
        if (linkBtn) linkBtn.addEventListener("click", () => insertTextAtCursor("[", "](https://)"));

        // 5. Ctrl + Enter (or Cmd + Enter) to submit
        textarea.addEventListener("keydown", function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === "Enter") {
                e.preventDefault();
                if (commentForm.checkValidity()) {
                    commentForm.submit();
                } else {
                    commentForm.reportValidity();
                }
            }
        });

        // 6. Prevent duplicate submission & show state
        commentForm.addEventListener("submit", function () {
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = "发送中...";
            }
        });

        // 7. Custom Reply Handling: keep form in place, scroll & show reply preview
        const replyBanner = document.getElementById("dear-reply-banner");
        const replyAuthorEl = document.getElementById("dear-reply-target-author");
        const replyQuoteEl = document.getElementById("dear-reply-target-quote");
        const replyCancelBtn = document.getElementById("dear-reply-cancel-btn");

        function setParentId(coid) {
            let parentInput = commentForm.querySelector("input[name=parent]");
            if (coid) {
                if (!parentInput) {
                    parentInput = document.createElement("input");
                    parentInput.type = "hidden";
                    parentInput.name = "parent";
                    commentForm.appendChild(parentInput);
                }
                parentInput.value = coid;
            } else if (parentInput) {
                parentInput.remove();
            }
        }

        function showReplyBanner(author, quote, coid) {
            setParentId(coid);
            if (replyAuthorEl) replyAuthorEl.textContent = author ? "@" + author : "";
            if (replyQuoteEl) {
                replyQuoteEl.textContent = quote ? "“" + quote + "”" : "";
                replyQuoteEl.title = quote || "";
            }
            if (replyBanner) replyBanner.style.display = "flex";

            const nativeCancel = document.getElementById("cancel-comment-reply-link");
            if (nativeCancel) nativeCancel.style.display = "inline-block";
        }

        function hideReplyBanner() {
            setParentId(false);
            if (replyBanner) replyBanner.style.display = "none";
            const nativeCancel = document.getElementById("cancel-comment-reply-link");
            if (nativeCancel) nativeCancel.style.display = "none";
        }

        if (replyCancelBtn) {
            replyCancelBtn.addEventListener("click", function (e) {
                e.preventDefault();
                hideReplyBanner();
            });
        }

        // Intercept TypechoComment.reply and TypechoComment.cancelReply
        window.TypechoComment = window.TypechoComment || {};

        window.TypechoComment.reply = function (htmlId, coid, btn) {
            const commentEl = document.getElementById(htmlId);
            let authorName = "";
            let contentSnippet = "";

            if (commentEl) {
                const authorEl = commentEl.querySelector(".comment-author .author-link, .comment-author .author-plain");
                if (authorEl) {
                    authorName = authorEl.textContent.trim();
                }
                const contentEl = commentEl.querySelector(".comment-content");
                if (contentEl) {
                    const text = (contentEl.innerText || contentEl.textContent || "").replace(/\s+/g, " ").trim();
                    contentSnippet = text.length > 45 ? text.substring(0, 45) + "..." : text;
                }
            }

            showReplyBanner(authorName, contentSnippet, coid);

            // Smooth scroll to respond section / comment form
            const targetScrollEl = document.getElementById("response") || document.querySelector(".respond") || commentForm;
            if (targetScrollEl) {
                targetScrollEl.scrollIntoView({ behavior: "smooth", block: "start" });
            }

            // Focus textarea
            setTimeout(function () {
                textarea.focus();
            }, 300);

            return false;
        };

        window.TypechoComment.cancelReply = function () {
            hideReplyBanner();
            return false;
        };

        // 8. User Info Hover Card
        const hoverCard = document.getElementById("dear-user-hover-card");
        if (hoverCard) {
            const cardAvatar = document.getElementById("dear-card-avatar");
            const cardName = document.getElementById("dear-card-name");
            const cardRole = document.getElementById("dear-card-role");
            const cardDate = document.getElementById("dear-card-date");
            const cardMail = document.getElementById("dear-card-mail");
            const cardUrl = document.getElementById("dear-card-url");
            const cardUrlRow = document.getElementById("dear-card-url-row");

            let showTimer = null;
            let hideTimer = null;

            function positionCard(trigger) {
                const rect = trigger.getBoundingClientRect();
                const cardWidth = hoverCard.offsetWidth || 260;
                const cardHeight = hoverCard.offsetHeight || 130;

                let left = rect.left + window.scrollX - 10;
                if (left + cardWidth > window.innerWidth - 12) {
                    left = window.innerWidth - cardWidth - 12;
                }
                if (left < 10) left = 10;

                // 默认显示在触发元素上方
                let top = rect.top + window.scrollY - cardHeight - 8;
                // 如果上方空间不足（靠近视口顶部），则回退显示在下方
                if (rect.top - cardHeight - 8 < 10) {
                    top = rect.bottom + window.scrollY + 8;
                }

                hoverCard.style.left = left + "px";
                hoverCard.style.top = top + "px";
            }

            function openCard(trigger) {
                clearTimeout(hideTimer);
                const data = trigger.dataset;
                if (!data || !data.author) return;

                if (cardAvatar) {
                    cardAvatar.src = data.avatar || "";
                    cardAvatar.alt = data.author;
                }
                if (cardName) cardName.textContent = data.author;
                if (cardRole) {
                    cardRole.textContent = data.role || "访客";
                    cardRole.className = "card-role " + (data.role === "博主" ? "role-author" : "role-visitor");
                }
                if (cardDate) cardDate.textContent = data.date ? "评论于 " + data.date : "";

                if (cardMail) {
                    if (data.mail) {
                        cardMail.textContent = data.mail;
                        cardMail.href = "mailto:" + data.mail;
                        cardMail.className = "card-link";
                    } else {
                        cardMail.textContent = "未公开邮箱";
                        cardMail.removeAttribute("href");
                        cardMail.className = "card-link card-link-none";
                    }
                }

                if (cardUrl && cardUrlRow) {
                    if (data.url && data.url !== "none" && data.url.trim() !== "") {
                        const displayUrl = data.url.replace(/^https?:\/\//i, "").replace(/\/$/, "");
                        cardUrl.textContent = displayUrl;
                        cardUrl.href = data.url.match(/^https?:\/\//i) ? data.url : "https://" + data.url;
                        cardUrl.className = "card-link";
                        cardUrlRow.style.display = "flex";
                    } else {
                        cardUrlRow.style.display = "none";
                    }
                }

                hoverCard.style.display = "block";
                positionCard(trigger);
                requestAnimationFrame(function () {
                    hoverCard.classList.add("active");
                });
            }

            function closeCard() {
                hoverCard.classList.remove("active");
                setTimeout(function () {
                    if (!hoverCard.classList.contains("active")) {
                        hoverCard.style.display = "none";
                    }
                }, 200);
            }

            // Event delegation on #comments for mouseover / mouseout
            const commentsSection = document.getElementById("comments");
            if (commentsSection) {
                commentsSection.addEventListener("mouseover", function (e) {
                    const trigger = e.target.closest(".dear-user-card-trigger");
                    if (trigger) {
                        clearTimeout(hideTimer);
                        clearTimeout(showTimer);
                        showTimer = setTimeout(function () {
                            openCard(trigger);
                        }, 160);
                    }
                });

                commentsSection.addEventListener("mouseout", function (e) {
                    const trigger = e.target.closest(".dear-user-card-trigger");
                    if (trigger) {
                        clearTimeout(showTimer);
                        hideTimer = setTimeout(closeCard, 220);
                    }
                });
            }

            hoverCard.addEventListener("mouseenter", function () {
                clearTimeout(hideTimer);
            });

            hoverCard.addEventListener("mouseleave", function () {
                hideTimer = setTimeout(closeCard, 220);
            });
        }
    });
})();

