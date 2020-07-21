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

    /**
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $this->vfmk->trackSource();
        $data = (object)$this->vfmk->getInstance()->getStatus();
        $answer = array_sum($data->math);

        return $this->rawFactory->create()
            ->setHttpResponseCode(200)
            ->setHeader('Content-Type', 'application/javascript')
            ->setContents(<<<JS
(function () {
    var foo = document.getElementsByClassName("{$data->script_class}")[0];
    if (foo) {
        foo.className = "";
        foo.innerHTML = '<input type="hidden" name="{$data->sequence}" value="{$data->seq_id}">'
                      + '<input type="hidden" name="{$data->honeypot}" value="{$answer}">';
    }
})();
JS
            );
    }
}