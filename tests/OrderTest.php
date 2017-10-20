<?php
/**
 * Test functionality of an order
 *
 * @package orders
 * @subpackage tests
 */
class OrderTest extends SapphireTest
{
	/**
	 * Add some scaffold order records
	 *
	 * @var string
	 * @config
	 */
	protected static $fixture_file = 'OrderTest.yml';

	/**
	 * Add some extra functionality on construction
	 *
	 * @return void
	 */	
	public function setUp()
    {
		parent::setUp();
	}

	/**
	 * Clean up after tear down
	 *
	 * @return void
	 */	
	public function tearDown()
    {
		parent::tearDown();
	}

	/**
	 * Test that the country is retrieved correctly and
	 * that billing and delivery addresses return as
	 * expected
	 *
	 * @return void
	 */
	public function testLocationDetails()
    {
		$order = $this->objFromFixture('Order', 'addressdetails');

		$bil_country = "United Kingdom";
		$del_country = "United Kingdom";
		$exp_billing = "123 Street Name,\nA Place,\nA City,\nAB12 3AB,\nGB";
		$exp_delivery = "321 Street Name,\nDelivery City,\nZX98 9XZ,\nGB";

		$this->assertEquals($bil_country, $order->getCountryFull());
		$this->assertEquals($del_country, $order->getDeliveryCountryFull());
		$this->assertEquals($exp_billing, $order->getBillingAddress());
		$this->assertEquals($exp_delivery, $order->getDeliveryAddress());
	}

	/**
	 * test that functions for changing statuses of an order
	 * behave correctly
	 *
	 * @return void
	 */
	public function testStatusDetails()
    {
		$order = $this->objFromFixture('Order', 'addressdetails');
		$order->markComplete("123456");

		$this->assertTrue($order->getPaid());
		$this->assertEquals("123456", $order->PaymentNo);
	}

	/**
	 * test that generation of the summary and summary HTML
	 * work as expected.
	 *
	 * @return void
	 */
	public function testItemSummary()
	{
		$order = $this->objFromFixture('Order', 'discount');

		$text = "2 x An item to discount;\n1 x Another item to discount;\n";
		$html = "2 x An item to discount;<br />\n1 x Another item to discount;<br />\n";

		$this->assertEquals($text, $order->ItemSummary);
		$this->assertEquals($html, $order->ItemSummaryHTML->RAW());
	}

	/**
	 * test that functions for setting and getting discounts
	 * work as expected
	 *
	 * @return void
	 */
	public function testDiscount()
    {
		$order = $this->objFromFixture('Order', 'standardnotax');

		$this->assertFalse($order->hasDiscount());
		$order->setDiscount("A Discount", 5.00);
		$this->assertTrue($order->hasDiscount());

		$this->assertEquals("A Discount", $order->Discount);
		$this->assertEquals(5.00, $order->DiscountAmount);
	}


	/**
	 * test that functions for setting and getting postage
	 * work as expected
	 *
	 * @return void
	 */
	public function testPostage()
    {
		$no_tax_order = $this->objFromFixture('Order', 'standardnotax');
		$tax_order = $this->objFromFixture('Order', 'standardtax');

		$no_tax_order->setPostage("Different Postage", 0.50);
		$tax_order->setPostage("Different Tax Postage", 1.00, 0.20);
		
		$this->assertEquals("Different Postage", $no_tax_order->PostageType);
		$this->assertEquals(0.50, $no_tax_order->PostageCost);
		$this->assertEquals(0, $no_tax_order->PostageTax);
		$this->assertEquals(0.50, $no_tax_order->Postage->RAW());

		$this->assertEquals("Different Tax Postage", $tax_order->PostageType);
		$this->assertEquals(1.00, $tax_order->PostageCost);
		$this->assertEquals(0.20, $tax_order->PostageTax);
		$this->assertEquals(1.00, $tax_order->Postage->RAW());
	}

	/**
	 * test that functions for calculating total amounts (such as)
	 * total items, total weight, etc.
	 *
	 * @return void
	 */
	public function testTotalCalculations()
    {
		$no_tax_order = $this->objFromFixture('Order', 'standardnotax');
		$tax_order = $this->objFromFixture('Order', 'standardtax');
		$discount_order = $this->objFromFixture('Order', 'discount');

		$this->assertEquals(2, $no_tax_order->TotalItems);
		$this->assertEquals(1, $no_tax_order->TotalWeight);
		$this->assertEquals(1, $tax_order->TotalItems);
		$this->assertEquals(0.75, $tax_order->TotalWeight);
		$this->assertEquals(3, $discount_order->TotalItems);
		$this->assertEquals(1.75, $discount_order->TotalWeight);
	}

	/**
	 * test that functions for calculating tax monitary info on
	 * an order are correct
	 *
	 * @return void
	 */
	public function testTaxCalculations()
    {
		$no_tax_order = $this->objFromFixture('Order', 'standardnotax');
		$tax_order_one = $this->objFromFixture('Order', 'standardtax');
		$tax_order_two = $this->objFromFixture('Order', 'complextax');
		$discount_order = $this->objFromFixture('Order', 'discount');

		$this->assertEquals(0, $no_tax_order->TaxTotal->RAW());
		$this->assertEquals(1.60, $tax_order_one->TaxTotal->RAW());
		$this->assertEquals(3.20, $tax_order_two->TaxTotal->RAW());
		$this->assertEquals(3.0, $discount_order->TaxTotal->RAW());
	}

	/**
	 * test that functions for calculating monitary info on
	 * an order are correct (such as tax, total, etc)
	 *
	 * @return void
	 */
	public function testCurrencyCalculations()
    {
		$no_tax_order = $this->objFromFixture('Order', 'standardnotax');
		$tax_order_one = $this->objFromFixture('Order', 'standardtax');
		$tax_order_two = $this->objFromFixture('Order', 'complextax');
		$discount_order = $this->objFromFixture('Order', 'discount');

		$this->assertEquals(11.98, $no_tax_order->SubTotal->RAW());
		$this->assertEquals(13.98, $no_tax_order->Total->RAW());
		$this->assertEquals(5.99, $tax_order_one->SubTotal->RAW());
		$this->assertEquals(9.59, $tax_order_one->Total->RAW());
		$this->assertEquals(13.97, $tax_order_two->SubTotal->RAW());
		$this->assertEquals(19.17, $tax_order_two->Total->RAW());
		$this->assertEquals(15.97, $discount_order->SubTotal->RAW());
		$this->assertEquals(17.97, $discount_order->Total->RAW());
	}
}