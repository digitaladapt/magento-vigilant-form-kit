<?php

namespace VigilantForm\MagentoKit;

use Magento\Customer\Model\Session;
use Magento\Framework\Filesystem\DirectoryList;
use Psr\Log\LoggerInterface;
use RuntimeException;
use VigilantForm\Kit\VigilantFormKit;

class Bootstrap
{
    /** @var VigilantFormKit */
    protected static $kit = null;

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
     * @return VigilantFormKit
     */
    public function create(): VigilantFormKit
    {
        if (!static::$kit) {
            $settings = $this->loadSettings();
            static::$kit = new VigilantFormKit(
                $settings->url,
                $settings->clientId,
                $settings->secret
            );
            static::$kit->setSession($settings->session, $settings->prefix);
            static::$kit->setHoneypot($settings->honeypot, $settings->sequence, $settings->script_src, $settings->script_class, $settings->require_js);
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
            'clientId'     => ['required' => true,  'default' => null],
            'secret'       => ['required' => true,  'default' => null],
            'prefix'       => ['required' => false, 'default' => null],
            'honeypot'     => ['required' => false, 'default' => null],
            'sequence'     => ['required' => false, 'default' => null],
            'script_src'   => ['required' => false, 'default' => 'vf-mk'],
            'script_class' => ['required' => false, 'default' => null],
            'require_js'   => ['required' => false, 'default' => true],
            'website'      => ['required' => false, 'default' => $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost'],
            'form_title'   => ['required' => false, 'default' => 'submit'],
        ];

        foreach ($fields as $key => ['required' => $required, 'default' => $default]) {
            if (!property_exists($settings, $key)) {
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
