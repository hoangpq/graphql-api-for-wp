<?php

declare(strict_types=1);

namespace GraphQLAPI\GraphQLAPI;

use PoP\APIEndpoints\EndpointUtils;
use GraphQLAPI\GraphQLAPI\Environment;
use PoP\AccessControl\Schema\SchemaModes;
use PoP\ComponentModel\Misc\GeneralUtils;
use GraphQLAPI\GraphQLAPI\ComponentConfiguration;
use GraphQLAPI\GraphQLAPI\Facades\ModuleRegistryFacade;
use GraphQLAPI\GraphQLAPI\ModuleResolvers\ModuleResolver;
use GraphQLAPI\GraphQLAPI\Admin\MenuPages\SettingsMenuPage;
use GraphQLAPI\GraphQLAPI\Facades\UserSettingsManagerFacade;
use PoP\CacheControl\Environment as CacheControlEnvironment;
use PoP\AccessControl\Environment as AccessControlEnvironment;
use PoP\ComponentModel\Facades\Instances\InstanceManagerFacade;
use PoP\ComponentModel\Environment as ComponentModelEnvironment;
use PoP\APIEndpointsForWP\Environment as APIEndpointsForWPEnvironment;
use PoP\GraphQLClientsForWP\Environment as GraphQLClientsForWPEnvironment;
use PoP\ComponentModel\ComponentConfiguration\ComponentConfigurationHelpers;
use PoP\CacheControl\ComponentConfiguration as CacheControlComponentConfiguration;
use PoP\AccessControl\ComponentConfiguration as AccessControlComponentConfiguration;
use PoP\ComponentModel\ComponentConfiguration as ComponentModelComponentConfiguration;
use PoP\APIEndpointsForWP\ComponentConfiguration as APIEndpointsForWPComponentConfiguration;
use PoP\GraphQLClientsForWP\ComponentConfiguration as GraphQLClientsForWPComponentConfiguration;
use PoP\Posts\Environment as PostsEnvironment;
use PoP\Posts\ComponentConfiguration as PostsComponentConfiguration;
use PoP\Users\Environment as UsersEnvironment;
use PoP\Users\ComponentConfiguration as UsersComponentConfiguration;
use PoP\Taxonomies\Environment as TaxonomiesEnvironment;
use PoP\Taxonomies\ComponentConfiguration as TaxonomiesComponentConfiguration;
use PoP\Pages\Environment as PagesEnvironment;
use PoP\Pages\ComponentConfiguration as PagesComponentConfiguration;
use PoP\CustomPosts\Environment as ContentEnvironment;
use PoP\CustomPosts\ComponentConfiguration as ContentComponentConfiguration;

/**
 * Sets the configuration in all the PoP components.
 *
 * To set the value for properties, it uses this order:
 *
 * 1. Retrieve it as an environment value, if defined
 * 2. Retrieve as a constant `GRAPHQL_API_...` from wp-config.php, if defined
 * 3. Retrieve it from the user settings, if stored
 * 4. Use the default value
 *
 * If a slug is set or updated in the environment variable or wp-config constant,
 * it is necessary to flush the rewrite rules for the change to take effect.
 * For that, on the WordPress admin, go to Settings => Permalinks and click on Save changes
 */
class PluginConfiguration
{
    protected static $normalizedOptionValuesCache;

    /**
     * Initialize all configuration
     *
     * @return array
     */
    public static function initialize(): void
    {
        self::mapEnvVariablesToWPConfigConstants();
        self::defineEnvironmentConstantsFromSettings();
    }

    /**
     * Get the values from the form submitted to options.php, and normalize them
     *
     * @return array
     */
    protected static function getNormalizedOptionValues(): array
    {
        if (is_null(self::$normalizedOptionValuesCache)) {
            $instanceManager = InstanceManagerFacade::getInstance();
            $settingsMenuPage = $instanceManager->getInstance(SettingsMenuPage::class);
            // Obtain the values from the POST and normalize them
            $value = $_POST[SettingsMenuPage::SETTINGS_FIELD];
            self::$normalizedOptionValuesCache = $settingsMenuPage->normalizeSettings($value);
        }
        return self::$normalizedOptionValuesCache;
    }

