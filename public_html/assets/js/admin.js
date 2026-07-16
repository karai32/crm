(function () {
    function debounce(callback, delay) {
        var timer = null;

        return function () {
            var args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () {
                callback.apply(null, args);
            }, delay);
        };
    }

    function selectedValues(container) {
        var values = new Set();

        container.querySelectorAll('input[type="checkbox"]:checked').forEach(function (checkbox) {
            values.add(checkbox.value);
        });

        return values;
    }

    function renderCheckboxes(container, items, selected) {
        var name = container.dataset.checkboxName;
        var visibleIds = new Set();
        container.innerHTML = '';

        if (!items.length) {
            container.innerHTML = '<p>No results found.</p>';
        } else {
            items.forEach(function (item) {
                var label = document.createElement('label');
                var checkbox = document.createElement('input');

                visibleIds.add(String(item.id));

                checkbox.type = 'checkbox';
                checkbox.name = name;
                checkbox.value = item.id;
                checkbox.checked = selected.has(String(item.id));

                label.appendChild(checkbox);
                label.appendChild(document.createTextNode(' ' + item.name));
                container.appendChild(label);
            });
        }

        selected.forEach(function (value) {
            if (visibleIds.has(value)) {
                return;
            }

            var hidden = document.createElement('input');

            hidden.type = 'hidden';
            hidden.name = name;
            hidden.value = value;
            container.appendChild(hidden);
        });
    }

    document.querySelectorAll('[data-endpoint][data-target]').forEach(function (input) {
        if (input.dataset.settingsSearch) {
            return;
        }

        var container = document.getElementById(input.dataset.target);

        if (!container) {
            return;
        }

        var selected = selectedValues(container);

        container.addEventListener('change', function (event) {
            if (event.target.type !== 'checkbox') {
                return;
            }

            if (event.target.checked) {
                selected.add(event.target.value);
            } else {
                selected.delete(event.target.value);
            }
        });

        input.addEventListener('input', debounce(function () {
            var url = input.dataset.endpoint + '?q=' + encodeURIComponent(input.value);

            fetch(url, {
                headers: {
                    'Accept': 'application/json'
                }
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Search failed');
                    }

                    return response.json();
                })
                .then(function (data) {
                    renderCheckboxes(container, data.items || [], selected);
                })
                .catch(function () {
                    container.innerHTML = '<p>Search is temporarily unavailable.</p>';
                });
        }, 250));
    });

})();

// Global topbar search
(function () {
    function debounceGlobalSearch(callback, delay) {
        var timer = null;

        return function () {
            var args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () {
                callback.apply(null, args);
            }, delay);
        };
    }

    function iconSvg(type) {
        if (type === 'client') {
            return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z"/></svg>';
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>';
    }

    function renderResults(dropdown, items) {
        dropdown.innerHTML = '';

        if (!items.length) {
            var empty = document.createElement('div');
            empty.className = 'topbar-search-empty';
            empty.textContent = (window.I18N && window.I18N.no_results) || 'No results found.';
            dropdown.appendChild(empty);
            dropdown.classList.add('open');
            return;
        }

        items.forEach(function (item) {
            var link = document.createElement('a');
            var icon = document.createElement('span');
            var body = document.createElement('span');
            var title = document.createElement('span');
            var meta = document.createElement('span');

            link.className = 'topbar-search-option';
            link.href = item.url;

            icon.className = 'topbar-search-option-icon ' + item.type;
            icon.innerHTML = iconSvg(item.type);

            body.className = 'topbar-search-option-body';
            title.className = 'topbar-search-option-title';
            title.textContent = item.name || '';
            meta.className = 'topbar-search-option-meta';
            var i18n = window.I18N || {};
            meta.textContent = item.type === 'client' ? (i18n.client || 'Client') : (i18n.contact || 'Contact');

            if (item.meta) {
                meta.textContent += ' - ' + item.meta;
            }

            body.appendChild(title);
            body.appendChild(meta);
            link.appendChild(icon);
            link.appendChild(body);
            dropdown.appendChild(link);
        });

        dropdown.classList.add('open');
    }

    document.querySelectorAll('[data-global-search]').forEach(function (wrap) {
        var input = wrap.querySelector('[data-global-search-input]');
        var dropdown = wrap.querySelector('[data-global-search-results]');

        if (!input || !dropdown) {
            return;
        }

        var search = debounceGlobalSearch(function () {
            var q = input.value.trim();

            if (q.length < 2) {
                dropdown.classList.remove('open');
                dropdown.innerHTML = '';
                return;
            }

            fetch(input.dataset.endpoint + '?q=' + encodeURIComponent(q), {
                headers: { Accept: 'application/json' }
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Search failed');
                    }

                    return response.json();
                })
                .then(function (data) {
                    renderResults(dropdown, data.items || []);
                })
                .catch(function () {
                    dropdown.innerHTML = '<div class="topbar-search-empty">Search is temporarily unavailable.</div>';
                    dropdown.classList.add('open');
                });
        }, 220);

        input.addEventListener('input', search);
        input.addEventListener('focus', search);
        input.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                dropdown.classList.remove('open');
                input.blur();
            }
        });

        dropdown.addEventListener('mousedown', function (event) {
            var link = event.target.closest('a');

            if (link) {
                window.location.href = link.href;
            }
        });

        document.addEventListener('click', function (event) {
            if (!wrap.contains(event.target)) {
                dropdown.classList.remove('open');
            }
        });
    });
})();

