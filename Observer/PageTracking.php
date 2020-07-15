<?php

namespace VigilantForm\MagentoKit\Observer;

use Magento\Framework\Event\{Observer, ObserverInterface};
use VigilantForm\MagentoKit\VigilantFormMagentoKit;

class PageTracking implements ObserverInterface
{
    /** @var VigilantFormMagentoKit */
    protected $vfmk;

    /**
     * @param VigilantFormMagentoKit $vfmk
     */
    public function __construct(VigilantFormMagentoKit $vfmk)
    {
        $this->vfmk = $vfmk;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /* get the file extension of the uri, will be blank for extensionless filenames, such as directories */
        $extension = pathinfo(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), PATHINFO_EXTENSION);

        /* if extension contains "htm" or blank string (directory) */
        if (stripos($extension, 'htm') !== false || $extension === '') {
            $this->vfmk->trackSource();
        }
    }
}
