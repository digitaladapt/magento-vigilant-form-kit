<?php

namespace VigilantForm\MagentoKit\Controller\Index;

use Magento\Framework\App\Action\{Action, Context};
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\ResultInterface;
use VigilantForm\MagentoKit\VigilantFormMagentoKit;

class Index extends Action
{
    /** @var RawFactory */
    protected $rawFactory;

    /** @var VigilantFormMagentoKit */
    protected $vfmk;

    /**
     * @param Context $context
     * @param RawFactory $rawFactory
     * @param VigilantFormMagentoKit $vfmk
     */
    public function __construct(Context $context, RawFactory $rawFactory, VigilantFormMagentoKit $vfmk)
    {
        parent::__construct($context);
        $this->rawFactory = $rawFactory;
        $this->vfmk = $vfmk;
    }

    public function execute(): ResultInterface
    {
        return $this->rawFactory->create()
            ->setHttpResponseCode(200)
            ->setHeader('Content-Type', 'application/javascript')
            ->setStatusHeader('') // ->setStatusHeader(statusHeaderCode, [statusHeaderVersion, [statusHeaderPhrase]])
            ->setContents('Hello World');
    }
}