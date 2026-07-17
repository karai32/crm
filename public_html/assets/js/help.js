(function () {
    var items = Array.from(document.querySelectorAll('.help-toc-item'));
    if (!items.length) {
        return;
    }

    var cards = Array.from(document.querySelectorAll('.help-card[id]'));
    if (!cards.length) {
        return;
    }

    var active = null;

    function setActive(id) {
        if (active === id) {
            return;
        }
        active = id;
        items.forEach(function (a) {
            a.classList.toggle('is-active', a.dataset.section === id);
        });
    }

    function update() {
        // If scrolled to the very bottom — always activate last card
        if (window.scrollY + window.innerHeight >= document.body.scrollHeight - 8) {
            setActive(cards[cards.length - 1].id);
            return;
        }

        // Trigger line = 30% from top of viewport
        var line = window.innerHeight * 0.3;
        var current = cards[0].id;

        for (var i = 0; i < cards.length; i++) {
            if (cards[i].getBoundingClientRect().top <= line) {
                current = cards[i].id;
            } else {
                break;
            }
        }

        setActive(current);
    }

    window.addEventListener('scroll', update, { passive: true });
    update();

    items.forEach(function (a) {
        a.addEventListener('click', function (e) {
            var t = document.getElementById(a.dataset.section);
            if (!t) {
                return;
            }
            e.preventDefault();
            t.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
})();
