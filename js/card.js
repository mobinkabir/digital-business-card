(function(){
  'use strict';
  var langFromStorage = false;
  var currentLang = (function(){ try { var v = localStorage.getItem('cardLang'); if (v) { langFromStorage = true; return v; } } catch(e){} return 'fa'; })();

  var TRANSLATIONS = {
    fa: { viewProject:'مشاهده پروژه', message:'پیام', channel:'کانال', present:'تاکنون', year:'سال', years:'سال', month:'ماه', months:'ماه', details:'جزئیات', section:'بخش', yearsExp:'سال تجربه', print:'چاپ / ذخیره PDF', install:'نصب اپ', savePng:'ذخیره به‌صورت PNG', resumeHint:'رزومهٔ کامل', downloadResume:'دانلود رزومه', viewCertificate:'مشاهده گواهینامه', secAbout:'دربارهٔ من', secCountries:'کشورهایی که کار کرده‌ام', secContact:'راه‌های ارتباطی', secSocials:'شبکه‌های اجتماعی', secExperience:'تجربهٔ کاری', secSkills:'مهارت‌ها', secEducation:'تحصیلات', secCerts:'گواهینامه‌ها', secGallery:'گالری', secProjects:'پروژه‌ها', secMap:'آدرس و نقشه', secResume:'رزومه' },
    en: { viewProject:'View project', message:'Message', channel:'Channel', present:'Present', year:'yr', years:'yrs', month:'mo', months:'mos', details:'Details', section:'Section', yearsExp:'Years Experience', print:'Print / Save PDF', install:'Install app', savePng:'Save as PNG', resumeHint:'Full resume', downloadResume:'Download resume', viewCertificate:'View certificate', secAbout:'About Me', secCountries:"Countries I've Worked In", secContact:'Contact', secSocials:'Social Media', secExperience:'Work Experience', secSkills:'Skills', secEducation:'Education', secCerts:'Certifications', secGallery:'Gallery', secProjects:'Projects', secMap:'Location & Map', secResume:'Resume' },
  };
  function t(key){ var dict = TRANSLATIONS[currentLang] || TRANSLATIONS.en || {}; if (dict[key] != null) return dict[key]; var fb = TRANSLATIONS.en || {}; return (fb[key] != null) ? fb[key] : key; }
  // Language switcher wiring (dropdown with flags).
  (function initLangSwitcher(){
    var wrap = document.getElementById('langDropdown');
    if (!wrap) return;
    var toggle = document.getElementById('langToggle');
    var toggleFlag = document.getElementById('langToggleFlag');
    var menu = document.getElementById('langMenu');
    var flagMap = { fa:'image/flags/ir.png', en:'image/flags/gb.png' };
    function syncToggle(){
      if (toggleFlag) toggleFlag.setAttribute('src', flagMap[currentLang] || flagMap.fa);
    }
    function closeMenu(){ if (menu) menu.hidden = true; if (toggle) toggle.setAttribute('aria-expanded','false'); }
    function openMenu(){ if (menu) menu.hidden = false; if (toggle) toggle.setAttribute('aria-expanded','true'); }
    if (toggle) toggle.addEventListener('click', function(e){ e.stopPropagation(); if (menu.hidden) openMenu(); else closeMenu(); });
    document.addEventListener('click', function(e){ if (wrap && !wrap.contains(e.target)) closeMenu(); });
    function syncActive(){
      wrap.querySelectorAll('.lang-option').forEach(function(b){
        b.classList.toggle('is-active', b.getAttribute('data-lang') === currentLang);
      });
    }
    wrap.querySelectorAll('.lang-option').forEach(function(b){
      b.addEventListener('click', function(){
        currentLang = b.getAttribute('data-lang') || 'fa';
        try { localStorage.setItem('cardLang', currentLang); } catch(e){}
        document.documentElement.lang = currentLang;
        // Reload so every section (including dates) re-renders in the
        // chosen language/calendar instead of keeping stale strings.
        try { location.reload(); } catch(e){}
        syncToggle();
        syncActive();
        closeMenu();
      });
    });
    syncActive();
    syncToggle();
  })();
  function el(id){ return document.getElementById(id); }
  function escAttr(s){ return String(s).replace(/\r\n/g,'\n').replace(/\r/g,'\n').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'&#10;'); }
  /* Only real hex colours reach a style attribute. card.json is editable by
     hand, and an arbitrary string there must never become extra CSS. */
  function safeColor(value, fallback){
    var c = String(value || '').trim();
    return /^#[0-9a-f]{3,8}$/i.test(c) ? c : (fallback || '');
  }

  /**
   * Panel-managed browser-tab identity. The static tags in index.html are also
   * rewritten server-side on save (for crawlers), but applying them here keeps
   * the open tab correct even when only data/card.json changed.
   */
  function applyBrand(cd){
    var name = (cd && cd.name) ? String(cd.name).trim() : '';
    var customTitle = (cd && typeof cd.site_title === 'string') ? cd.site_title.trim() : '';
    var title = customTitle || (name ? name + ' — Digital Business Card' : '');
    var customDesc = (cd && typeof cd.site_description === 'string') ? cd.site_description.trim() : '';
    var description = customDesc || (name ? name + ((cd.role && String(cd.role).trim()) ? ' — ' + String(cd.role).trim() : '') : '');
    var logo = (cd && typeof cd.site_logo === 'string') ? cd.site_logo.trim() : '';

    function setMeta(selector, value){
      if (!value) return;
      var node = document.querySelector(selector);
      if (node) node.setAttribute('content', value);
    }
    if (title) {
      document.title = title;
      setMeta('meta[property="og:title"]', title);
      setMeta('meta[name="twitter:title"]', title);
    }
    if (description) {
      setMeta('meta[name="description"]', description);
      setMeta('meta[property="og:description"]', description);
      setMeta('meta[name="twitter:description"]', description);
    }
    if (name) setMeta('meta[property="og:site_name"]', name);
    if (logo) {
      var icons = document.querySelectorAll('link[rel~="icon"], link[rel="apple-touch-icon"]');
      Array.prototype.forEach.call(icons, function(link){ link.setAttribute('href', logo); });
    }
    return logo;
  }

  function setAvatar(src, altName){
    var box = el('card-avatar');
    if (!box || !src) return;
    var img = box.querySelector('img');
    if (!img) {
      img = document.createElement('img');
      // CSP blocks inline onerror handlers; attach the fallback in JS.
      img.addEventListener('error', function(){
        img.style.display = 'none';
        box.innerHTML = '<div class="avatar-initial">?</div>';
      });
      box.textContent = '';
      box.appendChild(img);
    }
    img.alt = altName || '';
    img.src = src;
  }

  function renderStats(list){
    var box = el('card-stats');
    if (!box) return;
    box.textContent = '';
    (list || []).forEach(function(stat){
      var num = stat && stat.num != null ? String(stat.num).trim() : '';
      var label = stat && stat.label != null ? String(stat.label).trim() : '';
      if (!num && !label) return;
      var cell = document.createElement('div');
      cell.className = 'hero-stat';
      cell.setAttribute('dir', 'auto');
      var numEl = document.createElement('span');
      numEl.className = 'hero-stat-num';
      numEl.textContent = num;
      var labelEl = document.createElement('span');
      labelEl.className = 'hero-stat-label';
      labelEl.textContent = label;
      cell.appendChild(numEl);
      cell.appendChild(labelEl);
      box.appendChild(cell);
    });
    box.hidden = box.children.length === 0;
  }

  function applyLang(lang){
    // Swap every data-i18n label so the whole UI follows the chosen language.
    document.querySelectorAll('[data-i18n]').forEach(function(node){
      var key = node.getAttribute('data-i18n');
      if (key) node.textContent = t(key);
    });
    var d = window.__siteData;
    if (!d || !d.siteSettings) return;
    document.documentElement.lang = lang;
    // Keep the card layout LTR; only text alignment follows content (dir=auto).
    var sel = el('langSelect');
    if (sel) sel.value = lang;
    if (lang === 'fa') return;
    var s = d.siteSettings[lang];
    if (!s) return;
    if (s.heroName && el('card-name')) el('card-name').textContent = s.heroName;
    if (s.heroRole && el('card-role')) el('card-role').textContent = s.heroRole;
    var about = s.heroIntro || s.aboutBody || '';
    if (about && about.trim() && el('card-about')) {
      el('card-about').textContent = about.trim();
      el('card-about').setAttribute('dir', 'auto');
      if (el('card-about-section')) el('card-about-section').style.display = 'flex';
    }
    if (Array.isArray(s.aboutTags) && el('card-skills')) {
      el('card-skills').innerHTML = s.aboutTags.map(function(t){ return '<span class="pill">' + escAttr(t) + '</span>'; }).join('');
      if (el('card-skills-section')) el('card-skills-section').style.display = 'flex';
    }
    var siteStats = [];
    [1,2,3].forEach(function(n){ var num = s['stat' + n + 'Num']; var label = s['stat' + n + 'Label']; if (num || label) siteStats.push({num:num,label:label}); });
    if (siteStats.length) renderStats(siteStats);
  }

  function initExpTooltip(){
    var container = document.getElementById('card-experience');
    if (!container) return;
    var tip = document.getElementById('exp-detail-tip');
    if (!tip){ tip = document.createElement('div'); tip.id = 'exp-detail-tip'; tip.className = 'exp-detail-tip'; tip.setAttribute('role','tooltip'); tip.setAttribute('dir','auto'); tip.style.display = 'none'; document.body.appendChild(tip); }
    var active = null;
    function position(el){
      var r = el.getBoundingClientRect();
      tip.style.left = Math.max(8, Math.min(window.innerWidth - tip.offsetWidth - 8, r.left)) + 'px';
      requestAnimationFrame(function(){
        var h = tip.offsetHeight;
        if (r.top - h - 12 > 0) tip.style.top = (r.top - h - 10) + 'px';
        else tip.style.top = (r.bottom + 10) + 'px';
      });
    }
    function show(el){
      var text = el.getAttribute('data-details');
      if (!text) return;
      text = String(text).replace(/\r\n/g, '\n').replace(/\r/g, '\n');
      tip.textContent = text;
      tip.style.display = 'block';
      el.classList.add('is-tip-open');
      active = el;
      position(el);
    }
    function hide(el){
      tip.style.display = 'none';
      if (el) el.classList.remove('is-tip-open');
      if (active === el) active = null;
    }
    // mouseover/mouseout (bubbling) rather than mouseenter/mouseleave: the
    // latter fire for every child too, so moving from the role text to the ⓘ
    // icon inside the same row hid the tooltip. relatedTarget keeps it open
    // while the pointer stays anywhere inside the row.
    container.addEventListener('mouseover', function(e){ var item = e.target && e.target.closest ? e.target.closest('.timeline-item[data-details]') : null; if (item && item !== active) { if (active) hide(active); show(item); } });
    container.addEventListener('mouseout', function(e){
      var item = e.target && e.target.closest ? e.target.closest('.timeline-item[data-details]') : null;
      if (!item) return;
      var to = e.relatedTarget;
      if (to && item.contains(to)) return;
      if (item === active) hide(item);
    });
    container.addEventListener('focusin', function(e){ var item = e.target && e.target.closest ? e.target.closest('.timeline-item[data-details]') : null; if (item){ if (active && active !== item) hide(active); show(item); } });
    container.addEventListener('focusout', function(e){ var item = e.target && e.target.closest ? e.target.closest('.timeline-item[data-details]') : null; if (item) hide(item); });
    container.addEventListener('click', function(e){ var item = e.target && e.target.closest ? e.target.closest('.timeline-item[data-details]') : null; if (!item) return; if (active === item) hide(item); else { if (active) hide(active); show(item); } });
    document.addEventListener('click', function(e){ if (active && !active.contains(e.target) && !tip.contains(e.target)) hide(active); });
  }

  var EXP_MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

  function faDigits(value){
    return String(value).replace(/\d/g, function(d){ return '۰۱۲۳۴۵۶۷۸۹'[d]; });
  }

  function jalaliMonthYear(date){
    try {
      var parts = new Intl.DateTimeFormat('fa-IR', { month: 'long', year: 'numeric' }).formatToParts(date);
      var month = '';
      var year = '';
      parts.forEach(function(part){
        if (part.type === 'month') month = part.value;
        if (part.type === 'year') year = part.value;
      });
      if (month && year) return month + ' ' + year;
      return date.toLocaleDateString('fa-IR', { month: 'long', year: 'numeric' });
    } catch (err) {
      return '';
    }
  }

  function jalaliDateLabel(iso){
    if (!iso) return '';
    var d = new Date(String(iso) + 'T00:00:00');
    if (isNaN(d.getTime())) return '';
    try {
      return d.toLocaleDateString('fa-IR', { day: 'numeric', month: 'long', year: 'numeric' });
    } catch (err) {
      return '';
    }
  }

  var EN_MONTH_INDEX = { jan: 0, feb: 1, mar: 2, apr: 3, may: 4, jun: 5, jul: 6, aug: 7, sep: 8, oct: 9, nov: 10, dec: 11 };

  function monthYearToJalali(text){
    var m = /^([A-Za-z]+)\s+(\d{4})$/.exec(String(text || '').trim());
    if (!m) return '';
    var idx = EN_MONTH_INDEX[m[1].slice(0, 3).toLowerCase()];
    if (idx === undefined) return '';
    // Mid-month keeps the Jalali month stable around calendar boundaries.
    return jalaliMonthYear(new Date(parseInt(m[2], 10), idx, 15));
  }

  function durationToFa(text){
    var out = [];
    var y = /(\d+)\s*yrs?/i.exec(text);
    if (y) out.push(faDigits(y[1]) + ' سال');
    var mo = /(\d+)\s*mos?/i.exec(text);
    if (mo) out.push(faDigits(mo[1]) + ' ماه');
    var d = /(\d+)\s*days?/i.exec(text);
    if (d) out.push(faDigits(d[1]) + ' روز');
    if (!out.length && /month/i.test(text)) out.push('۱ ماه');
    return out.join(' ');
  }

  /** Jalali period fallback for older entries that only have Gregorian labels. */
  function expPeriodJalaliFallback(e){
    if (!e) return '';
    if (!e.start_g && e.end_g) {
      var onlyEnd = new Date(e.end_g + 'T00:00:00');
      if (!isNaN(onlyEnd.getTime())) return jalaliMonthYear(onlyEnd);
      return '';
    }
    if (e.start_g) {
      var s = new Date(e.start_g + 'T00:00:00');
      if (!isNaN(s.getTime())) {
        var startLabel = jalaliMonthYear(s);
        if (!startLabel) return '';
        var end;
        var endLabel;
        if (e.current) {
          end = new Date();
          endLabel = 'تاکنون';
        } else if (e.end_g) {
          end = new Date(e.end_g + 'T00:00:00');
          if (isNaN(end.getTime())) return startLabel;
          endLabel = jalaliMonthYear(end);
        } else {
          return startLabel;
        }
        var y = end.getFullYear() - s.getFullYear();
        var m = end.getMonth() - s.getMonth();
        if (end.getDate() < s.getDate()) m--;
        if (m < 0) { y--; m += 12; }
        var parts = [];
        if (y > 0) parts.push(faDigits(y) + ' سال');
        if (m > 0) parts.push(faDigits(m) + ' ماه');
        var extra = parts.length ? ' · ' + parts.join(' ') : '';
        return startLabel + ' – ' + endLabel + extra;
      }
    }
    // Legacy rows store only an English label such as
    // "May 2023 – May 2024 · 11 months"; translate it to Jalali.
    var raw = String(e.period || (e.en && e.en.period) || '').trim();
    if (!raw) return '';
    var chunks = raw.split(/\s+·\s+/);
    var sides = (chunks[0] || '').split(/\s+[–—-]\s+/);
    if (sides.length !== 2) return '';
    var sLabel = monthYearToJalali(sides[0]);
    var eLabel = /present|current|now/i.test(sides[1]) ? 'تاکنون' : monthYearToJalali(sides[1]);
    if (!sLabel || !eLabel) return '';
    var durFa = durationToFa(chunks[1] || '');
    return sLabel + ' – ' + eLabel + (durFa ? ' · ' + durFa : '');
  }

  /** Education / certification date line, switched by the panel setting. */
  function credentialMeta(item, en, format){
    var legacy = (item && item.meta) || (en && en.meta) || '';
    if (format === 'jalali') {
      if (item && item.meta_jalali) return item.meta_jalali;
      if (item && item.date_label_jalali) return item.date_label_jalali;
      if (item && item.date_g) {
        var jl = jalaliDateLabel(item.date_g);
        if (jl) return jl;
      }
    } else {
      // Keep a descriptive label ("2021 – 2025 · Average grade 89%") when the
      // panel still stores one; only fall back to the picked year otherwise.
      var bareYear = /^\d{4}$/.test(legacy.trim());
      if (legacy && !bareYear) return legacy;
      if (item && item.date_label_en) return item.date_label_en;
      if (item && item.date_g) return String(item.date_g).slice(0, 4);
      if (legacy) return legacy;
    }
    return legacy;
  }

  function expPeriodText(e, en, format){
    format = format || 'gregorian';
    /* A current job keeps running, so its stored label goes stale between
       saves. Recompute it from the start date whenever we can. */
    var liveCurrent = !!(e && e.current && e.start_g);
    if (format === 'jalali') {
      if (e && e.period_jalali && !liveCurrent) return e.period_jalali;
      var jf = expPeriodJalaliFallback(e);
      if (jf) return jf;
    }
    if (format === 'gregorian' && e && e.period && !liveCurrent) return e.period;
    if (e && !e.start_g && e.end_g) {
      var onlyEndG = new Date(e.end_g + 'T00:00:00');
      if (!isNaN(onlyEndG.getTime())) return EXP_MONTHS[onlyEndG.getMonth()] + ' ' + onlyEndG.getFullYear();
    }
    if (e && e.start_g) {
      var s = new Date(e.start_g + 'T00:00:00');
      if (!isNaN(s.getTime())) {
        var label = EXP_MONTHS[s.getMonth()] + ' ' + s.getFullYear();
        var end;
        var endLabel;
        if (e.current) {
          end = new Date();
          endLabel = t('present');
        } else if (e.end_g) {
          end = new Date(e.end_g + 'T00:00:00');
          if (isNaN(end.getTime())) return (en && (en.period || en.meta)) || '';
          endLabel = EXP_MONTHS[end.getMonth()] + ' ' + end.getFullYear();
        } else {
          return label;
        }
        var y = end.getFullYear() - s.getFullYear();
        var m = end.getMonth() - s.getMonth();
        var d = end.getDate() - s.getDate();
        if (d < 0) { m--; }
        if (m < 0) { y--; m += 12; }
        var parts = [];
        if (y > 0) parts.push(y + ' ' + (y > 1 ? t('years') : t('year')));
        if (m > 0) parts.push(m + ' ' + (m > 1 ? t('months') : t('month')));
        var extra = parts.length ? ' · ' + parts.join(' ') : '';
        return label + ' – ' + endLabel + extra;
      }
    }
    return (en && (en.period || en.meta)) || '';
  }

  var platformIcons = {
    instagram: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5m0 2a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3H7m11 2a1 1 0 0 1 1 1 1 1 0 0 1-1 1 1 1 0 0 1-1-1 1 1 0 0 1 1-1m-7 2a5 5 0 0 1 5 5 5 5 0 0 1-5 5 5 5 0 0 1-5-5 5 5 0 0 1 5-5m0 2a3 3 0 0 0-3 3 3 3 0 0 0 3 3 3 3 0 0 0 3-3 3 3 0 0 0-3-3z"/></svg>',
    linkedin: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14m-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.32 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 0 1 1.4 1.4v4.93h2.79M6.88 8.56a1.68 1.68 0 0 0 1.68-1.68c0-.93-.75-1.69-1.68-1.69a1.69 1.69 0 0 0-1.69 1.69c0 .93.76 1.68 1.69 1.68m1.39 9.94v-8.37H5.5v8.37h2.77z"/></svg>',
    github: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2A10 10 0 0 0 2 12c0 4.42 2.87 8.17 6.84 9.5.5.08.66-.23.66-.5v-1.69c-2.77.6-3.36-1.34-3.36-1.34-.46-1.16-1.11-1.47-1.11-1.47-.91-.62.07-.6.07-.6 1 .07 1.53 1.03 1.53 1.03.87 1.52 2.34 1.07 2.91.83.09-.65.35-1.09.63-1.34-2.22-.25-4.55-1.11-4.55-4.92 0-1.11.38-2 1.03-2.71-.1-.25-.45-1.29.1-2.64 0 0 .84-.27 2.75 1.02.79-.22 1.65-.33 2.5-.33.85 0 1.71.11 2.5.33 1.91-1.29 2.75-1.02 2.75-1.02.55 1.35.2 2.39.1 2.64.65.71 1.03 1.6 1.03 2.71 0 3.82-2.34 4.66-4.57 4.91.36.31.69.92.69 1.85V21c0 .27.16.59.67.5C19.14 20.16 22 16.42 22 12A10 10 0 0 0 12 2z"/></svg>',
    x: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
    youtube: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 15l5.19-3L10 9v6m11.56-7.83c.13.47.22 1.1.28 1.9.07.8.1 1.49.1 2.09L22 12c0 2.19-.16 3.8-.44 4.83-.25.9-.83 1.48-1.73 1.73-.47.13-1.33.22-2.65.28-1.29.07-2.49.1-3.59.1L12 19c-4.19 0-6.8-.16-7.83-.44-.9-.25-1.48-.83-1.73-1.73-.13-.47-.22-1.1-.28-1.9-.07-.8-.1-1.49-.1-2.09L2 12c0-2.19.16-3.8.44-4.83.25-.9.83-1.48 1.73-1.73.47-.13 1.33-.22 2.65-.28 1.29-.07 2.49-.1 3.59-.1L12 5c4.19 0 6.8.16 7.83.44.9.25 1.48.83 1.73 1.73z"/></svg>',
    telegram: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>',
    whatsapp: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884"/></svg>',
    facebook: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
    tiktok: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>'
  };

  var contactIcons = {
    phone: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
    email: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 4h16v16H4z"/><polyline points="22,6 12,13 2,6"/></svg>',
    link: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a15 15 0 0 1 0 18 15 15 0 0 1 0-18Z"/></svg>',
    whatsapp: platformIcons.whatsapp,
    telegram: platformIcons.telegram
  };

  /* ------------------------------------------------------------------
     Social networks: two sources feed the card.
       - data/card.json `socials[]`  (edited in the panel, authoritative)
       - data/content-site.json `socialLinks[]` (legacy site payload)
     card.json used to overwrite the legacy list with an empty string even
     when every row had a blank URL, which silently removed working links
     from the card. Everything below merges both sources, prefers card.json
     per platform, and never renders an empty list over a populated one.
     ------------------------------------------------------------------ */
  var SOCIAL_KEY = { ig:'instagram', li:'linkedin', gh:'github', x:'x', yt:'youtube', tg:'telegram', wa:'whatsapp', fb:'facebook', tt:'tiktok' };
  var SOCIAL_PALETTE = { instagram:'#E1306C', linkedin:'#0A66C2', github:'#24292F', x:'#0F1419', youtube:'#FF0000', telegram:'#26A5E4', whatsapp:'#25D366', facebook:'#1877F2', tiktok:'#FE2C55' };
  var siteSocials = [];

  function socialPlatformName(x){
    var key = String((x && (x.icon || x.platform)) || '').toLowerCase().trim();
    if (SOCIAL_KEY[key]) return SOCIAL_KEY[key];
    return key.replace(/\s+/g, '');
  }

  function normalizeSocial(x){
    if (!x || typeof x !== 'object') return null;
    var platform = socialPlatformName(x);
    var url = typeof x.url === 'string' ? x.url.trim() : '';
    if (!platform || !url) return null;
    return {
      platform: platform,
      label: String(x.label || platform),
      url: url,
      color: safeColor(x.color, SOCIAL_PALETTE[platform] || '#2563eb')
    };
  }

  function buildSocialsHtml(items){
    return (items || []).map(function(x){
      var p = x.platform;
      var icon = platformIcons[p] || platformIcons.instagram;
      var color = x.color || SOCIAL_PALETTE[p] || '#2563eb';
      return '<a data-platform="' + escAttr(p) + '" href="' + escAttr(x.url) + '" target="_blank" rel="noopener" aria-label="' + escAttr(x.label || p) + '" style="color:' + color + ';border-color:' + color + '40;background:' + color + '18;"><span style="color:' + color + ';display:inline-flex">' + icon + '</span></a>';
    }).join('');
  }

  function paintSocials(html){
    if (!html) return false;
    ['card-socials', 'card-socials-inline'].forEach(function(id){
      var box = el(id);
      if (!box) return;
      box.innerHTML = html;
      if (!box.classList.contains('template01-socials')) box.classList.add('template01-socials');
    });
    return true;
  }

  /* card.json first, then any legacy platform not covered by a card.json
     entry that actually has a URL. */
  function mergeSocials(primary, fallback){
    var seen = {};
    var out = [];
    (Array.isArray(primary) ? primary : []).forEach(function(x){
      var n = normalizeSocial(x);
      if (!n || seen[n.platform]) return;
      seen[n.platform] = true;
      out.push(n);
    });
    (Array.isArray(fallback) ? fallback : []).forEach(function(x){
      var n = normalizeSocial(x);
      if (!n || seen[n.platform]) return;
      seen[n.platform] = true;
      out.push(n);
    });
    return out;
  }

  fetch('data/content-site.json?v=' + Date.now())
    .then(function(r){ return r.json(); })
    .then(function(d){
      window.__siteData = d;
      var s = (d.siteSettings && (d.siteSettings.en || d.siteSettings.fa)) || {};
      var name = s.heroName || 'Your Name';
      if (el('card-name')) el('card-name').textContent = name;

      var role = s.heroRole || 'Your Professional Title';
      if (el('card-role')) el('card-role').textContent = role;

       var about = s.heroIntro || s.aboutBody || '';
       if (about && about.trim()) {
         if (el('card-about')) { el('card-about').textContent = about.trim(); el('card-about').setAttribute('dir', 'auto'); }
         if (el('card-about-section')) el('card-about-section').style.display = 'flex';
         var ad = document.querySelector('[data-section-divider="about"]');
         if (ad) ad.style.display = 'block';
       }

      var skills = Array.isArray(s.aboutTags) ? s.aboutTags : [] ;
      if (skills.length > 0 && el('card-skills')) {
        el('card-skills').innerHTML = skills.map(function(sk){ return '<span class="pill">' + escAttr(sk) + '</span>'; }).join('');
        if (el('card-skills-section')) el('card-skills-section').style.display = 'flex';
        var sd = document.querySelector('[data-section-divider="skills"]');
        if (sd) sd.style.display = 'block';
      }

      var photo = d.heroPhoto || '';
      var av = el('card-avatar');
      if (av) {
        if (photo) {
          var avImg = document.createElement('img');
          avImg.alt = name;
          // CSP blocks inline onerror handlers; attach the fallback in JS.
          avImg.addEventListener('error', function(){
            avImg.style.display = 'none';
            av.innerHTML = '<div class="avatar-initial">?</div>';
          });
          avImg.src = photo;
          av.textContent = '';
          av.appendChild(avImg);
        } else {
          av.innerHTML = '<div class="avatar-initial">?</div>';
        }
      }

      var legacySocials = Array.isArray(d.socialLinks) ? d.socialLinks : [] ;
      siteSocials = legacySocials.filter(function(x){ return x && x.visible !== false && x.url; });
      paintSocials(buildSocialsHtml(mergeSocials([], siteSocials)));

      // content-site.json keeps up to three stat pairs in siteSettings.
      var siteStats = [];
      [1, 2, 3].forEach(function(n){
        var num = s['stat' + n + 'Num'];
        var label = s['stat' + n + 'Label'];
        if (num || label) siteStats.push({ num: num, label: label });
      });
      renderStats(siteStats);

      var wa = siteSocials.find(function(x){ return socialPlatformName(x) === 'whatsapp' && x.url; });
      var tg = siteSocials.find(function(x){ return socialPlatformName(x) === 'telegram' && x.url; });
      var contactsHtml = '';
      if (wa) {
        contactsHtml += '<a class="list-item" href="' + escAttr(wa.url) + '" target="_blank" rel="noopener" style="border-color:#25D36680;background:linear-gradient(135deg,rgba(37,211,102,0.18),rgba(37,211,102,0.06));color:#25D366"><span class="list-item-icon" style="color:#25D366;border-color:#25D366">' + contactIcons.whatsapp + '</span><div class="list-item-text"><span class="list-item-label">WhatsApp</span><span class="list-item-value">Message</span></div><span class="list-item-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M9 18l6-6-6-6"/></svg></span></a>';
      }
      if (tg) {
        contactsHtml += '<a class="list-item" href="' + escAttr(tg.url) + '" target="_blank" rel="noopener" style="border-color:#26A5E480;background:linear-gradient(135deg,rgba(38,165,228,0.18),rgba(38,165,228,0.06));color:#26A5E4"><span class="list-item-icon" style="color:#26A5E4;border-color:#26A5E4">' + contactIcons.telegram + '</span><div class="list-item-text"><span class="list-item-label">Telegram</span><span class="list-item-value">Telegram profile</span></div><span class="list-item-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M9 18l6-6-6-6"/></svg></span></a>';
      }
      var yt = siteSocials.find(function(x){ return socialPlatformName(x) === 'youtube' && x.url; });
      if (yt) {
        contactsHtml += '<a class="list-item" href="' + escAttr(yt.url) + '" target="_blank" rel="noopener" style="border-color:#FF000080;background:linear-gradient(135deg,rgba(255,0,0,0.18),rgba(255,0,0,0.06));color:#FF0000"><span class="list-item-icon" style="color:#FF0000;border-color:#FF0000">' + platformIcons.youtube + '</span><div class="list-item-text"><span class="list-item-label">YouTube</span><span class="list-item-value">Channel</span></div><span class="list-item-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M9 18l6-6-6-6"/></svg></span></a>';
      }
      if (el('card-contacts')) el('card-contacts').innerHTML = contactsHtml;

      // Experience & Education
      var exp = Array.isArray(d.experience) ? d.experience : [] ;
      if (exp.length > 0 && el('card-experience')) {
        var expHtml = exp.map(function(e){
          var en = (e.en) || e;
          var roleExp = en.role || en.title || '';
          var orgExp = en.company || en.org || '';
          var periodExp = en.period || en.meta || '';
          return '<article class="timeline-item"><div class="timeline-header"><div><div class="timeline-role">' + roleExp + '</div><div class="timeline-org">' + orgExp + '</div></div><div class="timeline-meta">' + periodExp + '</div></div></article>';
        }).join('');
        el('card-experience').innerHTML = expHtml;
        if (el('card-experience-section')) el('card-experience-section').style.display = 'flex';
        var ediv = document.querySelector('[data-section-divider="experience"]');
        if (ediv) ediv.style.display = 'block';
      }

       var edu = Array.isArray(d.education) ? d.education : [] ;
       var cert = Array.isArray(d.certs) ? d.certs : [] ;
       var eduItems = [];
       edu.forEach(function(ed){
         var en = (ed.en) || ed;
         var deg = (ed.degree_type || (en && en.degree_type) || '').trim();
         var fld = (ed.field || ed.major || (en && (en.field || en.major)) || '').trim();
         var etitle = (deg || fld) ? (deg + (deg && fld ? ' — ' : '') + fld) : (en.title || ed.title || '');
         var eduFile = (ed && (ed.file || (en && en.file))) || '';
         var eduFileHtml = eduFile ? ('<a class="edu-file-link" href="' + escAttr(String(eduFile)) + '" target="_blank" rel="noopener">مشاهده مدرک</a>') : '';
         eduItems.push('<article class="edu-item"><div class="edu-title">' + escAttr(etitle) + '</div><div class="edu-org">' + escAttr(en.org || ed.org || '') + '</div><div class="edu-meta">' + escAttr(en.meta || ed.meta || '') + '</div>' + eduFileHtml + '</article>');
       });
       if (eduItems.length > 0 && el('card-education')) {
         el('card-education').innerHTML = eduItems.join('');
         if (el('card-education-section')) el('card-education-section').style.display = 'flex';
         var dve = document.querySelector('[data-section-divider="education"]');
         if (dve) dve.style.display = 'block';
       }
       var certItems = [];
       cert.forEach(function(c){
         var en = (c.en) || c;
         var certFile = (c && (c.file || (en && en.file))) || '';
         var fileHtml = certFile ? ('<a class="edu-file-link" href="' + escAttr(String(certFile)) + '" target="_blank" rel="noopener" data-i18n="viewCertificate">مشاهده گواهینامه</a>') : '';
         certItems.push('<article class="edu-item"><div class="edu-title">' + (en.title || '') + '</div><div class="edu-org">' + (en.org || '') + '</div><div class="edu-meta">' + (en.meta || '') + '</div>' + fileHtml + '</article>');
       });
       if (certItems.length > 0 && el('card-certs')) {
         el('card-certs').innerHTML = certItems.join('');
         if (el('card-certs-section')) el('card-certs-section').style.display = 'flex';
         var dvc = document.querySelector('[data-section-divider="certs"]');
         if (dvc) dvc.style.display = 'block';
       }

      var projects = Array.isArray(d.projects) ? d.projects : [] ;
      var portfolioHtml = projects.map(function(p){
        var title = (p && p.fa && p.fa.title) || (p && p.title) || 'Project';
        var url = (p && (p.url || p.link)) || '#';
        return '<a class="project-link" href="' + escAttr(url) + '" target="_blank" rel="noopener"><span class="project-dot"></span><div class="project-text"><span class="project-title">' + escAttr(title) + '</span><span class="project-sub">' + t('viewProject') + '</span></div><span class="project-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M9 18l6-6-6-6"/></svg></span></a>';
      }).join('');
      if (el('card-portfolio')) el('card-portfolio').innerHTML = portfolioHtml;
      // No projects means no "View all projects" placeholder (there is no
      // portfolio page to link to), so drop the empty section entirely.
      if (projects.length === 0) {
        if (el('card-portfolio-section')) {
          el('card-portfolio-section').style.display = 'none';
          el('card-portfolio-section').setAttribute('data-empty', '1');
        }
        var pdivEmpty = document.querySelector('[data-section-divider="portfolio"]');
        if (pdivEmpty) pdivEmpty.style.display = 'none';
      }

      // Apply language (non-default overrides hero fields from siteSettings).
      try { applyLang(currentLang); } catch (e) {}

      // Load card.json for countries/gallery/sections
      fetch('data/card.json?v=' + Date.now())
        .then(function(r){ return r.json(); })
        .then(function(cd){
          // card.json is what admin/card.php edits, so wherever it has a value
          // that value wins over content-site.json. This is what makes the
          // standalone panel able to change the card.
          var brandLogo = applyBrand(cd);
          // Apply the panel-chosen font (uses the system font stack by default).
          var cardEl = document.querySelector('.card');
          if (cardEl) {
            var chosenFont = (cd && typeof cd.font_family === 'string') ? cd.font_family.trim() : '';
            cardEl.style.fontFamily = chosenFont ? (chosenFont + ', sans-serif') : '';
          }
          if (cd && typeof cd.bg_color === 'string' && /^#[0-9a-f]{3,8}$/i.test(cd.bg_color.trim())) {
            document.body.style.background = cd.bg_color.trim();
            document.documentElement.style.background = cd.bg_color.trim();
          }
          // Profile photo frame: circle / square / rounded (panel setting).
          var photoShape = (cd && typeof cd.photo_shape === 'string') ? cd.photo_shape.trim() : '';
          var avatarBox = el('card-avatar');
          if (avatarBox && /^(circle|square|rounded)$/.test(photoShape)) {
            avatarBox.classList.remove('shape-circle', 'shape-square', 'shape-rounded');
            avatarBox.classList.add('shape-' + photoShape);
          }
          // Language switcher visibility + default language.
          var langWrap = document.getElementById('langDropdown');
          if (langWrap) {
            var visible = (cd && (cd.lang_switcher_visible === '0' || cd.lang_switcher_visible === false || cd.lang_switcher_visible === 0)) ? false : true;
            langWrap.style.display = visible ? '' : 'none';
            if (!langFromStorage && cd && typeof cd.default_language === 'string' && cd.default_language.trim()) {
              currentLang = cd.default_language.trim();
              try { localStorage.setItem('cardLang', currentLang); } catch(e){}
            }
            (function(){ var bs = document.querySelectorAll('.lang-btn'); bs.forEach(function(b){ b.classList.toggle('is-active', b.getAttribute('data-lang') === currentLang); }); })();
            if (currentLang !== 'fa') { try { applyLang(currentLang); } catch(e){} }
          }
          if (cd.name && el('card-name')) el('card-name').textContent = cd.name;
          if (cd.role && el('card-role')) el('card-role').textContent = cd.role;
          // Empty stats[] is explicit: the admin removed every stat, so the
          // strip must disappear instead of keeping the content-site values.
          if (Array.isArray(cd.stats)) renderStats(cd.stats);
          // Re-apply selected language on top of admin-managed values.
          if (currentLang !== 'fa') { try { applyLang(currentLang); } catch(e){} }
          // About is authoritative whenever card.json carries either field, even
          // when the admin cleared it (empty must clear the content-site text).
          var aboutManaged = (typeof cd.about_html === 'string') || (typeof cd.about === 'string');
          if (aboutManaged && el('card-about')) {
            if (cd.about_html && String(cd.about_html).trim()) {
              el('card-about').innerHTML = String(cd.about_html).trim();
            } else {
              el('card-about').textContent = String(cd.about || '').trim();
            }
            el('card-about').setAttribute('dir', 'auto');
            if (el('card-about-section')) el('card-about-section').style.display = 'flex';
            var aboutDivider = document.querySelector('[data-section-divider="about"]');
            if (aboutDivider) aboutDivider.style.display = 'block';
          }

           function clearManaged(boxId, sectionId, key){
             var box = el(boxId);
             if (box) box.innerHTML = '';
             var sec = el(sectionId);
             if (sec) { sec.style.display = 'none'; sec.setAttribute('data-empty', '1'); }
             var div = document.querySelector('[data-section-divider="' + key + '"]');
             if (div) div.style.display = 'none';
           }

           // Admin-managed experience override.
           if (Array.isArray(cd.experience) && el('card-experience')) {
             if (!cd.experience.length) {
               clearManaged('card-experience', 'card-experience-section', 'experience');
             } else {
             var expHtml2 = cd.experience.map(function(e){
               var en = (e && e.en) ? e.en : e;
               var roleExp = (en && (en.role || en.title)) || '';
               var orgExp = (en && (en.company || en.org)) || '';
               var periodExp = expPeriodText(e, en, (currentLang === 'fa' ? (cd.date_format || 'jalali') : 'gregorian'));
               var details = (e && e.details) ? String(e.details).trim() : '';
               var detailsAttr = details ? ' data-details="' + escAttr(details) + '"' : '';
               var infoIcon = details ? '<span class="timeline-info" aria-label="' + t('details') + '" title="' + t('details') + '">ⓘ</span>' : '';
               return '<article class="timeline-item' + (details ? ' has-details' : '') + '"' + detailsAttr + ' tabindex="' + (details ? '0' : '-1') + '">' +
                 '<div class="timeline-header"><div><div class="timeline-role">' + escAttr(roleExp) + '</div><div class="timeline-org">' + escAttr(orgExp) + '</div></div>' +
                 '<div class="timeline-meta">' + escAttr(periodExp) + infoIcon + '</div></div></article>';
             }).join('');
             el('card-experience').innerHTML = expHtml2;
             if (el('card-experience-section')) el('card-experience-section').style.display = 'flex';
             var ediv2 = document.querySelector('[data-section-divider="experience"]');
             if (ediv2) ediv2.style.display = 'block';
             initExpTooltip();
             }
           }

           // Admin-managed education override. Kept outside the experience
           // branch: deleting every experience entry must not erase education.
           if (Array.isArray(cd.education) && el('card-education')) {
             if (!cd.education.length) {
               clearManaged('card-education', 'card-education-section', 'education');
             } else {
             var eduHtml2 = cd.education.map(function(e){
               var en = (e && e.en) ? e.en : e;
               var deg = (e.degree_type || (en && en.degree_type) || '').trim();
               var fld = (e.field || e.major || (en && (en.field || en.major)) || '').trim();
               var title = (deg || fld) ? (deg + (deg && fld ? ' — ' : '') + fld) : ((en && (en.title || e.title)) || '');
               var org = (en && (en.org || en.company || e.org || e.company)) || '';
               var meta = credentialMeta(e, en, (currentLang === 'fa' ? (cd.date_format || 'jalali') : 'gregorian'));
               var eduFile = (e && (e.file || (en && en.file))) || '';
               var eduFileHtml = eduFile ? ('<a class="edu-file-link" href="' + escAttr(String(eduFile)) + '" target="_blank" rel="noopener">مشاهده مدرک</a>') : '';
               return '<article class="edu-item"><div class="edu-title">' + escAttr(title) + '</div><div class="edu-org">' + escAttr(org) + '</div><div class="edu-meta">' + escAttr(meta) + '</div>' + eduFileHtml + '</article>';
             }).join('');
             el('card-education').innerHTML = eduHtml2;
             if (el('card-education-section')) el('card-education-section').style.display = 'flex';
             var edivE = document.querySelector('[data-section-divider="education"]');
             if (edivE) edivE.style.display = 'block';
             }
           }
           // Admin-managed certifications override.
           if (Array.isArray(cd.certs) && el('card-certs')) {
             if (!cd.certs.length) {
               clearManaged('card-certs', 'card-certs-section', 'certs');
             } else {
             var certHtml2 = cd.certs.map(function(c){
               var en = (c && c.en) ? c.en : c;
               var title = (en && (en.title || c.title)) || '';
               var org = (en && (en.org || en.company || c.org || c.company)) || '';
               var meta = credentialMeta(c, en, (currentLang === 'fa' ? (cd.date_format || 'jalali') : 'gregorian'));
               var certFile = (c && (c.file || (en && en.file))) || '';
               var fileHtml = certFile ? ('<a class="edu-file-link" href="' + escAttr(String(certFile)) + '" target="_blank" rel="noopener">مشاهده گواهینامه</a>') : '';
               return '<article class="edu-item"><div class="edu-title">' + escAttr(title) + '</div><div class="edu-org">' + escAttr(org) + '</div><div class="edu-meta">' + escAttr(meta) + '</div>' + fileHtml + '</article>';
             }).join('');
             el('card-certs').innerHTML = certHtml2;
             if (el('card-certs-section')) el('card-certs-section').style.display = 'flex';
             var edivC = document.querySelector('[data-section-divider="certs"]');
             if (edivC) edivC.style.display = 'block';
             }
           }

           // Admin-managed projects override.
           if (Array.isArray(cd.projects) && el('card-portfolio')) {
             if (!cd.projects.length) {
               clearManaged('card-portfolio', 'card-portfolio-section', 'portfolio');
             } else {
             var portfolioHtml2 = cd.projects.map(function(p){
               var title = (p && ((p.fa && p.fa.title) || p.title)) || 'Project';
               var url = (p && (p.url || p.link)) || '#';
               return '<a class="project-link" href="' + escAttr(url) + '" target="_blank" rel="noopener"><span class="project-dot"></span><div class="project-text"><span class="project-title">' + escAttr(title) + '</span><span class="project-sub">' + t('viewProject') + '</span></div><span class="project-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M9 18l6-6-6-6"/></svg></span></a>';
             }).join('');
             el('card-portfolio').innerHTML = portfolioHtml2;
             if (el('card-portfolio-section')) el('card-portfolio-section').style.display = 'flex';
             var pdiv2 = document.querySelector('[data-section-divider="portfolio"]');
             if (pdiv2) pdiv2.style.display = 'block';
             }
           }

          if (Array.isArray(cd.tags) && el('card-skills')) {
            el('card-skills').innerHTML = cd.tags.map(function(t){ return '<span class="pill">' + escAttr(t) + '</span>'; }).join('');
            if (el('card-skills-section')) el('card-skills-section').style.display = 'flex';
            var skillsDivider = document.querySelector('[data-section-divider="skills"]');
            if (skillsDivider) skillsDivider.style.display = 'block';
          }
          if (cd.photo) {
            setAvatar(cd.photo, cd.name || '');
          } else if (brandLogo) {
            // No profile photo: the panel-managed logo doubles as the avatar
            // instead of leaving a generic placeholder badge.
            setAvatar(brandLogo, cd.name || '');
          }

          // Merge: panel-managed rows win, legacy platforms fill the gaps.
          var mergedSocials = mergeSocials(Array.isArray(cd.socials) ? cd.socials : [], siteSocials);
          paintSocials(buildSocialsHtml(mergedSocials));

          if (Array.isArray(cd.contacts)) {
            var contactKey = { wa:'whatsapp', tg:'telegram', ig:'instagram', li:'linkedin', gh:'github', x:'x', yt:'youtube', fb:'facebook', tt:'tiktok', call:'phone', phone:'phone', tel:'phone', email:'email', mail:'email', web:'link', website:'link', link:'link' };
            var tintFor = { whatsapp:'#25D366', telegram:'#26A5E4', youtube:'#FF0000', instagram:'#E1306C', linkedin:'#0A66C2', github:'#24292F', x:'#0F1419', facebook:'#1877F2', tiktok:'#FE2C55', phone:'#4F46E5', email:'#EA580C', link:'#7C3AED' };
            var cdContactsHtml = cd.contacts.filter(function(c){ return c.label || c.value || c.url; }).map(function(c){
              var k = contactKey[(c.icon || '').toLowerCase().trim()] || '';
              var icon = contactIcons[k] || platformIcons[k] || contactIcons.phone;
              var tint = tintFor[k] || '';
              var linkStyle = tint ? ' style="border-color:' + tint + '80;background:linear-gradient(135deg,' + tint + '2e,' + tint + '0f);color:' + tint + '"' : '';
              var iconStyle = tint ? ' style="color:' + tint + ';border-color:' + tint + '"' : '';
              var href = c.url || '#';
              var ext = /^https?:/i.test(href) ? ' target="_blank" rel="noopener"' : '';
              return '<a class="list-item" href="' + escAttr(href) + '"' + ext + linkStyle + '><span class="list-item-icon"' + iconStyle + '>' + icon + '</span><div class="list-item-text"><span class="list-item-label">' + escAttr(c.label || '') + '</span><span class="list-item-value">' + escAttr(c.value || '') + '</span></div><span class="list-item-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M9 18l6-6-6-6"/></svg></span></a>';
            }).join('');
            if (el('card-contacts')) el('card-contacts').innerHTML = cdContactsHtml;

            // Panel-managed "special links" (a flat label/sub/url list) render
            // at the end of the contact list; before this they were saved but
            // never shown anywhere on the card.
            if (Array.isArray(cd.specialLinks) && el('card-contacts')) {
              var specialsHtml = cd.specialLinks.filter(function(x){
                return x && (x.label || x.url);
              }).map(function(x){
                var label = x.label || x.url || '';
                var sub = x.sub || x.url || '';
                var href = x.url || '#';
                var ext = /^https?:/i.test(href) ? ' target="_blank" rel="noopener"' : '';
                return '<a class="list-item" href="' + escAttr(href) + '"' + ext + '><span class="list-item-icon">' + contactIcons.link + '</span><div class="list-item-text"><span class="list-item-label">' + escAttr(label) + '</span><span class="list-item-value">' + escAttr(sub) + '</span></div><span class="list-item-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M9 18l6-6-6-6"/></svg></span></a>';
              }).join('');
              if (specialsHtml) el('card-contacts').insertAdjacentHTML('beforeend', specialsHtml);
            }
          }

          var countries = Array.isArray(cd.countries) ? cd.countries : [] ;
           var banner = cd.banner || null;
           if (banner && banner.image && el('card-banner')) {
             el('card-banner-img').src = banner.image;
             el('card-banner-img').alt = banner.title || '';
             if (banner.url) el('card-banner-link').href = banner.url;
             // Without a URL the banner is still worth showing, but it must not
             // be a link that jumps to "#".
             else el('card-banner-link').removeAttribute('href');
             if (banner.title && el('card-banner-title')) el('card-banner-title').textContent = banner.title;
             if (banner.subtitle && el('card-banner-sub')) el('card-banner-sub').textContent = banner.subtitle;
             el('card-banner').style.display = 'block';
           }
          if (Array.isArray(cd.countries) && el('card-countries')) {
            var countriesBox = el('card-countries');
            countriesBox.textContent = '';
            countries.forEach(function(c){
              var raw = (typeof c === 'string') ? c : (c && c.code) || '';
              var code = String(raw).toLowerCase().replace(/[^a-z]/g, '').slice(0, 2);
              if (code.length !== 2) return;
              var nameC = (c && typeof c.name === 'string' && c.name.trim()) ? c.name.trim() : code.toUpperCase();
              var wrap = document.createElement('div');
              wrap.className = 'flag';
              wrap.title = nameC;
              wrap.setAttribute('aria-label', nameC);
              var flagImg = document.createElement('img');
              flagImg.alt = nameC;
              flagImg.loading = 'lazy';
              // CSP blocks inline onerror; fall back to the country code.
              flagImg.addEventListener('error', function(){
                var span = document.createElement('span');
                span.className = 'flag-code';
                span.textContent = code.toUpperCase();
                if (flagImg.parentNode) flagImg.parentNode.replaceChild(span, flagImg);
              });
              flagImg.src = 'image/flags/' + code + '.png';
              wrap.appendChild(flagImg);
              countriesBox.appendChild(wrap);
            });
          }

          var gallery = Array.isArray(cd.gallery) ? cd.gallery : [] ;
          if (gallery.length > 0 && el('card-gallery')) {
            var galleryHtml = gallery.filter(function(g){
              return g && typeof g === 'object' && g.image;
            }).map(function(g, idx){
              var img = g.image;
              var title = g.title || '';
              var cls = idx === 0 ? 'gallery-item active' : 'gallery-item';
              return '<div class="' + cls + '" title="' + escAttr(title) + '"><img src="' + escAttr(img) + '" alt="' + escAttr(title) + '" loading="lazy" decoding="async" draggable="false"></div>';
            }).join('');
            el('card-gallery').innerHTML = galleryHtml;

            // Gallery: one image at a time. Click opens a full-screen preview,
            // swipe/drag on touch or mouse moves between images, and there are
            // deliberately no on-image arrow buttons.
            (function initGallery(){
              var box = el('card-gallery');
              if (!box) return;
              var items = Array.prototype.slice.call(box.querySelectorAll('.gallery-item'));
              if (items.length === 0) return;

              var lightbox = document.getElementById('card-lightbox');
              var lightboxImg = lightbox ? lightbox.querySelector('img') : null;
              var closeBtn = lightbox ? lightbox.querySelector('.lightbox-close') : null;
              var current = 0;
              var swipedAt = 0;

              var counter = document.createElement('span');
              counter.className = 'gallery-counter';

              function renderLightbox(){
                var src = items[current].querySelector('img');
                if (!src || !lightboxImg) return;
                lightboxImg.src = src.currentSrc || src.src;
                lightboxImg.alt = src.alt || '';
              }

              function show(i){
                current = (i + items.length) % items.length;
                items.forEach(function(it, idx){ it.classList.toggle('active', idx === current); });
                counter.textContent = (current + 1) + ' / ' + items.length;
                if (lightbox && lightbox.classList.contains('open')) renderLightbox();
              }

              if (items.length > 1) {
                box.appendChild(counter);
                show(0);
              }

              function openLightbox(){
                if (!lightbox) return;
                renderLightbox();
                lightbox.classList.add('open');
                lightbox.setAttribute('aria-hidden', 'false');
                document.body.classList.add('lightbox-open');
                if (closeBtn) closeBtn.focus();
              }

              function closeLightbox(){
                if (!lightbox) return;
                lightbox.classList.remove('open');
                lightbox.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('lightbox-open');
                if (lightboxImg) lightboxImg.removeAttribute('src');
              }

              // Pointer-based swipe: works with finger and with mouse drag.
              function bindSwipe(target, enableNav){
                var startX = 0, startY = 0, tracking = false;
                target.addEventListener('pointerdown', function(e){
                  if (e.pointerType === 'mouse' && e.button !== 0) return;
                  tracking = true;
                  startX = e.clientX;
                  startY = e.clientY;
                });
                target.addEventListener('pointermove', function(e){
                  if (!tracking) return;
                  var dx = e.clientX - startX;
                  var dy = e.clientY - startY;
                  if (Math.abs(dx) > 12 && Math.abs(dx) > Math.abs(dy)) {
                    // Keep the horizontal gesture from selecting or dragging.
                    if (e.cancelable) e.preventDefault();
                  }
                });
                target.addEventListener('pointerup', function(e){
                  if (!tracking) return;
                  tracking = false;
                  var dx = e.clientX - startX;
                  var dy = e.clientY - startY;
                  if (enableNav && items.length > 1 && Math.abs(dx) > 45 && Math.abs(dx) > Math.abs(dy)) {
                    // LTR: drag left -> next, drag right -> previous.
                    show(dx < 0 ? current + 1 : current - 1);
                    swipedAt = Date.now();
                  }
                });
                target.addEventListener('pointercancel', function(){ tracking = false; });
              }

              box.addEventListener('click', function(e){
                if (Date.now() - swipedAt < 350) return;
                var item = e.target.closest ? e.target.closest('.gallery-item') : null;
                if (!item) return;
                openLightbox();
              });

              bindSwipe(box, true);

              if (lightbox) {
                bindSwipe(lightbox, true);
                lightbox.addEventListener('click', function(e){
                  if (Date.now() - swipedAt < 350) return;
                  if (e.target === lightbox) closeLightbox();
                });
                if (closeBtn) closeBtn.addEventListener('click', closeLightbox);
                document.addEventListener('keydown', function(e){
                  if (!lightbox.classList.contains('open')) return;
                  if (e.key === 'Escape') {
                    closeLightbox();
                  } else if (e.key === 'ArrowLeft') {
                    show(current - 1);
                  } else if (e.key === 'ArrowRight') {
                    show(current + 1);
                  }
                });
              }

              // No download affordance: no links, no context menu, no drag-out.
              function block(e){ e.preventDefault(); }
              box.addEventListener('contextmenu', block);
              box.addEventListener('dragstart', block);
              box.addEventListener('selectstart', block);
              if (lightbox) {
                lightbox.addEventListener('contextmenu', block);
                lightbox.addEventListener('dragstart', block);
              }
            })();

            if (el('card-gallery-section')) el('card-gallery-section').style.display = 'flex';
            var gdiv = document.querySelector('[data-section-divider="gallery"]');
            if (gdiv) gdiv.style.display = 'block';
          } else if (Array.isArray(cd.gallery) && el('card-gallery')) {
            // The panel saved an empty gallery on purpose.
            el('card-gallery').innerHTML = '';
          }

          // Hide known sections whose content ended up empty (e.g. an empty
          // gallery) before the section-order pass can re-show them.
          (function hideEmptySections(){
            var pairs = [
              ['card-about-section', 'card-about', 'about'],
              ['card-countries-section', 'card-countries', 'countries'],
              ['card-contact-section', 'card-contacts', 'contact'],
              ['card-experience-section', 'card-experience', 'experience'],
              ['card-skills-section', 'card-skills', 'skills'],
              ['card-education-section', 'card-education', 'education'],
              ['card-certs-section', 'card-certs', 'certs'],
              ['card-gallery-section', 'card-gallery', 'gallery'],
              ['card-portfolio-section', 'card-portfolio', 'portfolio'],
              ['card-map-section', 'card-map', 'map']
            ];
            pairs.forEach(function(pair){
              var sec = el(pair[0]);
              var box = el(pair[1]);
              if (!sec || !box) return;
              var hasText = (box.textContent || '').trim() !== '';
              var hasMedia = box.querySelector('img, svg, .pill, .flag, .project-link, .list-item') !== null;
              if (hasText || hasMedia) { sec.removeAttribute('data-empty'); return; }
              sec.setAttribute('data-empty', '1');
              sec.style.display = 'none';
              var dv = document.querySelector('[data-section-divider="' + pair[2] + '"]');
              if (dv) dv.style.display = 'none';
            });
            var socialsSec = el('card-socials-section');
            var socialsInline = el('card-socials-inline');
            var socialsTop = el('card-socials');
            var socialsHas = (socialsInline && socialsInline.children.length > 0) || (socialsTop && socialsTop.children.length > 0);
            if (socialsSec) {
              if (socialsHas) socialsSec.removeAttribute('data-empty');
              else {
                socialsSec.setAttribute('data-empty', '1');
                socialsSec.style.display = 'none';
                var sdv = document.querySelector('[data-section-divider="socials"]');
                if (sdv) sdv.style.display = 'none';
              }
            }
          })();

          // Slogan
          if (cd.slogan && cd.slogan.trim()) {
            var sl = el('card-slogan');
            if (sl) { sl.textContent = cd.slogan.trim(); sl.style.display = 'block'; }
          }

          // Resume download section (metadata only, file served by resume.php)
          var rsec = el('card-resume-section');
          var rdiv = document.querySelector('[data-section-divider="resume"]');
          if (cd.resume && cd.resume.file) {
            var rbox = el('card-resume');
            if (rbox) {
              var rname = (cd.resume.name || '').trim() || t('resume');
              var rtitle = (cd.resume.title || '').trim() || rname;
              rbox.innerHTML = '<div class="resume-card"><div class="resume-meta"><span class="resume-name" dir="auto">' + escAttr(rtitle) + '</span><span class="resume-hint" dir="auto" data-i18n="resumeHint">' + escAttr(t('resumeHint')) + '</span></div><a class="resume-download" href="resume.php" download rel="noopener" data-i18n="downloadResume">' + escAttr(t('downloadResume')) + '</a></div>';
            }
            if (rsec) { rsec.setAttribute('data-empty', '0'); rsec.style.display = 'flex'; }
            if (rdiv) rdiv.style.display = 'block';
          } else {
            if (rsec) { rsec.setAttribute('data-empty', '1'); rsec.style.display = 'none'; }
            if (rdiv) rdiv.style.display = 'none';
          }

          // Map / location section (address + map provider links)
          var msec = el('card-map-section');
          var mdiv = document.querySelector('[data-section-divider="map"]');
          var mmap = (cd && cd.map && typeof cd.map === 'object') ? cd.map : null;
          // Combine the split address parts (country/province/city/district/address)
          // into a single display string, keeping backward compat with the
          // legacy single `address` field.
          var combinedAddress = '';
          if (mmap) {
            var addrParts = [];
            if (mmap.country) addrParts.push(String(mmap.country).trim());
            if (mmap.province) addrParts.push(String(mmap.province).trim());
            if (mmap.city) addrParts.push(String(mmap.city).trim());
            if (mmap.district) addrParts.push(String(mmap.district).trim());
            if (mmap.address) addrParts.push(String(mmap.address).trim());
            combinedAddress = addrParts.filter(Boolean).join('، ');
            if (!combinedAddress && mmap.address) combinedAddress = String(mmap.address).trim();
          }
          if (msec) {
            if (mmap && (combinedAddress || mmap.balad || mmap.neshan || mmap.google)) {
              var mhtml = '';
              if (combinedAddress) mhtml += '<div class="map-address" dir="auto">' + escAttr(combinedAddress) + '</div>';
              var mlinks = [];
              if (mmap.balad) mlinks.push('<a class="map-link" href="' + escAttr(String(mmap.balad)) + '" target="_blank" rel="noopener"><span class="map-dot" aria-hidden="true"></span>بلد</a>');
              if (mmap.neshan) mlinks.push('<a class="map-link" href="' + escAttr(String(mmap.neshan)) + '" target="_blank" rel="noopener"><span class="map-dot" aria-hidden="true"></span>نشان</a>');
              if (mmap.google) mlinks.push('<a class="map-link" href="' + escAttr(String(mmap.google)) + '" target="_blank" rel="noopener"><span class="map-dot" aria-hidden="true"></span>Google Maps</a>');
              if (mlinks.length) mhtml += '<div class="map-links">' + mlinks.join('') + '</div>';
              var mbox = el('card-map');
              if (mbox) mbox.innerHTML = mhtml;
              msec.setAttribute('data-empty', '0');
              msec.style.display = 'flex';
              if (mdiv) mdiv.style.display = 'block';
            } else {
              msec.setAttribute('data-empty', '1');
              msec.style.display = 'none';
              if (mdiv) mdiv.style.display = 'none';
            }
          }

          // Reorder sections
          var sections = cd.sections || [];
          var container = document.getElementById('card-sections-container');
          if (container) {
            var fallback = ['countries','about','contact','socials','experience','skills','education','certs','gallery','portfolio','resume','map'];
            /* Custom sections created from the panel have no markup in
               index.html, so build them here from their own title/text. */
            function buildCustomSection(sec){
              var section = document.createElement('section');
              section.className = 'section';
              section.id = 'card-section-' + sec.key;
              section.setAttribute('data-section', sec.key);
              section.setAttribute('dir', 'auto');
              section.style.display = 'flex';

              var header = document.createElement('header');
              header.className = 'section-header';
              var title = document.createElement('h2');
              title.className = 'section-title';
              title.textContent = String(sec.label || '').trim() || t('section');
              header.appendChild(title);
              section.appendChild(header);

              var text = (sec.content || '').trim();
              if (text) {
                var p = document.createElement('p');
                p.className = 'about-text custom-section-text';
                p.textContent = text;
                section.appendChild(p);
              }

              var divider = document.createElement('div');
              divider.className = 'divider';
              divider.setAttribute('data-section-divider', sec.key);
              return { section: section, divider: divider };
            }
            if (sections.length > 0) {
              var map = {};
              Array.prototype.forEach.call(container.querySelectorAll('[data-section]'), function(sec){
                map[sec.getAttribute('data-section')] = sec;
              });
              sections = sections.filter(function(x){ return x && typeof x === 'object'; });
            sections.sort(function(a,b){ return (Number(a.order)||0) - (Number(b.order)||0); });
              sections.forEach(function(sec){
                if (!sec || typeof sec !== 'object') return;
                var key = String(sec.key || '').toLowerCase().replace(/[^a-z0-9_-]/g, '');
                if (!key) return;
                sec.key = key;
                var isCustom = fallback.indexOf(key) === -1;
                var node = map[key];
                var dd = document.querySelector('[data-section-divider="' + key + '"]');

                if (isCustom && !node) {
                  if (!sec.enabled) return;
                  var built = buildCustomSection(sec);
                  map[key] = built.section;
                  container.appendChild(built.section);
                  container.appendChild(built.divider);
                  return;
                }
                if (!sec.enabled) {
                  if (node) node.style.display = 'none';
                  if (dd) dd.style.display = 'none';
                  return;
                }
                if (node) {
                  // A managed list the admin emptied stays hidden even though
                  // its section toggle is on.
                  if (node.getAttribute('data-empty') === '1') {
                    node.style.display = 'none';
                    if (dd) dd.style.display = 'none';
                    return;
                  }
                  node.style.display = 'flex';
                  container.appendChild(node);
                  if (dd) {
                    dd.style.display = 'block';
                    container.appendChild(dd);
                  }
                }
              });
            } else {
              fallback.forEach(function(k){
                var s2 = container.querySelector('[data-section="' + k + '"]');
                if (s2) container.appendChild(s2);
                var d3 = container.querySelector('[data-section-divider="' + k + '"]');
                if (d3) container.appendChild(d3);
              });
            }
          }
        })
        .catch(function(){});
    })
    .catch(function(e){ console.error('card load error', e); });
  // Privacy-friendly view counter: one increment per page load.
  try { fetch('views.php?hit=1', { method: 'POST', keepalive: true }).catch(function () {}); } catch (e) {}
})();