    /**
     * If we are in options.php, already set the new slugs in the hook,
     * so that the EndpointHandler's `addRewriteEndpoints` (executed on `init`)
     * adds the rewrite with the new slug, which will be persisted on
     * flushing the rewrite rules
     *
     * @return mixed
     */
    protected static function maybeOverrideValueFromForm($value, string $module, string $option)
    {
        global $pagenow;
        if ($pagenow == 'options.php') {
            $value = self::getNormalizedOptionValues();
            // Return the specific value to this module/option
            $moduleRegistry = ModuleRegistryFacade::getInstance();
            $moduleResolver = $moduleRegistry->getModuleResolver($module);
            $optionName = $moduleResolver->getSettingOptionName($module, $option);
            return $value[$optionName];
        }
        return $value;
    }

    /**
     * Process the "URL path" option values
     *
     * @param string $value
     * @param string $module
     * @param string $option
     * @return string
     */
    protected static function getURLPathSettingValue(
        string $value,
        string $module,
        string $option
    ): string {
        // If we are on options.php, use the value submitted to the form,
        // so it's updated before doing `add_rewrite_endpoint` and `flush_rewrite_rules`
        $value = self::maybeOverrideValueFromForm($value, $module, $option);

        // Make sure the path has a "/" on both ends
        return EndpointUtils::slashURI($value);
    }

    /**
     * Process the "URL base path" option values
     *
     * @param string $value
     * @param string $module
     * @param string $option
     * @return string
     */
    protected static function getCPTPermalinkBasePathSettingValue(
        string $value,
        string $module,
        string $option
    ): string {
        // If we are on options.php, use the value submitted to the form,
        // so it's updated before doing `add_rewrite_endpoint` and `flush_rewrite_rules`
        $value = self::maybeOverrideValueFromForm($value, $module, $option);

        // Make sure the path does not have "/" on either end
        return trim($value, '/');
    }

