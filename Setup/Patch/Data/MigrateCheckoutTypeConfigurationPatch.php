<?php

declare(strict_types=1);

namespace Bold\CheckoutSelfHosted\Setup\Patch\Data;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Migrate configuration.
 */
class MigrateCheckoutTypeConfigurationPatch implements DataPatchInterface
{
    private const SOURCE_TYPE = 3;
    private const PATH_IS_ENABLED_SOURCE = 'checkout/bold_checkout_base/type';
    private const PATH_IS_ENABLED_TARGET = 'checkout/bold_checkout_self_hosted/is_enabled';

    private const PATHS_TO_MIGRATE = [
        'checkout/bold_checkout_base/template_type' => 'checkout/bold_checkout_self_hosted/template_type',
        'checkout/bold_checkout_base/template_file' => 'checkout/bold_checkout_self_hosted/template_file',
        'checkout/bold_checkout_advanced/template_url' => 'checkout/bold_checkout_self_hosted/template_url',
    ];

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    )
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * Perform integration permissions upgrade.
     *
     * @return void
     */
    public function apply(): void
    {
        $this->moduleDataSetup->startSetup();

        $connection = $this->moduleDataSetup->getConnection();
        $table = $connection->getTableName('core_config_data');
        $this->migrateCheckoutType($connection, $table);
        $this->migrateConfiguration($connection, $table);

        $this->moduleDataSetup->endSetup();
    }

    /**
     * Migrate checkout type.
     *
     * @param AdapterInterface $connection
     * @param string $table
     * @return void
     */
    private function migrateCheckoutType(AdapterInterface $connection, string $table): void
    {
        $select = $connection->select()->from(
            $table,
            [
                'scope',
                'scope_id',
                'value',
            ]
        )->where(
            'path = ?',
            self::PATH_IS_ENABLED_SOURCE
        );
        $sources = $connection->fetchAll($select);
        foreach ($sources as $source) {
            $data = [
                'scope' => $source['scope'],
                'scope_id' => $source['scope_id'],
                'path' => self::PATH_IS_ENABLED_TARGET,
                'value' => (int)$source['value'] === self::SOURCE_TYPE ? 1 : 0,
            ];
            $connection->insertOnDuplicate($table, $data, ['value']);
        }
    }

    /**
     * Migrate module configuration.
     *
     * @param AdapterInterface $connection
     * @param string $table
     * @return void
     */
    private function migrateConfiguration(AdapterInterface $connection, string $table): void
    {
        foreach (self::PATHS_TO_MIGRATE as $source => $target) {
            $query = sprintf(
                "UPDATE `%s` SET `path` = '%s' WHERE (`path`  = '%s');",
                $table,
                $target,
                $source
            );
            $connection->query($query);
        }
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }
}
