<?php

declare(strict_types=1);

namespace Leoloso\GraphQLByPoPWPPlugin\EndpointResolvers;

abstract class AbstractEndpointResolver
{
    /**
     * Initialize the resolver
     *
     * @return void
     */
    public function initialize(): void
    {
        // Do nothing
    }
}
