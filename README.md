# Community Store Quick Product Access

A small extension for [Community Store](https://github.com/concretecms-community-store/community_store) (2.0 or
later) on Concrete CMS 9: the page context menu of the dashboard sitemap (tree and explore view, and the toolbar's
sitemap panel) gets quick links into the store.

- On a **product page** (the page a store product is linked to): "Edit product in Store" opens the product in
  Store › Products.
- On a **category page** (a page products are assigned to under "Categories and Groups"): "Manage category in
  Store (n products)" opens the category's product list and order.

Pages that are neither, and the trash, keep the normal menu. The entries are only offered to users who may open
Store › Products.

## Installation ##

Put the folder `community_store_quick_product_access` into `packages/` and install it under Dashboard › Extend
Concrete. Community Store must be installed first. Nothing is written to the database; uninstalling removes the
links again.

## How it works ##

Concrete has no hook for the sitemap page menu. The package wraps the core sitemap node builder
(`Concrete\Core\Application\Service\Dashboard\Sitemap`) and adds the entries to the node data of the affected
pages; a small script (`js/sitemap-store-menu.js`, loaded for logged-in users) puts them at the top of the menu
template. Other packages that wrap the builder the same way keep working; the wrappers chain. The flat page search
of the sitemap builds its menu in a core single page that packages can't extend, so it has no entries.

## License ##

MIT, see LICENSE.
