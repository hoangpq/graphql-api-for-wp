<?php

declare(strict_types=1);

namespace GraphQLAPI\GraphQLAPI\Admin\TableActions;

use GraphQLAPI\GraphQLAPI\Facades\UserSettingsManagerFacade;

/**
 * Module List Table Action
 */
class ModuleListTableAction extends AbstractListTableAction
{
    public const ACTION_ENABLE = 'enable';
    public const ACTION_DISABLE = 'disable';
    public const INPUT_BULK_ACTION_IDS = 'bulk-action-items';

    private $processed = false;
    private $mutatedModuleIDs = [];
    private $mutatedEnabled = false;

    /**
     * Side effects: needed when the class is instantiated through
     * DependencyInjection, but to be avoided when instantiated from Plugin
     *
     * @param boolean $allowSideEffects
     */
    public function __construct(bool $allowSideEffects = true)
    {
        if ($allowSideEffects) {
            /**
             * If executing an operation, print a success message
             */
            \add_action('admin_notices', function () {
                $this->maybeAddAdminNotice();
            });
        }
    }

    /**
     * Show an admin notice with the successful message
     * Executing this function from within `setModulesEnabledValue` is too late,
     * since hook "admin_notices" will have been executed by then
     * Then, deduce if there will be an operation, and always say "successful"
     *
     * @return void
     */
    public function maybeAddAdminNotice(): void
    {
        // If processes, `maybeProcessAction` has already been executed,
        // so we have the results to show in the admin notice
        $message = '';
        if ($this->processed) {
            /**
             * Executing at the beginning (in Plugin.php): Add a precise message
             */
            if ($this->mutatedModuleIDs) {
                if (count($this->mutatedModuleIDs) == 1 && $this->mutatedEnabled) {
                    $message = \__('Module enabled successfully', 'graphql-api');
                } elseif (count($this->mutatedModuleIDs) > 1 && $this->mutatedEnabled) {
                    $message = \__('Modules enabled successfully', 'graphql-api');
                } elseif (count($this->mutatedModuleIDs) == 1 && !$this->mutatedEnabled) {
                    $message = \__('Module disabled successfully', 'graphql-api');
                } elseif (count($this->mutatedModuleIDs) > 1 && !$this->mutatedEnabled) {
                    $message = \__('Modules disabled successfully', 'graphql-api');
                }
            }
        } else {
            /**
             * Executed at the end (in ModuleListTable.php, `prepare_items`): Add a generic message
             */
            // See if executing any of the actions
            $actions = $this->getActions();
            $isBulkAction = in_array($_POST['action'], $actions) || in_array($_POST['action2'], $actions);
            $isSingleAction = in_array($this->currentAction(), $actions);
            if ($isBulkAction || $isSingleAction) {
                $message = \__('Operation successful', 'graphql-api');
            }
        }
        if ($message) {
            _e(sprintf(
                '<div class="notice notice-success is-dismissible">' .
                    '<p>%s</p>' .
                '</div>',
                $message
            ));
        }
    }

    /**
     * Process bulk and single actions.
     * This function can be executed only once, from either the Plugin class or the table
     *
     * @return void
     */
    public function maybeProcessAction()
    {
        // Process only once
        if ($this->processed) {
            return;
        }
        $this->processed = true;

        $actions = $this->getActions();
        $isBulkAction = in_array($_POST['action'], $actions) || in_array($_POST['action2'], $actions);
        /**
         * The Bulk takes precedence, because it's executed as a POST on the current URL
         * Then, the URL can contain an ?action=... which was just executed,
         * and we don't want to execute it again
         */
        if ($isBulkAction) {
            if ($moduleIDs = \esc_sql($_POST[self::INPUT_BULK_ACTION_IDS] ?? '')) {
                // Enable or disable
                if ($_POST['action'] == self::ACTION_ENABLE || $_POST['action2'] == self::ACTION_ENABLE) {
                    $this->setModulesEnabledValue($moduleIDs, true);
                } elseif ($_POST['action'] == self::ACTION_DISABLE || $_POST['action2'] == self::ACTION_DISABLE) {
                    $this->setModulesEnabledValue($moduleIDs, false);
                }
            }
            return;
        }
        $isSingleAction = in_array($this->currentAction(), $actions);
        if ($isSingleAction) {
            // Verify the nonce
            $nonce = \esc_attr($_REQUEST['_wpnonce']);
            if (!\wp_verify_nonce($nonce, 'graphql_api_enable_or_disable_module')) {
                $noParamsCurrentURL = \admin_url(sprintf(
                    'admin.php?page=%s',
                    $_GET['page']
                ));
                \wp_die(sprintf(
                    __('This URL is either stale or not valid. Please <a href="%s">click here to reload the page</a>, and try again', 'graphql-api'),
                    $noParamsCurrentURL
                ));
            }
            if ($moduleID = $_GET['item']) {
                // Enable or disable
                if (self::ACTION_ENABLE === $this->currentAction()) {
                    $this->setModulesEnabledValue([$moduleID], true);
                } elseif (self::ACTION_DISABLE === $this->currentAction()) {
                    $this->setModulesEnabledValue([$moduleID], false);
                }
            }
        }
    }

    /**
     * Enable or Disable a list of modules
     *
     * @param array $moduleIDs
     * @param boolean $value
     * @return void
     */
    protected function setModulesEnabledValue(array $moduleIDs, bool $isEnabled): void
    {
        $userSettingsManager = UserSettingsManagerFacade::getInstance();
        $moduleIDValues = [];
        foreach ($moduleIDs as $moduleID) {
            $moduleIDValues[$moduleID] = $isEnabled;
        }
        $userSettingsManager->setModulesEnabled($moduleIDValues);

        // Flags to indicate that data was mutated, which and how
        $this->mutatedModuleIDs = $moduleIDs;
        $this->mutatedEnabled = $isEnabled;

        // If modifying a CPT, must flush the rewrite rules
        // But do it at the end! Once the new configuration has been applied
        \add_action(
            'init',
            function () {
                \flush_rewrite_rules();

                // Update the timestamp
                $userSettingsManager = UserSettingsManagerFacade::getInstance();
                $userSettingsManager->storeTimestamp();
            },
            PHP_INT_MAX
        );
    }

    public function getActions(): array
    {
        return [
            self::ACTION_ENABLE,
            self::ACTION_DISABLE,
        ];
    }
}
