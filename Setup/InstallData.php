<?php

namespace NBOX\Shipping\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Catalog\Model\Product;

class InstallData implements InstallDataInterface
{
    private $eavSetupFactory;

    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        // Get the default attribute set ID
        $attributeSetId = $eavSetup->getDefaultAttributeSetId(Product::ENTITY);
        $groupName = 'Product Details';
        
        // Ensure the attribute group exists
        try {
            $groupId = $eavSetup->getAttributeGroupId(Product::ENTITY, $attributeSetId, $groupName);
        } catch (\Exception $e) {
            $eavSetup->addAttributeGroup(Product::ENTITY, $attributeSetId, $groupName, 99);
            $groupId = $eavSetup->getAttributeGroupId(Product::ENTITY, $attributeSetId, $groupName);
        }

        // Define attributes
        $attributes = [
            'length' => 'Length (cm)',
            'width' => 'Width (cm)',
            'height' => 'Height (cm)',
        ];

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

            // Assign attribute to group
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
}
