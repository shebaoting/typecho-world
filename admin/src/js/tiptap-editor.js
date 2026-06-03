import { Editor, Node, mergeAttributes } from '@tiptap/core';
import { Fragment } from '@tiptap/pm/model';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';
import Placeholder from '@tiptap/extension-placeholder';
import TaskList from '@tiptap/extension-task-list';
import TaskItem from '@tiptap/extension-task-item';
import TextAlign from '@tiptap/extension-text-align';
import { TextStyle } from '@tiptap/extension-text-style';
import Color from '@tiptap/extension-color';
import Highlight from '@tiptap/extension-highlight';
import Subscript from '@tiptap/extension-subscript';
import Superscript from '@tiptap/extension-superscript';
import Typography from '@tiptap/extension-typography';

const MoreBreak = Node.create({
    name: 'moreBreak',
    group: 'block',
    atom: true,
    selectable: true,
    draggable: true,

    parseHTML() {
        return [
            { tag: 'div[data-type="more"]' },
            { tag: 'hr[data-type="more"]' }
        ];
    },

    renderHTML({ HTMLAttributes }) {
        return ['div', mergeAttributes(HTMLAttributes, {
            class: 'typecho-more-break',
            'data-type': 'more'
        }), ['span', 'more']];
    }
});

const ImageUploadPlaceholder = Node.create({
    name: 'imageUploadPlaceholder',
    group: 'block',
    atom: true,
    selectable: true,
    draggable: true,

    addAttributes() {
        return {
            uploadId: {
                default: null,
                parseHTML: element => element.getAttribute('data-upload-id'),
                renderHTML: attributes => attributes.uploadId ? {'data-upload-id': attributes.uploadId} : {}
            }
        };
    },

    parseHTML() {
        return [
            { tag: 'div[data-type="image-upload"]' }
        ];
    },

    renderHTML({ HTMLAttributes }) {
        return ['div', mergeAttributes({
            class: 'typecho-image-upload-placeholder',
            'data-type': 'image-upload'
        }, HTMLAttributes), ['div', { class: 'typecho-image-upload-card' },
            ['span', { class: 'typecho-image-upload-icon', 'aria-hidden': 'true' }],
            ['strong', (window.TypechoTiptapConfig?.labels?.imageUploadHint || defaults.labels.imageUploadHint)],
            ['small', (window.TypechoTiptapConfig?.labels?.imageUploadLimit || defaults.labels.imageUploadLimit)],
            ['span', { class: 'typecho-image-upload-progress', 'aria-hidden': 'true' },
                ['span', { class: 'typecho-image-upload-progress-bar' }]
            ]
        ]];
    }
});

const defaults = {
    placeholder: 'Start writing...',
    labels: {
        editor: 'Editor',
        blockType: 'Block type',
        paragraph: 'Paragraph',
        heading1: 'Heading 1',
        heading2: 'Heading 2',
        heading3: 'Heading 3',
        heading4: 'Heading 4',
        heading5: 'Heading 5',
        heading6: 'Heading 6',
        bold: 'Bold',
        italic: 'Italic',
        underline: 'Underline',
        strike: 'Strike',
        code: 'Code',
        subscript: 'Subscript',
        superscript: 'Superscript',
        link: 'Link',
        imageUpload: 'Upload image',
        imageUploadHint: 'Click to upload or drag and drop',
        imageUploadLimit: 'Images will be inserted into the editor.',
        uploadingImages: 'Uploading {count} images',
        processingImages: 'Processing images',
        clearAll: 'Clear all',
        cancelUpload: 'Cancel upload',
        uploadFailed: 'Failed',
        highlight: 'Highlight',
        textColor: 'Text color',
        clearColor: 'Clear color',
        highlightYellow: 'Yellow highlight',
        highlightGreen: 'Green highlight',
        highlightBlue: 'Blue highlight',
        highlightPink: 'Pink highlight',
        colorDefault: 'Default color',
        colorRed: 'Red',
        colorGreen: 'Green',
        colorBlue: 'Blue',
        colorPurple: 'Purple',
        colorBrown: 'Brown',
        bulletList: 'Bullet list',
        orderedList: 'Ordered list',
        taskList: 'Task list',
        listType: 'List type',
        scriptType: 'Superscript or subscript',
        textAlign: 'Text align',
        alignLeft: 'Align left',
        alignCenter: 'Align center',
        alignRight: 'Align right',
        alignJustify: 'Justify',
        blockquote: 'Quote',
        codeBlock: 'Code block',
        horizontalRule: 'Divider',
        moreBreak: 'More break',
        resetFormatting: 'Reset formatting',
        undo: 'Undo',
        redo: 'Redo',
        linkPrompt: 'Enter URL',
        imagePrompt: 'Enter image URL'
    }
};

function getConfig() {
    const config = window.TypechoTiptapConfig || {};

    return {
        ...defaults,
        ...config,
        labels: {
            ...defaults.labels,
            ...(config.labels || {})
        }
    };
}

function toEditorContent(value) {
    const content = value || '';

    if (!content.trim()) {
        return '<p></p>';
    }

    return content.replace(/<!--\s*more\s*-->/ig, '<div data-type="more"></div>');
}

function toStorageContent(html) {
    const wrapper = document.createElement('div');
    wrapper.innerHTML = html;

    wrapper.querySelectorAll('[data-type="image-upload"]').forEach(function (node) {
        node.remove();
    });

    wrapper.querySelectorAll('[data-type="more"]').forEach(function (node) {
        node.replaceWith(document.createComment('more'));
    });

    return wrapper.innerHTML;
}

function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function (char) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[char];
    });
}

