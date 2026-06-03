<?php
/** @var \Typecho\Theme\ViewContext $view */
/** @var \Widget\Archive $archive */
/** @var \Widget\Options $site */
/** @var \Typecho\Theme\Manifest $theme */
/** @var \Typecho\Theme\Escaper $e */
/** @var \Typecho\Theme\AssetManager $assets */
/** @var \Typecho\Theme\DataProvider $data */
/** @var \Widget\User $user */
/** @var \Widget\Comments\Archive $comments */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
?>
<div id="comments">
    <?php $archive->comments()->to($comments); ?>
    <?php if ($comments->have()): ?>
        <h2 class="text-center"><?php echo $view->commentsNum(_t('暂无评论'), _t('1 条评论'), _t('%d 条评论')); ?></h2>

        <?php $comments->listComments(array(
            'commentStatus' => _t('你的评论正等待审核'),
            'avatarSize' => 64,
            'defaultAvatar' => 'identicon'
        )); ?>

        <nav><?php $comments->pageNav(_t('前一页'), _t('后一页'), 3, '...', array('wrapTag' => 'ul', 'itemTag' => 'li')); ?></nav>

    <?php endif; ?>

    <?php if ($archive->allow('comment')): ?>
        <div id="<?php echo $e->attr($view->respondId()); ?>" class="respond">
            <div class="cancel-comment-reply">
                <?php $comments->cancelReply(); ?>
            </div>

            <h5 id="response"><?php _e('你的评论'); ?></h5>

            <form method="post" action="<?php echo $e->url($view->commentUrl()); ?>" id="comment-form" role="form">
                <div class="grid">
                    <textarea placeholder="<?php _e('评论内容...'); ?>" rows="4" cols="300" name="text" id="textarea" required><?php echo $e->html($view->remember('text')); ?></textarea>
                </div>
                <?php if ($user->hasLogin()): ?>
                <p>
                    <?php _e('登录身份：'); ?><a href="<?php echo $e->url($site->profileUrl); ?>"><?php echo $e->html($user->screenName); ?></a><span class="mx-2 text-muted">&middot;</span><a href="<?php echo $e->url($site->logoutUrl); ?>"><?php _e('退出'); ?></a>
                </p>
                <?php else: ?>
                <div class="grid">
                    <input type="text" placeholder="<?php _e('名字'); ?>" name="author" id="author" value="<?php echo $e->attr($view->remember('author')); ?>" required/>
                    <input type="email" placeholder="<?php _e('Email'); ?>" name="mail" id="mail" value="<?php echo $e->attr($view->remember('mail')); ?>"<?php if ($site->commentsRequireMail): ?> required<?php endif; ?> />
                    <input type="url" placeholder="<?php _e('http://网站'); ?><?php if (!$site->commentsRequireUrl): ?><?php _e('（选填）'); ?><?php endif; ?>" name="url" id="url" value="<?php echo $e->attr($view->remember('url')); ?>"<?php if ($site->commentsRequireUrl): ?> required<?php endif; ?> />
                </div>
                <?php endif; ?>
                <button type="submit"><?php _e('提交评论'); ?></button>
            </form>
        </div>
    <?php else: ?>
        <div class="text-center text-muted"><?php _e('评论已关闭'); ?></div>
    <?php endif; ?>
</div>
