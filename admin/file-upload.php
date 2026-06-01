<?php if(!defined('__TYPECHO_ADMIN__')) exit; ?>

<?php
if (isset($post) || isset($page)) {
    $cid = isset($post) ? $post->cid : $page->cid;

    if ($cid) {
        \Widget\Contents\Attachment\Related::alloc(['parentId' => $cid])->to($attachment);
    } else {
        \Widget\Contents\Attachment\Unattached::alloc()->to($attachment);
    }

    \Widget\Contents\Attachment\Library::allocWithAlias('attachment-library-' . ($cid ?: 'new'), [
        'parentId' => $cid,
        'limit'    => 8
    ])->to($mediaLibrary);
}
?>

<div id="upload-panel" class="p">
    <div class="upload-area" data-url="<?php $security->index('/action/upload'); ?>">
        <?php _e('拖放文件到这里<br>或者 %s选择文件上传%s', '<a href="###" class="upload-file">', '</a>'); ?>
    </div>
    <?php if (isset($mediaLibrary) && $mediaLibrary->have()): ?>
        <div class="attachment-library-reuse">
            <div class="attachment-library-head">
                <strong><?php _e('从媒体库插入'); ?></strong>
                <a href="<?php $options->adminUrl('manage-medias.php'); ?>"><?php _e('管理'); ?></a>
            </div>
            <label for="attachment-library-search" class="sr-only"><?php _e('搜索媒体'); ?></label>
            <input id="attachment-library-search" type="text" class="text-s w-100"
                   placeholder="<?php _e('搜索媒体'); ?>"/>
            <ul id="attachment-library-list">
                <?php while ($mediaLibrary->next()): ?>
                    <?php
                    $mediaTitle = htmlspecialchars($mediaLibrary->title, ENT_QUOTES);
                    $mediaAlt = htmlspecialchars(\Typecho\Common::strBy(
                        $mediaLibrary->attachment->alt,
                        $mediaLibrary->title
                    ), ENT_QUOTES);
                    $mediaUrl = htmlspecialchars($mediaLibrary->attachment->url, ENT_QUOTES);
                    $mime = \Typecho\Common::mimeIconType($mediaLibrary->attachment->mime);
                    $size = number_format(ceil($mediaLibrary->attachment->size / 1024)) . ' Kb';
                    ?>
                    <li data-url="<?php echo htmlspecialchars($mediaLibrary->attachment->url, ENT_QUOTES); ?>"
                        data-image="<?php echo $mediaLibrary->attachment->isImage ? 1 : 0; ?>"
                        data-alt="<?php echo $mediaAlt; ?>"
                        data-keywords="<?php echo $mediaTitle . ' ' . $mediaAlt . ' ' . $mediaUrl; ?>">
                        <a href="###" class="attachment-library-row reuse" title="<?php _e('点击插入文件'); ?>">
                            <span class="attachment-library-preview">
                                <?php if ($mediaLibrary->attachment->isImage): ?>
                                    <img src="<?php echo $mediaUrl; ?>" alt="" width="28" height="28"/>
                                <?php else: ?>
                                    <i class="mime-<?php echo $mime; ?>"></i>
                                <?php endif; ?>
                            </span>
                            <span class="attachment-library-body">
                                <span class="attachment-library-title"><?php echo $mediaTitle; ?></span>
                                <span class="attachment-library-meta"><?php echo $size; ?></span>
                            </span>
                            <span class="attachment-library-action" aria-hidden="true"><?php _e('插入'); ?></span>
                        </a>
                    </li>
                <?php endwhile; ?>
            </ul>
            <p id="attachment-library-empty" class="none hidden"><?php _e('没有匹配的媒体'); ?></p>
        </div>
    <?php endif; ?>
    <ul id="file-list">
    <?php while ($attachment->next()): ?>
        <li data-cid="<?php $attachment->cid(); ?>"
            data-url="<?php echo htmlspecialchars($attachment->attachment->url, ENT_QUOTES); ?>"
            data-image="<?php echo $attachment->attachment->isImage ? 1 : 0; ?>"
            data-alt="<?php echo htmlspecialchars(\Typecho\Common::strBy($attachment->attachment->alt, $attachment->title), ENT_QUOTES); ?>"><input type="hidden" name="attachment[]" value="<?php $attachment->cid(); ?>" />
            <a class="insert" title="<?php _e('点击插入文件'); ?>" href="###"><?php $attachment->title(); ?></a>
            <div class="info">
                <?php echo number_format(ceil($attachment->attachment->size / 1024)); ?> Kb
                <a class="file" target="_blank" href="<?php $options->adminUrl('media.php?cid=' . $attachment->cid); ?>" title="<?php _e('编辑'); ?>"><i class="i-edit"></i></a>
                <a href="###" class="delete" title="<?php _e('删除'); ?>"><i class="i-delete"></i></a>
            </div>
        </li>
    <?php endwhile; ?>
    </ul>
</div>