    /**
     * Define the values for certain environment constants from the plugin settings
     *
     * @return array
     */
    protected static function defineEnvironmentConstantsFromSettings(): void
    {
        // All the environment variables to override
        $mappings = [
            // Editing Access Scheme
            [
                'class' => ComponentConfiguration::class,
                'envVariable' => Environment::EDITING_ACCESS_SCHEME,
                'module' => ModuleResolver::MAIN,
                'option' => ModuleResolver::OPTION_EDITING_ACCESS_SCHEME,
            ],
            // GraphQL single endpoint slug
            [
                'class' => APIEndpointsForWPComponentConfiguration::class,
                'envVariable' => APIEndpointsForWPEnvironment::GRAPHQL_API_ENDPOINT,
                'module' => ModuleResolver::SINGLE_ENDPOINT,
                'option' => ModuleResolver::OPTION_PATH,
                'callback' => function ($value) {
                    return self::getURLPathSettingValue(
                        $value,
                        ModuleResolver::SINGLE_ENDPOINT,
                        ModuleResolver::OPTION_PATH
                    );
                },
                'condition' => 'any',
            ],
            // Custom Endpoint path
            [
                'class' => ComponentConfiguration::class,
                'envVariable' => Environment::ENDPOINT_SLUG_BASE,
                'module' => ModuleResolver::CUSTOM_ENDPOINTS,
                'option' => ModuleResolver::OPTION_PATH,
                'callback' => function ($value) {
                    return self::getCPTPermalinkBasePathSettingValue(
                        $value,
                        ModuleResolver::CUSTOM_ENDPOINTS,
                        ModuleResolver::OPTION_PATH
                    );
                },
                'condition' => 'any',
            ],
            // Persisted Query path
            [
                'class' => ComponentConfiguration::class,
                'envVariable' => Environment::PERSISTED_QUERY_SLUG_BASE,
                'module' => ModuleResolver::PERSISTED_QUERIES,
                'option' => ModuleResolver::OPTION_PATH,
                'callback' => function ($value) {
                    return self::getCPTPermalinkBasePathSettingValue(
                        $value,
                        ModuleResolver::PERSISTED_QUERIES,
                        ModuleResolver::OPTION_PATH
                    );
                },
                'condition' => 'any',
            ],
            // GraphiQL client slug
            [
                'class' => GraphQLClientsForWPComponentConfiguration::class,
                'envVariable' => GraphQLClientsForWPEnvironment::GRAPHIQL_CLIENT_ENDPOINT,
                'module' => ModuleResolver::GRAPHIQL_FOR_SINGLE_ENDPOINT,
                'option' => ModuleResolver::OPTION_PATH,
                'callback' => function ($value) {
                    return self::getURLPathSettingValue(
                        $value,
                        ModuleResolver::GRAPHIQL_FOR_SINGLE_ENDPOINT,
                        ModuleResolver::OPTION_PATH
                    );
                },
                'condition' => 'any',
            ],
            // Voyager client slug
            [
                'class' => GraphQLClientsForWPComponentConfiguration::class,
                'envVariable' => GraphQLClientsForWPEnvironment::VOYAGER_CLIENT_ENDPOINT,
                'module' => ModuleResolver::INTERACTIVE_SCHEMA_FOR_SINGLE_ENDPOINT,
                'option' => ModuleResolver::OPTION_PATH,
                'callback' => function ($value) {
                    return self::getURLPathSettingValue(
                        $value,
                        ModuleResolver::INTERACTIVE_SCHEMA_FOR_SINGLE_ENDPOINT,
                        ModuleResolver::OPTION_PATH
                    );
                },
                'condition' => 'any',
            ],
            // Use private schema mode?
            [
                'class' => AccessControlComponentConfiguration::class,
                'envVariable' => AccessControlEnvironment::USE_PRIVATE_SCHEMA_MODE,
                'module' => ModuleResolver::PUBLIC_PRIVATE_SCHEMA,
                'option' => ModuleResolver::OPTION_MODE,
                'callback' => function ($value) {
                    // It is stored as string "private" in DB, and must be passed as bool `true` to component
                    return $value == SchemaModes::PRIVATE_SCHEMA_MODE;
                },
            ],
            // Enable individual access control for the schema mode?
            [
                'class' => AccessControlComponentConfiguration::class,
                'envVariable' => AccessControlEnvironment::ENABLE_INDIVIDUAL_CONTROL_FOR_PUBLIC_PRIVATE_SCHEMA_MODE,
                'module' => ModuleResolver::PUBLIC_PRIVATE_SCHEMA,
                'option' => ModuleResolver::OPTION_ENABLE_GRANULAR,
            ],
            // Use namespacing?
            [
                'class' => ComponentModelComponentConfiguration::class,
                'envVariable' => ComponentModelEnvironment::NAMESPACE_TYPES_AND_INTERFACES,
                'module' => ModuleResolver::SCHEMA_NAMESPACING,
                'option' => ModuleResolver::OPTION_USE_NAMESPACING,
            ],
            // Cache-Control default max-age
            [
                'class' => CacheControlComponentConfiguration::class,
                'envVariable' => CacheControlEnvironment::DEFAULT_CACHE_CONTROL_MAX_AGE,
                'module' => ModuleResolver::CACHE_CONTROL,
                'option' => ModuleResolver::OPTION_MAX_AGE,
            ],
            // Post default/max limits
            [
                'class' => PostsComponentConfiguration::class,
                'envVariable' => PostsEnvironment::POST_LIST_DEFAULT_LIMIT,
                'module' => ModuleResolver::SCHEMA_POST_TYPE,
                'option' => ModuleResolver::OPTION_POST_DEFAULT_LIMIT,
            ],
            [
                'class' => PostsComponentConfiguration::class,
                'envVariable' => PostsEnvironment::POST_LIST_MAX_LIMIT,
                'module' => ModuleResolver::SCHEMA_POST_TYPE,
                'option' => ModuleResolver::OPTION_POST_MAX_LIMIT,
            ],
            // User default/max limits
            [
                'class' => UsersComponentConfiguration::class,
                'envVariable' => UsersEnvironment::USER_LIST_DEFAULT_LIMIT,
                'module' => ModuleResolver::SCHEMA_USER_TYPE,
                'option' => ModuleResolver::OPTION_USER_DEFAULT_LIMIT,
            ],
            [
                'class' => UsersComponentConfiguration::class,
                'envVariable' => UsersEnvironment::USER_LIST_MAX_LIMIT,
                'module' => ModuleResolver::SCHEMA_USER_TYPE,
                'option' => ModuleResolver::OPTION_USER_MAX_LIMIT,
            ],
            // Tag default/max limits
            [
                'class' => TaxonomiesComponentConfiguration::class,
                'envVariable' => TaxonomiesEnvironment::TAG_LIST_DEFAULT_LIMIT,
                'module' => ModuleResolver::SCHEMA_TAXONOMY_TYPE,
                'option' => ModuleResolver::OPTION_TAG_DEFAULT_LIMIT,
            ],
            [
                'class' => TaxonomiesComponentConfiguration::class,
                'envVariable' => TaxonomiesEnvironment::TAG_LIST_MAX_LIMIT,
                'module' => ModuleResolver::SCHEMA_TAXONOMY_TYPE,
                'option' => ModuleResolver::OPTION_TAG_MAX_LIMIT,
            ],
            // Page default/max limits
            [
                'class' => PagesComponentConfiguration::class,
                'envVariable' => PagesEnvironment::PAGE_LIST_DEFAULT_LIMIT,
                'module' => ModuleResolver::SCHEMA_PAGE_TYPE,
                'option' => ModuleResolver::OPTION_PAGE_DEFAULT_LIMIT,
            ],
            [
                'class' => PagesComponentConfiguration::class,
                'envVariable' => PagesEnvironment::PAGE_LIST_MAX_LIMIT,
                'module' => ModuleResolver::SCHEMA_PAGE_TYPE,
                'option' => ModuleResolver::OPTION_PAGE_MAX_LIMIT,
            ],
            // Custom post default/max limits
            [
                'class' => ContentComponentConfiguration::class,
                'envVariable' => ContentEnvironment::CUSTOMPOST_LIST_DEFAULT_LIMIT,
                'module' => ModuleResolver::SCHEMA_CUSTOMPOST_UNION_TYPE,
                'option' => ModuleResolver::OPTION_CUSTOMPOST_DEFAULT_LIMIT,
            ],
            [
                'class' => ContentComponentConfiguration::class,
                'envVariable' => ContentEnvironment::CUSTOMPOST_LIST_MAX_LIMIT,
                'module' => ModuleResolver::SCHEMA_CUSTOMPOST_UNION_TYPE,
                'option' => ModuleResolver::OPTION_CUSTOMPOST_MAX_LIMIT,
            ],
        ];
        // For each environment variable, see if its value has been saved in the settings
        $userSettingsManager = UserSettingsManagerFacade::getInstance();
        $moduleRegistry = ModuleRegistryFacade::getInstance();
        foreach ($mappings as $mapping) {
            $module = $mapping['module'];
            $condition = $mapping['condition'] ?? true;
            // Check if the hook must be executed always (condition => 'any') or with
            // stated enabled (true) or disabled (false). By default, it's enabled
            if ($condition != 'any' && $condition != $moduleRegistry->isModuleEnabled($module)) {
                continue;
            }
            // If the environment value has been defined, or the constant in wp-config.php,
            // then do nothing, since they have priority
            $envVariable = $mapping['envVariable'];
            if (isset($_ENV[$envVariable]) || self::isWPConfigConstantDefined($envVariable)) {
                continue;
            }
            $hookName = ComponentConfigurationHelpers::getHookName(
                $mapping['class'],
                $envVariable
            );
            $option = $mapping['option'];
            $callback = $mapping['callback'];
            \add_filter(
                $hookName,
                function () use ($userSettingsManager, $module, $option, $callback) {
                    $value = $userSettingsManager->getSetting($module, $option);
                    if ($callback) {
                        return $callback($value);
                    }
                    return $value;
                }
            );
        }
    }