// Sector icon picker
(function () {
    function debounce(callback, delay) {
        var timer = null;

        return function () {
            var args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () {
                callback.apply(null, args);
            }, delay);
        };
    }

    function labelFor(name) {
        return name ? name.replace(/-/g, ' ') : 'Default icon';
    }

    function selectIcon(picker, name) {
        var value = picker.querySelector('[data-sector-icon-value]');
        var preview = picker.querySelector('[data-sector-icon-preview]');
        var label = picker.querySelector('[data-sector-icon-label]');
        var emptyIcon = picker.dataset.emptyIcon || 'crosshair';
        var iconName = name || '';
        var previewName = iconName || emptyIcon;

        value.value = iconName;
        preview.innerHTML = '';

        var icon = document.createElement('i');
        icon.className = 'ph ph-' + previewName;
        preview.appendChild(icon);

        label.textContent = labelFor(iconName);

        picker.querySelectorAll('[data-icon-name]').forEach(function (button) {
            button.classList.toggle('is-selected', button.dataset.iconName === iconName);
        });
    }

    function renderIconResults(picker, items) {
        var results = picker.querySelector('[data-sector-icon-results]');
        var selected = picker.querySelector('[data-sector-icon-value]').value;

        results.innerHTML = '';

        if (!items.length) {
            var empty = document.createElement('div');
            empty.className = 'sector-icon-empty';
            empty.textContent = (window.I18N && window.I18N.no_results) || 'No icons found.';
            results.appendChild(empty);
            return;
        }

        items.forEach(function (item) {
            var button = document.createElement('button');
            var icon = document.createElement('i');
            var label = document.createElement('span');

            button.className = 'sector-icon-option';
            button.type = 'button';
            button.dataset.iconName = item.name;
            button.classList.toggle('is-selected', item.name === selected);

            icon.className = 'ph ph-' + item.name;
            label.textContent = item.name;

            button.appendChild(icon);
            button.appendChild(label);
            results.appendChild(button);
        });
    }

    document.querySelectorAll('[data-sector-icon-picker]').forEach(function (picker) {
        var search = picker.querySelector('[data-sector-icon-search]');
        var clear = picker.querySelector('[data-sector-icon-clear]');
        var results = picker.querySelector('[data-sector-icon-results]');
        var endpoint = picker.dataset.searchEndpoint;

        results.addEventListener('click', function (event) {
            var button = event.target.closest('[data-icon-name]');

            if (button) {
                selectIcon(picker, button.dataset.iconName);
            }
        });

        clear.addEventListener('click', function () {
            selectIcon(picker, '');
        });

        search.addEventListener('input', debounce(function () {
            var query = search.value.trim();

            fetch(endpoint + '?q=' + encodeURIComponent(query), {
                headers: { Accept: 'application/json' }
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Icon search failed');
                    }

                    return response.json();
                })
                .then(function (data) {
                    renderIconResults(picker, data.items || []);
                })
                .catch(function () {
                    renderIconResults(picker, []);
                });
        }, 180));

        selectIcon(picker, picker.querySelector('[data-sector-icon-value]').value);
    });
})();

