<?php if (!defined('__TYPECHO_ADMIN__')) exit; ?>
<?php $applicationDeleteConfirm = true; ?>
<div class="row typecho-page-main" role="main">
    <div class="col-mb-12 col-tb-8">
        <?php \Widget\Languages\Rows::alloc()->to($languages); ?>
        <h4 class="typecho-list-table-title"><?php _e('已安装的语言包'); ?></h4>
        <table class="typecho-list-table">
            <colgroup>
                <col width="32%"/>
                <col width="18%"/>
                <col width="18%" class="kit-hidden-mb"/>
                <col width=""/>
            </colgroup>
            <thead>
            <tr>
                <th><?php _e('名称'); ?></th>
                <th><?php _e('标识'); ?></th>
                <th class="kit-hidden-mb"><?php _e('来源'); ?></th>
                <th><?php _e('操作'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if ($languages->have()): ?>
                <?php while ($languages->next()): ?>
                    <tr id="language-<?php $languages->name(); ?>"<?php if ($languages->activated): ?> class="current"<?php endif; ?>>
                        <td>
                            <?php $languages->title(); ?>
                            <?php if ($languages->activated): ?>
                                <span class="description">(<?php _e('当前语言'); ?>)</span>
                            <?php endif; ?>
                            <?php if ($languages->description): ?>
                                <p class="description"><?php $languages->description(); ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="mono"><?php $languages->name(); ?></td>
                        <td class="kit-hidden-mb"><?php $languages->builtin ? _e('内置') : _e('已安装'); ?></td>
                        <td>
                            <?php if ($languages->activated): ?>
                                <span class="important"><?php _e('当前语言'); ?></span>
                            <?php else: ?>
                                <a href="<?php $security->index('/action/languages-edit?change=' . $languages->name); ?>"><?php _e('启用'); ?></a>
                            <?php endif; ?>

                            <?php if (!$languages->builtin): ?>
                                &bull;
                                <a class="operate-delete"
                                   lang="<?php _e('你确认要删除语言包 %s 吗?', $languages->name); ?>"
                                   href="<?php $security->index('/action/languages-edit?delete=' . $languages->name); ?>"><?php _e('删除'); ?></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4"><h6 class="typecho-list-table-title"><?php _e('没有安装语言包'); ?></h6></td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="col-mb-12 col-tb-4" role="form">
        <h3><?php _e('安装语言包'); ?></h3>
        <form action="<?php $security->index('/action/languages-edit?do=install'); ?>" method="post" enctype="multipart/form-data">
            <ul class="typecho-option">
                <li>
                    <label class="typecho-label" for="language-upload"><?php _e('选择 .mo 语言包文件'); ?></label>
                    <input id="language-upload" name="language" type="file" class="file" accept=".mo" required>
                    <p class="description"><?php _e('语言包文件名需要使用 en_US.mo 这样的格式，安装后可以在上方启用。'); ?></p>
                </li>
            </ul>
            <ul class="typecho-option typecho-option-submit">
                <li>
                    <button type="submit" class="btn primary"><?php _e('安装语言包'); ?></button>
                </li>
            </ul>
        </form>
    </div>
</div>
