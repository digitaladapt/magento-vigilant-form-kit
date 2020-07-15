<?php

namespace VigilantForm\MagentoKit\Observer;

use Magento\Framework\Event\{Observer, ObserverInterface};
use VigilantForm\Kit\VigilantFormKit;
use VigilantForm\MagentoKit\Bootstrap;

class PageTracking implements ObserverInterface
{
    /** @var Bootstrap */
    protected $bootstrap;

    /**
     * @param Bootstrap $bootstrap
     */
    public function __construct(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
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
            $vfk = $this->bootstrap->create();
            $vfk->trackSource();
            $vfk->generateHoneypot();
        }
    }
}
