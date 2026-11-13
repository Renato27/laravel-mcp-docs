(function () {
    'use strict';

    document.querySelectorAll('[data-tabs]').forEach(function (tabs) {
        // Tab groups nest (client, then operating system, then setup method),
        // so only claim the tabs that belong to this group rather than every
        // one underneath it. The OS selector is driven separately, below, and
        // would otherwise be mistaken for a tab of the client group it sits in.
        var buttons = Array.prototype.slice.call(tabs.querySelectorAll('[role="tab"]'))
            .filter(function (button) {
                return button.closest('[data-tabs]') === tabs && !button.closest('[data-os-tabs]');
            });

        function select(button) {
            buttons.forEach(function (other) {
                var selected = other === button;
                other.setAttribute('aria-selected', selected ? 'true' : 'false');
                other.tabIndex = selected ? 0 : -1;

                var panel = document.getElementById(other.getAttribute('aria-controls'));
                if (panel) { panel.hidden = !selected; }
            });
        }

        buttons.forEach(function (button, index) {
            button.addEventListener('click', function () { select(button); });

            button.addEventListener('keydown', function (event) {
                var delta = event.key === 'ArrowRight' ? 1 : event.key === 'ArrowLeft' ? -1 : 0;
                if (!delta) { return; }

                event.preventDefault();
                var next = buttons[(index + delta + buttons.length) % buttons.length];
                select(next);
                next.focus();
            });
        });
    });

    document.querySelectorAll('[data-copy]').forEach(function (button) {
        button.addEventListener('click', function () {
            var code = button.parentNode.querySelector('code');
            if (!code || !navigator.clipboard) { return; }

            navigator.clipboard.writeText(code.textContent).then(function () {
                var original = button.textContent;
                button.textContent = 'Copied';
                setTimeout(function () { button.textContent = original; }, 1500);
            });
        });
    });
}());

(function () {
    'use strict';

    // Walkthroughs that differ between platforms render one tab per platform.
    // The reader picks a system once and every walkthrough on the page follows,
    // rather than making them choose again under each client.
    var KEY = 'mcp-docs-os';
    var groups = Array.prototype.slice.call(document.querySelectorAll('[data-os-tabs]'));

    if (!groups.length) {
        return;
    }

    function detect() {
        var platform = (navigator.userAgentData && navigator.userAgentData.platform) || navigator.platform || '';
        var hint = (platform + ' ' + navigator.userAgent).toLowerCase();

        // Android reports Linux too, and both belong on the Linux tab here.
        if (hint.indexOf('win') !== -1) { return 'windows'; }
        if (hint.indexOf('mac') !== -1 || hint.indexOf('iphone') !== -1 || hint.indexOf('ipad') !== -1) { return 'macos'; }
        if (hint.indexOf('linux') !== -1 || hint.indexOf('android') !== -1) { return 'linux'; }

        return null;
    }

    function values(button) {
        return (button.getAttribute('data-os-values') || '').split(' ');
    }

    function buttons(group) {
        return Array.prototype.slice.call(group.querySelectorAll('[role="tab"]'));
    }

    var current = null;

    function apply(os) {
        current = os;

        groups.forEach(function (group) {
            var tabs = buttons(group);
            var match = null;

            tabs.forEach(function (tab) {
                if (!match && values(tab).indexOf(os) !== -1) { match = tab; }
            });

            // Every group covers all three platforms, so a miss means markup
            // this script does not understand; leave it as rendered.
            if (!match) { return; }

            tabs.forEach(function (tab) {
                var selected = tab === match;
                tab.setAttribute('aria-selected', selected ? 'true' : 'false');
                tab.tabIndex = selected ? 0 : -1;

                var panel = document.getElementById(tab.getAttribute('aria-controls'));
                if (panel) { panel.hidden = !selected; }
            });
        });
    }

    function choose(button) {
        var choices = values(button);

        // A tab covering several platforms keeps the one already chosen, so
        // clicking "macOS & Linux" as a Linux reader does not switch them.
        var os = choices.indexOf(current) !== -1 ? current : choices[0];

        try { localStorage.setItem(KEY, os); } catch (e) {
            // Private browsing and blocked site data both throw; the choice
            // still applies for this page view.
        }

        apply(os);
    }

    groups.forEach(function (group) {
        buttons(group).forEach(function (button, index) {
            button.addEventListener('click', function () { choose(button); });

            button.addEventListener('keydown', function (event) {
                var delta = event.key === 'ArrowRight' ? 1 : event.key === 'ArrowLeft' ? -1 : 0;
                if (!delta) { return; }

                event.preventDefault();
                var tabs = buttons(group);
                var next = tabs[(index + delta + tabs.length) % tabs.length];

                choose(next);
                next.focus();
            });
        });
    });

    var stored = null;
    try { stored = localStorage.getItem(KEY); } catch (e) { /* see above */ }

    apply(['macos', 'windows', 'linux'].indexOf(stored) !== -1 ? stored : (detect() || 'macos'));
}());

