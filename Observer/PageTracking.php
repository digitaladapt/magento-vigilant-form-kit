<?php

namespace VigilantForm\MagentoKit\Observer;

use Magento\Framework\Event\{Observer, ObserverInterface};
use VigilantForm\MagentoKit\Traits\TrackPage;
use VigilantForm\MagentoKit\VigilantFormMagentoKit;

class PageTracking implements ObserverInterface
{
    use TrackPage;

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
        $this->trackSource();
    }
}