// Settings live search for sectors and tags
(function () {
    function debounceSettingsSearch(callback, delay) {
        var timer = null;

        return function () {
            var args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () {
                callback.apply(null, args);
            }, delay);
        };
    }

    function textCell(text, className) {
        var td = document.createElement('td');

        if (className) {
            td.className = className;
        }

        td.textContent = text || '-';
        return td;
    }

    function actionCell(item, input, deleteMessage) {
        var td = document.createElement('td');
        var wrap = document.createElement('div');
        var edit = document.createElement('a');
        var deleteForm = document.createElement('form');
        var csrfInput = document.createElement('input');
        var idInput = document.createElement('input');
        var del = document.createElement('button');

        wrap.className = 'action-links';

        var i18n = window.I18N || {};

        edit.className = 'action-edit';
        edit.href = input.dataset.editUrl + item.id;
        edit.textContent = i18n.edit || 'Edit';

        deleteForm.className = 'action-form';
        deleteForm.method = 'post';
        deleteForm.action = input.dataset.deleteUrl;
        csrfInput.type = 'hidden';
        csrfInput.name = '_csrf_token';
        csrfInput.value = input.dataset.csrfToken;
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = item.id;
        del.type = 'submit';
        del.className = 'action-delete';
        del.textContent = i18n.delete || 'Delete';
        del.addEventListener('click', function (event) {
            if (!confirm(deleteMessage)) {
                event.preventDefault();
            }
        });

        wrap.appendChild(edit);
        deleteForm.appendChild(csrfInput);
        deleteForm.appendChild(idInput);
        deleteForm.appendChild(del);
        wrap.appendChild(deleteForm);
        td.appendChild(wrap);

        return td;
    }

    function renderSectorRow(item, input) {
        var tr = document.createElement('tr');
        var nameTd = document.createElement('td');
        var nameLink = document.createElement('a');
        var iconWrap = document.createElement('span');
        var icon = document.createElement('i');
        var nameText = document.createElement('span');
        var statusTd = document.createElement('td');
        var badge = document.createElement('span');

        nameTd.className = 'col-name';
        nameLink.className = 'col-row-link sector-name-cell';
        nameLink.href = input.dataset.editUrl + item.id;

        iconWrap.className = 'sector-list-icon';
        icon.className = 'ph ph-' + (item.icon || 'crosshair');
        nameText.textContent = item.name || '-';

        iconWrap.appendChild(icon);
        nameLink.appendChild(iconWrap);
        nameLink.appendChild(nameText);
        nameTd.appendChild(nameLink);

        var i18n = window.I18N || {};
        badge.className = Number(item.is_active) === 1 ? 'badge-active' : 'badge-inactive';
        badge.textContent = Number(item.is_active) === 1 ? (i18n.active || 'Active') : (i18n.inactive || 'Inactive');
        statusTd.appendChild(badge);

        tr.appendChild(nameTd);
        tr.appendChild(textCell(item.slug, 'col-slug'));
        tr.appendChild(statusTd);
        tr.appendChild(actionCell(item, input, i18n.sector_delete_confirm || 'Delete this sector? If it is used by clients, it will be deactivated.'));

        return tr;
    }

    function renderTagRow(item, input) {
        var tr = document.createElement('tr');
        var nameTd = document.createElement('td');
        var colorTd = document.createElement('td');

        nameTd.className = 'col-name';

        if (item.color) {
            var pill = document.createElement('span');
            pill.className = 'tag-badge';
            pill.style.background = item.color + '22';
            pill.style.borderColor = item.color + '44';
            pill.style.color = item.color;
            pill.textContent = item.name;
            nameTd.appendChild(pill);
        } else {
            nameTd.textContent = item.name || '-';
        }

        if (item.color) {
            var colorWrap = document.createElement('span');
            var dot = document.createElement('span');
            var code = document.createElement('code');

            colorWrap.className = 'settings-color-value';
            dot.className = 'color-dot';
            dot.style.background = item.color;
            code.textContent = item.color;

            colorWrap.appendChild(dot);
            colorWrap.appendChild(code);
            colorTd.appendChild(colorWrap);
        } else {
            colorTd.textContent = '-';
        }

        var i18n = window.I18N || {};
        tr.appendChild(nameTd);
        tr.appendChild(textCell(item.slug, 'col-slug'));
        tr.appendChild(colorTd);
        tr.appendChild(actionCell(item, input, i18n.tag_delete_confirm || 'Delete this tag? Existing contact and client links will be removed.'));

        return tr;
    }

    function renderSettingsRows(input, target, items) {
        var type = input.dataset.settingsSearch;
        target.innerHTML = '';

        if (!items.length) {
            var emptyTr = document.createElement('tr');
            var emptyTd = document.createElement('td');

            emptyTd.colSpan = 4;
            emptyTd.className = 'settings-search-empty';
            emptyTd.textContent = (window.I18N && window.I18N.no_results) || 'No results found.';
            emptyTr.appendChild(emptyTd);
            target.appendChild(emptyTr);
            return;
        }

        items.forEach(function (item) {
            target.appendChild(type === 'tags'
                ? renderTagRow(item, input)
                : renderSectorRow(item, input));
        });
    }

    document.querySelectorAll('[data-settings-search]').forEach(function (input) {
        var target = document.getElementById(input.dataset.searchTarget);

        if (!target) {
            return;
        }

        var initialRows = target.innerHTML;
        var search = debounceSettingsSearch(function () {
            if (input.value.trim() === '') {
                target.innerHTML = initialRows;
                return;
            }

            fetch(input.dataset.searchEndpoint + '?q=' + encodeURIComponent(input.value), {
                headers: { Accept: 'application/json' }
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Search failed');
                    }

                    return response.json();
                })
                .then(function (data) {
                    renderSettingsRows(input, target, data.items || []);
                })
                .catch(function () {
                    renderSettingsRows(input, target, []);
                });
        }, 200);

        input.addEventListener('input', search);
    });
})();