function escapeAttribute(value) {
    return escapeHtml(value).replace(/`/g, '&#96;');
}

function createUploadFile(file) {
    if (file.name) {
        return file;
    }

    const extension = (file.type || 'image/png').split('/').pop() || 'png';
    const name = (new Date()).toISOString().replace(/\..+$/, '') + '.' + extension;

    try {
        return new File([file], name, { type: file.type });
    } catch (error) {
        return file;
    }
}

function initTiptapEditor() {
    const textarea = document.getElementById('text');

    if (!textarea || textarea.dataset.tiptapReady === '1') {
        return;
    }

    const config = getConfig();
    const labels = config.labels;
    const sourceField = textarea.closest('p') || textarea;
    const form = textarea.closest('form');
    const toolbarSlot = document.getElementById('write-toolbar-slot');
    const shell = document.createElement('section');
    const toolbar = document.createElement('div');
    const editorElement = document.createElement('div');
    const imageInput = document.createElement('input');

    textarea.dataset.tiptapReady = '1';
    shell.className = 'tiptap-shell';
    shell.setAttribute('aria-label', labels.editor);
    toolbar.className = 'tiptap-toolbar';
    toolbar.setAttribute('role', 'toolbar');
    toolbar.setAttribute('aria-label', labels.editor);
    editorElement.className = 'tiptap-editor';
    imageInput.type = 'file';
    imageInput.accept = 'image/*';
    imageInput.multiple = true;
    imageInput.className = 'tiptap-image-input';

    const blockOptions = [
        { value: 'paragraph', label: labels.paragraph, title: labels.paragraph, icon: 'paragraph' },
        { value: 'heading1', label: labels.heading1, title: labels.heading1, icon: 'heading' },
        { value: 'heading2', label: labels.heading2, title: labels.heading2, icon: 'heading' },
        { value: 'heading3', label: labels.heading3, title: labels.heading3, icon: 'heading' },
        { value: 'heading4', label: labels.heading4, title: labels.heading4, icon: 'heading' },
        { value: 'heading5', label: labels.heading5, title: labels.heading5, icon: 'heading' },
        { value: 'heading6', label: labels.heading6, title: labels.heading6, icon: 'heading' }
    ];

    const highlightOptions = [
        { value: '#fef08a', label: 'Yellow', title: labels.highlightYellow },
        { value: '#bbf7d0', label: 'Green', title: labels.highlightGreen },
        { value: '#bfdbfe', label: 'Blue', title: labels.highlightBlue },
        { value: '#fecaca', label: 'Red', title: labels.colorRed },
        { value: '#fed7aa', label: 'Orange', title: labels.colorBrown },
        { value: '#e9d5ff', label: 'Purple', title: labels.colorPurple },
        { value: '#d9f99d', label: 'Lime', title: labels.highlightGreen },
        { value: '#e5e7eb', label: 'Gray', title: labels.colorDefault },
        { value: '', label: 'Clear', title: labels.clearColor, clear: true }
    ];

    const textColorOptions = [
        { value: '#2f2a24', label: 'Default', title: labels.colorDefault },
        { value: '#b42318', label: 'Red', title: labels.colorRed },
        { value: '#a16207', label: 'Brown', title: labels.colorBrown },
        { value: '#4f6f64', label: 'Green', title: labels.colorGreen },
        { value: '#1d4ed8', label: 'Blue', title: labels.colorBlue },
        { value: '#7c3aed', label: 'Purple', title: labels.colorPurple },
        { value: '#475569', label: 'Gray', title: labels.colorDefault },
        { value: '#c2410c', label: 'Orange', title: labels.colorBrown },
        { value: '', label: 'Clear', title: labels.clearColor, clear: true }
    ];

    const alignOptions = [
        { value: 'left', label: labels.alignLeft, title: labels.alignLeft, icon: 'align-left' },
        { value: 'center', label: labels.alignCenter, title: labels.alignCenter, icon: 'align-center' },
        { value: 'right', label: labels.alignRight, title: labels.alignRight, icon: 'align-right' },
        { value: 'justify', label: labels.alignJustify, title: labels.alignJustify, icon: 'align-justify' }
    ];

    const listOptions = [
        { command: 'bulletList', label: labels.bulletList, title: labels.bulletList, icon: 'ulist' },
        { command: 'orderedList', label: labels.orderedList, title: labels.orderedList, icon: 'olist' },
        { command: 'taskList', label: labels.taskList, title: labels.taskList, icon: 'task-list' }
    ];

    const scriptOptions = [
        { command: 'superscript', label: labels.superscript, title: labels.superscript, icon: 'superscript' },
        { command: 'subscript', label: labels.subscript, title: labels.subscript, icon: 'subscript' }
    ];

    const controls = [
        [
            { type: 'menu', command: 'blockType', icon: 'heading', title: labels.blockType, options: blockOptions }
        ],
        [
            { command: 'bold', icon: 'bold', title: labels.bold },
            { command: 'italic', icon: 'italic', title: labels.italic },
            { command: 'underline', icon: 'underline', title: labels.underline },
            { command: 'strike', icon: 'strike', title: labels.strike },
            { command: 'code', icon: 'code', title: labels.code },
            { type: 'menu', command: 'scriptType', icon: 'script', title: labels.scriptType, options: scriptOptions }
        ],
        [
            { type: 'palette', command: 'textColor', icon: 'color', title: labels.textColor, options: textColorOptions },
            { type: 'palette', command: 'highlight', icon: 'highlight', title: labels.highlight, options: highlightOptions }
        ],
        [
            { command: 'link', icon: 'link', title: labels.link },
            { command: 'resetFormatting', icon: 'clear-format', title: labels.resetFormatting }
        ],
        [
            { type: 'menu', command: 'listType', icon: 'list', title: labels.listType, options: listOptions }
        ],
        [
            { type: 'menu', command: 'textAlign', icon: 'align-left', title: labels.textAlign, options: alignOptions }
        ],
        [
            { command: 'blockquote', icon: 'quote', title: labels.blockquote },
            { command: 'codeBlock', icon: 'code', title: labels.codeBlock },
            { command: 'horizontalRule', icon: 'hr', title: labels.horizontalRule },
            { command: 'moreBreak', icon: 'more', title: labels.moreBreak }
        ],
        [
            { command: 'imageUpload', icon: 'image', title: labels.imageUpload }
        ],
        [
            { command: 'undo', icon: 'undo', title: labels.undo },
            { command: 'redo', icon: 'redo', title: labels.redo }
        ]
    ];

    function appendButton(groupElement, control) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'tiptap-command';
        button.dataset.command = control.command;
        button.title = control.title;
        button.setAttribute('aria-label', control.title);

        if (control.icon) {
            const icon = document.createElement('span');
            icon.className = 'tiptap-icon tiptap-icon-' + control.icon;
            icon.setAttribute('aria-hidden', 'true');
            button.appendChild(icon);
        } else {
            button.textContent = control.label;
        }

        groupElement.appendChild(button);
    }

    function appendSelect(groupElement, control) {
        const select = document.createElement('select');
        select.className = 'tiptap-select';
        select.dataset.command = control.command;
        select.title = control.title;
        select.setAttribute('aria-label', control.title);

        control.options.forEach(function (option) {
            const optionElement = document.createElement('option');
            optionElement.value = option.value;
            optionElement.textContent = option.label;
            optionElement.title = option.title;
            select.appendChild(optionElement);
        });

        groupElement.appendChild(select);
    }

    function appendPalette(groupElement, control) {
        const palette = document.createElement('div');
        const trigger = document.createElement('button');
        const panel = document.createElement('div');

        palette.className = 'tiptap-popover tiptap-palette';
        palette.dataset.palette = control.command;
        trigger.type = 'button';
        trigger.className = 'tiptap-command tiptap-popover-trigger tiptap-palette-trigger';
        trigger.dataset.paletteTrigger = control.command;
        trigger.title = control.title;
        trigger.setAttribute('aria-label', control.title);
        trigger.setAttribute('aria-expanded', 'false');

        if (control.icon) {
            const icon = document.createElement('span');
            icon.className = 'tiptap-icon tiptap-icon-' + control.icon;
            icon.setAttribute('aria-hidden', 'true');
            trigger.appendChild(icon);
        } else {
            trigger.textContent = control.label;
        }

        panel.className = 'tiptap-palette-panel hidden';

        control.options.forEach(function (option) {
            const swatch = document.createElement('button');
            swatch.type = 'button';
            swatch.className = option.clear ? 'tiptap-swatch tiptap-swatch-clear' : 'tiptap-swatch';
            swatch.dataset.command = control.command;
            swatch.dataset.value = option.value;
            swatch.title = option.title;
            swatch.setAttribute('aria-label', option.title);

            if (!option.clear) {
                swatch.style.setProperty('--swatch-color', option.value);
            }

            panel.appendChild(swatch);
        });

        palette.appendChild(trigger);
        palette.appendChild(panel);
        groupElement.appendChild(palette);
    }

    function appendMenu(groupElement, control) {
        const menu = document.createElement('div');
        const trigger = document.createElement('button');
        const triggerIcon = document.createElement('span');
        const panel = document.createElement('div');

        menu.className = 'tiptap-popover tiptap-menu';
        menu.dataset.menuCommand = control.command;
        menu.dataset.defaultIcon = control.icon || '';
        trigger.type = 'button';
        trigger.className = 'tiptap-command tiptap-popover-trigger tiptap-menu-trigger';
        trigger.dataset.menuTrigger = control.command;
        trigger.title = control.title;
        trigger.setAttribute('aria-label', control.title);
        trigger.setAttribute('aria-expanded', 'false');
        triggerIcon.className = 'tiptap-icon tiptap-icon-' + control.icon;
        triggerIcon.dataset.menuIcon = 'true';
        triggerIcon.setAttribute('aria-hidden', 'true');
        panel.className = 'tiptap-menu-panel hidden';

        control.options.forEach(function (option) {
            const item = document.createElement('button');
            const icon = document.createElement('span');
            const label = document.createElement('span');

            item.type = 'button';
            item.className = 'tiptap-menu-item';
            item.dataset.command = option.command || control.command;
            item.dataset.value = option.value || '';
            item.dataset.icon = option.icon || control.icon || '';
            item.title = option.title;
            item.setAttribute('aria-label', option.title);
            icon.className = 'tiptap-icon tiptap-icon-' + (option.icon || control.icon);
            icon.setAttribute('aria-hidden', 'true');
            label.className = 'tiptap-menu-label';
            label.textContent = option.label;

            item.appendChild(icon);
            item.appendChild(label);
            panel.appendChild(item);
        });

        trigger.appendChild(triggerIcon);
        menu.appendChild(trigger);
        menu.appendChild(panel);
        groupElement.appendChild(menu);
    }

    controls.forEach(function (group) {
        const groupElement = document.createElement('div');
        groupElement.className = 'tiptap-toolbar-group';

        group.forEach(function (control) {
            if (control.type === 'select') {
                appendSelect(groupElement, control);
            } else if (control.type === 'palette') {
                appendPalette(groupElement, control);
            } else if (control.type === 'menu') {
                appendMenu(groupElement, control);
            } else {
                appendButton(groupElement, control);
            }
        });

        toolbar.appendChild(groupElement);
    });

    if (toolbarSlot) {
        toolbarSlot.appendChild(toolbar);
    } else {
        shell.appendChild(toolbar);
    }

    shell.appendChild(editorElement);
    shell.appendChild(imageInput);
    sourceField.parentNode.insertBefore(shell, sourceField);
    sourceField.classList.add('tiptap-source-wrap');
    textarea.classList.add('tiptap-source');

    let editor = null;
    let activeUploadId = null;
    let uploadIndex = 0;
    let uploadItemIndex = 0;
    let headingOutline = [];
    const uploadBatches = new Map();

    function notifyWrite() {
        if (!form) {
            return;
        }

        if (window.jQuery) {
            window.jQuery(form).trigger('write');
        } else {
            form.dispatchEvent(new Event('write', { bubbles: true }));
        }
    }

    function syncToTextarea(shouldNotify) {
        if (!editor) {
            return;
        }

        const value = toStorageContent(editor.getHTML());

        if (textarea.value !== value) {
            textarea.value = value;
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
        }

        if (shouldNotify) {
            notifyWrite();
        }
    }

    function updateDocumentOutline() {
        const outlineList = document.getElementById('write-outline-list');

        if (!outlineList || !editor) {
            return;
        }

        headingOutline = Array.from(editor.view.dom.querySelectorAll('h1,h2,h3,h4,h5,h6')).map(function (node) {
            const text = (node.textContent || '').trim();
            const level = parseInt(node.tagName.replace('H', ''), 10);

            if (!text) {
                return null;
            }

            return { level, text, node };
        }).filter(Boolean);

        const items = headingOutline.map(function (item) {
            return {
                level: item.level,
                text: item.text
            };
        });

        if (window.Typecho && typeof window.Typecho.renderWriteOutline === 'function') {
            window.Typecho.renderWriteOutline(items);
        }
    }

    function insertLinkedFile(file, url) {
        editor.chain().focus().insertContent(
            '<a href="' + escapeAttribute(url) + '">' + escapeHtml(file || url) + '</a>'
        ).run();
    }

    function createUploadId() {
        uploadIndex ++;
        return 'typecho-upload-' + Date.now() + '-' + uploadIndex;
    }

    function getUploadPlaceholderElement(uploadId) {
        if (!uploadId || !editor) {
            return null;
        }

        return Array.from(editor.view.dom.querySelectorAll('.typecho-image-upload-placeholder')).find(function (node) {
            return node.dataset.uploadId === uploadId;
        }) || null;
    }

    function insertUploadPlaceholder(afterUploadId) {
        const uploadId = createUploadId();
        const node = editor.schema.nodes.imageUploadPlaceholder.create({ uploadId });
        const anchor = findUploadPlaceholder(afterUploadId);

        if (anchor) {
            const pos = anchor.pos + anchor.node.nodeSize;
            editor.view.dispatch(editor.state.tr.insert(pos, node).scrollIntoView());
        } else {
            editor.chain().focus().insertContent({ type: 'imageUploadPlaceholder', attrs: { uploadId } }).run();
        }

        activeUploadId = uploadId;
        return uploadId;
    }

    function findUploadPlaceholder(uploadId) {
        let result = null;

        if (!uploadId) {
            return result;
        }

        editor.state.doc.descendants(function (node, pos) {
            if (node.type.name === 'imageUploadPlaceholder' && node.attrs.uploadId === uploadId) {
                result = { node, pos };
                return false;
            }

            return true;
        });

        return result;
    }

    function removeUploadPlaceholder(uploadId) {
        const placeholder = findUploadPlaceholder(uploadId);

        uploadBatches.delete(uploadId);

        if (!placeholder) {
            return;
        }

        editor.view.dispatch(editor.state.tr.delete(
            placeholder.pos,
            placeholder.pos + placeholder.node.nodeSize
        ).scrollIntoView());

        if (activeUploadId === uploadId) {
            activeUploadId = null;
        }
    }

    function cancelUploadItem(uploadId, itemId) {
        const batch = uploadBatches.get(uploadId);

        if (!batch) {
            return;
        }

        const item = batch.items.find(function (candidate) {
            return candidate.id === itemId;
        });

        if (!item || item.status !== 'uploading') {
            return;
        }

        item.status = 'cancelled';

        if (item.request) {
            item.request.abort();
        }

        updateUploadItem(uploadId, itemId, {
            status: 'cancelled',
            progress: item.progress || 0
        });
        finishUploadBatch(uploadId);
    }

    function cancelUploadBatch(uploadId) {
        const batch = uploadBatches.get(uploadId);

        if (!batch) {
            removeUploadPlaceholder(uploadId);
            return;
        }

        batch.items.forEach(function (item) {
            if (item.status === 'uploading' && item.request) {
                item.status = 'cancelled';
                item.request.abort();
            }
        });

        removeUploadPlaceholder(uploadId);
    }

    function setUploadPlaceholderState(uploadId, state) {
        const placeholder = getUploadPlaceholderElement(uploadId);

        if (placeholder) {
            placeholder.classList.toggle('is-uploading', state === 'uploading');
            placeholder.classList.toggle('is-error', state === 'error');
        }
    }

    function setUploadPlaceholderProgress(uploadId, percent) {
        const placeholder = getUploadPlaceholderElement(uploadId);

        if (!placeholder) {
            return;
        }

        const value = Math.max(0, Math.min(100, Number(percent) || 0));
        placeholder.style.setProperty('--upload-progress', value + '%');
        placeholder.dataset.progress = String(Math.round(value));
    }

    function formatFileSize(size) {
        if (!Number.isFinite(size)) {
            return '';
        }

        if (size < 1024) {
            return size + ' B';
        }

        if (size < 1024 * 1024) {
            return (size / 1024).toFixed(1) + ' KB';
        }

        return (size / 1024 / 1024).toFixed(2) + ' MB';
    }

    function getUploadUrl() {
        const uploadArea = document.querySelector('.upload-area');

        if (!uploadArea || !uploadArea.dataset.url) {
            return null;
        }

        const url = new URL(uploadArea.dataset.url, window.location.href);
        const cidInput = form ? form.querySelector('input[name="cid"]') : null;

        if (cidInput && cidInput.value) {
            url.searchParams.append('cid', cidInput.value);
        }

        return url.toString();
    }

    function parseUploadJson(text) {
        const cleaned = String(text || '').replace(/^\s*while\s*\(1\)\s*;\s*/, '');

        if (!cleaned.trim()) {
            throw new Error('Empty upload response');
        }

        try {
            return JSON.parse(cleaned);
        } catch (error) {
            // Upload can succeed before a PHP notice pollutes the response body. Recover the JSON
            // payload so the placeholder can still turn into the uploaded image.
        }

        const starts = [];

        for (let i = 0; i < cleaned.length; i ++) {
            if (cleaned[i] === '[' || cleaned[i] === '{') {
                starts.push(i);
            }
        }

        for (const start of starts) {
            const candidate = cleaned.slice(start);

            for (let end = candidate.length; end > 0; end --) {
                const char = candidate[end - 1];

                if (char !== ']' && char !== '}') {
                    continue;
                }

                try {
                    return JSON.parse(candidate.slice(0, end));
                } catch (error) {
                }
            }
        }

        throw new Error('Invalid upload response');
    }

    function parseUploadResponse(text) {
        const data = parseUploadJson(text);
        const attachment = Array.isArray(data) ? data[1] : data;

        if (!attachment || !attachment.url) {
            throw new Error('Invalid upload response');
        }

        return attachment;
    }

    function rememberAttachment(attachment) {
        if (!attachment || !attachment.cid || !form) {
            return;
        }

        const exists = form.querySelector('input[name="attachment[]"][value="' + attachment.cid + '"]');

        if (exists) {
            return;
        }

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'attachment[]';
        input.value = attachment.cid;
        form.appendChild(input);
    }

    function replaceUploadPlaceholder(uploadId, attachment) {
        const attachments = Array.isArray(attachment) ? attachment : [attachment];
        const imageNodes = attachments.map(function (item) {
            const src = item.url || item.src;

            if (!src) {
                return null;
            }

            return editor.schema.nodes.image.create({
                src,
                alt: item.alt || item.title || ''
            });
        }).filter(Boolean);

        if (imageNodes.length === 0) {
            return false;
        }

        const placeholder = findUploadPlaceholder(uploadId);

        if (placeholder) {
            const tr = editor.state.tr.replaceWith(
                placeholder.pos,
                placeholder.pos + placeholder.node.nodeSize,
                Fragment.fromArray(imageNodes)
            ).scrollIntoView();

            editor.view.dispatch(tr);
        } else {
            editor.chain().focus().insertContent(imageNodes.map(function (node) {
                return node.toJSON();
            })).run();
        }

        if (activeUploadId === uploadId) {
            activeUploadId = null;
        }

        uploadBatches.delete(uploadId);
        return true;
    }

    function createUploadBatch(uploadId, files) {
        const items = Array.from(files).map(function (file) {
            uploadItemIndex ++;
            return {
                id: uploadId + '-item-' + uploadItemIndex,
                file,
                name: file.name || 'image',
                size: file.size || 0,
                progress: 0,
                status: 'uploading',
                attachment: null,
                request: null
            };
        });

        const batch = {
            uploadId,
            items,
            completed: 0,
            failed: 0
        };

        uploadBatches.set(uploadId, batch);
        return batch;
    }

    function renderUploadBatch(batch) {
        const placeholder = getUploadPlaceholderElement(batch.uploadId);

        if (!placeholder) {
            return;
        }

        placeholder.classList.add('is-uploading', 'has-upload-list');
        placeholder.classList.remove('is-error', 'is-dragover');
        placeholder.innerHTML = '';

        const uploadList = document.createElement('div');
        const header = document.createElement('div');
        const title = document.createElement('strong');
        const clear = document.createElement('button');
        const body = document.createElement('div');

        uploadList.className = 'typecho-image-upload-list';
        header.className = 'typecho-image-upload-list-head';
        clear.type = 'button';
        clear.className = 'typecho-image-upload-clear';
        clear.dataset.uploadClear = batch.uploadId;
        title.textContent = labels.uploadingImages.replace('{count}', batch.items.length);
        clear.textContent = labels.clearAll;
        body.className = 'typecho-image-upload-list-body';

        batch.items.forEach(function (item) {
            const row = document.createElement('div');
            const icon = document.createElement('span');
            const meta = document.createElement('span');
            const name = document.createElement('strong');
            const size = document.createElement('small');
            const percent = document.createElement('span');
            const remove = document.createElement('button');

            row.className = 'typecho-image-upload-row';
            row.dataset.uploadItem = item.id;
            row.style.setProperty('--item-progress', '0%');
            icon.className = 'typecho-image-upload-row-icon';
            icon.setAttribute('aria-hidden', 'true');
            meta.className = 'typecho-image-upload-row-meta';
            name.textContent = item.name;
            size.textContent = formatFileSize(item.size);
            percent.className = 'typecho-image-upload-row-percent';
            percent.textContent = '0%';
            remove.type = 'button';
            remove.className = 'typecho-image-upload-remove';
            remove.dataset.uploadRemove = item.id;
            remove.setAttribute('aria-label', labels.cancelUpload + ' ' + item.name);
            remove.textContent = 'x';

            meta.appendChild(name);
            meta.appendChild(size);
            row.appendChild(icon);
            row.appendChild(meta);
            row.appendChild(percent);
            row.appendChild(remove);
            body.appendChild(row);
        });

        header.appendChild(title);
        header.appendChild(clear);
        uploadList.appendChild(header);
        uploadList.appendChild(body);
        placeholder.appendChild(uploadList);
    }

    function updateUploadBatchHeader(batch) {
        const placeholder = getUploadPlaceholderElement(batch.uploadId);
        const title = placeholder ? placeholder.querySelector('.typecho-image-upload-list-head strong') : null;

        if (!title) {
            return;
        }

        const activeItems = batch.items.filter(function (item) {
            return item.status !== 'cancelled';
        });
        const pending = activeItems.filter(function (item) {
            return item.status === 'uploading';
        }).length;

        title.textContent = pending > 0
            ? labels.uploadingImages.replace('{count}', pending)
            : labels.processingImages;
    }

    function updateUploadItem(uploadId, itemId, data) {
        const batch = uploadBatches.get(uploadId);
        const placeholder = getUploadPlaceholderElement(uploadId);
        const row = placeholder ? placeholder.querySelector('[data-upload-item="' + itemId + '"]') : null;

        if (batch) {
            const item = batch.items.find(function (candidate) {
                return candidate.id === itemId;
            });

            if (item) {
                Object.assign(item, data);
            }
        }

        if (!row) {
            return;
        }

        if (typeof data.progress !== 'undefined') {
            const progress = Math.max(0, Math.min(100, Number(data.progress) || 0));
            row.style.setProperty('--item-progress', progress + '%');
            const percent = row.querySelector('.typecho-image-upload-row-percent');

            if (percent) {
                percent.textContent = Math.round(progress) + '%';
            }
        }

        if (data.status) {
            row.dataset.status = data.status;
            row.classList.toggle('is-complete', data.status === 'complete');
            row.classList.toggle('is-error', data.status === 'error');
            row.classList.toggle('is-cancelled', data.status === 'cancelled');

            if (data.status === 'error') {
                const percent = row.querySelector('.typecho-image-upload-row-percent');
                if (percent) {
                    percent.textContent = labels.uploadFailed;
                }
            }
        }
    }

    function finishUploadBatch(uploadId) {
        const batch = uploadBatches.get(uploadId);

        if (!batch) {
            return;
        }

        updateUploadBatchHeader(batch);

        const unfinished = batch.items.some(function (item) {
            return item.status === 'uploading';
        });

        if (unfinished) {
            return;
        }

        const attachments = batch.items
            .filter(function (item) {
                return item.status === 'complete' && item.attachment;
            })
            .map(function (item) {
                return item.attachment;
            });

        if (attachments.length > 0) {
            replaceUploadPlaceholder(uploadId, attachments);
            syncToTextarea(true);
            updateToolbar();
            return;
        }

        if (batch.failed > 0) {
            setUploadPlaceholderState(uploadId, 'error');
        } else {
            removeUploadPlaceholder(uploadId);
        }
    }

    function uploadBatchItem(batch, item) {
        const uploadUrl = getUploadUrl();

        if (!uploadUrl) {
            updateUploadItem(batch.uploadId, item.id, {
                status: 'error',
                progress: 0
            });
            batch.failed ++;
            finishUploadBatch(batch.uploadId);
            return;
        }

        const data = new FormData();
        const request = new XMLHttpRequest();

        item.request = request;
        data.append('file', createUploadFile(item.file));
        request.open('POST', uploadUrl, true);

        request.upload.addEventListener('progress', function (event) {
            if (!event.lengthComputable || item.status !== 'uploading') {
                return;
            }

            updateUploadItem(batch.uploadId, item.id, {
                progress: 4 + (event.loaded / event.total) * 90
            });
        });

        request.addEventListener('load', function () {
            if (item.status === 'cancelled') {
                finishUploadBatch(batch.uploadId);
                return;
            }

            if (request.status < 200 || request.status >= 300) {
                if (window.console) {
                    console.error('Typecho image upload failed:', request.status, request.responseText);
                }
                updateUploadItem(batch.uploadId, item.id, {
                    status: 'error',
                    progress: item.progress || 0
                });
                batch.failed ++;
                finishUploadBatch(batch.uploadId);
                return;
            }

            try {
                const attachment = parseUploadResponse(request.responseText);

                rememberAttachment(attachment);
                updateUploadItem(batch.uploadId, item.id, {
                    status: 'complete',
                    progress: 100,
                    attachment
                });
                batch.completed ++;
            } catch (error) {
                if (window.console) {
                    console.error('Typecho image upload response error:', error, request.responseText);
                }
                updateUploadItem(batch.uploadId, item.id, {
                    status: 'error',
                    progress: item.progress || 0
                });
                batch.failed ++;
            }

            finishUploadBatch(batch.uploadId);
        });

        request.addEventListener('error', function () {
            if (item.status === 'cancelled') {
                finishUploadBatch(batch.uploadId);
                return;
            }

            updateUploadItem(batch.uploadId, item.id, {
                status: 'error',
                progress: item.progress || 0
            });
            batch.failed ++;
            finishUploadBatch(batch.uploadId);
        });

        updateUploadItem(batch.uploadId, item.id, {
            progress: 2
        });
        request.send(data);
    }

    function uploadImages(files, uploadId = activeUploadId) {
        if (!files || files.length === 0) {
            const url = window.prompt(labels.imagePrompt, '');

            if (url) {
                replaceUploadPlaceholder(uploadId, { url: url.trim(), alt: '' });
                syncToTextarea(true);
                updateToolbar();
            }

            return;
        }

        if (!uploadId || !findUploadPlaceholder(uploadId)) {
            uploadId = insertUploadPlaceholder(uploadId);
        }

        activeUploadId = uploadId;
        const batch = createUploadBatch(uploadId, files);

        renderUploadBatch(batch);
        setUploadPlaceholderState(uploadId, 'uploading');

        batch.items.forEach(function (item) {
            uploadBatchItem(batch, item);
        });
    }

    function setBlockType(value) {
        const chain = editor.chain().focus();

        if (value === 'paragraph') {
            chain.setParagraph().run();
            return;
        }

        const level = Number(value.replace('heading', ''));

        if (level >= 1 && level <= 6) {
            chain.setHeading({ level }).run();
        }
    }

    function runCommand(command, value) {
        const chain = editor.chain().focus();

        switch (command) {
            case 'blockType':
                setBlockType(value);
                break;
            case 'bold':
                chain.toggleBold().run();
                break;
            case 'italic':
                chain.toggleItalic().run();
                break;
            case 'underline':
                chain.toggleUnderline().run();
                break;
            case 'strike':
                chain.toggleStrike().run();
                break;
            case 'code':
                chain.toggleCode().run();
                break;
            case 'subscript':
                chain.unsetSuperscript().toggleSubscript().run();
                break;
            case 'superscript':
                chain.unsetSubscript().toggleSuperscript().run();
                break;
            case 'textColor':
                if (value) {
                    chain.setColor(value).run();
                } else {
                    chain.unsetColor().run();
                }
                break;
            case 'highlight':
                if (value) {
                    chain.setHighlight({ color: value }).run();
                } else {
                    chain.unsetHighlight().run();
                }
                break;
            case 'link': {
                const previousUrl = editor.getAttributes('link').href || '';
                const url = window.prompt(labels.linkPrompt, previousUrl);

                if (url === null) {
                    return;
                }

                if (url.trim() === '') {
                    chain.extendMarkRange('link').unsetLink().run();
                } else {
                    chain.extendMarkRange('link').setLink({ href: url.trim() }).run();
                }

                break;
            }
            case 'textAlign':
                chain.setTextAlign(value || 'left').run();
                break;
            case 'resetFormatting':
                chain.unsetAllMarks().unsetTextAlign().clearNodes().run();
                break;
            case 'bulletList':
                chain.toggleBulletList().run();
                break;
            case 'orderedList':
                chain.toggleOrderedList().run();
                break;
            case 'taskList':
                chain.toggleTaskList().run();
                break;
            case 'alignLeft':
                chain.setTextAlign('left').run();
                break;
            case 'alignCenter':
                chain.setTextAlign('center').run();
                break;
            case 'alignRight':
                chain.setTextAlign('right').run();
                break;
            case 'alignJustify':
                chain.setTextAlign('justify').run();
                break;
            case 'blockquote':
                chain.toggleBlockquote().run();
                break;
            case 'codeBlock':
                chain.toggleCodeBlock().run();
                break;
            case 'horizontalRule':
                chain.setHorizontalRule().run();
                break;
            case 'moreBreak':
                chain.insertContent({ type: 'moreBreak' }).run();
                break;
            case 'imageUpload':
                insertUploadPlaceholder(activeUploadId);
                break;
            case 'undo':
                editor.commands.undo();
                break;
            case 'redo':
                editor.commands.redo();
                break;
            default:
                break;
        }
    }

    function getActiveBlockType() {
        for (let level = 1; level <= 6; level++) {
            if (editor.isActive('heading', { level })) {
                return 'heading' + level;
            }
        }

        return 'paragraph';
    }

    function isActive(command) {
        switch (command) {
            case 'bulletList':
                return editor.isActive('bulletList');
            case 'orderedList':
                return editor.isActive('orderedList');
            case 'taskList':
                return editor.isActive('taskList');
            case 'codeBlock':
                return editor.isActive('codeBlock');
            case 'alignLeft':
                return editor.isActive({ textAlign: 'left' });
            case 'alignCenter':
                return editor.isActive({ textAlign: 'center' });
            case 'alignRight':
                return editor.isActive({ textAlign: 'right' });
            case 'alignJustify':
                return editor.isActive({ textAlign: 'justify' });
            case 'moreBreak':
                return editor.isActive('moreBreak');
            case 'undo':
            case 'redo':
            case 'horizontalRule':
            case 'imageUpload':
            case 'resetFormatting':
                return false;
            default:
                return editor.isActive(command);
        }
    }

    function canRun(command) {
        switch (command) {
            case 'undo':
                return editor.can().undo();
            case 'redo':
                return editor.can().redo();
            default:
                return true;
        }
    }

    function updateSelect(select) {
        const command = select.dataset.command;

        switch (command) {
            case 'blockType':
                select.value = getActiveBlockType();
                break;
            case 'textAlign':
                select.value = editor.getAttributes('paragraph').textAlign
                    || editor.getAttributes('heading').textAlign
                    || 'left';
                break;
            default:
                break;
        }
    }

    function getActiveTextAlign() {
        return editor.getAttributes('paragraph').textAlign
            || editor.getAttributes('heading').textAlign
            || 'left';
    }

    function closePopovers(except) {
        toolbar.querySelectorAll('.tiptap-popover').forEach(function (popover) {
            if (except && popover === except) {
                return;
            }

            popover.classList.remove('is-open');
            popover.querySelector('.tiptap-popover-trigger')?.setAttribute('aria-expanded', 'false');
            popover.querySelector('.tiptap-palette-panel, .tiptap-menu-panel')?.classList.add('hidden');
        });
    }

    function togglePopover(popover) {
        const isOpen = popover.classList.contains('is-open');

        closePopovers(popover);
        popover.classList.toggle('is-open', !isOpen);
        popover.querySelector('.tiptap-popover-trigger')?.setAttribute('aria-expanded', !isOpen ? 'true' : 'false');
        popover.querySelector('.tiptap-palette-panel, .tiptap-menu-panel')?.classList.toggle('hidden', isOpen);
    }

    function updatePalette(palette) {
        const command = palette.dataset.palette;
        const value = command === 'highlight'
            ? (editor.getAttributes('highlight').color || '')
            : (editor.getAttributes('textStyle').color || '');

        palette.querySelectorAll('.tiptap-swatch').forEach(function (swatch) {
            swatch.classList.toggle('is-active', swatch.dataset.value === value);
        });
    }

    function setMenuTriggerIcon(menu, iconName) {
        const icon = menu.querySelector('[data-menu-icon]');

        if (!icon || !iconName || icon.dataset.currentIcon === iconName) {
            return;
        }

        icon.className = 'tiptap-icon tiptap-icon-' + iconName;
        icon.dataset.currentIcon = iconName;
    }

    function getActiveListCommand() {
        if (editor.isActive('taskList')) {
            return 'taskList';
        }

        if (editor.isActive('orderedList')) {
            return 'orderedList';
        }

        if (editor.isActive('bulletList')) {
            return 'bulletList';
        }

        return '';
    }

    function getActiveScriptCommand() {
        if (editor.isActive('superscript')) {
            return 'superscript';
        }

        if (editor.isActive('subscript')) {
            return 'subscript';
        }

        return '';
    }

    function getActiveMenuValue(menu) {
        switch (menu.dataset.menuCommand) {
            case 'blockType':
                return getActiveBlockType();
            case 'textAlign':
                return getActiveTextAlign();
            case 'listType':
                return getActiveListCommand();
            case 'scriptType':
                return getActiveScriptCommand();
            default:
                return '';
        }
    }

    function updateMenu(menu) {
        const activeValue = getActiveMenuValue(menu);
        let activeIcon = menu.dataset.defaultIcon;

        menu.querySelectorAll('.tiptap-menu-item').forEach(function (item) {
            const itemValue = item.dataset.value || item.dataset.command;
            const active = activeValue && itemValue === activeValue;

            item.classList.toggle('is-active', active);
            item.setAttribute('aria-pressed', active ? 'true' : 'false');

            if (active && item.dataset.icon) {
                activeIcon = item.dataset.icon;
            }
        });

        setMenuTriggerIcon(menu, activeIcon);
    }

    function updateToolbar() {
        toolbar.querySelectorAll('.tiptap-command').forEach(function (button) {
            const command = button.dataset.command;
            if (!command) {
                return;
            }

            const active = isActive(command);
            const disabled = !canRun(command);

            button.classList.toggle('is-active', active);
            button.disabled = disabled;

            if (!['undo', 'redo', 'horizontalRule', 'imageUpload', 'resetFormatting'].includes(command)) {
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
            }
        });

        toolbar.querySelectorAll('.tiptap-select').forEach(updateSelect);
        toolbar.querySelectorAll('.tiptap-palette').forEach(updatePalette);
        toolbar.querySelectorAll('.tiptap-menu').forEach(updateMenu);
    }

    toolbar.addEventListener('click', function (event) {
        const target = event.target instanceof Element ? event.target : event.target.parentElement;
        const swatch = target ? target.closest('.tiptap-swatch') : null;
        const menuItem = target ? target.closest('.tiptap-menu-item') : null;
        const popoverTrigger = target ? target.closest('.tiptap-popover-trigger') : null;
        const button = target ? target.closest('.tiptap-command') : null;

        if (swatch) {
            event.preventDefault();
            runCommand(swatch.dataset.command, swatch.dataset.value);
            closePopovers();
            syncToTextarea(true);
            updateToolbar();
            return;
        }

        if (menuItem) {
            event.preventDefault();
            runCommand(menuItem.dataset.command, menuItem.dataset.value);
            closePopovers();
            syncToTextarea(true);
            updateToolbar();
            return;
        }

        if (popoverTrigger) {
            event.preventDefault();
            togglePopover(popoverTrigger.closest('.tiptap-popover'));
            return;
        }

        if (!button || button.disabled) {
            return;
        }

        event.preventDefault();
        runCommand(button.dataset.command);
        syncToTextarea(true);
        updateToolbar();
    });

    document.addEventListener('click', function (event) {
        if (!toolbar.contains(event.target)) {
            closePopovers();
        }
    });

    toolbar.addEventListener('change', function (event) {
        const target = event.target instanceof Element ? event.target : event.target.parentElement;
        const select = target ? target.closest('.tiptap-select') : null;

        if (!select) {
            return;
        }

        runCommand(select.dataset.command, select.value);
        syncToTextarea(true);
        updateToolbar();
    });

    imageInput.addEventListener('change', function () {
        uploadImages(imageInput.files, activeUploadId);
        imageInput.value = '';
    });

    try {
        editor = new Editor({
            element: editorElement,
            extensions: [
                StarterKit.configure({
                    heading: {
                        levels: [1, 2, 3, 4, 5, 6]
                    },
                    link: false
                }),
                Link.configure({
                    openOnClick: false,
                    HTMLAttributes: {
                        rel: 'noopener noreferrer',
                        target: '_blank'
                    }
                }),
                Image.configure({
                    inline: false,
                    allowBase64: false
                }),
                TaskList,
                TaskItem.configure({
                    nested: true
                }),
                TextAlign.configure({
                    types: ['heading', 'paragraph']
                }),
                TextStyle,
                Color,
                Highlight.configure({
                    multicolor: true
                }),
                Subscript,
                Superscript,
                Typography,
                Placeholder.configure({
                    placeholder: config.placeholder
                }),
                ImageUploadPlaceholder,
                MoreBreak
            ],
            content: toEditorContent(textarea.value),
            editorProps: {
                attributes: {
                    class: 'typecho-tiptap-prosemirror'
                }
            },
            onCreate() {
                updateDocumentOutline();
            },
            onUpdate() {
                syncToTextarea(true);
                updateDocumentOutline();
                updateToolbar();
            },
            onSelectionUpdate() {
                updateToolbar();
            },
            onFocus() {
                if (form) {
                    form.classList.remove('write-title-focus');
                }

                shell.classList.add('is-focused');
            },
            onBlur() {
                shell.classList.remove('is-focused');
                syncToTextarea(false);
            }
        });
    } catch (error) {
        sourceField.classList.remove('tiptap-source-wrap');
        if (toolbar.parentNode) {
            toolbar.parentNode.removeChild(toolbar);
        }

        shell.remove();
        window.console && window.console.error(error);
        return;
    }

    updateDocumentOutline();

    editor.view.dom.addEventListener('click', function (event) {
        const target = event.target instanceof Element ? event.target : event.target.parentElement;
        const clearButton = target ? target.closest('.typecho-image-upload-clear') : null;
        const removeButton = target ? target.closest('.typecho-image-upload-remove') : null;
        const placeholder = target ? target.closest('.typecho-image-upload-placeholder') : null;

        if (clearButton) {
            event.preventDefault();
            event.stopPropagation();
            cancelUploadBatch(clearButton.dataset.uploadClear);
            return;
        }

        if (removeButton) {
            event.preventDefault();
            event.stopPropagation();
            const uploadId = placeholder ? placeholder.dataset.uploadId : activeUploadId;
            cancelUploadItem(uploadId, removeButton.dataset.uploadRemove);
            return;
        }

        if (!placeholder) {
            return;
        }

        activeUploadId = placeholder.dataset.uploadId || null;
        event.preventDefault();

        if (placeholder.classList.contains('has-upload-list')) {
            return;
        }

        if (getUploadUrl()) {
            imageInput.click();
        } else {
            uploadImages([], activeUploadId);
        }
    });

    const outlineList = document.getElementById('write-outline-list');

    if (outlineList && outlineList.dataset.tiptapOutlineReady !== '1') {
        outlineList.dataset.tiptapOutlineReady = '1';
        outlineList.addEventListener('click', function (event) {
            const target = event.target instanceof Element ? event.target.closest('[data-outline-index]') : null;

            if (!target) {
                return;
            }

            const item = headingOutline[Number(target.dataset.outlineIndex)];

            if (!item || !item.node) {
                return;
            }

            item.node.scrollIntoView({ block: 'center', behavior: 'smooth' });
        });
    }

    editor.view.dom.addEventListener('dragover', function (event) {
        const target = event.target instanceof Element ? event.target : event.target.parentElement;
        const placeholder = target ? target.closest('.typecho-image-upload-placeholder') : null;

        if (!placeholder) {
            return;
        }

        placeholder.classList.add('is-dragover');
        event.preventDefault();
    });

    editor.view.dom.addEventListener('dragleave', function (event) {
        const target = event.target instanceof Element ? event.target : event.target.parentElement;
        const placeholder = target ? target.closest('.typecho-image-upload-placeholder') : null;

        if (placeholder) {
            placeholder.classList.remove('is-dragover');
        }
    });

    editor.view.dom.addEventListener('drop', function (event) {
        const target = event.target instanceof Element ? event.target : event.target.parentElement;
        const placeholder = target ? target.closest('.typecho-image-upload-placeholder') : null;

        if (!placeholder) {
            return;
        }

        activeUploadId = placeholder.dataset.uploadId || null;
        placeholder.classList.remove('is-dragover');
        const transfer = event.dataTransfer;

        if (!transfer || transfer.files.length === 0) {
            return;
        }

        uploadImages(transfer.files, activeUploadId);
        event.typechoUploadHandled = true;
        event.preventDefault();
        event.stopImmediatePropagation();
    });

    editor.view.dom.addEventListener('paste', function (event) {
        const clipboard = event.clipboardData;

        if (!clipboard) {
            return;
        }

        const files = [];

        Array.from(clipboard.items || []).forEach(function (item) {
            if (item.kind !== 'file') {
                return;
            }

            const file = item.getAsFile();

            if (file && file.size > 0) {
                files.push(file);
            }
        });

        if (files.length > 0) {
            uploadImages(files, activeUploadId);
            event.preventDefault();
        }
    });

    editor.view.dom.addEventListener('drop', function (event) {
        if (event.typechoUploadHandled) {
            return;
        }

        const transfer = event.dataTransfer;

        if (!transfer || transfer.files.length === 0) {
            return;
        }

        uploadImages(transfer.files, activeUploadId);
        event.preventDefault();
    });

    if (form) {
        form.addEventListener('submit', function () {
            syncToTextarea(false);
        }, true);
    }

    window.Typecho = window.Typecho || {};
    window.Typecho.insertFileToEditor = function (file, url, isImage) {
        if (!editor) {
            return;
        }

        if (isImage) {
            const uploadId = activeUploadId;

            if (uploadId) {
                setUploadPlaceholderProgress(uploadId, 100);
            }

            replaceUploadPlaceholder(uploadId, { url, alt: file || '' });
        } else {
            insertLinkedFile(file, url);
        }

        syncToTextarea(true);
        updateToolbar();
    };

    window.Typecho.uploadComplete = function (attachment) {
        window.Typecho.insertFileToEditor(attachment.title || attachment.alt, attachment.url, attachment.isImage);
    };

    window.Typecho.tiptapEditor = editor;
    window.Typecho.syncEditor = function () {
        syncToTextarea(false);
    };

    updateToolbar();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTiptapEditor);
} else {
    initTiptapEditor();
}
