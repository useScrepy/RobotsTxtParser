<?php
namespace vipnytt\RobotsTxtParser;

use vipnytt\RobotsTxtParser\Exceptions\StatusCodeException;

class StatusCodeParser implements RobotsTxtInterface
{
    /**
     * Valid schemes
     */
    const VALID_SCHEME = [
        'http',
        'https',
    ];

    /**
     * Status code
     * @var int
     */
    protected $code = 200;

    /**
     * Scheme
     * @var string
     */
    protected $scheme;

    /**
     * Applicable
     * @var bool
     */
    protected $applicable;

    /**
     * Replacement coded
     * @var array
     */
    protected $unofficialCodes = [
        522 => 408, // CloudFlare could not negotiate a TCP handshake with the origin server.
        523 => 404, // CloudFlare could not reach the origin server; for example, if the DNS records for the origin server are incorrect.
        524 => 408, // CloudFlare was able to complete a TCP connection to the origin server, but did not receive a timely HTTP response.
    ];

    /**
     * Constructor
     *
     * @param integer|null $code - HTTP status code
     * @param string|null $scheme
     * @throws StatusCodeException
     */
    public function __construct($code, $scheme)
    {
        $this->code = $code;
        $this->scheme = $scheme;
        $this->applicable = $this->isApplicable();
    }

    protected function isApplicable()
    {
        if (!in_array($this->scheme, self::VALID_SCHEME)) {
            return false;
        }
        if (
            $this->code < 100 ||
            $this->code > 599
        ) {
            throw new StatusCodeException('Invalid HTTP(S) status code');
        }
        return true;
    }

    /**
     * Replace an unofficial code
     *
     * @return int|false
     */
    public function replaceUnofficial()
    {
        if (in_array($this->code, array_keys($this->unofficialCodes))) {
            $this->code = $this->unofficialCodes[$this->code];
            return $this->code;
        }
        return false;
    }

    /**
     * Determine the correct group
     *
     * @return string|null
     * @throws Exceptions\ClientException
     */
    public function check()
    {
        if (!$this->applicable) {
            return null;
        }
        switch (floor($this->code / 100) * 100) {
            case 400:
                return self::DIRECTIVE_ALLOW;
            case 500:
                return self::DIRECTIVE_DISALLOW;
        }
        return null;
    }
}