    /**
     * Map the environment variables from the components, to WordPress wp-config.php constants
     *
     * @return array
     */
    protected static function mapEnvVariablesToWPConfigConstants(): void
    {
        // All the environment variables to override
        $mappings = [
            [
                'class' => ComponentConfiguration::class,
                'envVariable' => Environment::ADD_EXCERPT_AS_DESCRIPTION,
            ],
            [
                'class' => APIEndpointsForWPComponentConfiguration::class,
                'envVariable' => APIEndpointsForWPEnvironment::GRAPHQL_API_ENDPOINT,
            ],
            [
                'class' => GraphQLClientsForWPComponentConfiguration::class,
                'envVariable' => GraphQLClientsForWPEnvironment::GRAPHIQL_CLIENT_ENDPOINT,
            ],
            [
                'class' => GraphQLClientsForWPComponentConfiguration::class,
                'envVariable' => GraphQLClientsForWPEnvironment::VOYAGER_CLIENT_ENDPOINT,
            ],
            [
                'class' => AccessControlComponentConfiguration::class,
                'envVariable' => AccessControlEnvironment::USE_PRIVATE_SCHEMA_MODE,
            ],
            [
                'class' => AccessControlComponentConfiguration::class,
                'envVariable' => AccessControlEnvironment::ENABLE_INDIVIDUAL_CONTROL_FOR_PUBLIC_PRIVATE_SCHEMA_MODE,
            ],
            [
                'class' => ComponentModelComponentConfiguration::class,
                'envVariable' => ComponentModelEnvironment::NAMESPACE_TYPES_AND_INTERFACES,
            ],
            [
                'class' => CacheControlComponentConfiguration::class,
                'envVariable' => CacheControlEnvironment::DEFAULT_CACHE_CONTROL_MAX_AGE,
            ],
        ];
        // For each environment variable, see if it has been defined as a wp-config.php constant
        foreach ($mappings as $mapping) {
            $class = $mapping['class'];
            $envVariable = $mapping['envVariable'];

            // If the environment value has been defined, then do nothing, since it has priority
            if (isset($_ENV[$envVariable])) {
                continue;
            }
            $hookName = ComponentConfigurationHelpers::getHookName(
                $class,
                $envVariable
            );

            \add_filter(
                $hookName,
                /**
                 * Override the value of an environment variable if it has been definedas a constant
                 * in wp-config.php, with the environment name prepended with "GRAPHQL_API_"
                 */
                function ($value) use ($envVariable) {
                    if (self::isWPConfigConstantDefined($envVariable)) {
                        return self::getWPConfigConstantValue($envVariable);
                    }
                    return $value;
                }
            );
        }
    }

