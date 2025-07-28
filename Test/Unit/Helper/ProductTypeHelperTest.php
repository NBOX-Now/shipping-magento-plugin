<?php
declare(strict_types=1);

namespace Nbox\Shipping\Test\Unit\Helper;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\App\Helper\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Psr\Log\LoggerInterface;
use Nbox\Shipping\Helper\ProductTypeHelper;

/**
 * Unit test for ProductTypeHelper
 */
class ProductTypeHelperTest extends TestCase
{
    /**
     * @var ProductTypeHelper
     */
    private $productTypeHelper;

    /**
     * @var MockObject|Context
     */
    private $contextMock;

    /**
     * @var MockObject|ProductRepositoryInterface
     */
    private $productRepositoryMock;

    /**
     * @var MockObject|LoggerInterface
     */
    private $loggerMock;

    /**
     * @var MockObject|ProductInterface
     */
    private $productMock;

    /**
     * @var MockObject|AbstractType
     */
    private $productTypeMock;

    /**
     * Set up test fixtures
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->productMock = $this->createMock(ProductInterface::class);
        $this->productTypeMock = $this->createMock(AbstractType::class);

        $this->productTypeHelper = new ProductTypeHelper(
            $this->contextMock,
            $this->productRepositoryMock,
            $this->loggerMock
        );
    }

    /**
     * Test that virtual products are identified as non-shippable
     */
    public function testIsShippableProductWithVirtualProduct()
    {
        $this->productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn('virtual');

        $result = $this->productTypeHelper->isShippableProduct($this->productMock);

        $this->assertFalse($result, 'Virtual products should not be shippable');
    }

    /**
     * Test that downloadable products are identified as non-shippable
     */
    public function testIsShippableProductWithDownloadableProduct()
    {
        $this->productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn('downloadable');

        $result = $this->productTypeHelper->isShippableProduct($this->productMock);

        $this->assertFalse($result, 'Downloadable products should not be shippable');
    }

    /**
     * Test that grouped products are identified as non-shippable
     */
    public function testIsShippableProductWithGroupedProduct()
    {
        $this->productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn('grouped');

        $result = $this->productTypeHelper->isShippableProduct($this->productMock);

        $this->assertFalse($result, 'Grouped products should not be shippable');
    }

    /**
     * Test that simple products with weight are shippable
     */
    public function testIsShippableProductWithSimpleProductHasWeight()
    {
        $this->productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn('simple');

        $this->productMock->expects($this->once())
            ->method('getTypeInstance')
            ->willReturn($this->productTypeMock);

        $this->productTypeMock->expects($this->once())
            ->method('hasWeight')
            ->willReturn(true);

        $result = $this->productTypeHelper->isShippableProduct($this->productMock);

        $this->assertTrue($result, 'Simple products with weight should be shippable');
    }

    /**
     * Test that simple products without weight are not shippable
     */
    public function testIsShippableProductWithSimpleProductNoWeight()
    {
        $this->productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn('simple');

        $this->productMock->expects($this->once())
            ->method('getTypeInstance')
            ->willReturn($this->productTypeMock);

        $this->productTypeMock->expects($this->once())
            ->method('hasWeight')
            ->willReturn(false);

        $result = $this->productTypeHelper->isShippableProduct($this->productMock);

        $this->assertFalse($result, 'Simple products without weight should not be shippable');
    }

    /**
     * Test that configurable products are shippable
     */
    public function testIsShippableProductWithConfigurableProduct()
    {
        $this->productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn('configurable');

        $this->productMock->expects($this->once())
            ->method('getTypeInstance')
            ->willReturn($this->productTypeMock);

        $this->productTypeMock->expects($this->once())
            ->method('hasWeight')
            ->willReturn(true);

        $result = $this->productTypeHelper->isShippableProduct($this->productMock);

        $this->assertTrue($result, 'Configurable products should be shippable');
    }

    /**
     * Test isVirtualProduct method
     */
    public function testIsVirtualProduct()
    {
        $this->productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn('virtual');

        $result = $this->productTypeHelper->isVirtualProduct($this->productMock);

        $this->assertTrue($result, 'Virtual products should be identified as virtual');
    }

    /**
     * Test getNonShippableProductTypes method
     */
    public function testGetNonShippableProductTypes()
    {
        $expected = ['virtual', 'downloadable', 'grouped'];
        $result = $this->productTypeHelper->getNonShippableProductTypes();

        $this->assertEquals($expected, $result, 'Should return correct non-shippable product types');
    }

    /**
     * Test getShippableProductTypes method
     */
    public function testGetShippableProductTypes()
    {
        $expected = ['simple', 'configurable', 'bundle'];
        $result = $this->productTypeHelper->getShippableProductTypes();

        $this->assertEquals($expected, $result, 'Should return correct shippable product types');
    }

    /**
     * Test isShippableProductType method
     */
    public function testIsShippableProductType()
    {
        $this->assertTrue($this->productTypeHelper->isShippableProductType('simple'));
        $this->assertTrue($this->productTypeHelper->isShippableProductType('configurable'));
        $this->assertTrue($this->productTypeHelper->isShippableProductType('bundle'));
        
        $this->assertFalse($this->productTypeHelper->isShippableProductType('virtual'));
        $this->assertFalse($this->productTypeHelper->isShippableProductType('downloadable'));
        $this->assertFalse($this->productTypeHelper->isShippableProductType('grouped'));
    }

    /**
     * Test exception handling in isShippableProduct
     */
    public function testIsShippableProductWithException()
    {
        $this->productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn('simple');

        $this->productMock->expects($this->once())
            ->method('getTypeInstance')
            ->willThrowException(new \Exception('Test exception'));

        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with(
                $this->stringContains('Error checking weight capability'),
                $this->arrayHasKey('exception')
            );

        $result = $this->productTypeHelper->isShippableProduct($this->productMock);

        $this->assertTrue($result, 'Should default to shippable when exception occurs');
    }
}