<?php

class StuntCoders_DeletedProductUrl_Model_Observer
{
    const REDIRECT_TYPE = 'RP';

    /**
     * @param $observer
     */
    public function processProductAfterSaveEvent($observer)
    {
        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getEvent()->getProduct();

        $rewrites = Mage::getModel('core/url_rewrite')->getCollection()
            ->addFieldToFilter('id_path', array('like' => $this->_getIdPathPrefix($product) . '%'));

        foreach($rewrites as $rewrite){
            $rewrite->delete();
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function processProductAfterDeleteEvent(Varien_Event_Observer $observer)
    {
        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getEvent()->getProduct();

        $categoryIds = $product->getCategoryIds();

        foreach ($product->getStoreIds() as $storeId) {
            if (empty($categoryIds)) {
                $this->_createRewrite($product, $storeId);
            } else {
                foreach ($categoryIds as $categoryId) {
                    $this->_createRewrite($product, $storeId, $categoryId);
                }
                $this->_createDefaultRewrite($product, $storeId, reset($categoryIds));
            }
        }
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param int $storeId
     * @param null|int $categoryId
     */
    protected function _createDefaultRewrite($product, $storeId, $categoryId = null)
    {
        try {
            $rewrite = $this->_buildRewrite(
                $this->_getIdPath($product),
                $this->_getRequestPath($product, $storeId),
                $this->_getTargetPath($categoryId, $storeId)
            );

            $rewrite->save();
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param int $storeId
     * @param null|int $categoryId
     */
    protected function _createRewrite($product, $storeId, $categoryId = null)
    {
        try {
            $rewrite = $this->_buildRewrite(
                $this->_getIdPath($product, $categoryId),
                $this->_getRequestPath($product, $storeId, $categoryId),
                $this->_getTargetPath($categoryId, $storeId)
            );

            $rewrite->setCategoryId($categoryId);

            $rewrite->save();
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * @param int $idPath
     * @param string $requestPath
     * @param string $targetPath
     * @return Mage_Core_Model_Url_Rewrite
     */
    protected function _buildRewrite($idPath, $requestPath, $targetPath)
    {
        $rewrite = Mage::getModel('core/url_rewrite');

        $rewrite->setIdPath($idPath)
            ->setRequestPath($requestPath)
            ->setTargetPath($targetPath)
            ->setOptions(self::REDIRECT_TYPE)
            ->setIsSystem(0);

        return $rewrite;
    }

    /**
     * @param int $categoryId
     * @param int $storeId
     * @return string
     */
    protected function _getTargetPath($categoryId, $storeId)
    {
        if (empty($categoryId)) {
            return Mage::helper('stuntcoders_deletedproducturl')->getHomePageUrlKey();
        }

        $category = Mage::getModel('catalog/category')->setStoreId($storeId)->load($categoryId);

        return $category->getRequestPath();
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param int $storeId
     * @param int|null $categoryId
     * @return string
     */
    protected function _getRequestPath($product, $storeId, $categoryId = null)
    {
        $idPath = $this->_getOldIdPath($product, $categoryId);

        $rewrite = $product->getUrlModel()->getUrlRewrite();
        $rewrite->setStoreId($storeId)
            ->loadByIdPath($idPath);

        if (!$rewrite->getId()) {
            Mage::throwException('Rewrite no longer exists');
        }

        return $rewrite->getRequestPath();
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param null|int $categoryId
     * @return string
     */
    protected function _getOldIdPath($product, $categoryId = null)
    {
        $idPath = sprintf('product/%d', $product->getId());

        if ($categoryId) {
            $idPath = sprintf('%s/%d', $idPath, $categoryId);
        }

        return $idPath;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param null|int $categoryId
     * @return string
     */
    protected function _getIdPath($product, $categoryId = null)
    {
        $idPath = sprintf('%s-%d', $this->_getIdPathPrefix($product), $product->getId());

        if ($categoryId) {
            $idPath = sprintf('%s-%d', $idPath, $categoryId);
        }

        return $idPath;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @return string
     */
    protected function _getIdPathPrefix($product){
        return 'sc-old-product-' . $product->formatUrlKey($product->getUrlKey());
    }
}
