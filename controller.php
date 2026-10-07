<?php

namespace Concrete\Package\CommunityStoreQuickProductAccess;

use CommunityStoreQuickProductAccess\Sitemap\StoreSitemap;
use Concrete\Core\Application\Service\Dashboard\Sitemap as CoreSitemap;
use Concrete\Core\Asset\Asset;
use Concrete\Core\Asset\AssetList;
use Concrete\Core\Package\Package;
use Concrete\Core\Page\View\PageView;
use Concrete\Core\User\User;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Community Store extension: the page context menu of the dashboard sitemap gets quick links into the store for
 * product pages ("Edit product in Store") and category pages ("Manage category in Store").
 */
class Controller extends Package
{
    protected $pkgHandle = 'community_store_quick_product_access';
    protected $appVersionRequired = '9.0.0';
    protected $pkgVersion = '1.0.0';
    protected $packageDependencies = ['community_store' => '2.0'];
    protected $pkgAutoloaderRegistries = [
        'src' => 'CommunityStoreQuickProductAccess',
    ];

    public function getPackageName()
    {
        return t('Community Store Quick Product Access');
    }

    public function getPackageDescription()
    {
        return t('Jump from a product or category page in the sitemap straight to the product or category in the Community Store dashboard.');
    }

    public function on_start()
    {
        // Concrete has no hook for the sitemap page menu: the node builder is wrapped (whatever is bound right now,
        // the core service or another package's wrapper, so several packages can add their own entries), and
        // js/sitemap-store-menu.js puts the entries into the menu template.
        $inner = $this->app->make(CoreSitemap::class);
        $this->app->singleton(CoreSitemap::class, function ($app) use ($inner) {
            return new StoreSitemap($app, $inner);
        });

        AssetList::getInstance()->register(
            'javascript',
            'community_store_quick_product_access/sitemap-store-menu',
            'js/sitemap-store-menu.js',
            ['version' => $this->pkgVersion, 'position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => false],
            $this
        );
        $this->app->make('director')->addListener('on_before_render', function ($event) {
            // Every page a logged-in user sees: the toolbar's sitemap panel is loaded into the page by AJAX
            $view = $event->getArgument('view');
            if ($view instanceof PageView && $this->app->make(User::class)->isRegistered()) {
                $view->requireAsset('javascript', 'community_store_quick_product_access/sitemap-store-menu');
            }
        });
    }
}
