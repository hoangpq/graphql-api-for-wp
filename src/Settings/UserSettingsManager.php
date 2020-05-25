<?php

declare(strict_types=1);

namespace GraphQLAPI\GraphQLAPI\Settings;

use GraphQLAPI\GraphQLAPI\Settings\Options;

class UserSettingsManager implements UserSettingsManagerInterface
{
    /**
     * Cache the values in memory
     *
     * @var array
     */
    protected $options = [];

    public function hasSettingsItem(string $item): bool
    {
        return $this->hasItem(Options::SETTINGS, $item);
    }
    public function getSettingsItem(string $item)
    {
        return $this->getItem(Options::SETTINGS, $item);
    }

    public function hasModuleItem(string $module): bool
    {
        return $this->hasItem(Options::MODULES, $module);
    }
    public function getModuleItem(string $module)
    {
        return $this->getItem(Options::MODULES, $module);
    }
    public function storeModuleItem(string $module, $value): void
    {
        $this->storeItem(Options::MODULES, $module, $value);
    }
    public function storeModuleItems(array $moduleValues): void
    {
        $this->storeItems(Options::MODULES, $moduleValues);
    }

    /**
     * Get the stored value for the option under the group
     *
     * @param array|null $var
     * @param string $optionName
     * @param string $item
     * @return void
     */
    protected function getItem(string $optionName, string $item)
    {
        $this->maybeLoadOptions($optionName);
        return $this->options[$optionName][$item];
    }

    /**
     * Is there a stored value for the option under the group
     *
     * @param string $optionName
     * @param string $item
     * @return void
     */
    protected function hasItem(string $optionName, string $item): bool
    {
        $this->maybeLoadOptions($optionName);
        return isset($this->options[$optionName][$item]);
    }

    /**
     * Load the options from the DB
     *
     * @param string $optionName
     * @return void
     */
    protected function maybeLoadOptions(string $optionName): void
    {
        // Lazy load the options
        if (is_null($this->options[$optionName])) {
            $this->options[$optionName] = \get_option($optionName, []);
        }
    }

    /**
     * Store the options in the DB
     *
     * @param string $optionName
     * @return void
     */
    protected function storeItem(string $optionName, string $item, $value): void
    {
        $this->storeItems($optionName, [$item => $value]);
    }

    /**
     * Store the options in the DB
     *
     * @param string $optionName
     * @return void
     */
    protected function storeItems(string $optionName, array $itemValues): void
    {
        $this->maybeLoadOptions($optionName);
        // Change the values of the items
        $this->options[$optionName] = array_merge(
            $this->options[$optionName],
            $itemValues
        );
        // Save to the DB
        \update_option($optionName, $this->options[$optionName]);
    }
}
