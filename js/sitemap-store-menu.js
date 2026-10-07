/**
 * Dashboard sitemap: quick links into the Community Store dashboard in the page context menu of product and category
 * pages. The entries (node.csqaMenu = [{url, label}]) come from CommunityStoreQuickProductAccess\Sitemap\StoreSitemap;
 * the core menu is an underscore template returned by ConcretePageAjaxSearchMenu.get(), so the entries are put into
 * that template. If the core markup ever changes, they are added after the menu was shown instead.
 */
(function () {
    'use strict';

    var MARKER = '<div class="dropdown-menu">';
    var ENTRY = '<% if (item.csqaMenu && item.csqaMenu.length) { %>'
        + '<% _.each(item.csqaMenu, function (entry) { %>'
        + '<a class="dropdown-item" data-csqa-link="1" href="<%- entry.url %>"><%- entry.label %></a>'
        + '<% }); %>'
        + '<div class="dropdown-divider"></div>'
        + '<% } %>';

    function install() {
        var menu = window.ConcretePageAjaxSearchMenu;
        if (!menu || typeof menu.get !== 'function') {
            return false;
        }
        if (menu.csqaStoreMenu) {
            return true;
        }
        menu.csqaStoreMenu = true;
        var original = menu.get;
        if (String(original.call(menu)).indexOf(MARKER) !== -1) {
            menu.get = function () {
                var template = original.apply(this, arguments);
                if (typeof template === 'string' && template.indexOf('data-csqa-link') === -1) {
                    return template.replace(MARKER, MARKER + ENTRY);
                }

                return template;
            };
        } else if (window.ConcreteEvent && window.jQuery) {
            window.ConcreteEvent.subscribe('ConcreteMenuShow', function (e, data) {
                var node = data && data.menu && data.menu.options ? data.menu.options.data : null;
                if (!node || !node.csqaMenu || !node.csqaMenu.length || !data.menuElement) {
                    return;
                }
                var $dropdown = window.jQuery(data.menuElement).find('.dropdown-menu').first();
                if (!$dropdown.length || $dropdown.find('[data-csqa-link]').length) {
                    return;
                }
                $dropdown.prepend(window.jQuery('<div class="dropdown-divider"></div>'));
                for (var i = node.csqaMenu.length - 1; i >= 0; i--) {
                    $dropdown.prepend(window.jQuery('<a class="dropdown-item" data-csqa-link="1"></a>').attr('href', node.csqaMenu[i].url).text(node.csqaMenu[i].label));
                }
            });
        }

        return true;
    }

    // cms.js (which defines the menu) may be loaded after this script, e.g. in a theme's footer: try again later
    if (!install()) {
        document.addEventListener('DOMContentLoaded', install);
        window.addEventListener('load', install);
    }
})();
