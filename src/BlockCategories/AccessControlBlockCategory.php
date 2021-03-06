<?php

declare(strict_types=1);

namespace GraphQLAPI\GraphQLAPI\BlockCategories;

use GraphQLAPI\GraphQLAPI\BlockCategories\AbstractBlockCategory;
use GraphQLAPI\GraphQLAPI\PostTypes\GraphQLAccessControlListPostType;

class AccessControlBlockCategory extends AbstractBlockCategory
{
    public const ACCESS_CONTROL_BLOCK_CATEGORY = 'graphql-api-access-control';

    /**
     * Custom Post Type for which to enable the block category
     *
     * @return string
     */
    public function getPostTypes(): array
    {
        return [
            GraphQLAccessControlListPostType::POST_TYPE,
        ];
    }

    /**
     * Block category's slug
     *
     * @return string
     */
    protected function getBlockCategorySlug(): string
    {
        return self::ACCESS_CONTROL_BLOCK_CATEGORY;
    }

    /**
     * Block category's title
     *
     * @return string
     */
    protected function getBlockCategoryTitle(): string
    {
        return __('Access Control for GraphQL', 'graphql-api');
    }
}
