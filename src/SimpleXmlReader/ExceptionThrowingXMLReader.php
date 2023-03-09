<?php

namespace SimpleXmlReader;

use DOMNode;
use XMLReader;

class ExceptionThrowingXMLReader extends XMLReader
{
    /**
     * @throws XmlException
     */
    public static function open($URI, $encoding = null, $options = 0): bool
    {
        return static::ensureSuccess(@parent::open($URI, $encoding, $options), 'open');
    }

    /**
     * @throws XmlException
     */
    static protected function ensureSuccess($returnValue, $operation)
    {
        if (! $returnValue) {
            throw new XmlException("Error while performing XMLReader::$operation");
        }
        return $returnValue;
    }

    /**
     * @throws XmlException
     */
    public function expand($baseNode = null): DOMNode|false
    {
        if (null === $baseNode) {
            return static::ensureSuccess(@parent::expand(), 'expend');
        } else {
            return static::ensureSuccess(@parent::expand($baseNode), 'expend');
        }
    }

    /**
     * @throws XmlException
     */
    public function read(): bool
    {
        return static::ensureSuccess(@parent::read(), 'read');
    }

    public function tryRead(): bool
    {
        // We're ignoring any PHP errors, as we are trying to read
        return @parent::read();
    }

    /**
     * @throws XmlException
     */
    public function next($name = null): bool
    {
        if (null === $name) {
            return static::ensureSuccess(@parent::next(), 'next');
        }

        return static::ensureSuccess(@parent::next($name), 'next');
    }

    public function tryNext($localName = null): bool
    {
        // We're ignoring any PHP errors, as we are trying to fetch the next element
        if (null === $localName) {
            return @parent::next();
        }

        return @parent::next($localName);
    }
}
