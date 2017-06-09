<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCms
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCms\Model\ResourceModel\Page\Indexer\Fulltext\Action;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCore\Model\ResourceModel\Indexer\AbstractIndexer;

/**
 * ElasticSearch category full indexer resource model.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCms
 * @author    Fanny DECLERCK <fadec@smile.fr>
 */
class Full extends AbstractIndexer
{
    /**
     * @var \Magento\Framework\EntityManager\MetadataPool
     */
    private $metadataPool;

    /**
     * Full constructor.
     *
     * @param \Magento\Framework\App\ResourceConnection     $resource     Resource Connection
     * @param \Magento\Store\Model\StoreManagerInterface    $storeManager Store Manager
     * @param \Magento\Framework\EntityManager\MetadataPool $metadataPool Metadata Pool
     */
    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        MetadataPool $metadataPool
    ) {
        $this->metadataPool = $metadataPool;
        parent::__construct($resource, $storeManager);
    }

    /**
     * Load a bulk of cms page data.
     *
     * @param int     $storeId    Store id.
     * @param string  $cmsPageIds Cms page ids filter.
     * @param integer $fromId     Load product with id greater than.
     * @param integer $limit      Number of product to get loaded.
     *
     * @return array
     */
    public function getSearchableCmsPage($storeId, $cmsPageIds = null, $fromId = 0, $limit = 100)
    {
        $select = $this->getConnection()->select()
                       ->from(['p' => $this->getTable('cms_page')]);

        $this->addIsVisibleInStoreFilter($select, $storeId);

        if ($cmsPageIds !== null) {
            $select->where('p.page_id IN (?)', $cmsPageIds);
        }

        $select->where('p.page_id > ?', $fromId)
               ->where('p.is_searchable = ?', true)
               ->limit($limit)
               ->order('p.page_id');

        return $this->connection->fetchAll($select);
    }

    /**
     * Filter the select to append only cms page of current store.
     *
     * @param \Zend_Db_Select $select  Product select to be filtered.
     * @param integer         $storeId Store Id
     *
     * @return \Smile\ElasticsuiteCms\Model\ResourceModel\Page\Indexer\Fulltext\Action\Full Self Reference
     */
    private function addIsVisibleInStoreFilter($select, $storeId)
    {
        $linkField = $this->metadataPool->getMetadata(PageInterface::class)->getLinkField();

        $select->join(
            ['ps' => $this->getTable('cms_page_store')],
            "p.$linkField = ps.$linkField"
        );
        $select->where('ps.store_id IN (?)', [0, $storeId]);

        return $this;
    }
}
