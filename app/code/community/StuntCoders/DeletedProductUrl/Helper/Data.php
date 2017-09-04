<?php

class StuntCoders_DeletedProductUrl_Helper_Data extends Mage_Core_Helper_Abstract
{
    const HOME_PAGE_URL_KEY_PATH = 'web/default/cms_home_page';

    /**
     * @return string
     */
    public function getHomePageUrlKey()
    {
        return Mage::getStoreConfig(self::HOME_PAGE_URL_KEY_PATH);
    }
}
