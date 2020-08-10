<?php

namespace VigilantForm\MagentoKit;

use Magento\Framework\Session\SessionManagerInterface;

class SessionAdapter
{
    /** @var SessionManagerInterface */
    protected $session;

    public function __construct(SessionManagerInterface $session)
    {
        $this->session = $session;
    }

    public function exists(string $key): bool
    {
        $hasKey = "has{$key}";
        return $this->session->$hasKey();
    }

    public function get(string $key, $default = null)
    {
        $getKey = "get{$key}";
        return $this->session->$getKey() ?? $default;
    }

    public function put(string $key, $value = null): void
    {
        $setKey = "set{$key}";
        $this->session->$setKey($value);
    }
}
