<?php
include 'common.php';
include 'header.php';
include 'menu.php';

$post = \Widget\Contents\Post\Edit::alloc()->prepare();
?>
<main class="main">
    <div class="body container">
        <form id="write-post-form" class="typecho-page-main typecho-post-area write-layout" action="<?php $security->index('/action/contents-post-edit'); ?>" method="post" name="write_post">
            <div class="write-topbar">
                <?php include 'page-title.php'; ?>
                <span class="left write-status">
                    <button type="button" id="btn-cancel-preview" class="btn write-icon-button"
                            title="<?php _e('取消预览'); ?>" aria-label="<?php _e('取消预览'); ?>">
                        <i class="i-caret-left" aria-hidden="true"></i>
                    </button>
                </span>
                <div class="write-actions">
                    <button type="button" class="btn write-icon-button write-panel-toggle" aria-controls="write-settings-panel"
                            aria-expanded="false" title="<?php _e('选项与附件'); ?>" aria-label="<?php _e('选项与附件'); ?>">
                        <i class="write-action-icon write-action-settings" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="btn write-icon-button write-custom-fields-toggle" aria-controls="write-custom-fields-panel"
                            aria-expanded="false" title="<?php _e('自定义字段'); ?>" aria-label="<?php _e('自定义字段'); ?>">
                        <i class="write-action-icon write-action-fields" aria-hidden="true"></i>
                    </button>
                    <input type="hidden" name="do" value="publish" />
                    <input type="hidden" name="cid" value="<?php $post->cid(); ?>"/>
                    <button type="button" id="btn-preview" class="btn write-icon-button" title="<?php _e('预览文章'); ?>" aria-label="<?php _e('预览文章'); ?>">
                        <i class="write-action-icon write-action-preview" aria-hidden="true"></i>
                    </button>
                    <button type="submit" name="do" value="save" id="btn-save"
                            class="btn write-icon-button" title="<?php _e('保存草稿'); ?>" aria-label="<?php _e('保存草稿'); ?>">
                        <i class="write-action-icon write-action-save" aria-hidden="true"></i>
                    </button>
                    <button type="submit" name="do" value="publish" class="btn primary write-icon-button"
                            id="btn-submit" title="<?php _e('发布文章'); ?>" aria-label="<?php _e('发布文章'); ?>">
                        <i class="write-action-icon write-action-publish" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div class="write-workspace">
                <div id="write-toolbar-slot" class="write-toolbar-slot" aria-label="<?php _e('编辑工具栏'); ?>"></div>
                <div class="write-grid">
                <div class="write-editor-column" role="main">
                <?php if ($post->draft): ?>
                    <?php if ($post->draft['cid'] != $post->cid): ?>
                        <?php $postModifyDate = new \Typecho\Date($post->draft['modified']); ?>
                        <cite
                            class="edit-draft-notice"><?php _e('你正在编辑的是保存于 %s 的修订版, 你也可以 <a href="%s">删除它</a>', $postModifyDate->word(),
                                $security->getIndex('/action/contents-post-edit?do=deleteDraft&cid=' . $post->cid)); ?></cite>
                    <?php else: ?>
                        <cite class="edit-draft-notice"><?php _e('当前正在编辑的是未发布的草稿'); ?></cite>
                    <?php endif; ?>
                    <input name="draft" type="hidden" value="<?php echo $post->draft['cid'] ?>"/>
                <?php endif; ?>

                <p class="title">
                    <label for="title" class="sr-only"><?php _e('标题'); ?></label>
                    <input type="text" id="title" name="title" autocomplete="off" value="<?php $post->title(); ?>"
                           placeholder="<?php _e('标题'); ?>" class="w-100 text title"/>
                </p>
                <?php $permalink = \Typecho\Common::url($options->routingTable['post']['url'], $options->index);
                [$scheme, $permalink] = explode(':', $permalink, 2);
                $permalink = ltrim($permalink, '/');
                $permalink = preg_replace("/\[([_a-z0-9-]+)[^\]]*\]/i", "{\\1}", $permalink);
                if ($post->have()) {
                    $permalink = preg_replace_callback(
                        "/\{(cid|category|year|month|day)\}/i",
                        function ($matches) use ($post) {
                            $key = $matches[1];
                            return $post->getRouterParam($key);
                        },
                        $permalink
                    );
                }
                $input = '<input type="text" id="slug" name="slug" autocomplete="off" value="' . htmlspecialchars($post->slug ?? '') . '" class="mono" />';
                ?>
                <p class="mono url-slug">
                    <label for="slug" class="sr-only"><?php _e('网址缩略名'); ?></label>
                    <?php echo preg_replace("/\{slug\}/i", $input, $permalink); ?>
                </p>
                <p>
                    <label for="text" class="sr-only"><?php _e('文章内容'); ?></label>
                    <textarea style="height: <?php $options->editorSize(); ?>px" autocomplete="off" id="text"
                              name="text" class="w-100 mono"><?php echo htmlspecialchars($post->text); ?></textarea>
                </p>

                <?php \Typecho\Plugin::factory('admin/write-post.php')->call('content', $post); ?>
                </div>

                <aside id="edit-secondary" class="write-outline-panel" role="complementary" aria-label="<?php _e('文章大纲'); ?>">
                    <section class="write-outline">
                        <h3 class="write-outline-title"><?php _e('大纲'); ?></h3>
                        <ol id="write-outline-list" class="write-outline-list"></ol>
                        <p id="write-outline-empty" class="write-outline-empty"><?php _e('暂无标题节点'); ?></p>
                    </section>
                </aside>
                </div>
            </div>

            <div id="write-settings-panel" class="write-settings-panel hidden" role="dialog"
                 aria-label="<?php _e('选项与附件'); ?>">
                <div class="write-settings-popover">
                    <div class="write-settings-header">
                        <strong><?php _e('选项与附件'); ?></strong>
                        <button type="button" class="write-settings-close" aria-label="<?php _e('关闭'); ?>">×</button>
                    </div>
                <ul class="typecho-option-tabs">
                    <li class="active w-50"><a href="#tab-advance"><?php _e('选项'); ?></a></li>
                    <li class="w-50"><a href="#tab-files" id="tab-files-btn"><?php _e('附件'); ?></a></li>
                </ul>


                <div id="tab-advance" class="tab-content">
                    <section class="typecho-post-option" role="application">
                        <label for="date" class="typecho-label"><?php _e('发布日期'); ?></label>
                        <p><input class="typecho-date w-100" type="text" name="date" id="date" autocomplete="off"
                                  value="<?php $post->have() && $post->created > 0 ? $post->date('Y-m-d H:i') : ''; ?>"/>
                        </p>
                    </section>

                    <section class="typecho-post-option category-option">
                        <label class="typecho-label"><?php _e('分类'); ?></label>
                        <?php \Widget\Metas\Category\Rows::alloc()->to($category); ?>
                        <ul>
                            <?php $categories = $post->have() ? array_column($post->categories, 'mid') : []; ?>
                            <?php while ($category->next()): ?>
                                <li><?php echo str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $category->levels); ?><input
                                        type="checkbox" id="category-<?php $category->mid(); ?>"
                                        value="<?php $category->mid(); ?>" name="category[]"
                                        <?php if (in_array($category->mid, $categories)): ?>checked="true"<?php endif; ?>/>
                                    <label
                                        for="category-<?php $category->mid(); ?>"><?php $category->name(); ?></label>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </section>

                    <section class="typecho-post-option tag-option">
                        <label for="token-input-tags" class="typecho-label"><?php _e('标签'); ?></label>
                        <p><input id="tags" name="tags" type="text" value="<?php $post->have() ? $post->tags(',', false) : ''; ?>"
                                  class="w-100 text"/></p>
                    </section>

                    <?php
                    $postPinned = (int) $post->getEditFieldValue('_pinned', 0);
                    $postFeatured = (int) $post->getEditFieldValue('_featured', 0);
                    $postSeries = (string) $post->getEditFieldValue('_series', '');
                    ?>
                    <section class="typecho-post-option">
                        <label class="typecho-label"><?php _e('内容组织'); ?></label>
                        <p>
                            <input id="core-pinned" name="fields[int:_pinned]" type="checkbox" value="1"
                                   <?php if ($postPinned > 0): ?>checked="true"<?php endif; ?> />
                            <label for="core-pinned"><?php _e('置顶'); ?></label>
                            <input id="core-featured" name="fields[int:_featured]" type="checkbox" value="1"
                                   <?php if ($postFeatured > 0): ?>checked="true"<?php endif; ?> />
                            <label for="core-featured"><?php _e('推荐'); ?></label>
                        </p>
                        <p><input id="core-series" name="fields[_series]" type="text"
                                  value="<?php echo htmlspecialchars($postSeries); ?>" class="w-100 text"
                                  placeholder="<?php _e('系列名称'); ?>"/></p>
                    </section>

                    <?php \Typecho\Plugin::factory('admin/write-post.php')->call('option', $post); ?>

                    <?php
                    $seoTitle = (string) $post->getEditFieldValue('_seo_title', '');
                    $seoDescription = (string) $post->getEditFieldValue('_seo_description', '');
                    $ogImage = (string) $post->getEditFieldValue('_og_image', '');
                    ?>
                    <div class="typecho-post-option post-option-panels">
                        <div class="post-option-panel-actions">
                            <button type="button" class="btn btn-xs post-option-toggle"
                                    data-panel="#advance-panel" aria-controls="advance-panel" aria-expanded="false">
                                <?php _e('高级选项'); ?> <i class="i-caret-down"></i>
                            </button>
                            <button type="button" class="btn btn-xs post-option-toggle"
                                    data-panel="#seo-panel" aria-controls="seo-panel" aria-expanded="false">
                                <?php _e('SEO'); ?> <i class="i-caret-down"></i>
                            </button>
                        </div>

                        <div id="advance-panel" class="post-option-panel-content hidden">

                            <?php if ($user->pass('editor', true)): ?>
                                <section class="typecho-post-option visibility-option">
                                    <label for="visibility" class="typecho-label"><?php _e('公开度'); ?></label>
                                    <p>
                                        <select id="visibility" name="visibility">
                                            <?php if ($user->pass('editor', true)): ?>
                                                <option
                                                    value="publish"<?php if (($post->status == 'publish' && !$post->password) || !$post->status): ?> selected<?php endif; ?>><?php _e('公开'); ?></option>
                                                <option
                                                    value="hidden"<?php if ($post->status == 'hidden'): ?> selected<?php endif; ?>><?php _e('隐藏'); ?></option>
                                                <option
                                                    value="password"<?php if (strlen($post->password ?? '') > 0): ?> selected<?php endif; ?>><?php _e('密码保护'); ?></option>
                                                <option
                                                    value="private"<?php if ($post->status == 'private'): ?> selected<?php endif; ?>><?php _e('私密'); ?></option>
                                            <?php endif; ?>
                                            <option
                                                value="waiting"<?php if (!$user->pass('editor', true) || $post->status == 'waiting'): ?> selected<?php endif; ?>><?php _e('待审核'); ?></option>
                                        </select>
                                    </p>
                                    <p id="post-password"<?php if (strlen($post->password ?? '') == 0): ?> class="hidden"<?php endif; ?>>
                                        <label for="protect-pwd" class="sr-only">内容密码</label>
                                        <input type="text" name="password" id="protect-pwd" class="text-s"
                                               value="<?php $post->password(); ?>" size="16"
                                               placeholder="<?php _e('内容密码'); ?>" autocomplete="off"/>
                                    </p>
                                </section>
                            <?php endif; ?>

                            <section class="typecho-post-option allow-option">
                                <label class="typecho-label"><?php _e('权限控制'); ?></label>
                                <ul>
                                    <li><input id="allowComment" name="allowComment" type="checkbox" value="1"
                                               <?php if ($post->allow('comment')): ?>checked="true"<?php endif; ?> />
                                        <label for="allowComment"><?php _e('允许评论'); ?></label></li>
                                    <li><input id="allowPing" name="allowPing" type="checkbox" value="1"
                                               <?php if ($post->allow('ping')): ?>checked="true"<?php endif; ?> />
                                        <label for="allowPing"><?php _e('允许被引用'); ?></label></li>
                                    <li><input id="allowFeed" name="allowFeed" type="checkbox" value="1"
                                               <?php if ($post->allow('feed')): ?>checked="true"<?php endif; ?> />
                                        <label for="allowFeed"><?php _e('允许在聚合中出现'); ?></label></li>
                                </ul>
                            </section>

                            <section class="typecho-post-option">
                                <label for="trackback" class="typecho-label"><?php _e('引用通告'); ?></label>
                                <p><textarea id="trackback" class="w-100 mono" name="trackback" rows="2"></textarea></p>
                                <p class="description"><?php _e('每一行一个引用地址, 用回车隔开'); ?></p>
                            </section>

                            <?php \Typecho\Plugin::factory('admin/write-post.php')->call('advanceOption', $post); ?>
                        </div><!-- end #advance-panel -->

                        <div id="seo-panel" class="post-option-panel-content hidden">
                            <section class="typecho-post-option seo-option">
                                <p>
                                    <label for="seo-title" class="sr-only"><?php _e('SEO 标题'); ?></label>
                                    <input id="seo-title" name="fields[_seo_title]" type="text"
                                           value="<?php echo htmlspecialchars($seoTitle); ?>" class="w-100 text"
                                           placeholder="<?php _e('SEO 标题'); ?>"/>
                                </p>
                                <p>
                                    <label for="seo-description" class="sr-only"><?php _e('SEO 描述'); ?></label>
                                    <textarea id="seo-description" name="fields[_seo_description]" class="w-100"
                                              rows="3" placeholder="<?php _e('SEO 描述'); ?>"><?php echo htmlspecialchars($seoDescription); ?></textarea>
                                </p>
                                <p>
                                    <label for="og-image" class="sr-only"><?php _e('Open Graph 图片地址'); ?></label>
                                    <input id="og-image" name="fields[_og_image]" type="text"
                                           value="<?php echo htmlspecialchars($ogImage); ?>" class="w-100 text"
                                           placeholder="<?php _e('Open Graph 图片地址'); ?>"/>
                                </p>
                            </section>
                        </div><!-- end #seo-panel -->
                    </div>

                    <?php if ($post->have()): ?>
                        <?php $historyItems = $post->getHistoryItems(); ?>
                        <?php if (!empty($historyItems)): ?>
                            <section class="typecho-post-option revision-history">
                                <label class="typecho-label"><?php _e('修订历史'); ?></label>
                                <ul>
                                    <?php foreach ($historyItems as $history): ?>
                                        <?php
                                        $historyDate = new \Typecho\Date($history['modified']);
                                        $historyLabel = match ($history['type']) {
                                            'revision' => _t('当前修订'),
                                            'history'  => _t('发布历史'),
                                            default    => _t('草稿快照'),
                                        };
                                        ?>
                                        <li>
                                            <span><?php echo $historyLabel; ?> · <?php echo $historyDate->word(); ?></span>
                                            <a href="<?php echo $security->getIndex('/action/contents-post-edit?do=rollback&cid=' . $post->cid . '&history=' . $history['cid']); ?>"><?php _e('回滚'); ?></a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </section>
                        <?php endif; ?>
                        <?php $modified = new \Typecho\Date($post->modified); ?>
                        <section class="typecho-post-option">
                            <p class="description">
                                <br>&mdash;<br>
                                <?php _e('本文由 <a href="%s">%s</a> 撰写',
                                    \Typecho\Common::url('manage-posts.php?uid=' . $post->author->uid, $options->adminUrl), $post->author->screenName); ?>
                                <br>
                                <?php _e('最后更新于 %s', $modified->word()); ?>
                            </p>
                        </section>
                    <?php endif; ?>
                </div><!-- end #tab-advance -->

                <div id="tab-files" class="tab-content hidden">
                    <?php include 'file-upload.php'; ?>
                </div><!-- end #tab-files -->
                </div>
            </div>

            <div id="write-custom-fields-panel" class="write-settings-panel write-custom-fields-panel hidden" role="dialog"
                 aria-label="<?php _e('自定义字段'); ?>">
                <div class="write-settings-popover">
                    <div class="write-settings-header">
                        <strong><?php _e('自定义字段'); ?></strong>
                        <button type="button" class="write-field-add operate-add" title="<?php _e('添加字段'); ?>" aria-label="<?php _e('添加字段'); ?>">
                            <span aria-hidden="true"></span>
                        </button>
                        <button type="button" class="write-settings-close" aria-label="<?php _e('关闭'); ?>">×</button>
                    </div>
                    <?php $customFieldsAlwaysOpen = true; ?>
                    <?php include 'custom-fields.php'; ?>
                    <?php unset($customFieldsAlwaysOpen); ?>
                </div>
            </div>
        </form>
    </div>
</main>

<?php
include 'copyright.php';
include 'common-js.php';
include 'form-js.php';
include 'write-js.php';

\Typecho\Plugin::factory('admin/write-post.php')->trigger($plugged)->call('richEditor', $post);
if (!$plugged) {
    include 'editor-js.php';
}

include 'file-upload-js.php';
include 'custom-fields-js.php';
\Typecho\Plugin::factory('admin/write-post.php')->call('bottom', $post);
include 'footer.php';
?>
