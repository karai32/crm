(function () {
    'use strict';

    var root = document.querySelector('[data-help-center]');
    if (!root) {
        return;
    }

    var search = root.querySelector('[data-help-search]');
    var sectionLinks = Array.from(root.querySelectorAll('[data-help-section-link]'));
    var searchItems = Array.from(root.querySelectorAll('[data-help-search-item]'));
    var emptyState = root.querySelector('[data-help-search-empty]');
    var mobileTrigger = root.querySelector('[data-help-mobile-trigger]');
    var mobileClose = root.querySelector('[data-help-mobile-close]');
    var mobileBackdrop = root.querySelector('[data-help-mobile-backdrop]');
    var technicalGroup = root.querySelector('[data-help-technical-group]');
    var technicalToggle = root.querySelector('[data-help-technical-toggle]');
    var technicalMenu = root.querySelector('[data-help-technical-menu]');

    function setTechnicalMenuOpen(open) {
        if (!technicalToggle || !technicalMenu) {
            return;
        }
        technicalToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        technicalMenu.hidden = !open;
    }

    if (technicalToggle) {
        technicalToggle.addEventListener('click', function () {
            setTechnicalMenuOpen(technicalToggle.getAttribute('aria-expanded') !== 'true');
        });
    }

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

        searchItems.forEach(function (item) {
            var searchText = (item.dataset.searchText || '').toLocaleLowerCase();
            var matches = query === '' || searchText.includes(query);
            item.hidden = !matches;
            if (matches) {
                visibleCount += 1;
            }
        });

        if (technicalGroup) {
            if (query !== '' && !technicalGroup.hidden) {
                setTechnicalMenuOpen(true);
            } else if (query === '') {
                setTechnicalMenuOpen(technicalGroup.classList.contains('is-active'));
            }
        }

        if (emptyState) {
            emptyState.hidden = visibleCount !== 0;
        }

        if (query !== '' && window.matchMedia('(max-width: 920px)').matches) {
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

})();
