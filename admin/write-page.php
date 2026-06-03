<?php
include 'common.php';
include 'header.php';
include 'menu.php';

$page = \Widget\Contents\Page\Edit::alloc()->prepare();

$parentPageId = $page->getParent();
$parentPages = [0 => _t('不选择')];
$parents = \Widget\Contents\Page\Admin::allocWithAlias(
    'options',
    'ignoreRequest=1' . ($request->is('cid') ? '&ignore=' . $request->get('cid') : '')
);

while ($parents->next()) {
    $parentPages[$parents->cid] = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $parents->levels) . $parents->title;
}
?>
<main class="main">
    <div class="body container">
        <form id="write-page-form" class="typecho-page-main typecho-post-area write-layout" action="<?php $security->index('/action/contents-page-edit'); ?>" method="post" name="write_page">
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
                    <input type="hidden" name="cid" value="<?php $page->cid(); ?>"/>
                    <button type="button" id="btn-preview" class="btn write-icon-button" title="<?php _e('预览页面'); ?>" aria-label="<?php _e('预览页面'); ?>">
                        <i class="write-action-icon write-action-preview" aria-hidden="true"></i>
                    </button>
                    <button type="submit" name="do" value="save" id="btn-save"
                            class="btn write-icon-button" title="<?php _e('保存草稿'); ?>" aria-label="<?php _e('保存草稿'); ?>">
                        <i class="write-action-icon write-action-save" aria-hidden="true"></i>
                    </button>
                    <button type="submit" name="do" value="publish" class="btn primary write-icon-button"
                            id="btn-submit" title="<?php _e('发布页面'); ?>" aria-label="<?php _e('发布页面'); ?>">
                        <i class="write-action-icon write-action-publish" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div class="write-workspace">
                <div id="write-toolbar-slot" class="write-toolbar-slot" aria-label="<?php _e('编辑工具栏'); ?>"></div>
                <div class="write-grid">
                <div class="write-editor-column" role="main">
                <?php if ($page->draft): ?>
                    <?php if ($page->draft['cid'] != $page->cid): ?>
                        <?php $pageModifyDate = new \Typecho\Date($page->draft['modified']); ?>
                        <cite
                            class="edit-draft-notice"><?php _e('你正在编辑的是保存于 %s 的修订版, 你也可以 <a href="%s">删除它</a>', $pageModifyDate->word(),
                                $security->getIndex('/action/contents-page-edit?do=deleteDraft&cid=' . $page->cid)); ?></cite>
                    <?php else: ?>
                        <cite class="edit-draft-notice"><?php _e('当前正在编辑的是未发布的草稿'); ?></cite>
                    <?php endif; ?>
                    <input name="draft" type="hidden" value="<?php echo $page->draft['cid'] ?>"/>
                <?php endif; ?>

                <p class="title">
                    <label for="title" class="sr-only"><?php _e('标题'); ?></label>
                    <input type="text" id="title" name="title" autocomplete="off" value="<?php $page->title(); ?>"
                           placeholder="<?php _e('标题'); ?>" class="w-100 text title"/>
                </p>
                <?php $permalink = \Typecho\Common::url($options->routingTable['page']['url'], $options->index);
                [$scheme, $permalink] = explode(':', $permalink, 2);
                $permalink = ltrim($permalink, '/');
                $permalink = preg_replace("/\[([_a-z0-9-]+)[^\]]*\]/i", "{\\1}", $permalink);
                if ($page->have()) {
                    $permalink = preg_replace_callback(
                        "/\{(cid)\}/i",
                        function ($matches) use ($page) {
                            $key = $matches[1];
                            return $page->getRouterParam($key);
                        },
                        $permalink
                    );
                }
                $input = '<input type="text" id="slug" name="slug" autocomplete="off" value="' . htmlspecialchars($page->slug ?? '') . '" class="mono" />';
                ?>
                <p class="mono url-slug">
                    <label for="slug" class="sr-only"><?php _e('网址缩略名'); ?></label>
                    <?php echo preg_replace_callback("/\{(slug|directory)\}/i", function ($matches) use ($input) {
                        if ($matches[1] == 'slug') {
                            return $input;
                        } else {
                            return '{directory/' . $input . '}';
                        }
                    }, $permalink); ?>
                </p>
                <p>
                    <label for="text" class="sr-only"><?php _e('页面内容'); ?></label>
                    <textarea style="height: <?php $options->editorSize(); ?>px" autocomplete="off" id="text"
                              name="text" class="w-100 mono"><?php echo htmlspecialchars($page->text); ?></textarea>
                </p>

                <?php \Typecho\Plugin::factory('admin/write-page.php')->call('content', $page); ?>
                </div>

                <aside id="edit-secondary" class="write-outline-panel" role="complementary" aria-label="<?php _e('页面大纲'); ?>">
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
                                  value="<?php $page->have() && $page->created > 0 ? $page->date('Y-m-d H:i') : ''; ?>"/>
                        </p>
                    </section>

                    <section class="typecho-post-option">
                        <label for="order" class="typecho-label"><?php _e('页面顺序'); ?></label>
                        <p><input type="number" id="order" name="order" value="<?php $page->order(); ?>"
                                  class="w-100"/></p>
                        <p class="description"><?php _e('为你的自定义页面设定一个序列值以后, 能够使得它们按此值从小到大排列'); ?></p>
                    </section>

                    <section class="typecho-post-option">
                        <label for="template" class="typecho-label"><?php _e('自定义模板'); ?></label>
                        <p>
                            <select name="template" id="template">
                                <option value=""><?php _e('不选择'); ?></option>
                                <?php $templates = $page->getTemplates();
                                foreach ($templates as $template => $name): ?>
                                    <option
                                        value="<?php echo $template; ?>"<?php if ($template == $page->template): ?> selected="true"<?php endif; ?>><?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </p>
                        <p class="description"><?php _e('如果你为此页面选择了一个自定义模板, 系统将按照你选择的模板文件展现它'); ?></p>
                    </section>

                    <section class="typecho-post-option">
                        <label for="parent" class="typecho-label"><?php _e('父级页面'); ?></label>
                        <p>
                            <select name="parent" id="parent">
                                <?php foreach ($parentPages as $pageId => $pageTitle): ?>
                                    <option
                                        value="<?php echo $pageId; ?>"<?php if ($pageId == ($page->parent ?? $parentPageId)): ?> selected="true"<?php endif; ?>><?php echo $pageTitle; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </p>
                        <p class="description"><?php _e('如果你设定了父级页面, 此页面将作为子页面呈现'); ?></p>
                    </section>

                    <?php \Typecho\Plugin::factory('admin/write-page.php')->call('option', $page); ?>

                    <?php
                    $seoTitle = (string) $page->getEditFieldValue('_seo_title', '');
                    $seoDescription = (string) $page->getEditFieldValue('_seo_description', '');
                    $ogImage = (string) $page->getEditFieldValue('_og_image', '');
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

                            <section class="typecho-post-option visibility-option">
                                <label for="visibility" class="typecho-label"><?php _e('公开度'); ?></label>
                                <p>
                                    <select id="visibility" name="visibility">
                                        <option
                                            value="publish"<?php if ($page->status == 'publish' || !$page->status): ?> selected<?php endif; ?>><?php _e('公开'); ?></option>
                                        <option
                                            value="hidden"<?php if ($page->status == 'hidden'): ?> selected<?php endif; ?>><?php _e('隐藏'); ?></option>
                                    </select>
                                </p>
                            </section>

                            <section class="typecho-post-option allow-option">
                                <label class="typecho-label"><?php _e('权限控制'); ?></label>
                                <ul>
                                    <li><input id="allowComment" name="allowComment" type="checkbox" value="1"
                                               <?php if ($page->allow('comment')): ?>checked="true"<?php endif; ?> />
                                        <label for="allowComment"><?php _e('允许评论'); ?></label></li>
                                    <li><input id="allowPing" name="allowPing" type="checkbox" value="1"
                                               <?php if ($page->allow('ping')): ?>checked="true"<?php endif; ?> />
                                        <label for="allowPing"><?php _e('允许被引用'); ?></label></li>
                                    <li><input id="allowFeed" name="allowFeed" type="checkbox" value="1"
                                               <?php if ($page->allow('feed')): ?>checked="true"<?php endif; ?> />
                                        <label for="allowFeed"><?php _e('允许在聚合中出现'); ?></label></li>
                                </ul>
                            </section>

                            <?php \Typecho\Plugin::factory('admin/write-page.php')->call('advanceOption', $page); ?>
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

                    <?php if ($page->have()): ?>
                        <?php $historyItems = $page->getHistoryItems(); ?>
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
                                            <a href="<?php echo $security->getIndex('/action/contents-page-edit?do=rollback&cid=' . $page->cid . '&history=' . $history['cid']); ?>"><?php _e('回滚'); ?></a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </section>
                        <?php endif; ?>
                        <?php $modified = new \Typecho\Date($page->modified); ?>
                        <section class="typecho-post-option">
                            <p class="description">
                                <br>&mdash;<br>
                                <?php _e('本页面由 <a href="%s">%s</a> 创建',
                                    \Typecho\Common::url('manage-pages.php?uid=' . $page->author->uid, $options->adminUrl), $page->author->screenName); ?>
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

\Typecho\Plugin::factory('admin/write-page.php')->trigger($plugged)->call('richEditor', $page);
if (!$plugged) {
    include 'editor-js.php';
}

include 'file-upload-js.php';
include 'custom-fields-js.php';
\Typecho\Plugin::factory('admin/write-page.php')->bottom($page);
include 'footer.php';
?>
