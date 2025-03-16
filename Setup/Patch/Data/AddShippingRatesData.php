<?php

namespace NBOX\Shipping\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

/**
 * Class AddShippingRatesData
 *
 * Data patch to add shipping rate attributes to products.
 */
class AddShippingRatesData implements DataPatchInterface
{
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * AddShippingRatesData constructor.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * Apply the patch to add custom shipping rate attributes.
     *
     * @return void
     */
    public function apply()
    {
        /** @var EavSetup $eavSetup */
        $setup = $this->moduleDataSetup;
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        $setup->startSetup();

        // Get the default attribute set ID for products
        $attributeSetId = $eavSetup->getDefaultAttributeSetId(Product::ENTITY);
        $groupName = 'Product Details';

        // Ensure the attribute group exists, create it if not
        try {
            $groupId = $eavSetup->getAttributeGroupId(Product::ENTITY, $attributeSetId, $groupName);
        } catch (\Exception $e) {
            // If the group doesn't exist, create it
            $eavSetup->addAttributeGroup(Product::ENTITY, $attributeSetId, $groupName, 99);
            $groupId = $eavSetup->getAttributeGroupId(Product::ENTITY, $attributeSetId, $groupName);
        }

        // Define attributes
        $attributes = [
            'length' => 'Length (cm)',
            'width' => 'Width (cm)',
            'height' => 'Height (cm)',
        ];

        // Add each attribute to the product entity
        foreach ($attributes as $code => $label) {
            $eavSetup->addAttribute(
                Product::ENTITY,
                $code,
                [
                    'type' => 'decimal',
                    'label' => $label,
                    'input' => 'text',
                    'required' => false,
                    'visible' => true,
                    'user_defined' => true,
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'group' => $groupName,
                    'note' => "Enter the $label in centimeters",
                    'sort_order' => 50,
                    'backend' => '',
                    'frontend' => '',
                    'default' => '',
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => true,
                    'is_searchable' => true,
                    'used_in_product_listing' => true,
                    'is_visible_on_front' => true,
                    'is_visible_in_admin' => true,
                ]
            );

            // Assign attribute to the group
            $eavSetup->addAttributeToGroup(
                Product::ENTITY,
                $attributeSetId,
                $groupId,
                $code,
                50
            );
        }

        $setup->endSetup();
    }

    /**
     * Get patch dependencies.
     *
     * @return array
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * Get patch aliases.
     *
     * @return array
     */
    public function getAliases()
    {
        return [];
    }
}
