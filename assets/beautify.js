/* ============================================================================
   beautify.js — Editorial Ink enhancements for the CMS
   1) Replace emoji icons with clean inline SVG (Lucide-style strokes) so the
      admin/user panels and about page look professional and theme-consistent.
   2) Subtle, safe scroll-reveal motion (content is fully visible without JS).
   ========================================================================== */
(function () {
  'use strict';
  var root = document.documentElement;

  /* ---------------------------------------------------------------------------
     1. EMOJI → SVG
     Stroke icons share one look (1.8 stroke, round joins). We match by the
     emoji character so we don't have to touch any PHP markup.
  --------------------------------------------------------------------------- */
  var S = function (paths) {
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" ' +
      'stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" ' +
      'aria-hidden="true">' + paths + '</svg>';
  };

  var ICONS = {
    // dashboard / grid
    '📊': S('<path d="M3 3v18h18"/><rect x="7" y="11" width="3" height="6" rx="1"/><rect x="12" y="7" width="3" height="10" rx="1"/><rect x="17" y="13" width="3" height="4" rx="1"/>'),
    // posts / document
    '📝': S('<path d="M4 4a2 2 0 0 1 2-2h7l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/><path d="M13 2v6h6"/><path d="M8 13h8M8 17h6"/>'),
    // comments
    '💬': S('<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.4A8 8 0 1 1 21 12Z"/>'),
    // users
    '👥': S('<circle cx="9" cy="8" r="3.2"/><path d="M3.5 20a5.5 5.5 0 0 1 11 0"/><path d="M16 5.2a3.2 3.2 0 0 1 0 6.1"/><path d="M17.5 14.4A5.5 5.5 0 0 1 21 20"/>'),
    // single profile
    '👤': S('<circle cx="12" cy="8" r="3.4"/><path d="M5 20a7 7 0 0 1 14 0"/>'),
    // write / pen
    '✍️': S('<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>'),
    '✍': S('<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>'),
    // logout
    '🚪': S('<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/>'),
    // globe / visit site
    '🌐': S('<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a15 15 0 0 1 0 18a15 15 0 0 1 0-18Z"/>'),
    // envelope / subscribers
    '📧': S('<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>'),
    '📨': S('<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>'),
    // download / export
    '📥': S('<path d="M12 3v12"/><path d="m7 11 5 4 5-4"/><path d="M5 21h14"/>'),
    // rocket / goal
    '🚀': S('<path d="M5 15c-1.5 1.5-2 5-2 5s3.5-.5 5-2c.9-.9.9-2.3 0-3.2a2.3 2.3 0 0 0-3 .2Z"/><path d="M9 12a13 13 0 0 1 9-9 13 13 0 0 1-1 10l-4 3Z"/><path d="M12 9a2 2 0 1 0 4 0 2 2 0 0 0-4 0Z"/><path d="M9 12l-3 0 2-4"/><path d="M12 15l0 3 4-2"/>'),
    // check / published
    '✅': S('<circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.5 2.5 4.5-5"/>'),
    // clock / pending
    '🕐': S('<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'),
    // wrench / tools
    '🛠️': S('<path d="M14.5 5.5a3.5 3.5 0 0 0-4.6 4.6L3 17l4 4 6.9-6.9a3.5 3.5 0 0 0 4.6-4.6l-2.4 2.4-2.1-2.1Z"/>'),
    '🛠': S('<path d="M14.5 5.5a3.5 3.5 0 0 0-4.6 4.6L3 17l4 4 6.9-6.9a3.5 3.5 0 0 0 4.6-4.6l-2.4 2.4-2.1-2.1Z"/>'),
    // developer / person coding
    '👨‍💻': S('<circle cx="12" cy="7" r="3.2"/><path d="M6 20a6 6 0 0 1 12 0"/><path d="M9 13l-2 2 2 2M15 13l2 2-2 2"/>'),
    '👩‍💻': S('<circle cx="12" cy="7" r="3.2"/><path d="M6 20a6 6 0 0 1 12 0"/><path d="M9 13l-2 2 2 2M15 13l2 2-2 2"/>'),
    // lightning (hero tag) — keep the bolt but as crisp SVG
    '⚡': S('<path d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z"/>')
  };

  // Build a scanner regex from the emoji keys (longest first for ZWJ sequences).
  var KEYS = Object.keys(ICONS).sort(function (a, b) { return b.length - a.length; });

  function replaceInSpan(span) {
    var raw = (span.textContent || '').trim();
    for (var i = 0; i < KEYS.length; i++) {
      if (raw.indexOf(KEYS[i]) !== -1) {
        span.innerHTML = ICONS[KEYS[i]];
        span.classList.add('has-svg-icon');
        return true;
      }
    }
    return false;
  }

  function swapIcons() {
    // Elements that hold an emoji as their icon in the existing markup.
    var holders = document.querySelectorAll('.icon, .stat-icon, .emoji, .team-icon, .visit-link, .hero-tag');
    holders.forEach(function (el) {
      if (el.classList.contains('has-svg-icon')) return;

      if (el.classList.contains('visit-link') || el.classList.contains('hero-tag')) {
        // These mix an emoji with text — replace just the emoji, keep the words.
        var html = el.innerHTML;
        for (var i = 0; i < KEYS.length; i++) {
          if (html.indexOf(KEYS[i]) !== -1) {
            el.innerHTML = html.replace(KEYS[i], '<span class="ic-inline" style="display:inline-flex;vertical-align:-2px;margin-right:6px">' + ICONS[KEYS[i]] + '</span>');
            break;
          }
        }
        return;
      }
      replaceInSpan(el);
    });

    // Size the inline hero-tag / visit-link glyphs.
    document.querySelectorAll('.ic-inline svg').forEach(function (svg) {
      svg.style.width = '14px'; svg.style.height = '14px';
    });
  }

  /* ---------------------------------------------------------------------------
     2. SCROLL-REVEAL (respects reduced-motion)
  --------------------------------------------------------------------------- */
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  var selectors = [
    '.post-card', '.sidebar-widget', '.auth-card', '.hero-stat',
    '.trending-item', '.stat-card', '.card', '.content-header',
    '.profile-card', '.form-panel', '.panel', '.team-member', '.about-card'
  ];

  function reveal() {
    var targets = document.querySelectorAll(selectors.join(','));
    if (!targets.length) return;
    root.classList.add('reveal-ready');

    var i = 0;
    targets.forEach(function (el) {
      el.classList.add('reveal');
      el.style.transitionDelay = Math.min((i % 6) * 55, 275) + 'ms';
      i++;
    });

    if (!('IntersectionObserver' in window)) {
      targets.forEach(function (el) { el.classList.add('is-visible'); });
      return;
    }
    var io = new IntersectionObserver(function (entries, obs) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) { entry.target.classList.add('is-visible'); obs.unobserve(entry.target); }
      });
    }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
    targets.forEach(function (el) { io.observe(el); });

    setTimeout(function () {
      document.querySelectorAll('.reveal:not(.is-visible)').forEach(function (el) { el.classList.add('is-visible'); });
    }, 1200);
  }

  function ready() {
    try { swapIcons(); } catch (e) { /* icons are enhancement-only */ }
    if (!reduce) reveal();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', ready);
  } else {
    ready();
  }
})();
