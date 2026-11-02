<?php if (!defined('__TYPECHO_ROOT_DIR__'))
    exit;

$enableCommentUrl = is_null($this->options->Dear_commentUrl) ? true : (bool)$this->options->Dear_commentUrl;
$enableMarkdown = !empty($this->options->commentsMarkdown);

$namePlaceholder = _t('*称呼 (必填)');
$mailPlaceholder = $this->options->commentsRequireMail ? _t('*邮箱 (必填)') : _t('邮箱 (选填)');
$urlPlaceholder = $this->options->commentsRequireUrl ? _t('*个人网站 (必填)') : _t('个人网站');

/**
 * 自定义评论树渲染
 */
function threadedComments($comments, $options)
{
    $commentClass = '';
    $isAuthor = false;
    if ($comments->authorId) {
        if ($comments->authorId == $comments->ownerId) {
            $commentClass .= ' comment-by-author';
            $isAuthor = true;
        } else {
            $commentClass .= ' comment-by-user';
        }
    }

    if ($comments->url) {
        $author = '<a href="' . htmlspecialchars($comments->url) . '" target="_blank" rel="external nofollow" class="author-link">' . htmlspecialchars($comments->author) . '</a>';
    } else {
        $author = '<span class="author-plain">' . htmlspecialchars($comments->author) . '</span>';
    }

    $emailMd5 = md5(strtolower(trim($comments->mail)));
    $avatar = 'https://secure.gravatar.com/avatar/' . $emailMd5 . '?s=80&r=X&d=';
    $avatarLarge = 'https://secure.gravatar.com/avatar/' . $emailMd5 . '?s=160&r=X&d=';
    $authorName = htmlspecialchars($comments->author);
    $authorMail = htmlspecialchars($comments->mail);
    $authorUrl = htmlspecialchars($comments->url ?? '');
    $commentDate = $comments->date('Y-m-d H:i', false);
    $role = $isAuthor ? _t('博主') : _t('访客');
?>
    <li id="li-<?php $comments->theId(); ?>" class="comment-body<?php
        if ($comments->levels > 0) {
            echo ' comment-child';
            $comments->levelsAlt(' comment-level-odd', ' comment-level-even');
        } else {
            echo ' comment-parent';
        }
        $comments->alt(' comment-odd', ' comment-even');
        echo $commentClass;
    ?>">
        <div id="<?php $comments->theId(); ?>" class="comment-item">
            <div class="comment-avatar-col dear-user-card-trigger"
                 data-author="<?php echo $authorName; ?>"
                 data-avatar="<?php echo $avatarLarge; ?>"
                 data-mail="<?php echo $authorMail; ?>"
                 data-url="<?php echo $authorUrl; ?>"
                 data-date="<?php echo $commentDate; ?>"
                 data-role="<?php echo $role; ?>">
                <img class="avatar" src="<?php echo $avatar; ?>" alt="<?php echo $authorName; ?>" loading="lazy" />
            </div>
            <div class="comment-main-col">
                <div class="comment-meta">
                    <span class="comment-author dear-user-card-trigger"
                          data-author="<?php echo $authorName; ?>"
                          data-avatar="<?php echo $avatarLarge; ?>"
                          data-mail="<?php echo $authorMail; ?>"
                          data-url="<?php echo $authorUrl; ?>"
                          data-date="<?php echo $commentDate; ?>"
                          data-role="<?php echo $role; ?>">
                        <?php echo $author; ?>
                        <?php if ($isAuthor): ?>
                            <span class="comment-author-badge" title="<?php _e('本文作者'); ?>"><?php _e('博主'); ?></span>
                        <?php endif; ?>
                    </span>
                    <span class="comment-meta-dot">·</span>
                    <time class="comment-time" datetime="<?php $comments->date('c'); ?>"><?php $comments->date('Y-m-d H:i'); ?></time>
                    <span class="comment-reply-btn"><?php $comments->reply('回复'); ?></span>
                </div>
                <div class="comment-content">
                    <?php $comments->content(); ?>
                </div>
            </div>
        </div>
        <?php if ($comments->children): ?>
            <div class="comment-children">
                <?php $comments->threadedComments($options); ?>
            </div>
        <?php endif; ?>
    </li>
<?php } ?>

