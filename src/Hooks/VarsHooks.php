<?php

declare(strict_types=1);

namespace GraphQLAPI\GraphQLAPI\Hooks;

use PoP\Engine\Hooks\AbstractHookSet;
use GraphQLAPI\GraphQLAPI\Facades\ModuleRegistryFacade;
use GraphQLAPI\GraphQLAPI\ModuleResolvers\FunctionalityModuleResolver;
use PoP\GraphQLAPI\DataStructureFormatters\GraphQLDataStructureFormatter;

class VarsHooks extends AbstractHookSet
{
    protected function init()
    {
        // Implement immediately, before VarsHooks in API adds output=json
        $this->hooksAPI->addAction(
            'ApplicationState:addVars',
            array($this, 'maybeRemoveVars'),
            0,
            1
        );
    }

    /**
     * If the single endpoint is disabled, or if pointing to a different URL
     * than the single endpoint (eg: /posts/) and the datastructure param
     * is not provided it's not "graphql", then:
     * Do not allow to query the endpoint through URL.
     *
     * Examples of not allowed URLs:
     * - /single-endpoint/?scheme=api&datastructure=graphql <= single endpoint disabled
     * - /posts/?scheme=api
     */
    public function maybeRemoveVars($vars_in_array)
    {
        $vars = &$vars_in_array[0];
        if ($vars['scheme'] == \POP_SCHEME_API) {
            $moduleRegistry = ModuleRegistryFacade::getInstance();
            // By setting explicit allowed datastructures, we avoid the empty one
            // being processed /?scheme=api <= native API
            // If ever need to support REST or another format, add a hook here
            $allowedDataStructures = [
                GraphQLDataStructureFormatter::getName(),
            ];
            if (
                // If single endpoint not enabled
                !$moduleRegistry->isModuleEnabled(FunctionalityModuleResolver::SINGLE_ENDPOINT)
                // If datastructure is not GraphQL (or another allowed one)
                || !in_array($vars['datastructure'], $allowedDataStructures)
            ) {
                unset($vars['scheme']);
                unset($vars['datastructure']);
            }
        }
    }
}
