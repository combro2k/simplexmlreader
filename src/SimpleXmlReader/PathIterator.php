<?php

namespace SimpleXmlReader;

use DOMNode;
use Exception;
use SimpleXMLElement;
use XMLReader;
use Iterator;
use DOMDocument;

class PathIterator implements Iterator
{
    const IS_MATCH = 'IS_MATCH';
    const DESCENDANTS_COULD_MATCH = 'DESCENDANTS_COULD_MATCH';
    const DESCENDANTS_CANT_MATCH = 'DESCENDANTS_CANT_MATCH';

    protected ExceptionThrowingXMLReader $reader;
    protected $searchPath;
    protected array $searchCrumbs;
    protected array $crumbs;
    protected mixed $currentDomExpansion;
    protected int $rewindCount;
    protected bool $isValid;
    protected mixed $returnType;

    private int $matchCount;

    public function __construct(ExceptionThrowingXMLReader $reader, $path, $returnType)
    {
        $this->reader = $reader;
        $this->searchPath = $path;
        $this->searchCrumbs = explode('/', $path);
        $this->crumbs = array();
        $this->matchCount = -1;
        $this->rewindCount = 0;
        $this->isValid = false;
        $this->returnType = $returnType;
    }

    public function current(): mixed
    {
        return $this->currentDomExpansion;
    }

    public function key(): mixed
    {
        return $this->matchCount;
    }

    public function next(): void
    {
        $this->isValid = $this->tryGotoNextIterationElement();

        if ($this->isValid) {
            $this->matchCount += 1;
            $this->currentDomExpansion = $this->getXMLObject();
        }
    }

    /**
     * @throws XmlException
     */
    public function rewind(): void
    {
        $this->rewindCount += 1;
        if ($this->rewindCount > 1) {
            throw new XmlException('Multiple rewinds not supported');
        }
        $this->next();
    }

    public function valid(): bool
    {
        return $this->isValid;
    }

    /**
     * @throws XMlException
     * @throws Exception
     */
    protected function getXMLObject(): string|bool|null|SimpleXMLElement|DOMNode
    {
        switch ($this->returnType) {
            case SimpleXMLReader::RETURN_DOM:
                return $this->reader->expand();

            case SimpleXMLReader::RETURN_INNER_XML_STRING:
                return $this->reader->readInnerXML();

            case SimpleXMLReader::RETURN_OUTER_XML_STRING:
                return $this->reader->readOuterXML();

            case SimpleXMLReader::RETURN_SIMPLE_XML:
                $simplexml = simplexml_import_dom($this->reader->expand(new DOMDocument('1.0')));
                if (false === $simplexml) {
                    throw new XMlException('Failed to create a SimpleXMLElement from the current XML node (invalid XML?)');
                }

                return $simplexml;

            default:
                throw new Exception(sprintf("Unknown return type: %s", $this->returnType));
        }
    }

    protected function pathIsMatching(): string
    {
        if (count($this->crumbs) > count($this->searchCrumbs)) {
            return self::DESCENDANTS_CANT_MATCH;
        }
        foreach ($this->crumbs as $i => $crumb) {
            $searchCrumb = $this->searchCrumbs[$i];
            if ($searchCrumb == $crumb || $searchCrumb == '*') {
                continue;
            }
            return self::DESCENDANTS_CANT_MATCH;
        }
        if (count($this->crumbs) == count($this->searchCrumbs)) {
            return self::IS_MATCH;
        }
        return self::DESCENDANTS_COULD_MATCH;
    }

    public function tryGotoNextIterationElement(): bool
    {
        $r = $this->reader;

        if ($r->nodeType == XMLReader::NONE) {
            // first time we do a read from the xml
            if (! $r->tryRead()) { return false; }
        } else {
            // if we have already had a match
            if (! $r->tryNext()) { return false; }
        }

        while (true) {
            // search for open tag
            while ($r->nodeType != XMLReader::ELEMENT) {
                if (! $r->tryRead()) { return false; }
            }

            // fill crumbs
            array_splice($this->crumbs, $r->depth, count($this->crumbs), array($r->name));

            switch ($this->pathIsMatching()) {

                case self::DESCENDANTS_COULD_MATCH:
                    if (! $r->tryRead()) { return false; }
                    continue 2;

                case self::DESCENDANTS_CANT_MATCH:
                    if (! $r->tryNext()) { return false; }
                    continue 2;

                case self::IS_MATCH:
                    return true;
            }

            return false;
        }
    }
}
