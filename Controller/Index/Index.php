<?php

namespace VigilantForm\MagentoKit\Controller\Index;

use DateTimeImmutable;
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
        $this->vfmk->trackSource(true);
        $data = (object)$this->vfmk->getInstance()->getStatus();
        $answer = array_sum($data->math);
        $now = new DateTimeImmutable();
        $expires = $now->modify('+15 seconds');

        $js = <<<JS
(function () {
    var foo = document.getElementsByClassName("{$data->script_class}")[0];
    if (foo) {
        foo.className = "";
        foo.innerHTML = '<input type="hidden" name="{$data->sequence}" value="{$data->seq_id}">'
                      + '<input type="hidden" name="{$data->honeypot}" value="{$answer}">';
    }
})();
JS;

        if ($this->getRequest()->getParam('multi') === 'true') {
            $js = <<<JS
(function () {
    var foo = document.getElementsByClassName("{$data->script_class}");
    if (foo) {
        var bar;
        for (var i = foo.length - 1; i >= 0; --i) {
            bar = foo[i];
            bar.className = "";
            bar.innerHTML = '<input type="hidden" name="{$data->sequence}" value="{$data->seq_id}">'
                          + '<input type="hidden" name="{$data->honeypot}" value="{$answer}">';
        }
    }
})();
JS;
        }

        return $this->rawFactory->create()
            ->setHttpResponseCode(200)
            ->setHeader('Cache-Control', 'max-age=15, must-revalidate, private') /* browser may cache for 15 seconds */
            ->setHeader('Pragma', null) /* must set, or will default to blocking caching */
            ->setHeader('Date', $now->format(DATE_RFC7231))
            ->setHeader('Expires', $expires->format(DATE_RFC7231)) /* send a consistent caching policy */
            ->setHeader('Content-Type', 'application/javascript')
            ->setContents($js);
    }
}