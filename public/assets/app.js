(function () {
  const header = document.querySelector('[data-header]');
  const nav = document.querySelector('[data-site-nav]');
  const navToggle = document.querySelector('[data-nav-toggle]');
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function updateHeader() {
    if (!header) return;
    header.classList.toggle('is-scrolled', window.scrollY > 6);
  }

  updateHeader();
  window.addEventListener('scroll', updateHeader, { passive: true });

  if (nav && navToggle) {
    const setNavigation = (isOpen) => {
      nav.classList.toggle('is-open', isOpen);
      document.body.classList.toggle('nav-open', isOpen);
      navToggle.setAttribute('aria-expanded', String(isOpen));
      navToggle.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
      if (isOpen) {
        document.body.classList.remove('admin-sidebar-open');
        document.querySelector('.admin-tabs.is-open')?.classList.remove('is-open');
        document.querySelector('.admin-sidebar-toggle')?.setAttribute('aria-expanded', 'false');
      }
    };

    navToggle.addEventListener('click', () => {
      setNavigation(!nav.classList.contains('is-open'));
    });
    nav.querySelectorAll('a, button').forEach((item) => item.addEventListener('click', () => setNavigation(false)));
  }

  const adminTabs = document.querySelector('.admin-tabs');
  if (adminTabs) {
    const adminShell = adminTabs.closest('.admin-shell');
    const sidebarToggle = document.createElement('button');
    const sidebarBackdrop = document.createElement('button');

    if (!adminTabs.id) adminTabs.id = 'admin-sidebar-navigation';
    adminTabs.setAttribute('aria-label', 'Admin modules');
    sidebarToggle.className = 'admin-sidebar-toggle';
    sidebarToggle.type = 'button';
    sidebarToggle.setAttribute('aria-expanded', 'false');
    sidebarToggle.setAttribute('aria-controls', adminTabs.id);
    sidebarToggle.setAttribute('aria-label', 'Open admin navigation');
    sidebarToggle.textContent = 'Sections';
    sidebarBackdrop.className = 'admin-sidebar-backdrop';
    sidebarBackdrop.type = 'button';
    sidebarBackdrop.setAttribute('aria-label', 'Close admin navigation');

    if (adminShell) {
      adminShell.classList.add('has-admin-sidebar');
      adminShell.insertBefore(sidebarToggle, adminShell.firstElementChild);
    }
    document.body.appendChild(sidebarBackdrop);
    let lastSidebarToggleAt = 0;

    const closeSidebar = () => {
      document.body.classList.remove('admin-sidebar-open');
      adminTabs.classList.remove('is-open');
      sidebarToggle.setAttribute('aria-expanded', 'false');
      sidebarToggle.setAttribute('aria-label', 'Open admin navigation');
    };

    sidebarToggle.addEventListener('click', () => {
      lastSidebarToggleAt = Date.now();
      const isOpen = adminTabs.classList.toggle('is-open');
      document.body.classList.toggle('admin-sidebar-open', isOpen);
      if (isOpen && nav && navToggle) {
        nav.classList.remove('is-open');
        document.body.classList.remove('nav-open');
        navToggle.setAttribute('aria-expanded', 'false');
        navToggle.setAttribute('aria-label', 'Open navigation');
      }
      sidebarToggle.setAttribute('aria-expanded', String(isOpen));
      sidebarToggle.setAttribute('aria-label', isOpen ? 'Close admin navigation' : 'Open admin navigation');
    });
    sidebarBackdrop.addEventListener('click', () => {
      if (Date.now() - lastSidebarToggleAt < 220) return;
      closeSidebar();
    });
    adminTabs.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeSidebar));
  }

  const revealItems = document.querySelectorAll('.reveal');
  if (prefersReducedMotion) {
    revealItems.forEach((item) => item.classList.add('is-visible'));
  } else if ('IntersectionObserver' in window && revealItems.length) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.18 });

    revealItems.forEach((item) => observer.observe(item));
  } else {
    revealItems.forEach((item) => item.classList.add('is-visible'));
  }

  const counters = document.querySelectorAll('[data-count]');
  if (counters.length) {
    const animateCounter = (node) => {
      const target = Number(node.dataset.count || '0');
      if (prefersReducedMotion) {
        node.textContent = target.toLocaleString();
        return;
      }
      const duration = 900;
      const start = performance.now();

      const tick = (time) => {
        const progress = Math.min(1, (time - start) / duration);
        const eased = 1 - Math.pow(1 - progress, 3);
        node.textContent = Math.round(target * eased).toLocaleString();
        if (progress < 1) requestAnimationFrame(tick);
      };

      requestAnimationFrame(tick);
    };

    if ('IntersectionObserver' in window) {
      const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            animateCounter(entry.target);
            counterObserver.unobserve(entry.target);
          }
        });
      }, { threshold: 0.5 });
      counters.forEach((counter) => counterObserver.observe(counter));
    } else {
      counters.forEach(animateCounter);
    }
  }

  const grid = document.querySelector('[data-listing-grid]');
  const search = document.querySelector('[data-market-search]');
  const category = document.querySelector('[data-market-category]');
  const type = document.querySelector('[data-market-type]');
  const emptyState = document.querySelector('[data-empty-state]');

  function filterListings() {
    if (!grid) return;
    const query = (search && search.value ? search.value : '').trim().toLowerCase();
    const categoryValue = category ? category.value : 'all';
    const typeValue = type ? type.value : 'all';
    let visible = 0;

    grid.querySelectorAll('.listing-card').forEach((card) => {
      const matchesQuery = !query || (card.dataset.title || '').includes(query);
      const matchesCategory = categoryValue === 'all' || card.dataset.category === categoryValue;
      const matchesType = typeValue === 'all' || card.dataset.type === typeValue;
      const show = matchesQuery && matchesCategory && matchesType;
      card.hidden = !show;
      if (show) visible += 1;
    });

    if (emptyState) emptyState.hidden = visible !== 0;
  }

  [search, category, type].forEach((control) => {
    if (control) control.addEventListener('input', filterListings);
  });

  filterListings();

  document.querySelectorAll('form[data-loading-form]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!form.checkValidity()) return;
      const submitter = event.submitter && event.submitter.matches('button, input[type="submit"]')
        ? event.submitter
        : form.querySelector('button[type="submit"], input[type="submit"]');
      const skeleton = form.closest('section')?.querySelector('[data-skeleton]');

      form.classList.add('is-submitting');
      if (skeleton) skeleton.hidden = false;

      if (submitter) {
        submitter.dataset.originalLabel = submitter.textContent;
        submitter.textContent = submitter.dataset.loadingLabel || 'Working...';
        submitter.disabled = true;
      }
    });
  });

  const signupForm = document.querySelector('[data-signup-form]');
  if (signupForm) {
    const password = signupForm.querySelector('[data-password]');
    const confirmation = signupForm.querySelector('[data-password-confirm]');
    const hint = signupForm.querySelector('[data-password-hint]');

    function updatePasswordHint() {
      if (!password || !confirmation || !hint) return;
      const enough = password.value.length >= 8;
      const matches = password.value && password.value === confirmation.value;
      if (matches && enough) {
        hint.textContent = 'Passwords match.';
        hint.classList.add('is-good');
      } else if (confirmation.value && !matches) {
        hint.textContent = 'Passwords do not match yet.';
        hint.classList.remove('is-good');
      } else {
        hint.textContent = 'Use at least 8 characters.';
        hint.classList.remove('is-good');
      }
    }

    [password, confirmation].forEach((field) => {
      if (field) field.addEventListener('input', updatePasswordHint);
    });
  }

  const listingForm = document.querySelector('[data-listing-form]');
  if (listingForm) {
    const transactionSelect = listingForm.querySelector('[data-transaction-select]');
    const priceField = listingForm.querySelector('[data-price-field]');
    const rentField = listingForm.querySelector('[data-rent-field]');

    function updatePricingFields() {
      const isRent = transactionSelect && transactionSelect.value === 'rent';
      if (priceField) priceField.hidden = isRent;
      if (rentField) rentField.hidden = !isRent;
    }

    if (transactionSelect) {
      transactionSelect.addEventListener('change', updatePricingFields);
      updatePricingFields();
    }
  }

  document.querySelectorAll('[data-preview-form]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      let success = form.querySelector('.preview-success');
      if (!success) {
        success = document.createElement('div');
        success.className = 'preview-success';
        success.innerHTML = '<p>Preview submitted. In Laravel, this posts to the backend controller.</p>';
        form.insertBefore(success, form.firstElementChild.nextElementSibling);
      }
    });
  });

  const filterDrawer = document.querySelector('[data-filter-drawer]');
  const filterToggle = document.querySelector('[data-filter-toggle]');
  if (filterDrawer && filterToggle) {
    if (!filterDrawer.id) filterDrawer.id = 'advanced-filter-drawer';
    filterToggle.setAttribute('aria-controls', filterDrawer.id);
    filterToggle.setAttribute('aria-expanded', 'false');
    const closeFilter = () => {
      filterDrawer.classList.remove('is-open');
      filterDrawer.setAttribute('aria-hidden', 'true');
      filterToggle.setAttribute('aria-expanded', 'false');
      filterToggle.focus();
    };
    filterToggle.addEventListener('click', () => {
      filterDrawer.classList.add('is-open');
      filterDrawer.setAttribute('aria-hidden', 'false');
      filterToggle.setAttribute('aria-expanded', 'true');
      const firstInput = filterDrawer.querySelector('input, select, button, textarea, a');
      if (firstInput) firstInput.focus();
    });
    filterDrawer.querySelectorAll('[data-filter-close]').forEach((button) => {
      button.addEventListener('click', closeFilter);
    });
  }

  document.querySelectorAll('[data-bulk-select-all]').forEach((control) => {
    control.addEventListener('change', () => {
      const table = control.closest('table');
      if (!table) return;
      table.querySelectorAll('.bulk-checkbox').forEach((checkbox) => {
        checkbox.checked = control.checked;
      });
    });
  });

  document.querySelectorAll('[data-bulk-form]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      const shell = form.closest('.admin-shell') || document;
      const selected = Array.from(shell.querySelectorAll('.bulk-checkbox:checked'));
      const inputWrap = form.querySelector('[data-bulk-inputs]');

      if (!selected.length) {
        event.preventDefault();
        form.classList.add('is-shaking');
        setTimeout(() => form.classList.remove('is-shaking'), 360);
        return;
      }

      if (inputWrap) {
        inputWrap.innerHTML = '';
        selected.forEach((checkbox) => {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'ids[]';
          input.value = checkbox.value;
          inputWrap.appendChild(input);
        });
      }
    });
  });

  const previewButtons = document.querySelectorAll('[data-preview-post]');
  if (previewButtons.length) {
    const previewModal = document.createElement('div');
    previewModal.className = 'post-preview-modal';
    previewModal.innerHTML = `
      <div class="post-preview-dialog" role="dialog" aria-modal="true" aria-labelledby="post-preview-title">
        <button class="post-preview-close" type="button" aria-label="Close preview">Close</button>
        <img alt="" data-preview-image>
        <div>
          <span class="eyebrow" data-preview-owner></span>
          <h2 id="post-preview-title" data-preview-title></h2>
          <p data-preview-description></p>
          <div class="preview-facts">
            <strong data-preview-price></strong>
            <span data-preview-location></span>
            <small data-preview-reason></small>
          </div>
        </div>
      </div>
    `;
    document.body.appendChild(previewModal);

    const closePreview = () => previewModal.classList.remove('is-open');
    previewModal.querySelector('.post-preview-close').addEventListener('click', closePreview);
    previewModal.addEventListener('click', (event) => {
      if (event.target === previewModal) closePreview();
    });

    previewButtons.forEach((button) => {
      button.addEventListener('click', () => {
        previewModal.querySelector('[data-preview-image]').src = button.dataset.image || '';
        previewModal.querySelector('[data-preview-title]').textContent = button.dataset.title || '';
        previewModal.querySelector('[data-preview-owner]').textContent = button.dataset.owner || '';
        previewModal.querySelector('[data-preview-description]').textContent = button.dataset.description || '';
        previewModal.querySelector('[data-preview-price]').textContent = button.dataset.price || '';
        previewModal.querySelector('[data-preview-location]').textContent = button.dataset.location || '';
        previewModal.querySelector('[data-preview-reason]').textContent = `Flag: ${button.dataset.reason || 'No flags'}`;
        previewModal.classList.add('is-open');
        previewModal.querySelector('.post-preview-close').focus();
      });
    });
  }

  const chartColors = ['#195c3a', '#7fb5dd', '#dc5a46', '#f1b72f', '#6e513e'];

  function parseChartData(canvas) {
    try {
      return {
        labels: JSON.parse(canvas.dataset.labels || '[]'),
        values: JSON.parse(canvas.dataset.values || '[]').map(Number),
      };
    } catch (error) {
      return { labels: [], values: [] };
    }
  }

  function setupCanvas(canvas) {
    const rect = canvas.getBoundingClientRect();
    const ratio = window.devicePixelRatio || 1;
    const width = Math.max(260, rect.width || canvas.clientWidth || 320);
    const height = Math.max(220, rect.height || canvas.clientHeight || 240);
    canvas.width = Math.floor(width * ratio);
    canvas.height = Math.floor(height * ratio);
    const context = canvas.getContext('2d');
    context.setTransform(ratio, 0, 0, ratio, 0, 0);
    return { context, width, height };
  }

  function drawEmptyChart(context, width, height) {
    context.clearRect(0, 0, width, height);
    context.fillStyle = 'rgba(25, 92, 58, 0.08)';
    context.beginPath();
    context.arc(width / 2, height / 2, Math.min(width, height) * 0.27, 0, Math.PI * 2);
    context.fill();
    context.fillStyle = '#60716b';
    context.font = '700 14px Inter, sans-serif';
    context.textAlign = 'center';
    context.fillText('No data yet', width / 2, height / 2 + 5);
  }

  function drawDoughnut(canvas) {
    const { labels, values } = parseChartData(canvas);
    const { context, width, height } = setupCanvas(canvas);
    const total = values.reduce((sum, value) => sum + value, 0);

    if (!total) {
      drawEmptyChart(context, width, height);
      return;
    }

    const radius = Math.min(width, height) * 0.29;
    const cx = width * 0.38;
    const cy = height * 0.5;
    let start = -Math.PI / 2;

    context.clearRect(0, 0, width, height);
    values.forEach((value, index) => {
      const slice = (value / total) * Math.PI * 2;
      context.beginPath();
      context.moveTo(cx, cy);
      context.arc(cx, cy, radius, start, start + slice);
      context.closePath();
      context.fillStyle = chartColors[index % chartColors.length];
      context.fill();
      start += slice;
    });

    context.globalCompositeOperation = 'destination-out';
    context.beginPath();
    context.arc(cx, cy, radius * 0.58, 0, Math.PI * 2);
    context.fill();
    context.globalCompositeOperation = 'source-over';

    context.fillStyle = '#17302b';
    context.font = '900 22px Inter, sans-serif';
    context.textAlign = 'center';
    context.fillText(total.toLocaleString(), cx, cy + 8);

    context.textAlign = 'left';
    labels.forEach((label, index) => {
      const y = 34 + index * 28;
      context.fillStyle = chartColors[index % chartColors.length];
      context.fillRect(width * 0.68, y - 10, 12, 12);
      context.fillStyle = '#60716b';
      context.font = '800 12px Inter, sans-serif';
      context.fillText(`${label}: ${values[index].toLocaleString()}`, width * 0.68 + 20, y);
    });
  }

  function drawLineChart(canvas) {
    const { labels, values } = parseChartData(canvas);
    const { context, width, height } = setupCanvas(canvas);
    const max = Math.max(1, ...values);
    const left = 42;
    const right = 18;
    const top = 24;
    const bottom = 40;
    const plotWidth = width - left - right;
    const plotHeight = height - top - bottom;

    context.clearRect(0, 0, width, height);
    context.strokeStyle = 'rgba(23, 48, 43, 0.12)';
    context.lineWidth = 1;
    for (let index = 0; index <= 4; index += 1) {
      const y = top + (plotHeight / 4) * index;
      context.beginPath();
      context.moveTo(left, y);
      context.lineTo(width - right, y);
      context.stroke();
    }

    const points = values.map((value, index) => {
      const x = left + (plotWidth / Math.max(1, values.length - 1)) * index;
      const y = top + plotHeight - (value / max) * plotHeight;
      return { x, y, value };
    });

    context.strokeStyle = '#195c3a';
    context.lineWidth = 4;
    context.lineJoin = 'round';
    context.beginPath();
    points.forEach((point, index) => {
      if (index === 0) context.moveTo(point.x, point.y);
      else context.lineTo(point.x, point.y);
    });
    context.stroke();

    points.forEach((point) => {
      context.fillStyle = '#f1b72f';
      context.beginPath();
      context.arc(point.x, point.y, 5, 0, Math.PI * 2);
      context.fill();
      context.strokeStyle = '#ffffff';
      context.lineWidth = 2;
      context.stroke();
    });

    context.fillStyle = '#60716b';
    context.font = '800 11px Inter, sans-serif';
    context.textAlign = 'center';
    labels.forEach((label, index) => {
      if (values.length > 9 && index % 3 !== 0 && index !== labels.length - 1) return;
      const x = left + (plotWidth / Math.max(1, labels.length - 1)) * index;
      context.fillText(label, x, height - 15);
    });

    context.textAlign = 'left';
    context.fillText(`$${max.toLocaleString()}`, 4, top + 4);
  }

  function drawMultiLineChart(canvas) {
    const labels = JSON.parse(canvas.dataset.labels || '[]');
    const orders = JSON.parse(canvas.dataset.orders || '[]').map(Number);
    const revenue = JSON.parse(canvas.dataset.revenue || '[]').map(Number);
    const { context, width, height } = setupCanvas(canvas);
    const left = 48;
    const right = 54;
    const top = 28;
    const bottom = 42;
    const plotWidth = width - left - right;
    const plotHeight = height - top - bottom;
    const maxOrders = Math.max(1, ...orders);
    const maxRevenue = Math.max(1, ...revenue);

    context.clearRect(0, 0, width, height);
    context.strokeStyle = 'rgba(23, 48, 43, 0.12)';
    context.lineWidth = 1;
    for (let index = 0; index <= 4; index += 1) {
      const y = top + (plotHeight / 4) * index;
      context.beginPath();
      context.moveTo(left, y);
      context.lineTo(width - right, y);
      context.stroke();
    }

    const drawSeries = (values, max, color) => {
      const points = values.map((value, index) => ({
        x: left + (plotWidth / Math.max(1, values.length - 1)) * index,
        y: top + plotHeight - (value / max) * plotHeight,
      }));
      context.strokeStyle = color;
      context.lineWidth = 4;
      context.lineJoin = 'round';
      context.beginPath();
      points.forEach((point, index) => {
        if (index === 0) context.moveTo(point.x, point.y);
        else context.lineTo(point.x, point.y);
      });
      context.stroke();
      points.forEach((point) => {
        context.fillStyle = color;
        context.beginPath();
        context.arc(point.x, point.y, 4, 0, Math.PI * 2);
        context.fill();
      });
    };

    drawSeries(orders, maxOrders, '#dc5a46');
    drawSeries(revenue, maxRevenue, '#195c3a');

    context.fillStyle = '#60716b';
    context.font = '800 11px Inter, sans-serif';
    context.textAlign = 'center';
    labels.forEach((label, index) => {
      if (labels.length > 9 && index % 3 !== 0 && index !== labels.length - 1) return;
      const x = left + (plotWidth / Math.max(1, labels.length - 1)) * index;
      context.fillText(label, x, height - 15);
    });

    context.textAlign = 'left';
    context.fillText(`${maxOrders} orders`, 4, top + 4);
    context.textAlign = 'right';
    context.fillText(`$${maxRevenue.toLocaleString()}`, width - 4, top + 4);
  }

  function drawBarChart(canvas) {
    const { labels, values } = parseChartData(canvas);
    const { context, width, height } = setupCanvas(canvas);
    const max = Math.max(1, ...values);
    const left = 112;
    const right = 28;
    const top = 24;
    const rowHeight = Math.max(26, (height - top - 20) / Math.max(1, values.length));

    context.clearRect(0, 0, width, height);
    if (!values.reduce((sum, value) => sum + value, 0)) {
      drawEmptyChart(context, width, height);
      return;
    }

    labels.forEach((label, index) => {
      const y = top + index * rowHeight;
      const barWidth = ((width - left - right) * values[index]) / max;

      context.fillStyle = '#60716b';
      context.font = '800 12px Inter, sans-serif';
      context.textAlign = 'right';
      context.fillText(label, left - 12, y + 16);

      context.fillStyle = 'rgba(25, 92, 58, 0.1)';
      context.fillRect(left, y + 4, width - left - right, 14);
      context.fillStyle = chartColors[index % chartColors.length];
      context.fillRect(left, y + 4, Math.max(8, barWidth), 14);

      context.fillStyle = '#17302b';
      context.textAlign = 'left';
      context.fillText(values[index].toLocaleString(), left + Math.max(8, barWidth) + 8, y + 16);
    });
  }

  function drawAdminChart(canvas) {
    if (canvas.dataset.chart === 'multi-line') {
      drawMultiLineChart(canvas);
      return;
    }

    if (canvas.dataset.chart === 'line') {
      drawLineChart(canvas);
      return;
    }

    if (canvas.dataset.chart === 'bars') {
      drawBarChart(canvas);
      return;
    }

    drawDoughnut(canvas);
  }

  const adminCharts = document.querySelectorAll('[data-chart]');
  if (adminCharts.length) {
    const drawCharts = () => adminCharts.forEach(drawAdminChart);
    drawCharts();
    window.addEventListener('resize', drawCharts, { passive: true });
  }

  let pendingConfirmForm = null;
  const confirmModal = document.createElement('div');
  confirmModal.className = 'confirm-modal';
  confirmModal.innerHTML = `
    <div class="confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
      <h2 id="confirm-title">Confirm action</h2>
      <p data-confirm-message></p>
      <div class="confirm-actions">
        <button class="confirm-cancel" type="button" data-confirm-cancel>Cancel</button>
        <button class="confirm-accept" type="button" data-confirm-accept>Continue</button>
      </div>
    </div>
  `;
  document.body.appendChild(confirmModal);

  const confirmMessage = confirmModal.querySelector('[data-confirm-message]');
  const closeConfirm = () => {
    pendingConfirmForm = null;
    confirmModal.classList.remove('is-open');
  };

  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      pendingConfirmForm = form;
      if (confirmMessage) confirmMessage.textContent = form.dataset.confirm || 'Continue with this action?';
      confirmModal.classList.add('is-open');
      confirmModal.querySelector('[data-confirm-cancel]').focus();
    });
  });

  confirmModal.querySelector('[data-confirm-cancel]').addEventListener('click', closeConfirm);
  confirmModal.querySelector('[data-confirm-accept]').addEventListener('click', () => {
    const form = pendingConfirmForm;
    closeConfirm();
    if (form) form.submit();
  });
  confirmModal.addEventListener('click', (event) => {
    if (event.target === confirmModal) closeConfirm();
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && confirmModal.classList.contains('is-open')) closeConfirm();
    if (event.key === 'Escape' && filterDrawer && filterDrawer.classList.contains('is-open')) {
      filterDrawer.classList.remove('is-open');
      filterDrawer.setAttribute('aria-hidden', 'true');
      if (filterToggle) {
        filterToggle.setAttribute('aria-expanded', 'false');
        filterToggle.focus();
      }
    }
    const previewModal = document.querySelector('.post-preview-modal.is-open');
    if (event.key === 'Escape' && previewModal) previewModal.classList.remove('is-open');
    if (event.key === 'Escape' && nav && nav.classList.contains('is-open') && navToggle) {
      nav.classList.remove('is-open');
      document.body.classList.remove('nav-open');
      navToggle.setAttribute('aria-expanded', 'false');
      navToggle.setAttribute('aria-label', 'Open navigation');
    }
    if (event.key === 'Escape' && document.body.classList.contains('admin-sidebar-open')) {
      const openAdminTabs = document.querySelector('.admin-tabs.is-open');
      const adminToggle = document.querySelector('.admin-sidebar-toggle');
      document.body.classList.remove('admin-sidebar-open');
      if (openAdminTabs) openAdminTabs.classList.remove('is-open');
      if (adminToggle) {
        adminToggle.setAttribute('aria-expanded', 'false');
        adminToggle.setAttribute('aria-label', 'Open admin navigation');
        adminToggle.focus();
      }
    }
  });

  const themePreview = document.querySelector('[data-theme-preview]');
  if (themePreview) {
    themePreview.addEventListener('change', () => {
      document.body.classList.remove('theme-harvest', 'theme-field', 'theme-sunset', 'theme-night');
      document.body.classList.add(`theme-${themePreview.value}`);
      adminCharts.forEach(drawAdminChart);
    });
  }
})();
