(function () {
    'use strict';

    var root = document.querySelector('[data-help-center]');
    if (!root) {
        return;
    }

    var search = root.querySelector('[data-help-search]');
    var sectionLinks = Array.from(root.querySelectorAll('[data-help-section-link]'));
    var emptyState = root.querySelector('[data-help-search-empty]');
    var mobileTrigger = root.querySelector('[data-help-mobile-trigger]');
    var mobileClose = root.querySelector('[data-help-mobile-close]');
    var mobileBackdrop = root.querySelector('[data-help-mobile-backdrop]');
    var outlineLinks = Array.from(root.querySelectorAll('[data-help-outline-link]'));
    var articleSections = Array.from(root.querySelectorAll('[data-help-article-section]'));

    function setNavigationOpen(open) {
        document.body.classList.toggle('help-navigation-open', open);
        if (mobileTrigger) {
            mobileTrigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
    }

    if (mobileTrigger) {
        mobileTrigger.addEventListener('click', function () {
            setNavigationOpen(!document.body.classList.contains('help-navigation-open'));
        });
    }

    if (mobileClose) {
        mobileClose.addEventListener('click', function () {
            setNavigationOpen(false);
        });
    }

    if (mobileBackdrop) {
        mobileBackdrop.addEventListener('click', function () {
            setNavigationOpen(false);
        });
    }

    sectionLinks.forEach(function (link) {
        link.addEventListener('click', function () {
            setNavigationOpen(false);
        });
    });

    function filterNavigation() {
        if (!search) {
            return;
        }

        var query = search.value.trim().toLocaleLowerCase();
        var visibleCount = 0;

        sectionLinks.forEach(function (link) {
            var searchText = (link.dataset.searchText || '').toLocaleLowerCase();
            var matches = query === '' || searchText.includes(query);
            link.hidden = !matches;
            if (matches) {
                visibleCount += 1;
            }
        });

        if (emptyState) {
            emptyState.hidden = visibleCount !== 0;
        }

        if (query !== '' && window.matchMedia('(max-width: 960px)').matches) {
            setNavigationOpen(true);
        }
    }

    if (search) {
        search.addEventListener('input', filterNavigation);
    }

    document.addEventListener('keydown', function (event) {
        var target = event.target;
        var isTyping = target instanceof HTMLElement && (
            target.matches('input, textarea, select') || target.isContentEditable
        );

        if (event.key === '/' && !isTyping && search) {
            event.preventDefault();
            search.focus();
            return;
        }

        if (event.key === 'Escape') {
            setNavigationOpen(false);
            if (search && document.activeElement === search) {
                search.blur();
            }
        }
    });

    function setActiveOutline(id) {
        outlineLinks.forEach(function (link) {
            link.classList.toggle('is-active', link.dataset.section === id);
        });
    }

    function updateOutline() {
        if (!articleSections.length) {
            return;
        }

        var marker = window.innerHeight * 0.3;
        var current = articleSections[0].id;

        articleSections.forEach(function (section) {
            if (section.getBoundingClientRect().top <= marker) {
                current = section.id;
            }
        });

        if (window.scrollY + window.innerHeight >= document.documentElement.scrollHeight - 8) {
            current = articleSections[articleSections.length - 1].id;
        }

        setActiveOutline(current);
    }

    var ticking = false;
    window.addEventListener('scroll', function () {
        if (ticking) {
            return;
        }

        ticking = true;
        window.requestAnimationFrame(function () {
            updateOutline();
            ticking = false;
        });
    }, { passive: true });

    root.querySelectorAll('a[href^="#"]').forEach(function (link) {
        link.addEventListener('click', function (event) {
            var target = document.querySelector(link.getAttribute('href'));
            if (!target) {
                return;
            }

            event.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            window.history.replaceState(null, '', '#' + target.id);
        });
    });

    updateOutline();
})();
