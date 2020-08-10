<?php

namespace VigilantForm\MagentoKit\Observer;

use Magento\Customer\Model\Session;
use Magento\Framework\Event\{Observer, ObserverInterface};
use Magento\Framework\Filesystem\DirectoryList;
use Psr\Log\LoggerInterface;
use VigilantForm\MagentoKit\Traits\TrackPage;
use VigilantForm\MagentoKit\VigilantFormMagentoKit;

class CustomerTracking implements ObserverInterface
{
    use TrackPage;

    /** @var DirectoryList */
    protected $directory;

    /** @var LoggerInterface */
    protected $logger;

    /** @var VigilantFormMagentoKit */
    protected $vfmk;

    /**
     * @param DirectoryList $directory
     * @param LoggerInterface $logger
     */
    public function __construct(DirectoryList $directory, LoggerInterface $logger)
    {
        $this->directory = $directory;
        $this->logger = $logger;
        $this->vfmk = null;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /* this allows us to work with the freshly created customer session */
        $session = $observer->getData('customer_session');
        if ($session instanceof Session) {
            $this->vfmk = new VigilantFormMagentoKit($this->directory, $session, $this->logger);
            $this->trackSource();
        }
    }
}
