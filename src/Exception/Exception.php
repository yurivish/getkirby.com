<?php

namespace Kirby\Exception;

use Kirby\Cms\App;
use Kirby\Toolkit\I18n;
use Kirby\Toolkit\Str;

/**
 * Exception
 * Thrown for general exceptions and extended by
 * other exception classes
 *
 * @package   Kirby Exception
 * @author    Nico Hoffmann <nico@getkirby.com>
 * @link      http://getkirby.com
 * @copyright Bastian Allgeier
 * @license   MIT
 */
class Exception extends \Exception
{
    /**
     * Data variables that can be used inside the exception message
     *
     * @var array
     */
    protected $data;

    /**
     * HTTP code that corresponds with the exception
     *
     * @var int
     */
    protected $httpCode;

    /**
     * Additional details that are not included in the exception message
     *
     * @var array
     */
    protected $details;

    /**
     * Whether the exception message could be translated into the user's language
     *
     * @var boolean
     */
    protected $isTranslated = true;

    /**
     * Defaults that can be overridden by specific
     * exception classes
     */
    protected static $defaultKey = 'general';
    protected static $defaultFallback = 'An error occurred';
    protected static $defaultData = [];
    protected static $defaultHttpCode = 500;
    protected static $defaultDetails = [];

    /**
     * Prefix for the exception key (e.g. 'error.general')
     *
     * @var string
     */
    private static $prefix = 'error';

    /**
     * Class constructor
     *
     * @param array|string $args Full option array ('key', 'translate', 'fallback',
     *                           'data', 'httpCode', 'details' and 'previous') or
     *                           just the message string
     */
    public function __construct($args = [])
    {
        // set data and httpCode from provided arguments or defaults
        $this->data     = $args['data']     ?? static::$defaultData;
        $this->httpCode = $args['httpCode'] ?? static::$defaultHttpCode;
        $this->details  = $args['details']  ?? static::$defaultDetails;

        // define the Exception key
        $key = self::$prefix . '.' . ($args['key'] ?? static::$defaultKey);

        if (is_string($args) === true) {
            $this->isTranslated = false;
            parent::__construct($args);
        } else {
            // define whether message can/should be translated
            $translate = ($args['translate'] ?? true) === true && class_exists('Kirby\Cms\App') === true;

            // fallback waterfall for message string
            $message = null;

            if ($translate) {
                // 1. translation for provided key in current language
                // 2. translation for provided key in default language
                if (isset($args['key']) === true) {
                    $message = I18n::translate(self::$prefix . '.' . $args['key']);
                    $this->isTranslated = true;
                }
            }

            // 3. provided fallback message
            if ($message === null) {
                $message = $args['fallback'] ?? null;
                $this->isTranslated = false;
            }

            if ($translate) {
                // 4. translation for default key in current language
                // 5. translation for default key in default language
                if ($message === null) {
                    $message = I18n::translate(self::$prefix . '.' . static::$defaultKey);
                    $this->isTranslated = true;
                }
            }

            // 6. default fallback message
            if ($message === null) {
                $message = static::$defaultFallback;
                $this->isTranslated = false;
            }

            // format message with passed data
            $message = Str::template($message, $this->data, '-', '{', '}');

            // handover to Exception parent class constructor
            parent::__construct($message, null, $args['previous'] ?? null);
        }

        // set the Exception code to the key
        $this->code = $key;
    }

    /**
     * Returns the data variables from the message
     *
     * @return array
     */
    final public function getData(): array
    {
        return $this->data;
    }

    /**
     * Returns the additional details that are
     * not included in the message
     *
     * @return array
     */
    final public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * Returns the exception key (error type)
     *
     * @return string
     */
    final public function getKey(): string
    {
        return $this->getCode();
    }

    /**
     * Returns the HTTP code that corresponds
     * with the exception
     *
     * @return array
     */
    final public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    /**
     * Returns whether the exception message could
     * be translated into the user's language
     *
     * @return boolean
     */
    final public function isTranslated(): bool
    {
        return $this->isTranslated;
    }

    /**
     * Converts the object to an array
     *
     * @return array
     */
    public function toArray(): array
    {
        // remove the document root from the file path
        $file = $this->getFile();
        if (empty($_SERVER['DOCUMENT_ROOT']) === false) {
            $file = ltrim(Str::after($file, $_SERVER['DOCUMENT_ROOT']), '/');
        }

        return [
            'exception' => static::class,
            'message'   => $this->getMessage(),
            'key'       => $this->getKey(),
            'file'      => $file,
            'line'      => $this->getLine(),
            'details'   => $this->getDetails(),
            'code'      => $this->getHttpCode()
        ];
    }
}