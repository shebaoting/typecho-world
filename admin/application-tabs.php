<?php if (!defined('__TYPECHO_ADMIN__')) exit; ?>
<?php
$applicationTabs = [
    'themes'    => _t('外观'),
    'plugins'   => _t('插件'),
    'languages' => _t('语言包'),
];

$applicationTab = $applicationTab ?? 'themes';
?>
<ul class="typecho-option-tabs fix-tabs">
    <?php foreach ($applicationTabs as $key => $label): ?>
        <li<?php if ($applicationTab == $key): ?> class="current"<?php endif; ?>>
            <a href="<?php $options->adminUrl('applications.php?tab=' . $key); ?>"><?php echo $label; ?></a>
        </li>
    <?php endforeach; ?>
</ul>
