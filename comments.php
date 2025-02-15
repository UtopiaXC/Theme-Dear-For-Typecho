<?php if (!defined('__TYPECHO_ROOT_DIR__'))
    exit;
require('config.php'); ?>
<?php
//代码参考 Theme Quark http://sunhua.me/Quark.html
function threadedComments($comments, $options)
{
    $commentClass = '';
    if ($comments->authorId) {
        if ($comments->authorId == $comments->ownerId) {
            $commentClass .= ' comment-by-author';
        } else {
            $commentClass .= ' comment-by-user';
        }
    }
    $commentLevelClass = $comments->levels > 0 ? ' comment-child' : ' comment-parent';
    if ($comments->url) {
        $author = '<a href="' . $comments->url . '"' . '" target="_blank"' . ' rel="external nofollow">' . $comments->author . '</a>';
    } else {
        $author = $comments->author;
    }
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
        <div id="<?php $comments->theId(); ?>">
            <?php $avatar = 'https://secure.gravatar.com/avatar/' . md5(strtolower($comments->mail)) . '?s=80&r=X&d='; ?>
            <img class="avatar" src="<?php echo $avatar ?>" alt="<?php echo $comments->author; ?>" />
            <div class="comment_main">
                <div class="comment_meta">
                    <span class="comment_author"><?php echo $author ?></span> <span class="comment_time"><?php $comments->date(); ?></span><span class="comment_reply"><?php $comments->reply(); ?></span>
                </div>
                <?php $comments->content(); ?>
            </div>
        </div>
        <?php if ($comments->children) { ?><div class="comment-children"><?php $comments->threadedComments($options); ?></div><?php } ?>
    </li>
<?php } ?>
<div id="comments" class="gen">
<link rel="stylesheet" href="<?php $this->options->themeUrl('./asset/css/comments.css'); ?>">
    <?php $this->comments()->to($comments); ?>
    <?php if ($this->allow('comment')): ?>
        <div id="<?php $this->respondId(); ?>" class="respond">
            <div class="cancel-comment-reply">
                <?php $comments->cancelReply(); ?>
            </div>

            <h3 id="response"><?php _e('Comments'); ?></h3>



            <form method="post" action="<?php $this->commentUrl() ?>" id="comment-form" role="form">
                <?php if ($this->user->hasLogin()): ?>
                    <p><?php _e('当前登录: '); ?><a
                            href="<?php $this->options->profileUrl(); ?>"><?php $this->user->screenName(); ?></a>. <a
                            href="<?php $this->options->logoutUrl(); ?>" title="Logout"><?php _e('退出'); ?> &raquo;</a>
                    </p>
                <?php else: ?>

                    <div class="comment-info">
                        <input type="text" name="author" id="comment-name" class="text" placeholder="name"
                            value="<?php $this->remember('author'); ?>" required />
                        <input type="email" name="mail" id="comment-mail" class="text" placeholder="email"
                            value="<?php $this->remember('mail'); ?>" <?php if ($this->options->commentsRequireMail): ?>
                                required<?php endif; ?> />
                        <?php
                        if ($enableCommentUrl) {
                            echo '<input type="url" name="url" id="comment-url" class="text" placeholder="';
                            _e("http://");
                            echo '" value="' . $this->remember('url') . '"/>';
                        }
                        ?>

                    </div>
                <?php endif; ?>
                <div class="comment-editor">
                    <textarea name="text" id="textarea" class="textarea" required=""></textarea>
                </div>
                <div class="comment-buttons">
                    <div class="left">
                        <span>
                            <?php
                            if ($this->options->commentsRequireModeration) {
                                echo '审核已开启';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="right">
                        <button type="submit" class="submit">↵</button>
                    </div>
                </div>


            </form>

        </div>
    <?php else: ?>
        <h3><?php _e('评论功能已关闭'); ?></h3>
    <?php endif; ?>
    <?php if ($comments->have()): ?>
        <h3><?php $this->commentsNum(_t('暂无评论'), _t('仅有一条评论'), _t('已有 %d 条评论')); ?></h3>

        <?php $comments->listComments(); ?>
        <br>
        <?php $comments->pageNav('&nbsp;←&nbsp;', '&nbsp;→&nbsp;', '3', '…'); ?>

    <?php endif; ?>
</div>