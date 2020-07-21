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
     */
    public function trackSource(): void
    {
        if (!static::$tracked) {
            $this->getInstance()->trackSource();
            static::$tracked = true;
        }
    }

    /**
     * Call once per html form, reusing the html multiple times will cause problems.
     * If user has javascript disabled, to pass the honeypot, they'll be asked
     * a simple math problem. If they have javascript, they will see nothing.
     * @see VigilantFormKit::generateHoneypot()
     * @return string Returns chunk of html to insert into a form.
     */
    public function generateHoneypot(): string
    {
        $this->trackSource();
        $data = (object)$this->getInstance()->getStatus(false);
        return <<<HTML
<div class="{$data->script_class}"></div>
<script src="{$data->script_src}"></script>
HTML;
    }

    /**
     * @see VigilantFormKit::submitForm()
     * @param array $fields The user submission, such as $_POST.
     * @param string $website Optional, name of the website that the form exists on.
     * @param string $form_title Optional, name of the form was submitted.
     * @return bool Returns true on success, will throw an exception otherwise.
     * @throws UnexpectedValueException when attempt to store form is unsuccessful.
     */
    public function submitForm(array $fields, string $website = null, string $form_title = null): bool
    {
        $this->trackSource();
        $website = $website ?? $this->getWebsite();
        $form_title = $form_title ?? $this->getFormTitle();
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
