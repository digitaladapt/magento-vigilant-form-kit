<?php

namespace VigilantForm\MagentoKit\Traits;

use VigilantForm\MagentoKit\VigilantFormMagentoKit;

trait TrackPage
{
    /** @var VigilantFormMagentoKit */
    protected $vfmk;

    protected function trackSource(): void
    {
        /* get the file extension of the uri, will be blank for extensionless filenames, such as directories */
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        /* if extension contains "htm" or blank string (directory) */
        if (stripos($extension, 'htm') !== false || $extension === '') {
            /* track page, if request is expected referral, flag it as such */
            $this->vfmk->trackSource(
                strpos($path, 'vigilant_form/index/index') !== false ||
                strpos($path, 'customer/section/load') !== false
            );
        }
    }
}