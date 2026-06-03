<?php if(!defined('__TYPECHO_ADMIN__')) exit; ?>
<?php \Typecho\Plugin::factory('admin/write-js.php')->call('write'); ?>
<?php \Widget\Metas\Tag\Cloud::alloc('sort=count&desc=1&limit=200')->to($tags); ?>

<script src="<?php $options->adminStaticUrl('js', 'timepicker.js'); ?>"></script>
<script src="<?php $options->adminStaticUrl('js', 'tokeninput.js'); ?>"></script>
<script>
$(document).ready(function() {
    // 日期时间控件
    $('#date').mask('9999-99-99 99:99').datetimepicker({
        currentText     :   '<?php _e('现在'); ?>',
        prevText        :   '<?php _e('上一月'); ?>',
        nextText        :   '<?php _e('下一月'); ?>',
        monthNames      :   ['<?php _e('一月'); ?>', '<?php _e('二月'); ?>', '<?php _e('三月'); ?>', '<?php _e('四月'); ?>',
            '<?php _e('五月'); ?>', '<?php _e('六月'); ?>', '<?php _e('七月'); ?>', '<?php _e('八月'); ?>',
            '<?php _e('九月'); ?>', '<?php _e('十月'); ?>', '<?php _e('十一月'); ?>', '<?php _e('十二月'); ?>'],
        dayNames        :   ['<?php _e('星期日'); ?>', '<?php _e('星期一'); ?>', '<?php _e('星期二'); ?>',
            '<?php _e('星期三'); ?>', '<?php _e('星期四'); ?>', '<?php _e('星期五'); ?>', '<?php _e('星期六'); ?>'],
        dayNamesShort   :   ['<?php _e('周日'); ?>', '<?php _e('周一'); ?>', '<?php _e('周二'); ?>', '<?php _e('周三'); ?>',
            '<?php _e('周四'); ?>', '<?php _e('周五'); ?>', '<?php _e('周六'); ?>'],
        dayNamesMin     :   ['<?php _e('日'); ?>', '<?php _e('一'); ?>', '<?php _e('二'); ?>', '<?php _e('三'); ?>',
            '<?php _e('四'); ?>', '<?php _e('五'); ?>', '<?php _e('六'); ?>'],
        closeText       :   '<?php _e('完成'); ?>',
        timeOnlyTitle   :   '<?php _e('选择时间'); ?>',
        timeText        :   '<?php _e('时间'); ?>',
        hourText        :   '<?php _e('时'); ?>',
        amNames         :   ['<?php _e('上午'); ?>', 'A'],
        pmNames         :   ['<?php _e('下午'); ?>', 'P'],
        minuteText      :   '<?php _e('分'); ?>',
        dateFormat      :   'yy-mm-dd',
        timeFormat      :   'HH:mm',
        showSecond      :   false,
        showMillisec    :   false,
        showMicrosec    :   false,
        showTimezone    :   false,
        timezone        :   <?php $options->timezone(); ?> / 60,
        hour            :   (new Date()).getHours(),
        minute          :   (new Date()).getMinutes(),
        showButtonPanel :   true
    });

    // 聚焦
    $('#title').select();

    // text 自动拉伸
    Typecho.editorResize('text', '<?php $security->index('/action/ajax?do=editorResize'); ?>');

    // tag autocomplete 提示
    const tags = $('#tags'), tagsPre = [];
    
    if (tags.length > 0) {
        const items = tags.val().split(',');
        for (let i = 0; i < items.length; i ++) {
            const tag = items[i];

            if (!tag) {
                continue;
            }

            tagsPre.push({
                id      :   tag,
                tags    :   tag
            });
        }

        tags.tokenInput(<?php 
        $data = array();
        while ($tags->next()) {
            $data[] = array(
                'id'    =>  $tags->name,
                'tags'  =>  $tags->name
            );
        }
        echo json_encode($data);
        ?>, {
            propertyToSearch:   'tags',
            tokenValue      :   'tags',
            searchDelay     :   0,
            preventDuplicates   :   true,
            animateDropdown :   false,
            hintText        :   '<?php _e('请输入标签名'); ?>',
            noResultsText   :   '<?php _e('此标签不存在, 按回车创建'); ?>',
            prePopulate     :   tagsPre,

            onResult        :   function (result, query, val) {
                // remove special chars
                val = val.replace(/<|>|&|"|'/g, '');

                if (!query) {
                    return result;
                }

                if (!result) {
                    result = [];
                }

                if (!result[0] || result[0]['id'] !== query) {
                    result.unshift({
                        id      :   val,
                        tags    :   val
                    });
                }

                return result.slice(0, 5);
            }
        });

        // tag autocomplete 提示宽度设置
        $('#token-input-tags').focus(function() {
            const t = $('.token-input-dropdown'),
                offset = t.outerWidth() - t.width();
            t.width($('.token-input-list').outerWidth() - offset);
        });
    }

    // 缩略名自适应宽度
    const slug = $('#slug');

    if (slug.length > 0) {
        const wrap = $('<div />').css({
            'position'  :   'relative',
            'display'   :   'inline-block'
        }),
        justifySlug = $('<pre />').css({
            'display'   :   'block',
            'visibility':   'hidden',
            'height'    :   slug.height(),
            'padding'   :   '0 2px',
            'margin'    :   0
        }).insertAfter(slug.wrap(wrap).css({
            'left'      :   0,
            'top'       :   0,
            'minWidth'  :   '5px',
            'position'  :   'absolute',
            'width'     :   '100%'
        }));

        function justifySlugWidth() {
            const val = slug.val();
            justifySlug.text(val.length > 0 ? val : '     ');
        }

        slug.on('input propertychange', justifySlugWidth);
        justifySlugWidth();
    }

    // 处理保存文章的逻辑
    const form = $('form[name=write_post],form[name=write_page]'),
        idInput = $('input[name=cid]'),
        draft = $('input[name=draft]'),
        btnPreview = $('#btn-preview'),
        statusTarget = $('.write-status').length > 0 ? $('.write-status') : $('.left').first(),
        autoSave = $('<span id="auto-save-message"></span>').prependTo(statusTarget);

    let cid = idInput.val(),
        draftId = draft.length > 0 ? draft.val() : 0,
        changed = false,
        written = false,
        lastSaveTime = null;

    form.on('write', function () {
        written = true;
        form.trigger('datachange');
    });

    form.on('change', function () {
        if (written) {
            form.trigger('datachange');
        }
    });

    $('button[name=do]').click(function () {
        $('input[name=do]').val($(this).val());
    });

    // 自动检测离开页
    $(window).on('beforeunload', function () {
        if (changed && !form.hasClass('submitting')) {
            return '<?php _e('内容已经改变尚未保存, 您确认要离开此页面吗?'); ?>';
        }
    });

    // 发送保存请求
    Typecho.savePost = function(cb) {
        if (!changed) {
            cb && cb();
            return;
        }

        const callback = function (o) {
            lastSaveTime = o.time;
            cid = o.cid;
            draftId = o.draftId;
            idInput.val(cid);
            autoSave.text('<?php _e('已保存'); ?>' + ' (' + o.time + ')').effect('highlight', 1000);

            cb && cb();
        };

        changed = false;
        autoSave.text('<?php _e('正在保存'); ?>');

        const data = new FormData(form.get(0));
        data.append('do', 'save');
        form.triggerHandler('submit');

        $.ajax({
            url: form.attr('action'),
            processData: false,
            contentType: false,
            type: 'POST',
            data: data,
            success: callback,
            error: function () {
                autoSave.text('<?php _e('保存失败, 请重试'); ?>');
            },
            complete: function () {
                form.trigger('submitted');
            }
        });
    };

    <?php if ($options->autoSave): ?>
    // 自动保存
    let saveTimer = null;
    let stopAutoSave = false;

    form.on('datachange', function () {
        changed = true;
        autoSave.text('<?php _e('尚未保存'); ?>' + (lastSaveTime ? ' (<?php _e('上次保存时间'); ?>: ' + lastSaveTime + ')' : ''));

        if (saveTimer) {
            clearTimeout(saveTimer);
        }

        saveTimer = setTimeout(function () {
            !stopAutoSave && Typecho.savePost();
        }, 3000);
    }).on('submit', function () {
        stopAutoSave = true;
    }).on('submitted', function () {
        stopAutoSave = false;
    });
    <?php else: ?>
    form.on('datachange', function () {
        changed = true;
    });
    <?php endif; ?>

    // 计算夏令时偏移
    const dstOffset = (function () {
        const d = new Date(),
            jan = new Date(d.getFullYear(), 0, 1),
            jul = new Date(d.getFullYear(), 6, 1),
            stdOffset = Math.max(jan.getTimezoneOffset(), jul.getTimezoneOffset());

        return stdOffset - d.getTimezoneOffset();
    })();
    
    if (dstOffset > 0) {
        $('<input name="dst" type="hidden" />').appendTo(form).val(dstOffset);
    }

    // 时区
    $('<input name="timezone" type="hidden" />').appendTo(form).val(- (new Date).getTimezoneOffset() * 60);

    // 预览功能
    let isFullScreen = false;

    function previewData(cid) {
        isFullScreen = $(document.body).hasClass('fullscreen');
        $(document.body).addClass('fullscreen preview');

        const frame = $('<iframe frameborder="0" class="preview-frame preview-loading"></iframe>')
            .attr('src', './preview.php?cid=' + cid)
            .attr('sandbox', 'allow-same-origin allow-scripts')
            .appendTo(document.body);

        frame.on('load', function () {
            frame.removeClass('preview-loading');
        });

        frame.height($(window).height() - 53);
    }

    function cancelPreview() {
        if (!isFullScreen) {
            $(document.body).removeClass('fullscreen');
        }

        $(document.body).removeClass('preview');
        $('.preview-frame').remove();
    }

    $('#btn-cancel-preview').click(cancelPreview);

    $(window).on('message', function (e) {
        if (e.originalEvent.data === 'cancelPreview') {
            cancelPreview();
        }
    });

    btnPreview.click(function () {
        if (changed) {
            if (confirm('<?php _e('修改后的内容需要保存后才能预览, 是否保存?'); ?>')) {
                Typecho.savePost(function () {
                    previewData(draftId);
                });
            }
        } else if (!!draftId) {
            previewData(draftId);
        } else if (!!cid) {
            previewData(cid);
        }
    });

    // 写作页右上角浮层
    const settingsPanel = $('#write-settings-panel'),
        settingsToggle = $('.write-panel-toggle'),
        customFieldsPanel = $('#write-custom-fields-panel'),
        customFieldsToggle = $('.write-custom-fields-toggle'),
        floatingPanels = settingsPanel.add(customFieldsPanel),
        floatingToggles = settingsToggle.add(customFieldsToggle);

    function closeFloatingPanels(exceptPanel) {
        floatingPanels.each(function () {
            const panel = $(this);

            if (exceptPanel && panel.is(exceptPanel)) {
                return;
            }

            panel.addClass('hidden');
            floatingToggles.filter('[aria-controls="' + panel.attr('id') + '"]').attr('aria-expanded', 'false');
        });
    }

    function toggleFloatingPanel(panel, toggle) {
        const isOpen = !panel.hasClass('hidden');

        closeFloatingPanels(panel);
        panel.toggleClass('hidden', isOpen);
        toggle.attr('aria-expanded', isOpen ? 'false' : 'true');
    }

    settingsToggle.on('click', function () {
        toggleFloatingPanel(settingsPanel, settingsToggle);

        return false;
    });

    customFieldsToggle.on('click', function () {
        toggleFloatingPanel(customFieldsPanel, customFieldsToggle);

        return false;
    });

    floatingPanels.find('.write-settings-close').on('click', function () {
        closeFloatingPanels();
        return false;
    });

    $(document).on('click', function (event) {
        if (
            floatingPanels.filter(':not(.hidden)').length === 0
            || floatingPanels.is(event.target)
            || floatingPanels.has(event.target).length > 0
            || floatingToggles.is(event.target)
            || floatingToggles.has(event.target).length > 0
        ) {
            return;
        }

        closeFloatingPanels();
    }).on('keydown', function (event) {
        if (event.key === 'Escape') {
            closeFloatingPanels();
        }
    });

    $('#title').on('focus', function () {
        form.addClass('write-title-focus');
    });

    $('#text').on('focus', function () {
        form.removeClass('write-title-focus');
    });

    function renderWriteOutline(items) {
        const list = $('#write-outline-list'),
            empty = $('#write-outline-empty');

        if (list.length === 0) {
            return;
        }

        list.empty();

        if (!items || items.length === 0) {
            empty.removeClass('hidden');
            return;
        }

        empty.addClass('hidden');

        items.forEach(function (item, index) {
            $('<li></li>')
                .attr('data-level', item.level || 2)
                .append($('<button type="button"></button>')
                    .attr('data-outline-index', index)
                    .text(item.text || '<?php _e('未命名标题'); ?>'))
                .appendTo(list);
        });
    }

    Typecho.renderWriteOutline = renderWriteOutline;

    function collectTextareaOutline() {
        const text = $('#text').val() || '',
            wrapper = document.createElement('div'),
            items = [];

        wrapper.innerHTML = text;
        Array.from(wrapper.querySelectorAll('h1,h2,h3,h4,h5,h6')).forEach(function (heading) {
            const content = $.trim(heading.textContent || '');

            if (content) {
                items.push({
                    level: parseInt(heading.tagName.replace('H', ''), 10),
                    text: content
                });
            }
        });

        if (items.length === 0) {
            text.split(/\r?\n/).forEach(function (line) {
                const match = line.match(/^\s{0,3}(#{1,6})\s+(.+?)\s*#*\s*$/);

                if (match) {
                    items.push({
                        level: match[1].length,
                        text: $.trim(match[2])
                    });
                }
            });
        }

        renderWriteOutline(items);
    }

    $('#text').on('input', collectTextareaOutline);
    collectTextareaOutline();

    // 控制选项和附件的切换
    settingsPanel.find('.typecho-option-tabs li').click(function() {
        settingsPanel.find('.typecho-option-tabs li.active').removeClass('active');
        settingsPanel.find('.tab-content').addClass('hidden');

        const activeTab = $(this).addClass('active').find('a').attr('href');
        settingsPanel.find(activeTab).removeClass('hidden');

        return false;
    });

    settingsPanel.find('.post-option-toggle').on('click', function () {
        const button = $(this),
            panels = button.closest('.post-option-panels'),
            target = settingsPanel.find(button.data('panel')),
            isOpen = button.attr('aria-expanded') === 'true';

        panels.find('.post-option-toggle').attr('aria-expanded', 'false');
        panels.find('.post-option-panel-content').addClass('hidden');

        if (!isOpen && target.length > 0) {
            button.attr('aria-expanded', 'true');
            target.removeClass('hidden');
        }
    });

    // 自动隐藏密码框
    $('#visibility').change(function () {
        const val = $(this).val(), password = $('#post-password');

        if ('password' === val) {
            password.removeClass('hidden');
        } else {
            password.addClass('hidden');
        }
    });
    
    // 草稿删除确认
    $('.edit-draft-notice a').click(function () {
        if (confirm('<?php _e('您确认要删除这份草稿吗?'); ?>')) {
            window.location.href = $(this).attr('href');
        }

        return false;
    });

    $('.revision-history a').click(function () {
        if (confirm('<?php _e('您确认要回滚到这个版本吗? 当前内容会保存为历史版本。'); ?>')) {
            window.location.href = $(this).attr('href');
        }

        return false;
    });
});
</script>
