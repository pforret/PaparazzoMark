<?php

declare(strict_types=1);

namespace Pforret\PaparazzoMark\Config;

use Noodlehaus\Config;

class ConfigLoader
{
    private Config $config;

    private array $data;

    private string $defaultSection = '_default';

    public function __construct(string $configPath)
    {
        if (! file_exists($configPath)) {
            throw new \RuntimeException("Config file not found: {$configPath}");
        }

        $this->config = Config::load($configPath);
        $this->data = $this->config->all();
    }

    /**
     * Find config file in multiple locations
     */
    public static function findConfigFile(array $searchPaths, string $filename = 'pmark.ini'): ?string
    {
        foreach ($searchPaths as $path) {
            $configPath = rtrim($path, '/').DIRECTORY_SEPARATOR.$filename;
            if (file_exists($configPath)) {
                return realpath($configPath);
            }
        }

        return null;
    }

    /**
     * Get value with fallback to [_default] section then to default value
     * Mimics legacy IniFile::get_value() behavior
     */
    public function getValue(string $section, string $key, mixed $default = null): mixed
    {
        // Check section first
        if (isset($this->data[$section][$key])) {
            return $this->data[$section][$key];
        }

        // Fallback to _default section
        if (isset($this->data[$this->defaultSection][$key])) {
            return $this->data[$this->defaultSection][$key];
        }

        // Fallback to default value
        return $default;
    }

    /**
     * Get all non-special sections (not starting with _)
     * Mimics legacy IniFile::get_sections() behavior
     */
    public function getSections(): array
    {
        $sections = [];
        foreach (array_keys($this->data) as $sectionName) {
            if (is_array($this->data[$sectionName]) &&
                $sectionName !== $this->defaultSection &&
                ! str_starts_with($sectionName, '_')) {
                $sections[] = $sectionName;
            }
        }

        return $sections;
    }

    /**
     * Get all values for a section with defaults merged
     * Mimics legacy IniFile::get_all_values() behavior
     */
    public function getAllValues(string $section): array
    {
        if (! isset($this->data[$section])) {
            return [];
        }

        // Start with defaults
        $values = [];
        if (isset($this->data[$this->defaultSection]) && is_array($this->data[$this->defaultSection])) {
            $values = $this->data[$this->defaultSection];
        }

        // Merge with section values (section values override defaults)
        if (is_array($this->data[$section])) {
            $values = array_merge($values, $this->data[$section]);
        }

        return $values;
    }

    /**
     * Get raw config data
     */
    public function getData(): array
    {
        return $this->data;
    }
}
