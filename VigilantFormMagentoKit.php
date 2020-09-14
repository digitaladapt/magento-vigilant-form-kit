<?php

namespace VigilantForm\MagentoKit;

use Magento\Customer\Model\Session;
use Magento\Framework\Filesystem\DirectoryList;
use Psr\Log\LoggerInterface;
use RuntimeException;
use UnexpectedValueException;
use VigilantForm\Kit\VigilantFormKit;

class VigilantFormMagentoKit
{
    public const MULTI_FORM = 'form';
    public const MULTI_CODE = 'code';

    /** @var VigilantFormKit */
    protected static $kit = null;

    /** @var bool */
    protected static $tracked = false;

    /** @var string|null */
    protected static $website = null;

    /** @var string|null default form-title */
    protected static $formTitle = null;

    /** @var DirectoryList */
    protected $directory;

    /** @var Session */
    protected $session;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param DirectoryList $directory
     * @param Session $session
     * @param LoggerInterface $logger
     */
    public function __construct(
        DirectoryList $directory,
        Session $session,
        LoggerInterface $logger
    ) {
        $this->directory = $directory;
        $this->session   = $session;
        $this->logger    = $logger;
    }

    /**
     * Sets various meta data, based on $_SERVER, so we have it when the form is submitted.
     * @see VigilantFormKit::trackSource()
     * @param bool $useReferral Optional, defaults to false, set to true if within non-page (script or image) file.
     */
    public function trackSource(bool $useReferral = false): void
    {
        if (!static::$tracked) {
            $this->getInstance()->trackSource($useReferral);
            static::$tracked = true;
        }
    }

    /**
     * Reusing the html multiple times is allowed, but only on the same page.
     * Pages with multiple forms may experience performance boost by switching
     * * to MULTI_FORM in form and MULTI_CODE at bottom of html.
     * If user has javascript disabled, they will failed the honeypot.
     * Regardless of if they have javascript, they will see nothing.
     * @see VigilantFormKit::generateHoneypot()
     * @param string|null mode Optional, one of static::MULTI_*.
     * @return string Returns chunk of html to insert into a form.
     */
    public function generateHoneypot(string $mode = null): string
    {
        $this->trackSource();
        /* referral is only used to prevent over-caching */
        $refPath = htmlentities(urlencode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));
        $data = (object)$this->getInstance()->getStatus(false);

        switch ($mode) {
            case static::MULTI_FORM:
                return <<<HTML
<div class="{$data->script_class}"></div>
HTML;
            case static::MULTI_CODE:
                return <<<HTML
<script src="{$data->script_src}?multi=true&amp;referral={$refPath}"></script>
HTML;
            default:
                return <<<HTML
<div class="{$data->script_class}"></div>
<script src="{$data->script_src}?referral={$refPath}"></script>
HTML;
        }
    }

    /**
     * @see VigilantFormKit::submitForm()
     * @param array|null $fields Optional, the user submission, defaults to $_POST.
     * @param string|null $form_title Optional, name of the form was submitted, default from config.
     * @param string|null $website Optional, name of the website that the form exists on, default from config.
     * @return bool Returns true on success, will throw an exception otherwise.
     * @throws UnexpectedValueException when attempt to store form is unsuccessful.
     */
    public function submitForm(array $fields = null, string $form_title = null, string $website = null): bool
    {
        $this->trackSource();
        $fields     = $fields     ?? $_POST;
        $form_title = $form_title ?? $this->getFormTitle();
        $website    = $website    ?? $this->getWebsite();
        return $this->getInstance()->submitForm($website, $form_title, $fields);
    }

    /**
     * @return VigilantFormKit
     */
    public function getInstance(): VigilantFormKit
    {
        if (!static::$kit) {
            $settings = $this->loadSettings();
            static::$kit = new VigilantFormKit(
                $settings->url,
                $settings->client_id,
                $settings->secret
            );
            static::$kit->setSession($settings->session, $settings->prefix);
            static::$kit->setHoneypot($settings->honeypot, $settings->sequence, $settings->script_src, $settings->script_class);
            static::$kit->setLogger($this->logger);

            static::$website = $settings->website;
            static::$formTitle = $settings->form_title;
        }

        return static::$kit;
    }

    /**
     * @return string
     */
    public function getWebsite(): string
    {
        return static::$website ?? $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    }

    /**
     * @return string
     */
    public function getFormTitle(): string
    {
        return static::$formTitle ?? 'submit';
    }

    /**
     * @return object
     */
    protected function loadSettings()
    {
        $settings = json_decode(file_get_contents($this->directory->getRoot() . DIRECTORY_SEPARATOR . 'vigilantform.json'));

        /* ensure we loaded a settings object */
        if  (!$settings || !is_object($settings)) {
            throw new RuntimeException("VigilantForm Settings File is missing or invalid json");
        }

        /* ensure the settings object is valid */
        $fields = [
            'url'          => ['required' => true,  'default' => null],
            'client_id'    => ['required' => true,  'default' => null],
            'secret'       => ['required' => true,  'default' => null],
            'prefix'       => ['required' => false, 'default' => null],
            'honeypot'     => ['required' => false, 'default' => null],
            'sequence'     => ['required' => false, 'default' => null],
            'script_src'   => ['required' => false, 'default' => '/vigilant_form/index/index'],
            'script_class' => ['required' => false, 'default' => 'vf-mk'],
            'website'      => ['required' => false, 'default' => $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost'],
            'form_title'   => ['required' => false, 'default' => 'submit'],
        ];

        foreach ($fields as $key => ['required' => $required, 'default' => $default]) {
            if (!isset($settings->$key)) {
                if ($required) {
                    throw new RuntimeException("VigilantForm Settings File is missing required key: '{$key}'");
                } else {
                    $settings->$key = $default;
                }
            }
        }

        /* attach the magento session via the adapter */
        $settings->session = new SessionAdapter($this->session);
        return $settings;
    }
}
