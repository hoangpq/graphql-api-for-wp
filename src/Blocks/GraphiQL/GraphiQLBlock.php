<?php

declare(strict_types=1);

namespace Leoloso\GraphQLByPoPWPPlugin\Blocks\GraphiQL;

use Leoloso\GraphQLByPoPWPPlugin\Blocks\AbstractBlock;
use Leoloso\GraphQLByPoPWPPlugin\General\EndpointHelpers;
use Leoloso\GraphQLByPoPWPPlugin\Blocks\GraphQLByPoPBlockTrait;
use PoP\ComponentModel\Facades\Instances\InstanceManagerFacade;
use Leoloso\GraphQLByPoPWPPlugin\BlockCategories\AbstractBlockCategory;
use Leoloso\GraphQLByPoPWPPlugin\BlockCategories\PersistedQueryBlockCategory;

/**
 * GraphiQL block
 */
class GraphiQLBlock extends AbstractBlock
{
    use GraphQLByPoPBlockTrait;

    public const ATTRIBUTE_NAME_QUERY = 'query';
    public const ATTRIBUTE_NAME_VARIABLES = 'variables';

    protected function getBlockName(): string
    {
        return 'graphiql';
    }

    protected function getBlockCategory(): ?AbstractBlockCategory
    {
        $instanceManager = InstanceManagerFacade::getInstance();
        return $instanceManager->getInstance(PersistedQueryBlockCategory::class);
    }

    protected function isDynamicBlock(): bool
    {
        return true;
    }

    /**
     * Pass localized data to the block
     *
     * @return array
     */
    protected function getLocalizedData(): array
    {
        return array_merge(
            parent::getLocalizedData(),
            [
                'nonce' => \wp_create_nonce('wp_rest'),
                'endpoint' => EndpointHelpers::getAdminGraphQLEndpoint(),
            ]
        );
    }
    
    public function renderBlock(array $attributes, string $content): string
    {
        $content = sprintf(
            '<div class="%s">',
            $this->getBlockClassName() . ' ' . $this->getAlignClass()
        );
        $query = $attributes[self::ATTRIBUTE_NAME_QUERY];
        $variables = $attributes[self::ATTRIBUTE_NAME_VARIABLES];
        $content .= sprintf(
            '<p><strong>%s</strong></p>',
            \__('GraphQL Query:', 'graphql-api')
        ) . (
            $query ? sprintf(
                '<pre><code class="prettyprint language-graphql">%s</code></pre>',
                $query
            ) : sprintf(
                '<p><em>%s</em></p>',
                \__('(Not set)', 'graphql-api')
            )
        );
        if ($variables) {
            $content .= sprintf(
                '<p><strong>%s</strong></p>',
                \__('Variables:', 'graphql-api')
            ) . sprintf(
                '<pre><code class="prettyprint language-json">%s</code></pre>',
                $variables
            );
        }
        $content .= '</div>';
        return $content;
    }
}