// Token Picker - AJAX search with chip tokens
(function () {
    function debounceTP(fn, ms) {
        var t;
        return function () { var a = arguments; clearTimeout(t); t = setTimeout(function () { fn.apply(null, a); }, ms); };
    }

    function TokenPicker(el) {
        var self = this;
        self.el = el;
        self.endpoint = el.dataset.endpoint;
        self.inputName = el.dataset.name;
        self.withColor = el.dataset.withColor === '1';
        self.placeholder = el.dataset.placeholder || 'Search...';
        self.max = parseInt(el.dataset.max, 10) || 0;
        self.paginate = el.dataset.paginate === '1';
        self.currentQuery = '';
        self.currentPage = 1;
        self.hasMore = false;
        self.loadingMore = false;
        self.selected = [];
        self.staticOptions = null;
        try { var so = JSON.parse(el.dataset.options || 'null'); if (Array.isArray(so)) self.staticOptions = so; } catch (e) { }

        try { var p = JSON.parse(el.dataset.selected || '[]'); if (Array.isArray(p)) self.selected = p; } catch (e) { }

        // Build DOM: [field box] [dropdown] [tokens below]
        el.innerHTML = '';

        self.fieldEl = document.createElement('div');
        self.fieldEl.className = 'token-picker-field';

        self.inputEl = document.createElement('input');
        self.inputEl.type = 'text';
        self.inputEl.className = 'token-picker-input';
        self.inputEl.setAttribute('autocomplete', 'new-password');
        self.inputEl.setAttribute('spellcheck', 'false');

        self.dropEl = document.createElement('div');
        self.dropEl.className = 'token-picker-dropdown';

        self.tokensEl = document.createElement('div');
        self.tokensEl.className = 'token-picker-tokens';

        self.fieldEl.appendChild(self.inputEl);
        self.fieldEl.appendChild(self.dropEl);

        el.appendChild(self.fieldEl);
        el.appendChild(self.tokensEl);

        self.render();
        self.bind();
    }

    TokenPicker.prototype.isSelected = function (id) {
        return this.selected.some(function (s) { return String(s.id) === String(id); });
    };

    TokenPicker.prototype.render = function () {
        var self = this;
        // Chips
        self.tokensEl.innerHTML = '';
        self.selected.forEach(function (item) {
            var chip = document.createElement('span');
            chip.className = 'token-chip';
            if (self.withColor && item.color) {
                chip.style.cssText = 'background:' + item.color + '22;border-color:' + item.color + '55;color:' + item.color;
            }
            var txt = document.createElement('span');
            txt.textContent = item.name;
            var rm = document.createElement('button');
            rm.type = 'button';
            rm.className = 'token-chip-remove';
            rm.innerHTML = '&times;';
            rm.dataset.id = item.id;
            chip.appendChild(txt);
            chip.appendChild(rm);
            self.tokensEl.appendChild(chip);
        });

        // Hidden inputs
        self.el.querySelectorAll('input[data-tp]').forEach(function (n) { n.remove(); });
        self.selected.forEach(function (item) {
            var h = document.createElement('input');
            h.type = 'hidden';
            h.name = self.inputName;
            h.value = item.id;
            h.dataset.tp = '1';
            self.el.appendChild(h);
        });

        // Placeholder
        self.inputEl.placeholder = self.selected.length ? '' : self.placeholder;
    };

    TokenPicker.prototype.add = function (item) {
        if (this.max === 1) {
            this.selected = [];
        }
        if (!this.isSelected(item.id)) {
            this.selected.push(item);
            this.render();
        }
    };

    TokenPicker.prototype.remove = function (id) {
        this.selected = this.selected.filter(function (s) { return String(s.id) !== String(id); });
        this.render();
    };

    TokenPicker.prototype.buildDropItem = function (item) {
        var self = this;
        var opt = document.createElement('div');
        opt.className = 'token-picker-option' + (self.isSelected(item.id) ? ' token-picker-option--selected' : '');
        if (self.withColor && item.color) {
            var dot = document.createElement('span');
            dot.className = 'token-picker-option-dot';
            dot.style.background = item.color;
            opt.appendChild(dot);
        }
        var lbl = document.createElement('span');
        lbl.textContent = item.name;
        opt.appendChild(lbl);
        opt.dataset.id = item.id;
        opt.dataset.name = item.name;
        opt.dataset.color = item.color || '';
        return opt;
    };

    TokenPicker.prototype.appendDropItems = function (items) {
        var self = this;
        items.forEach(function (item) {
            self.dropEl.appendChild(self.buildDropItem(item));
        });
    };

    TokenPicker.prototype.openDrop = function (items) {
        var self = this;
        self.dropEl.innerHTML = '';
        if (!items.length) {
            var empty = document.createElement('div');
            empty.className = 'token-picker-empty';
            empty.textContent = (window.I18N && window.I18N.no_results) || 'No results found.';
            self.dropEl.appendChild(empty);
        } else {
            self.appendDropItems(items);
        }
        self.dropEl.classList.add('open');
    };

    TokenPicker.prototype.closeDrop = function () {
        this.dropEl.classList.remove('open');
        this.dropEl.innerHTML = '';
    };

    TokenPicker.prototype.search = function (q) {
        var self = this;

        // Static options mode — filter locally, no AJAX
        if (self.staticOptions !== null) {
            var lq = q.toLowerCase().trim();
            var results = lq === ''
                ? self.staticOptions.slice()
                : self.staticOptions.filter(function (item) {
                    return item.name.toLowerCase().indexOf(lq) !== -1;
                });
            self.currentQuery = q;
            self.hasMore = false;
            self.openDrop(results);
            return;
        }

        if (!self.endpoint) { return; }

        self.currentQuery = q;
        self.currentPage = 1;
        self.hasMore = false;
        self.loadingMore = false;
        var sep = self.endpoint.indexOf('?') !== -1 ? '&' : '?';
        var url = self.endpoint + sep + 'q=' + encodeURIComponent(q);
        if (self.paginate) { url += '&page=1'; }
        fetch(url, { headers: { Accept: 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
            .then(function (d) {
                if (self.paginate) { self.hasMore = !!d.has_more; }
                self.openDrop(d.items || []);
            })
            .catch(function () { self.closeDrop(); });
    };

    TokenPicker.prototype.loadMore = function () {
        var self = this;
        if (!self.hasMore || self.loadingMore) { return; }
        self.loadingMore = true;
        self.currentPage++;
        var sep = self.endpoint.indexOf('?') !== -1 ? '&' : '?';
        var url = self.endpoint + sep + 'q=' + encodeURIComponent(self.currentQuery) + '&page=' + self.currentPage;
        fetch(url, { headers: { Accept: 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
            .then(function (d) {
                self.hasMore = !!d.has_more;
                self.loadingMore = false;
                self.appendDropItems(d.items || []);
            })
            .catch(function () { self.loadingMore = false; });
    };

    TokenPicker.prototype.bind = function () {
        var self = this;
        var doSearch = debounceTP(function (q) { self.search(q); }, 200);

        self.inputEl.addEventListener('focus', function () { self.search(self.inputEl.value); });
        self.inputEl.addEventListener('input', function () { doSearch(self.inputEl.value); });

        self.dropEl.addEventListener('mousedown', function (e) {
            // mousedown so we prevent the blur before the click registers
            var opt = e.target.closest('.token-picker-option');
            if (!opt) return;
            e.preventDefault();
            var id = opt.dataset.id;
            if (self.isSelected(id)) {
                self.remove(id);
                opt.classList.remove('token-picker-option--selected');
            } else {
                self.add({ id: id, name: opt.dataset.name, color: opt.dataset.color || null });
                opt.classList.add('token-picker-option--selected');
            }
            self.inputEl.value = '';
            if (self.max === 1) {
                self.closeDrop();
            } else {
                self.inputEl.focus();
                self.search('');
            }
        });

        if (self.paginate) {
            self.dropEl.addEventListener('scroll', function () {
                if (self.dropEl.scrollHeight - self.dropEl.scrollTop <= self.dropEl.clientHeight + 50) {
                    self.loadMore();
                }
            });
        }

        self.tokensEl.addEventListener('click', function (e) {
            var rm = e.target.closest('.token-chip-remove');
            if (rm) self.remove(rm.dataset.id);
        });

        self.fieldEl.addEventListener('click', function () { self.inputEl.focus(); });

        self.inputEl.addEventListener('blur', function () {
            setTimeout(function () { self.closeDrop(); }, 150);
        });

        self.inputEl.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { self.closeDrop(); self.inputEl.blur(); }
            if (e.key === 'Backspace' && self.inputEl.value === '' && self.selected.length) {
                self.remove(self.selected[self.selected.length - 1].id);
            }
        });
    };

    document.querySelectorAll('.token-picker').forEach(function (el) { new TokenPicker(el); });
})();

// Profile dropdown
(function () {
    var btn = document.getElementById('profileBtn');
    var dropdown = document.getElementById('profileDropdown');

    if (!btn || !dropdown) { return; }

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdown.classList.toggle('open');
    });

    document.addEventListener('click', function (e) {
        if (!dropdown.contains(e.target)) {
            dropdown.classList.remove('open');
        }
    });
})();

// Sidebar user actions toggle
(function () {
    var btn = document.getElementById('sidebarUserBtn');
    var actions = document.getElementById('sidebarUserActions');

    if (!btn || !actions) { return; }

    btn.addEventListener('click', function () {
        actions.classList.toggle('open');
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { actions.classList.remove('open'); }
    });
})();

// Burger / mobile sidebar
(function () {
    var burger = document.getElementById('burgerBtn');
    var overlay = document.getElementById('sidebarOverlay');

    if (!burger) { return; }

    function close() { document.body.classList.remove('sidebar-open'); }

    burger.addEventListener('click', function () {
        document.body.classList.toggle('sidebar-open');
    });

    if (overlay) { overlay.addEventListener('click', close); }
})();

// Password toggle
(function () {
    document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
        var input = document.getElementById(button.getAttribute('data-password-toggle'));
        if (!input) { return; }
        button.addEventListener('click', function () {
            var isVisible = input.type === 'text';
            input.type = isVisible ? 'password' : 'text';
            button.setAttribute('aria-pressed', isVisible ? 'false' : 'true');
            button.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
        });
    });
})();