    /**
     * Determine if the environment variable was defined as a constant in wp-config.php
     *
     * @return mixed
     */
    protected static function getWPConfigConstantValue(string $envVariable)
    {
        return constant(self::getWPConfigConstantName($envVariable));
    }

    /**
     * Determine if the environment variable was defined as a constant in wp-config.php
     *
     * @return string
     */
    protected static function isWPConfigConstantDefined(string $envVariable): bool
    {
        return defined(self::getWPConfigConstantName($envVariable));
    }

    /**
     * Constants defined in wp-config.php must start with this prefix to override GraphQL API environment variables
     *
     * @return string
     */
    protected static function getWPConfigConstantName($envVariable): string
    {
        return 'GRAPHQL_API_' . $envVariable;
    }

    /**
     * Provide the configuration for all components required in the plugin
     *
     * @return array
     */
    public static function getComponentClassConfiguration(): array
    {
        $componentClassConfiguration = [];
        self::addPredefinedComponentClassConfiguration($componentClassConfiguration);
        self::addBasedOnModuleEnabledStateComponentClassConfiguration($componentClassConfiguration);
        return $componentClassConfiguration;
    }

    /**
     * Add the fixed configuration for all components required in the plugin
     *
     * @return void
     */
    protected static function addPredefinedComponentClassConfiguration(array &$componentClassConfiguration): void
    {
        $componentClassConfiguration[\PoP\Engine\Component::class] = [
            \PoP\Engine\Environment::ADD_MANDATORY_CACHE_CONTROL_DIRECTIVE => false,
        ];
        $componentClassConfiguration[\PoP\GraphQLClientsForWP\Component::class] = [
            \PoP\GraphQLClientsForWP\Environment::GRAPHQL_CLIENTS_COMPONENT_URL => \GRAPHQL_API_URL . 'vendor/getpop/graphql-clients-for-wp',
        ];
        // Disable the Native endpoint
        $componentClassConfiguration[\PoP\APIEndpointsForWP\Component::class] = [
            \PoP\APIEndpointsForWP\Environment::DISABLE_NATIVE_API_ENDPOINT => true,
        ];
    }

    /**
     * Return the opposite value
     *
     * @param boolean $value
     * @return boolean
     */
    protected static function opposite(bool $value): bool
    {
        return !$value;
    }

