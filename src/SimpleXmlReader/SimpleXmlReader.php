<?php

namespace SimpleXmlReader;

class SimpleXmlReader
{
    const RETURN_DOM = 'RETURN_DOM';
    const RETURN_SIMPLE_XML = 'RETURN_SIMPLE_XML';
    const RETURN_INNER_XML_STRING = 'RETURN_INNER_XML_STRING';
    const RETURN_OUTER_XML_STRING = 'RETURN_OUTER_XML_STRING';

    protected ExceptionThrowingXMLReader $xmlReader;

    protected function __construct()
    {
        $this->xmlReader = new ExceptionThrowingXMLReader();
    }

    public static function autoOpenXML($path, $encoding = 'UTF-8', $options = 0): SimpleXmlReader
    {
        if (strtolower(substr($path, -3)) == '.gz') {
            return self::openGzippedXML($path, $encoding, $options);
        } else {
            return self::openXML($path, $encoding, $options);
        }
    }

    /**
     * @throws XmlException
     */
    public static function openXML($path, $encoding = 'UTF-8', $options = 0): SimpleXmlReader
    {
        $simpleXmlReader = new self();
        $simpleXmlReader->xmlReader->open($path, $encoding, $options);
        return $simpleXmlReader;
    }

    /**
     * @throws XmlException
     */
    public static function openGzippedXML($path, $encoding = 'UTF-8', $options = 0): SimpleXmlReader
    {
        return self::openXML("compress.zlib://$path", $encoding, $options);
    }

    public static function openFromString($source, $encoding = 'UTF-8', $options = 0): SimpleXmlReader
    {
        $simpleXmlReader = new self();
        $simpleXmlReader->xmlReader->XML($source, $encoding, $options);
        return $simpleXmlReader;
    }

    public function path($path, $returnType = self::RETURN_SIMPLE_XML): PathIterator
    {
        return new PathIterator($this->xmlReader, $path, $returnType);
    }
}