(function () {
    'use strict';

    var root = document.documentElement;
    var KEY = 'mcp-docs-theme';
    var buttons = Array.prototype.slice.call(document.querySelectorAll('[data-theme-choice]'));

    // With the switcher disabled the page is locked to its configured theme, so
    // a choice stored from an earlier visit must not be reapplied.
    if (!buttons.length) {
        return;
    }

    function apply(choice) {
        // "system" means: remove the override and let prefers-color-scheme decide.
        if (choice === 'system') {
            root.removeAttribute('data-theme');
        } else {
            root.setAttribute('data-theme', choice);
        }

        buttons.forEach(function (button) {
            button.setAttribute('aria-pressed', button.dataset.themeChoice === choice ? 'true' : 'false');
        });
    }

    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            var choice = button.dataset.themeChoice;

            try {
                choice === 'system' ? localStorage.removeItem(KEY) : localStorage.setItem(KEY, choice);
            } catch (e) {
                // Private browsing and blocked site data both throw; the choice
                // still applies for this page view.
            }

            apply(choice);
        });
    });

    var stored = null;
    try { stored = localStorage.getItem(KEY); } catch (e) { /* see above */ }

    apply(stored === 'dark' || stored === 'light' ? stored : 'system');
}());

(function () {
    'use strict';

    var root = document.documentElement;
    var SIDEBAR_KEY = 'mcp-docs-sidebar';
    var GROUPS_KEY = 'mcp-docs-collapsed-groups';

    function read(key, fallback) {
        try {
            var raw = localStorage.getItem(key);
            return raw === null ? fallback : raw;
        } catch (e) {
            return fallback;
        }
    }

    function write(key, value) {
        try {
            value === null ? localStorage.removeItem(key) : localStorage.setItem(key, value);
        } catch (e) {
            // Private browsing and blocked site data both throw; the change
            // still applies for this page view.
        }
    }

    // Whole sidebar ------------------------------------------------------

    var sidebarToggles = Array.prototype.slice.call(document.querySelectorAll('[data-sidebar-toggle]'));

    function applySidebar(collapsed) {
        collapsed
            ? root.setAttribute('data-sidebar', 'collapsed')
            : root.removeAttribute('data-sidebar');

        sidebarToggles.forEach(function (button) {
            button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');

            var label = collapsed ? 'Expand navigation' : 'Collapse navigation';
            button.setAttribute('aria-label', label);
            button.setAttribute('title', label);
        });
    }

    sidebarToggles.forEach(function (button) {
        button.addEventListener('click', function () {
            var collapsed = root.getAttribute('data-sidebar') !== 'collapsed';

            write(SIDEBAR_KEY, collapsed ? 'collapsed' : null);
            applySidebar(collapsed);
        });
    });

    if (sidebarToggles.length) {
        applySidebar(read(SIDEBAR_KEY, null) === 'collapsed');
    }

    // Per-server groups --------------------------------------------------

    var collapsed = {};

    try {
        collapsed = JSON.parse(read(GROUPS_KEY, '{}')) || {};
    } catch (e) {
        collapsed = {};
    }

    Array.prototype.slice.call(document.querySelectorAll('[data-group-toggle]')).forEach(function (button) {
        var panel = document.getElementById(button.getAttribute('aria-controls'));
        if (!panel) { return; }

        function apply(isCollapsed) {
            panel.hidden = isCollapsed;
            button.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
        }

        button.addEventListener('click', function () {
            var next = button.getAttribute('aria-expanded') === 'true';

            // Both states are recorded rather than deleting on expand: with the
            // cards configured closed, an absent key means closed, not open.
            collapsed[panel.id] = next;

            write(GROUPS_KEY, JSON.stringify(collapsed));
            apply(next);
        });

        // The rendered state carries the configured default; a stored choice wins.
        apply(collapsed[panel.id] === undefined ? panel.hidden : collapsed[panel.id] === true);
    });
}());

(function () {
    'use strict';

    document.querySelectorAll('[data-prose-toggle]').forEach(function (button) {
        var block = button.closest('[data-collapsible]');
        if (!block) { return; }

        var label = button.querySelector('.prose-more-label');

        button.addEventListener('click', function () {
            var open = !block.classList.contains('is-open');

            block.classList.toggle('is-open', open);
            button.setAttribute('aria-expanded', open ? 'true' : 'false');

            if (label) { label.textContent = open ? 'Show less' : 'Show all'; }

            // Collapsing from far down the block would leave the reader
            // stranded below it.
            if (!open) {
                var top = block.getBoundingClientRect().top;
                if (top < 0) { block.scrollIntoView({ block: 'nearest' }); }
            }
        });
    });
}());