    /**
     * Add configuration values if modules are enabled or disabled
     *
     * @return void
     */
    protected static function addBasedOnModuleEnabledStateComponentClassConfiguration(array &$componentClassConfiguration): void
    {
        $moduleRegistry = ModuleRegistryFacade::getInstance();
        $moduleToComponentClassConfigurationMappings = [
            [
                'module' => ModuleResolver::SINGLE_ENDPOINT,
                'class' => \PoP\APIEndpointsForWP\Component::class,
                'envVariable' => \PoP\APIEndpointsForWP\Environment::DISABLE_GRAPHQL_API_ENDPOINT,
                'callback' => [self::class, 'opposite'],
            ],
            [
                'module' => ModuleResolver::GRAPHIQL_FOR_SINGLE_ENDPOINT,
                'class' => \PoP\GraphQLClientsForWP\Component::class,
                'envVariable' => \PoP\GraphQLClientsForWP\Environment::DISABLE_GRAPHIQL_CLIENT_ENDPOINT,
                'callback' => [self::class, 'opposite'],
            ],
            [
                'module' => ModuleResolver::INTERACTIVE_SCHEMA_FOR_SINGLE_ENDPOINT,
                'class' => \PoP\GraphQLClientsForWP\Component::class,
                'envVariable' => \PoP\GraphQLClientsForWP\Environment::DISABLE_VOYAGER_CLIENT_ENDPOINT,
                'callback' => [self::class, 'opposite'],
            ],
        ];
        foreach ($moduleToComponentClassConfigurationMappings as $mapping) {
            // Copy the state (enabled/disabled) to the component
            $value = $moduleRegistry->isModuleEnabled($mapping['module']);
            if ($callback = $mapping['callback']) {
                $value = $callback($value);
            }
            $componentClassConfiguration[$mapping['class']][$mapping['envVariable']] = $value;
        }
    }

    /**
     * Provide the classes of the components whose schema initialization must be skipped
     *
     * @return array
     */
    public static function getSkippingSchemaComponentClasses(): array
    {
        $moduleRegistry = ModuleRegistryFacade::getInstance();

        // Component classes enabled/disabled by module
        $maybeSkipSchemaModuleComponentClasses = [
            ModuleResolver::DIRECTIVE_SET_CONVERT_LOWER_UPPERCASE => [
                \PoP\UsefulDirectives\Component::class,
            ],
            ModuleResolver::SCHEMA_POST_TYPE => [
                \PoP\PostMediaWP\Component::class,
                \PoP\PostMedia\Component::class,
                \PoP\PostMetaWP\Component::class,
                \PoP\PostMeta\Component::class,
                \PoP\PostsWP\Component::class,
                \PoP\Posts\Component::class,
            ],
            ModuleResolver::SCHEMA_COMMENT_TYPE => [
                \PoP\CommentMetaWP\Component::class,
                \PoP\CommentMeta\Component::class,
                \PoP\CommentsWP\Component::class,
                \PoP\Comments\Component::class,
            ],
            ModuleResolver::SCHEMA_USER_TYPE => [
                \PoP\UserMetaWP\Component::class,
                \PoP\UserMeta\Component::class,
                \PoP\UsersWP\Component::class,
                \PoP\Users\Component::class,
                \PoP\UserRolesWP\Component::class,
                \PoP\UserRoles\Component::class,
                \PoP\UserState\Component::class,
            ],
            ModuleResolver::SCHEMA_PAGE_TYPE => [
                \PoP\PagesWP\Component::class,
                \PoP\Pages\Component::class,
            ],
            ModuleResolver::SCHEMA_MEDIA_TYPE => [
                \PoP\PostMediaWP\Component::class,
                \PoP\PostMedia\Component::class,
                \PoP\MediaWP\Component::class,
                \PoP\Media\Component::class,
            ],
            ModuleResolver::SCHEMA_TAXONOMY_TYPE => [
                \PoP\TaxonomiesWP\Component::class,
                \PoP\Taxonomies\Component::class,
                \PoP\TaxonomyMetaWP\Component::class,
                \PoP\TaxonomyMeta\Component::class,
                \PoP\TaxonomyQueryWP\Component::class,
                \PoP\TaxonomyQuery\Component::class,
            ],
            ModuleResolver::SCHEMA_CUSTOMPOST_UNION_TYPE => [
                \PoP\CustomPostsWP\Component::class,
                \PoP\CustomPosts\Component::class,
            ],
        ];
        $skipSchemaModuleComponentClasses = array_filter(
            $maybeSkipSchemaModuleComponentClasses,
            function ($module) use ($moduleRegistry) {
                return !$moduleRegistry->isModuleEnabled($module);
            },
            ARRAY_FILTER_USE_KEY
        );
        return GeneralUtils::arrayFlatten(array_values(
            $skipSchemaModuleComponentClasses
        ));
    }
}
