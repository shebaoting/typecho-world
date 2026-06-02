(function () {
  const modal = document.getElementById('app-market-modal');

  if (!modal) {
    return;
  }

  const title = document.getElementById('app-market-modal-title');
  const body = document.getElementById('app-market-modal-body');
  const foot = document.getElementById('app-market-modal-foot');
  let activeForm = null;

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

  function openModal() {
    modal.hidden = false;
  }

  function closeModal() {
    modal.hidden = true;
    activeForm = null;
  }

  function renderConfirm(button) {
    const mode = button.dataset.mode === 'update' ? 'update' : 'install';
    title.textContent = mode === 'update' ? '更新确认' : '安装确认';
    body.innerHTML = [
      '<ul class="app-market-confirm-list">',
      '<li><span>应用名称</span><strong>' + escapeHtml(button.dataset.name) + '</strong></li>',
      '<li><span>类型</span><strong>' + escapeHtml(button.dataset.type) + '</strong></li>',
      '<li><span>版本</span><strong>' + escapeHtml(button.dataset.version) + '</strong></li>',
      '<li><span>安装位置</span><code>' + escapeHtml(button.dataset.location) + '</code></li>',
      '<li><span>兼容性</span><strong>' + escapeHtml(button.dataset.compat) + '</strong></li>',
      '</ul>',
      '<p class="description">' + escapeHtml(button.dataset.message) + '</p>',
      button.dataset.note
        ? '<p class="description">' + (mode === 'update' ? '更新说明：' : '版本说明：') + escapeHtml(button.dataset.note) + '</p>'
        : ''
    ].join('');
    foot.innerHTML = [
      '<button type="button" class="btn" data-market-close>取消</button>',
      '<button type="button" class="btn primary" data-market-start>',
      mode === 'update' ? '开始更新' : '开始安装',
      '</button>'
    ].join('');
    openModal();
  }

  function renderProgress() {
    title.textContent = '安装进度';
    body.innerHTML = [
      '<ul class="app-market-progress">',
      '<li data-step="0">检查应用信息</li>',
      '<li data-step="1">下载应用包</li>',
      '<li data-step="2">校验文件</li>',
      '<li data-step="3">解压安装</li>',
      '<li data-step="4">完成安装</li>',
      '</ul>'
    ].join('');
    foot.innerHTML = '<button type="button" class="btn" disabled>安装中</button>';
  }

  function setStep(index, className) {
    const items = body.querySelectorAll('.app-market-progress li');
    items.forEach(function (item, itemIndex) {
      item.classList.remove('is-active', 'is-done', 'is-error');
      if (itemIndex < index) {
        item.classList.add('is-done');
      } else if (itemIndex === index) {
        item.classList.add(className || 'is-active');
      }
    });
  }

  function renderResult(payload, ok) {
    title.textContent = ok ? '安装完成' : '安装失败';
    body.innerHTML = '<p class="' + (ok ? 'success' : 'error') + '">' + escapeHtml(payload.message || '') + '</p>';

    if (ok && payload.actions && payload.actions.length) {
      body.innerHTML += '<div class="app-market-result-actions">' + payload.actions.map(function (action) {
        if (!action.url) {
          return '<button type="button" class="btn" data-market-close>' + escapeHtml(action.label) + '</button>';
        }
        return '<a class="btn primary" href="' + escapeHtml(action.url) + '">' + escapeHtml(action.label) + '</a>';
      }).join('') + '</div>';
    }

    foot.innerHTML = ok
      ? '<button type="button" class="btn" onclick="window.location.reload()">刷新列表</button>'
      : '<button type="button" class="btn primary" data-market-retry>重试</button><button type="button" class="btn" data-market-close>关闭</button>';
  }

  function submitActiveForm() {
    const formData = new FormData(activeForm);

    return fetch(activeForm.action, {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin'
    }).then(function (response) {
      return response.json().then(function (payload) {
        if (!response.ok || !payload.success) {
          throw payload;
        }
        return payload;
      });
    });
  }

  function startInstall() {
    if (!activeForm) {
      return;
    }

    renderProgress();
    setStep(0);

    window.setTimeout(function () {
      setStep(1);
      submitActiveForm().then(function (payload) {
        setStep(2);
        window.setTimeout(function () {
          setStep(3);
          window.setTimeout(function () {
            setStep(4, 'is-done');
            renderResult(payload, true);
          }, 240);
        }, 240);
      }).catch(function (payload) {
        setStep(1, 'is-error');
        renderResult(payload || { message: '安装失败，原文件未被修改' }, false);
      });
    }, 240);
  }

  document.addEventListener('submit', function (event) {
    const form = event.target.closest('.app-market-action-form');
    if (!form) {
      return;
    }

    const button = form.querySelector('.app-market-action');
    if (!button || button.disabled) {
      return;
    }

    event.preventDefault();
    activeForm = form;
    renderConfirm(button);
  });

  modal.addEventListener('click', function (event) {
    if (event.target.closest('[data-market-close]')) {
      closeModal();
      return;
    }

    if (event.target.closest('[data-market-start]')) {
      startInstall();
      return;
    }

    if (event.target.closest('[data-market-retry]')) {
      renderConfirm(activeForm.querySelector('.app-market-action'));
    }
  });
})();
