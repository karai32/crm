(function () {
    var items = document.querySelectorAll('.help-toc-item');
    if (!items.length || !window.IntersectionObserver) { return; }

    var active = null;

    function setActive(id) {
        if (active === id) { return; }
        active = id;
        items.forEach(function (a) {
            a.classList.toggle('is-active', a.dataset.section === id);
        });
    }

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) { if (e.isIntersecting) { setActive(e.target.id); } });
    }, { rootMargin: '-20% 0px -70% 0px', threshold: 0 });

    document.querySelectorAll('.help-card').forEach(function (card) { observer.observe(card); });

    var lastItem = items[items.length - 1];
    window.addEventListener('scroll', function () {
        if (lastItem && window.innerHeight + window.scrollY >= document.body.scrollHeight - 64) {
            setActive(lastItem.dataset.section);
        }
    }, { passive: true });

    items.forEach(function (a) {
        a.addEventListener('click', function (e) {
            var t = document.getElementById(a.dataset.section);
            if (!t) { return; }
            e.preventDefault();
            t.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
}());
