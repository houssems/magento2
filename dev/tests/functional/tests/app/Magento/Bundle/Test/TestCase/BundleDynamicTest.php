<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Bundle\Test\TestCase;

use Magento\Bundle\Test\Fixture\Bundle;
use Mtf\Factory\Factory;
use Mtf\TestCase\Functional;

/**
 * Class BundleDynamicTest
 * Bundle product dynamic test
 */
class BundleDynamicTest extends Functional
{
    /* tags */
    const TEST_TYPE = 'acceptance_test';
    /* end tags */

    /**
     * Login into backend area before test
     *
     * @return void
     */
    protected function setUp()
    {
        Factory::getApp()->magentoBackendLoginUser();
    }

    /**
     * Creating bundle (dynamic) product and assigning it to the category
     *
     * @ZephyrId MAGETWO-12702
     * @return void
     */
    public function testCreate()
    {
        //Data
        $bundle = Factory::getFixtureFactory()->getMagentoBundleBundleDynamic();
        $bundle->switchData('bundle');
        //Pages & Blocks
        $manageProductsGrid = Factory::getPageFactory()->getCatalogProductIndex();
        $createProductPage = Factory::getPageFactory()->getCatalogProductNew();
        $productForm = $createProductPage->getProductForm();
        //Steps
        $manageProductsGrid->open();
        $manageProductsGrid->getGridPageActionBlock()->addProduct('bundle');
        $category = $bundle->getCategories()['category'];
        $productForm->fill($bundle, null, $category);
        $createProductPage->getFormPageActions()->save();
        //Verification
        $createProductPage->getMessagesBlock()->waitSuccessMessage();
        // Flush cache
        $cachePage = Factory::getPageFactory()->getAdminCache();
        $cachePage->open();
        $cachePage->getActionsBlock()->flushMagentoCache();
        $cachePage->getMessagesBlock()->waitSuccessMessage();
        //Verification
        $this->assertOnGrid($bundle);
        $this->assertOnCategory($bundle);
    }

    /**
     * Assert existing product on admin product grid
     *
     * @param Bundle $product
     * @return void
     */
    protected function assertOnGrid($product)
    {
        $productGridPage = Factory::getPageFactory()->getCatalogProductIndex();
        $productGridPage->open();
        $gridBlock = $productGridPage->getProductGrid();
        $this->assertTrue($gridBlock->isRowVisible(['sku' => $product->getSku()]));
    }

    /**
     * Checking the product on the category page
     *
     * @param Bundle $product
     * @return void
     */
    protected function assertOnCategory($product)
    {
        //Pages
        $frontendHomePage = Factory::getPageFactory()->getCmsIndexIndex();
        $categoryPage = Factory::getPageFactory()->getCatalogCategoryView();
        $productPage = Factory::getPageFactory()->getCatalogProductView();
        //Steps
        $frontendHomePage->open();
        $frontendHomePage->getTopmenu()->selectCategoryByName($product->getCategoryName());
        //Verification on category product list
        $productListBlock = $categoryPage->getListProductBlock();
        $this->assertTrue(
            $productListBlock->isProductVisible($product->getName()),
            'Product "' . $product->getName() . '" is absent on category page'
        );
        $productListBlock->openProductViewPage($product->getName());
        //Verification on product detail page
        $productViewBlock = $productPage->getViewBlock();
        $this->assertSame($product->getName(), $productViewBlock->getProductName());
        $this->assertEquals(
            $product->getProductPrice(),
            [
                'price_from' => $productViewBlock->getPriceBlock()->getPriceFrom(),
                'price_to' => $productViewBlock->getPriceBlock()->getPriceTo()
            ]
        );
        $expectedOptions = $product->getBundleOptions();
        $actualOptions = $productViewBlock->getOptions($product)['bundle_options'];
        foreach ($actualOptions as $key => $actualOption) {
            $this->assertContains($expectedOptions[$key]['title'], $actualOption);
        }
    }
}