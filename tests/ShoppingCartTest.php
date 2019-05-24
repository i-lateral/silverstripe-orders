<?php
/**
 * Test functionality of Shopping Carts
 *
 * @package orders
 * @subpackage tests
 */
class ShoppingCartTest extends SapphireTest
{

    public function testGetClassName()
    {
        $cart = ShoppingCart::get();
        $this->assertEquals("ShoppingCart", $cart->getClassName());
    }

    public function testGetTitle()
    {
        $cart = ShoppingCart::get();
        $this->assertEquals("Shopping Cart", $cart->getTitle());
    }

    public function testGetMetaTitle()
    {
        $cart = ShoppingCart::get();
        $this->assertEquals("Shopping Cart", $cart->getMetaTitle());
    }

    public function testShouldCleanEstimates()
    {
        $curr = Config::inst()->get(Checkout::class, 'cron_cleaner');
        $siteconfig = SiteConfig::current_site_config();
        
        // Test after enabling cron cleaning and configure default time
        Checkout::config()->cron_cleaner = true;
        Estimate::config()->default_end = 300;
        $cart = ShoppingCart::get();

        $this->assertFalse($cart->shouldCleanEstimates());

        // Disable cron based cleaning
        Checkout::config()->cron_cleaner = false;

        $now = new DateTime(SS_Datetime::now()->format('Y-m-d H:i:s'));
        $siteconfig->LastEstimateClean = $now
            ->modify("-500 seconds")
            ->format('Y-m-d H:i:s');

        $this->assertTrue($cart->shouldCleanEstimates());

        $now = new DateTime(SS_Datetime::now()->format('Y-m-d H:i:s'));
        $siteconfig->LastEstimateClean = $now
            ->modify("-200 seconds")
            ->format('Y-m-d H:i:s');

        var_dump("here");
        $this->assertFalse($cart->shouldCleanEstimates());

        // Revert cron cleaner
        Checkout::config()->cron_cleaner = $curr;
    }
}