<div id="comments" class="gen">
    <link rel="stylesheet" href="<?php $this->options->themeUrl('./asset/css/comments.css'); ?>">

    <?php $this->comments()->to($comments); ?>

    <!-- 评论表单 -->
    <?php if ($this->allow('comment')): ?>
        <div id="<?php $this->respondId(); ?>" class="respond">
            <div class="respond-header">
                <h3 id="response" class="respond-title"><?php _e('Comments'); ?></h3>
                <div class="cancel-comment-reply">
                    <?php $comments->cancelReply('&times; 取消回复'); ?>
                </div>
            </div>

            <form method="post" action="<?php $this->commentUrl() ?>" id="comment-form" role="form">
                <!-- 回复提示条 -->
                <div id="dear-reply-banner" class="reply-banner" style="display:none;">
                    <div class="reply-banner-info">
                        <span class="reply-banner-icon">
                            <svg class="dear-icon" viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 10 4 15 9 20"></polyline>
                                <path d="M20 4v7a4 4 0 0 1-4 4H4"></path>
                            </svg>
                        </span>
                        <div class="reply-banner-text">
                            <span class="reply-label"><?php _e('回复'); ?></span>
                            <strong class="reply-target-author" id="dear-reply-target-author"></strong>
                            <span class="reply-target-quote" id="dear-reply-target-quote"></span>
                        </div>
                    </div>
                    <button type="button" class="reply-banner-cancel" id="dear-reply-cancel-btn" title="<?php _e('取消回复'); ?>">
                        <span class="cancel-symbol">&times;</span>
                        <span class="cancel-text"><?php _e('取消'); ?></span>
                    </button>
                </div>

                <?php if ($this->user->hasLogin()): ?>
                    <div class="comment-logged-user">
                        <span class="logged-chip">
                            <?php 
                            $userMail = $this->user->mail;
                            $userAvatar = 'https://secure.gravatar.com/avatar/' . md5(strtolower(trim($userMail))) . '?s=48&r=X&d='; 
                            ?>
                            <img class="avatar-mini" src="<?php echo $userAvatar; ?>" alt="<?php $this->user->screenName(); ?>" />
                            <span><?php _e('已登录为'); ?> <a href="<?php $this->options->profileUrl(); ?>" class="user-link"><?php $this->user->screenName(); ?></a></span>
                        </span>
                        <a href="<?php $this->options->logoutUrl(); ?>" class="logout-link" title="Logout"><?php _e('退出'); ?> &raquo;</a>
                    </div>
                <?php else: ?>
                    <div class="comment-info-grid">
                        <div class="comment-input-wrap">
                            <span class="input-icon" title="<?php _e('称呼'); ?>">
                                <svg class="dear-icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </span>
                            <input type="text" name="author" id="comment-name" class="text" placeholder="<?php echo $namePlaceholder; ?>"
                                value="<?php $this->remember('author'); ?>" required />
                        </div>
                        <div class="comment-input-wrap">
                            <span class="input-icon" title="<?php _e('邮箱'); ?>">
                                <svg class="dear-icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                    <polyline points="22,6 12,13 2,6"></polyline>
                                </svg>
                            </span>
                            <input type="email" name="mail" id="comment-mail" class="text" placeholder="<?php echo $mailPlaceholder; ?>"
                                value="<?php $this->remember('mail'); ?>" <?php if ($this->options->commentsRequireMail): ?>required<?php endif; ?> />
                        </div>
                        <?php if ($enableCommentUrl): ?>
                            <div class="comment-input-wrap">
                                <span class="input-icon" title="<?php _e('个人网站'); ?>">
                                    <svg class="dear-icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="2" y1="12" x2="22" y2="12"></line>
                                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1 4-10z"></path>
                                    </svg>
                                </span>
                                <input type="url" name="url" id="comment-url" class="text" placeholder="<?php echo $urlPlaceholder; ?>"
                                    value="<?php $this->remember('url'); ?>" <?php if ($this->options->commentsRequireUrl): ?>required<?php endif; ?> />
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="comment-editor-box">
                    <textarea name="text" id="textarea" class="textarea" required placeholder="<?php _e('愿借君之一笔，留待岁月回甘。'); ?>"><?php $this->remember('text'); ?></textarea>
                    
                    <div class="comment-toolbar">
                        <div class="toolbar-left">
                            <button type="button" class="tool-btn" id="dear-btn-kaomoji" title="<?php _e('插入颜文字'); ?>">(・ω・)</button>
                            <?php if ($enableMarkdown): ?>
                                <button type="button" class="tool-btn" id="dear-btn-bold" title="<?php _e('加粗'); ?>"><strong>B</strong></button>
                                <button type="button" class="tool-btn" id="dear-btn-italic" title="<?php _e('斜体'); ?>"><em>I</em></button>
                                <button type="button" class="tool-btn" id="dear-btn-quote" title="<?php _e('引用'); ?>">”</button>
                                <button type="button" class="tool-btn" id="dear-btn-code" title="<?php _e('代码'); ?>">&lt;/&gt;</button>
                                <button type="button" class="tool-btn" id="dear-btn-link" title="<?php _e('链接'); ?>">
                                    <svg class="dear-icon" viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                                    </svg>
                                </button>
                                <span class="markdown-badge" title="<?php _e('支持 Markdown 语法'); ?>">Markdown</span>
                            <?php endif; ?>
                        </div>
                        <div class="toolbar-right">
                            <span class="shortcut-tip">Ctrl + Enter</span>
                        </div>
                    </div>

                    <div id="dear-kaomoji-drawer" class="kaomoji-drawer" style="display:none;"></div>
                </div>

                <div class="comment-bottom-bar">
                    <div class="bottom-left">
                        <?php if ($this->options->commentsRequireModeration): ?>
                            <span class="moderation-badge">
                                <svg class="dear-icon" viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                </svg>
                                <?php _e('审核机制已开启，评论通过后显示'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="bottom-right">
                        <button type="submit" class="submit-btn" id="comment-submit-btn" title="<?php _e('发送评论 (Ctrl + Enter)'); ?>"><?php _e('发送'); ?></button>
                    </div>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div class="comments-closed">
            <p><?php _e('评论功能已关闭'); ?></p>
        </div>
    <?php endif; ?>

    <!-- 评论列表 -->
    <div class="comments-list-section">
        <div class="comments-header-row">
            <h3 class="comments-count-title">
                <?php $this->commentsNum(_t('暂无评论'), _t('仅 1 条评论'), _t('全部 %d 条评论')); ?>
            </h3>
        </div>

        <?php if ($comments->have()): ?>
            <?php $comments->listComments(); ?>
            <div class="comments-nav-wrap">
                <?php $comments->pageNav('&nbsp;←&nbsp;', '&nbsp;→&nbsp;', '3', '…'); ?>
            </div>
        <?php else: ?>
            <div class="comments-empty-card">
                <div class="empty-icon">
                    <svg class="dear-icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 20h9"></path>
                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                    </svg>
                </div>
                <div class="empty-text"><?php _e('愿借君之一笔，留待岁月回甘。'); ?></div>
            </div>
        <?php endif; ?>
    </div>

    <!-- 用户信息悬浮卡片 -->
    <div id="dear-user-hover-card" class="dear-hover-card" style="display:none;">
        <div class="dear-hover-card-inner">
            <div class="dear-hover-card-header">
                <img class="card-avatar" id="dear-card-avatar" src="" alt="" />
                <div class="card-header-main">
                    <div class="card-name-row">
                        <strong class="card-name" id="dear-card-name"></strong>
                        <span class="card-role" id="dear-card-role"></span>
                    </div>
                    <div class="card-date" id="dear-card-date"></div>
                </div>
            </div>
            <div class="dear-hover-card-divider"></div>
            <div class="dear-hover-card-body">
                <div class="card-info-row" id="dear-card-mail-row">
                    <svg class="dear-icon" viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    <a class="card-link" id="dear-card-mail" href="" target="_blank" rel="nofollow"></a>
                </div>
                <div class="card-info-row" id="dear-card-url-row">
                    <svg class="dear-icon" viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="2" y1="12" x2="22" y2="12"></line>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                    </svg>
                    <a class="card-link" id="dear-card-url" href="" target="_blank" rel="external nofollow"></a>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php $this->options->themeUrl('./asset/js/comments.js'); ?>"></script>
</div>
