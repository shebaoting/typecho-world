<?php
include 'common.php';
include 'header.php';
include 'menu.php';

$themeName = $options->theme;
$manifest = \Typecho\Theme\Manifest::load($themeName, $options);
$compatibility = $manifest->compatibility();
$checker = new \Typecho\Theme\Checker();
$findings = $checker->check($themeName, $options);
$summary = \Typecho\Theme\Checker::summary($findings);
$last = \Typecho\Theme\Diagnostics::last($themeName);
$db = \Typecho\Db::get();

$escape = function ($value) use ($options) {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, $options->charset);
};

$levelLabel = [
    'error'   => _t('错误'),
    'warning' => _t('警告'),
    'info'    => _t('提示'),
];
?>

<main class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <?php include 'theme-tabs.php'; ?>

        <div class="typecho-page-main" role="main">
            <section class="typecho-list-operate">
                <h3><?php _e('当前主题'); ?></h3>
                <p>
                    <strong><?php echo $escape($manifest->get('title', $themeName)); ?></strong>
                    <span class="description">
                        <?php _e('目录：%s', $escape($themeName)); ?>
                    </span>
                </p>
                <p class="description">
                    <?php _e('Manifest 缓存：%s', $manifest->isCacheHit() ? _t('命中') : _t('本次重建')); ?>
                    &nbsp;·&nbsp;
                    <?php _e('核心版本：%s', $escape(\Typecho\Common::VERSION)); ?>
                    &nbsp;·&nbsp;
                    <?php _e('后台当前请求查询数：%d', $db->getQueryCount()); ?>
                </p>
                <p class="description">
                    <?php _e('主题要求：%s', $escape($manifest->coreRequirement() ?: _t('未声明'))); ?>
                    &nbsp;·&nbsp;
                    <?php echo !empty($compatibility['compatible']) ? _t('兼容当前核心') : $escape($compatibility['message'] ?? ''); ?>
                </p>
            </section>

            <section>
                <h3><?php _e('Theme Check'); ?></h3>
                <p class="description">
                    <?php _e('错误 %d，警告 %d，提示 %d', $summary['error'], $summary['warning'], $summary['info']); ?>
                </p>
                <?php if (empty($findings)): ?>
                    <p><?php _e('没有发现问题。'); ?></p>
                <?php else: ?>
                    <table class="typecho-list-table">
                        <thead>
                        <tr>
                            <th><?php _e('级别'); ?></th>
                            <th><?php _e('规则'); ?></th>
                            <th><?php _e('文件'); ?></th>
                            <th><?php _e('说明'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($findings as $finding): ?>
                            <tr>
                                <td><?php echo $escape($levelLabel[$finding['level']] ?? $finding['level']); ?></td>
                                <td><code><?php echo $escape($finding['code']); ?></code></td>
                                <td><?php echo $escape($finding['file'] ?? ''); ?></td>
                                <td><?php echo $escape($finding['message']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>

            <section>
                <h3><?php _e('约定识别结果'); ?></h3>
                <div class="row">
                    <div class="col-mb-12 col-tb-6">
                        <h4><?php _e('模板'); ?></h4>
                        <ul>
                            <?php foreach ($manifest->templates() as $key => $file): ?>
                                <li><code><?php echo $escape($key); ?></code> → <?php echo $escape($file); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="col-mb-12 col-tb-6">
                        <h4><?php _e('片段与组件'); ?></h4>
                        <ul>
                            <?php foreach ($manifest->parts() as $key => $file): ?>
                                <li><?php _e('片段'); ?> <code><?php echo $escape($key); ?></code> → <?php echo $escape($file); ?></li>
                            <?php endforeach; ?>
                            <?php foreach ($manifest->components() as $key => $file): ?>
                                <li><?php _e('组件'); ?> <code><?php echo $escape($key); ?></code> → <?php echo $escape($file); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <h4><?php _e('导航位'); ?></h4>
                <ul>
                    <?php foreach ($manifest->navigationSlots() as $slot => $label): ?>
                        <li><code><?php echo $escape($slot); ?></code>：<?php echo $escape($label); ?></li>
                    <?php endforeach; ?>
                </ul>
                <h4><?php _e('Design Tokens'); ?></h4>
                <?php if (empty($manifest->tokens())): ?>
                    <p class="description"><?php _e('未声明 Design Tokens。'); ?></p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($manifest->tokens() as $group => $values): ?>
                            <li><code><?php echo $escape($group); ?></code>：<?php echo $escape(implode(', ', array_keys((array) $values))); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <h4><?php _e('构建 Manifest'); ?></h4>
                <?php $build = $manifest->build(); ?>
                <?php if (empty($build)): ?>
                    <p class="description"><?php _e('未启用构建 manifest。'); ?></p>
                <?php else: ?>
                    <p class="description">
                        <?php _e('文件：%s', $escape($build['manifest'] ?? '')); ?>
                        &nbsp;·&nbsp;
                        <?php _e('入口数：%d', count($manifest->buildManifest())); ?>
                    </p>
                <?php endif; ?>
            </section>

            <section>
                <h3><?php _e('最近一次前台渲染'); ?></h3>
                <?php if (empty($last)): ?>
                    <p><?php _e('暂无记录。访问一次前台页面后刷新本页即可看到渲染数据。'); ?></p>
                <?php else: ?>
                    <p class="description">
                        <?php _e('时间：%s', $escape($last['finishedAt'] ?? '')); ?>
                        &nbsp;·&nbsp;
                        <?php _e('模板：%s', $escape($last['template'] ?? '')); ?>
                        &nbsp;·&nbsp;
                        <?php _e('渲染：%sms', $escape($last['renderTimeMs'] ?? '0')); ?>
                        &nbsp;·&nbsp;
                        <?php _e('查询：%d 次 / %sms', (int) ($last['queryCount'] ?? 0), $escape($last['queryTimeMs'] ?? '0')); ?>
                    </p>

                    <h4><?php _e('模板候选'); ?></h4>
                    <ul>
                        <?php foreach (($last['candidates'] ?? []) as $candidate): ?>
                            <li>
                                <code><?php echo $escape($candidate['key'] ?? ''); ?></code>
                                → <?php echo $escape($candidate['file'] ?? ''); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <h4><?php _e('加载资源'); ?></h4>
                    <ul>
                        <?php foreach (($last['assets'] ?? []) as $asset): ?>
                            <li>
                                <code><?php echo $escape($asset['type'] ?? ''); ?>:<?php echo $escape($asset['name'] ?? ''); ?></code>
                                → <?php echo $escape($asset['path'] ?? ''); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <h4><?php _e('片段与组件调用'); ?></h4>
                    <ul>
                        <?php foreach (($last['parts'] ?? []) as $part): ?>
                            <li><?php _e('片段'); ?> <code><?php echo $escape($part['name'] ?? ''); ?></code> → <?php echo $escape($part['file'] ?? ''); ?></li>
                        <?php endforeach; ?>
                        <?php foreach (($last['components'] ?? []) as $component): ?>
                            <li><?php _e('组件'); ?> <code><?php echo $escape($component['name'] ?? ''); ?></code> → <?php echo $escape($component['file'] ?? ''); ?></li>
                        <?php endforeach; ?>
                    </ul>

                    <h4><?php _e('片段缓存'); ?></h4>
                    <?php if (empty($last['fragments'] ?? [])): ?>
                        <p class="description"><?php _e('最近一次渲染未使用片段缓存。'); ?></p>
                    <?php else: ?>
                        <ul>
                            <?php foreach (($last['fragments'] ?? []) as $fragment): ?>
                                <li>
                                    <code><?php echo $escape($fragment['key'] ?? ''); ?></code>
                                    → <?php echo !empty($fragment['hit']) ? _t('命中') : _t('重建'); ?>
                                    <?php _e('，TTL：%d 秒', (int) ($fragment['ttl'] ?? 0)); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </div>
    </div>
</main>

<?php
include 'copyright.php';
include 'common-js.php';
include 'footer.php';
?>
