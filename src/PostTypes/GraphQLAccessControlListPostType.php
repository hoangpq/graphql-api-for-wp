<?php

declare(strict_types=1);

namespace GraphQLAPI\GraphQLAPI\PostTypes;

use GraphQLAPI\GraphQLAPI\Blocks\AccessControlBlock;
use GraphQLAPI\GraphQLAPI\Plugin;
use GraphQLAPI\GraphQLAPI\PostTypes\AbstractPostType;
use PoP\ComponentModel\Container\ContainerBuilderUtils;
use PoP\ComponentModel\Facades\Instances\InstanceManagerFacade;

class GraphQLAccessControlListPostType extends AbstractPostType
{
    /**
     * Custom Post Type name
     */
    public const POST_TYPE = 'graphql-acl';

    /**
     * Custom Post Type name
     *
     * @return string
     */
    protected function getPostType(): string
    {
        return self::POST_TYPE;
    }

    /**
     * Custom post type name
     *
     * @return void
     */
    public function getPostTypeName(): string
    {
        return \__('Access Control List', 'graphql-api');
    }

    /**
     * Custom Post Type plural name
     *
     * @param bool $uppercase Indicate if the name must be uppercase (for starting a sentence) or, otherwise, lowercase
     * @return string
     */
    protected function getPostTypePluralNames(bool $uppercase): string
    {
        return \__('Access Control Lists', 'graphql-api');
    }

    /**
     * Indicate if the excerpt must be used as the CPT's description and rendered when rendering the post
     *
     * @return boolean
     */
    public function usePostExcerptAsDescription(): bool
    {
        return true;
    }

    /**
     * Gutenberg templates for the Custom Post Type
     *
     * @return array
     */
    protected function getGutenbergTemplate(): array
    {
        $instanceManager = InstanceManagerFacade::getInstance();
        $aclBlock = $instanceManager->getInstance(AccessControlBlock::class);
        return [
            [$aclBlock->getBlockFullName()],
        ];
    }

    /**
     * Use both the Access Control block and all of its nested blocks
     */
    protected function getGutenbergBlocksForCustomPostType(): array
    {
        $instanceManager = InstanceManagerFacade::getInstance();
        $aclBlock = $instanceManager->getInstance(AccessControlBlock::class);
        $aclNestedBlocks = array_map(
            function ($serviceClass) use ($instanceManager) {
                return $instanceManager->getInstance($serviceClass);
            },
            ContainerBuilderUtils::getServiceClassesUnderNamespace(
                Plugin::NAMESPACE . '\\Blocks\\AccessControlRuleBlocks'
            )
        );
        return array_merge(
            [
                $aclBlock->getBlockFullName(),
            ],
            array_map(
                function ($aclNestedBlock) {
                    return $aclNestedBlock->getBlockFullName();
                },
                $aclNestedBlocks
            )
        );
    }
}
