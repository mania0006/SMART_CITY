// ============================================================
//  SMART CITY COMPLAINT SYSTEM — main.js  [DARK PREMIUM]
// ============================================================

'use strict';

document.addEventListener('DOMContentLoaded', function () {

    // ── 1. AUTO-DISMISS FLASH MESSAGES ───────────────────────
    document.querySelectorAll('.flash').forEach(function (flash) {
        const close = document.createElement('button');
        close.innerHTML = '<i class="fas fa-times"></i>';
        close.style.cssText = 'margin-left:auto;background:none;border:none;cursor:pointer;color:inherit;opacity:0.6;font-size:13px;padding:0 0 0 12px;transition:opacity 0.15s;flex-shrink:0;';
        close.addEventListener('mouseenter', () => close.style.opacity = '1');
        close.addEventListener('mouseleave', () => close.style.opacity = '0.6');
        close.addEventListener('click', () => dismissFlash(flash));
        flash.appendChild(close);
        setTimeout(() => dismissFlash(flash), 4500);
    });

    function dismissFlash(el) {
        el.style.transition = 'all 0.4s cubic-bezier(0.16,1,0.3,1)';
        el.style.opacity = '0';
        el.style.transform = 'translateY(-6px)';
        el.style.maxHeight = el.offsetHeight + 'px';
        requestAnimationFrame(() => {
            el.style.maxHeight = '0';
            el.style.marginBottom = '0';
            el.style.paddingTop = '0';
            el.style.paddingBottom = '0';
        });
        setTimeout(() => el.remove(), 400);
    }


    // ── 2. CONFIRM DELETE FORMS ───────────────────────────────
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!confirm(form.getAttribute('data-confirm') || 'Are you sure?')) {
                e.preventDefault();
            }
        });
    });


    // ── 3. FILE UPLOAD — DRAG & DROP + PREVIEW ────────────────
    const fileInput = document.querySelector('input[type="file"]');
    if (fileInput) {
        const wrap = fileInput.closest('.file-upload-area') || fileInput.parentElement;
        fileInput.addEventListener('change', handleFileChange);
        if (wrap) {
            ['dragenter', 'dragover'].forEach(ev => {
                wrap.addEventListener(ev, e => {
                    e.preventDefault();
                    wrap.style.borderColor = 'var(--accent-cyan)';
                    wrap.style.background = 'rgba(0,212,255,0.04)';
                });
            });
            ['dragleave', 'drop'].forEach(ev => {
                wrap.addEventListener(ev, e => {
                    e.preventDefault();
                    wrap.style.borderColor = '';
                    wrap.style.background = '';
                });
            });
            wrap.addEventListener('drop', e => {
                const dt = e.dataTransfer;
                if (dt && dt.files.length) {
                    fileInput.files = dt.files;
                    handleFileChange.call(fileInput);
                }
            });
        }

        function handleFileChange() {
            const file = this.files[0];
            if (!file) return;
            if (file.size > 5 * 1024 * 1024) {
                showToast('File too large! Maximum size is 5 MB.', 'error');
                this.value = '';
                return;
            }
            const label = document.querySelector('.file-upload-text');
            if (label) {
                label.innerHTML = `<span>${escapeHTML(file.name)}</span> <small style="color:var(--text-tertiary);font-size:11px;">(${formatBytes(file.size)})</small>`;
            }
            const preview = document.getElementById('file-preview');
            if (preview && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = e => {
                    preview.innerHTML = `
                        <div style="margin-top:12px;position:relative;display:inline-block;">
                            <img src="${e.target.result}" style="max-width:200px;border-radius:8px;border:1px solid var(--border-subtle);display:block;">
                            <span style="position:absolute;top:6px;right:6px;background:var(--bg-void);border-radius:4px;padding:2px 6px;font-size:11px;color:var(--text-tertiary);font-family:var(--font-mono);">${formatBytes(file.size)}</span>
                        </div>`;
                };
                reader.readAsDataURL(file);
            }
        }
    }


    // ── 4. ACTIVE NAV LINK ────────────────────────────────────
    const currentPath = window.location.pathname;
    document.querySelectorAll('.nav-links a').forEach(function (link) {
        const href = link.getAttribute('href');
        if (!href || href === '#') return;
        const seg = href.split('/').filter(Boolean).pop();
        if (seg && currentPath.includes(seg)) {
            link.style.background = 'var(--bg-elevated)';
            link.style.color = 'var(--text-primary)';
            link.style.borderColor = 'var(--border-subtle)';
        }
    });


    // ── 5. STAR RATING INTERACTIVE ───────────────────────────
    const ratingSelect = document.querySelector('select[name="rating"]');
    const starsContainer = document.querySelector('.rating-stars-interactive');
    if (ratingSelect && starsContainer) {
        ratingSelect.style.display = 'none';
        buildStarUI(ratingSelect, starsContainer);
    }

    function buildStarUI(select, container) {
        container.innerHTML = '';
        for (let i = 1; i <= 5; i++) {
            const star = document.createElement('span');
            star.textContent = '★';
            star.dataset.val = i;
            star.style.cssText = 'font-size:28px;cursor:pointer;transition:all 0.15s;color:var(--border-subtle);padding:0 2px;';
            star.addEventListener('click', () => {
                select.value = i;
                updateStars(container, i);
            });
            star.addEventListener('mouseenter', () => updateStars(container, i, true));
            container.addEventListener('mouseleave', () => updateStars(container, parseInt(select.value) || 0));
            container.appendChild(star);
        }
    }

    function updateStars(container, val, hover = false) {
        container.querySelectorAll('span').forEach((s, idx) => {
            const filled = idx < val;
            s.style.color = filled ? 'var(--warning)' : 'var(--border-subtle)';
            s.style.filter = filled ? 'drop-shadow(0 0 5px rgba(245,158,11,0.5))' : 'none';
            s.style.transform = (hover && idx < val) ? 'scale(1.15)' : 'scale(1)';
        });
    }


    // ── 6. CATEGORY → DEPARTMENT AUTO-FILL ───────────────────
    const categorySelect = document.querySelector('select[name="category_id"]');
    const deptSelect     = document.querySelector('select[name="dept_id"]');
    if (categorySelect && deptSelect) {
        const map = { '1':'1', '2':'2', '3':'3', '4':'4', '5':'1' };
        categorySelect.addEventListener('change', function () {
            const id = map[this.value];
            if (!id) return;
            for (let i = 0; i < deptSelect.options.length; i++) {
                if (deptSelect.options[i].value === id) {
                    deptSelect.selectedIndex = i;
                    deptSelect.style.borderColor = 'var(--accent-cyan)';
                    setTimeout(() => deptSelect.style.borderColor = '', 800);
                    break;
                }
            }
        });
    }


    // ── 7. CONFIRM LOGOUT ─────────────────────────────────────
    const logoutLink = document.querySelector('a[href*="logout"]');
    if (logoutLink) {
        logoutLink.addEventListener('click', function (e) {
            if (!confirm('Sign out of Smart City Portal?')) e.preventDefault();
        });
    }


    // ── 8. CLICKABLE TABLE ROWS ───────────────────────────────
    document.querySelectorAll('tr[data-href]').forEach(function (row) {
        row.style.cursor = 'pointer';
        row.addEventListener('click', function (e) {
            if (e.target.closest('a, button, .btn')) return;
            window.location.href = this.dataset.href;
        });
    });


    // ── 9. PRINT BUTTON ───────────────────────────────────────
    document.querySelector('.btn-print')?.addEventListener('click', () => window.print());


    // ── 10. STAT CARD COUNTER ANIMATION ──────────────────────
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const el = entry.target;
            const target = parseFloat(el.textContent.replace(/,/g, ''));
            if (isNaN(target) || target === 0) return;
            animateCount(el, target);
            observer.unobserve(el);
        });
    }, { threshold: 0.6 });

    document.querySelectorAll('.stat-info h3').forEach(el => observer.observe(el));

    function animateCount(el, target) {
        const duration = 900;
        const isFloat = target % 1 !== 0;
        let start = null;
        function step(ts) {
            if (!start) start = ts;
            const progress = Math.min((ts - start) / duration, 1);
            const ease = 1 - Math.pow(1 - progress, 3);
            const val = target * ease;
            el.textContent = isFloat ? val.toFixed(1) : Math.round(val).toLocaleString();
            if (progress < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }


    // ── 11. PROGRESS BAR ANIMATION ───────────────────────────
    document.querySelectorAll('.progress-fill').forEach(bar => {
        const target = bar.style.width;
        bar.style.width = '0';
        setTimeout(() => { bar.style.width = target; }, 150);
    });


    // ── 12. TOAST NOTIFICATION SYSTEM ────────────────────────
    window.showToast = function (message, type = 'info', duration = 3500) {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.style.cssText = `
                position: fixed; bottom: 28px; right: 28px;
                display: flex; flex-direction: column; gap: 10px;
                z-index: 9999; pointer-events: none;
            `;
            document.body.appendChild(container);
        }

        const icons = { success: 'fa-check-circle', error: 'fa-times-circle', warning: 'fa-exclamation-triangle', info: 'fa-info-circle' };
        const colors = { success: 'var(--success)', error: 'var(--danger)', warning: 'var(--warning)', info: 'var(--accent-cyan)' };

        const toast = document.createElement('div');
        toast.style.cssText = `
            background: var(--bg-elevated);
            border: 1px solid var(--border-subtle);
            border-left: 3px solid ${colors[type]};
            color: var(--text-primary);
            padding: 13px 18px;
            border-radius: var(--r-md);
            font-family: var(--font-body);
            font-size: 13.5px;
            font-weight: 500;
            display: flex; align-items: center; gap: 10px;
            box-shadow: var(--shadow-lg);
            pointer-events: all;
            opacity: 0;
            transform: translateX(20px);
            transition: all 0.3s cubic-bezier(0.16,1,0.3,1);
            max-width: 340px;
            word-break: break-word;
        `;
        toast.innerHTML = `<i class="fas ${icons[type]}" style="color:${colors[type]};flex-shrink:0;font-size:15px;"></i>${escapeHTML(message)}`;
        container.appendChild(toast);

        requestAnimationFrame(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateX(0)';
        });

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(12px)';
            setTimeout(() => toast.remove(), 350);
        }, duration);
    };


    // ── 13. SMOOTH PAGE TRANSITIONS ──────────────────────────
    document.querySelectorAll('a[href]').forEach(link => {
        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('mailto') || href.startsWith('javascript') || link.target === '_blank') return;
        link.addEventListener('click', function (e) {
            if (e.ctrlKey || e.metaKey || e.shiftKey) return;
            if (link.closest('[data-confirm]') || href.includes('logout')) return;
            document.body.style.transition = 'opacity 0.18s ease';
            document.body.style.opacity = '0.7';
        });
    });

    window.addEventListener('pageshow', () => {
        document.body.style.opacity = '1';
    });


    // ── 14. FORM SUBMIT LOADING STATE ────────────────────────
    // FIX: Button disable karna HATA DIYA — yahi asli masla tha!
    // Disabled button ka name/value POST mein nahi jata
    // Isliye isset($_POST['add_announcement']) hamesha FALSE tha
    document.querySelectorAll('form').forEach(form => {
        if (form.hasAttribute('data-confirm')) return;
        form.addEventListener('submit', function () {
            const submitBtn = this.querySelector('[type="submit"]');
            if (!submitBtn) return;
            const original = submitBtn.innerHTML;
            // Sirf visual change — disabled NAHI karte
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size:13px;"></i> Processing…';
            setTimeout(() => {
                submitBtn.innerHTML = original;
            }, 8000);
        });
    });


    // ── 15. TABLE SEARCH / FILTER ─────────────────────────────
    const searchInput = document.querySelector('.table-search');
    const targetTable = document.querySelector('.table-searchable');
    if (searchInput && targetTable) {
        searchInput.addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            targetTable.querySelectorAll('tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }


    // ── 16. COPY COMPLAINT ID ────────────────────────────────
    document.querySelectorAll('.complaint-id').forEach(el => {
        el.style.cursor = 'pointer';
        el.title = 'Click to copy';
        el.addEventListener('click', () => {
            navigator.clipboard.writeText(el.textContent.trim())
                .then(() => showToast('Complaint ID copied!', 'success', 2000))
                .catch(() => {});
        });
    });


    // ── HELPERS ───────────────────────────────────────────────
    function escapeHTML(str) {
        return String(str).replace(/[&<>"']/g, c =>
            ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c])
        );
    }

    function formatBytes(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    console.log('%c Smart City Portal ✦ ', 'background:#00d4ff;color:#000;font-weight:700;padding:4px 10px;border-radius:4px;font-family:monospace;');
});