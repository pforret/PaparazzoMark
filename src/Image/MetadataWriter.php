<?php

declare(strict_types=1);

namespace Pforret\PaparazzoMark\Image;

class MetadataWriter
{
    private TempFileManager $tempFileManager;

    public function __construct(TempFileManager $tempFileManager)
    {
        $this->tempFileManager = $tempFileManager;
    }

    /**
     * Generate IPTC metadata command string
     * Ports PrepMagick::set_tags() logic from lines 168-230
     */
    public function getMetadataCommand(array $parameters): string|false
    {
        $iptcTags = [];

        // Credit (photographer name)
        if (! empty($parameters['contact_name'])) {
            $iptcTags[] = $this->formatIptc('2#110#Credit', $parameters['contact_name']);
        }

        // Contact (email)
        if (! empty($parameters['contact_email'])) {
            $iptcTags[] = $this->formatIptc('2#118#Contact', $parameters['contact_email']);
        }

        // Object Name (event name)
        if (! empty($parameters['event'])) {
            $iptcTags[] = $this->formatIptc('2#05#Object Name', $parameters['event']);
        }

        // Keywords (tags)
        if (! empty($parameters['tags'])) {
            $tags = explode(' ', $parameters['tags']);
            foreach ($tags as $tag) {
                if (! empty($tag)) {
                    $iptcTags[] = $this->formatIptc('2#25#Keyword', $tag);
                }
            }
        }

        // Caption
        if (! empty($parameters['caption'])) {
            $iptcTags[] = $this->formatIptc('2#120#Caption', $parameters['caption']);
        }

        // Copyright and By-line (URL)
        if (! empty($parameters['contact_url'])) {
            $iptcTags[] = $this->formatIptc('2#80#By-line', $parameters['contact_url']);
            $iptcTags[] = $this->formatIptc('2#116#Copyright Notice', $parameters['contact_url']);
        }

        // If no tags, return false
        if (empty($iptcTags)) {
            return false;
        }

        // Create temp file with IPTC data
        sort($iptcTags);
        $iptcText = implode("\r\n", $iptcTags);

        $tmpTxt = $this->tempFileManager->getTempDir().DIRECTORY_SEPARATOR.
            '_iptc.'.substr(md5(serialize($parameters)), 0, 8).'.txt';

        file_put_contents($tmpTxt, $iptcText);
        $this->tempFileManager->track($tmpTxt);

        // Return ImageMagick command to apply IPTC profile
        return "-strip -profile 8BIMTEXT:{$tmpTxt}";
    }

    /**
     * Format IPTC tag
     * Ports PrepMagick::format_iptc() from lines 232-235
     */
    private function formatIptc(string $key, string $value): string
    {
        return "{$key}=\"{$value}\"";
    }
}
