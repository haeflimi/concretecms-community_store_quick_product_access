<?php

namespace CommunityStoreQuickProductAccess\Sitemap;

use Concrete\Core\Application\Application;
use Concrete\Core\Application\Service\Dashboard\Sitemap;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Page\Page;
use Concrete\Core\Permission\Checker;
use Concrete\Core\Support\Facade\Url;
use stdClass;
use Throwable;

/**
 * Wraps the sitemap node builder: nodes of product pages and category pages (pages products are assigned to)
 * get the quick links into the store dashboard, which js/sitemap-store-menu.js shows in the page context menu.
 * The wrapped builder may be the core service or another package's wrapper; every node it returns, children
 * included, is decorated here.
 */
class StoreSitemap extends Sitemap
{
    /** @var Sitemap */
    protected $inner;

    /** @var array<int, array{pID: int, name: string}>|null page ID => product */
    protected $productsByPage;

    /** @var array<int, int>|null page ID => number of products assigned */
    protected $categoryCounts;

    /** @var bool|null */
    protected $canOpenStore;

    public function __construct(Application $app, Sitemap $inner)
    {
        parent::__construct($app);
        $this->inner = $inner;
    }

    public function getNode($cItem, $includeChildren = true, $onGetNode = null)
    {
        $node = $this->inner->getNode($cItem, $includeChildren, $onGetNode);
        $this->decorateTree($node);

        return $node;
    }

    public function getSubNodes($parent, $onGetNode = null)
    {
        $nodes = $this->inner->getSubNodes($parent, $onGetNode);
        foreach ((array) $nodes as $node) {
            $this->decorateTree($node);
        }

        return $nodes;
    }

    // The builder's settings must reach the wrapped one too

    public function setExpandedNodes($nodes)
    {
        $this->inner->setExpandedNodes($nodes);

        return parent::setExpandedNodes($nodes);
    }

    public function setIncludeSystemPages($systemPages)
    {
        $this->inner->setIncludeSystemPages($systemPages);

        return parent::setIncludeSystemPages($systemPages);
    }

    public function setDisplayNodePagination($paginate)
    {
        $this->inner->setDisplayNodePagination($paginate);

        return parent::setDisplayNodePagination($paginate);
    }

    public function setIsSitemapOverlay($isSitemapOverlay)
    {
        $this->inner->setIsSitemapOverlay($isSitemapOverlay);

        return parent::setIsSitemapOverlay($isSitemapOverlay);
    }

    protected function decorateTree($node): void
    {
        if (!$node instanceof stdClass) {
            return;
        }
        $this->decorate($node);
        foreach ((array) ($node->children ?? []) as $child) {
            $this->decorateTree($child);
        }
    }

    protected function decorate(stdClass $node): void
    {
        if (!empty($node->isTrash) || !empty($node->isInTrash) || empty($node->cID) || !$this->canOpenStore()) {
            return;
        }
        $cID = (int) $node->cID;
        $entries = (array) ($node->csqaMenu ?? []);
        $product = $this->getProductsByPage()[$cID] ?? null;
        if ($product !== null) {
            $entries[] = [
                'url' => (string) Url::to('/dashboard/store/products/edit', $product['pID']),
                'label' => t('Edit product in Store'),
            ];
        }
        $count = $this->getCategoryCounts()[$cID] ?? 0;
        if ($count > 0) {
            $entries[] = [
                'url' => (string) Url::to('/dashboard/store/products/categories/manage', $cID),
                'label' => t2('Manage category in Store (%s product)', 'Manage category in Store (%s products)', $count),
            ];
        }
        if ($entries !== []) {
            $node->csqaMenu = $entries;
        }
    }

    /**
     * @return array<int, array{pID: int, name: string}>
     */
    protected function getProductsByPage(): array
    {
        if ($this->productsByPage === null) {
            $this->productsByPage = [];
            try {
                $rows = $this->db()->fetchAllAssociative('SELECT cID, pID, pName FROM CommunityStoreProducts WHERE cID > 0');
                foreach ($rows as $row) {
                    $this->productsByPage[(int) $row['cID']] = ['pID' => (int) $row['pID'], 'name' => (string) $row['pName']];
                }
            } catch (Throwable $e) {
                // Store tables missing (mid install/uninstall): the sitemap must still work
            }
        }

        return $this->productsByPage;
    }

    /**
     * @return array<int, int>
     */
    protected function getCategoryCounts(): array
    {
        if ($this->categoryCounts === null) {
            $this->categoryCounts = [];
            try {
                $rows = $this->db()->fetchAllAssociative('SELECT cID, COUNT(*) AS n FROM CommunityStoreProductLocations WHERE cID > 0 GROUP BY cID');
                foreach ($rows as $row) {
                    $this->categoryCounts[(int) $row['cID']] = (int) $row['n'];
                }
            } catch (Throwable $e) {
                // see above
            }
        }

        return $this->categoryCounts;
    }

    protected function canOpenStore(): bool
    {
        if ($this->canOpenStore === null) {
            $page = Page::getByPath('/dashboard/store/products');
            $this->canOpenStore = $page && !$page->isError() && (new Checker($page))->canViewPage();
        }

        return $this->canOpenStore;
    }

    protected function db(): Connection
    {
        return $this->app->make(Connection::class);
    }
}
