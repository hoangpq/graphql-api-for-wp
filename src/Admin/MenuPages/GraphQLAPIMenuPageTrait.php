<?php

declare(strict_types=1);

namespace GraphQLAPI\GraphQLAPI\Admin\MenuPages;

use GraphQLAPI\GraphQLAPI\Admin\Menus\Menu;

trait GraphQLAPIMenuPageTrait
{
    public function getMenuName(): string
    {
        return Menu::getName();
    }
}
