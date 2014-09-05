<?php
/**
 * Extension to add extra settings into siteconfig
 *
 * @package checkout
 * @author i-lateral (http://www.i-lateral.com)
 */
class CheckoutSiteConfig extends DataExtension {
    private static $db = array(
        'PaymentSuccessContent' => 'Text',
        'PaymentFailerContent'  => 'Text',
        'OrderPrefix'           => 'Varchar(9)',
        'TaxRate'               => "Decimal",
        "TaxPriceInclude"       => "Boolean",
        'TaxName'               => "Varchar"
    );

    private static $has_many = array(
        'PostageAreas'      => 'PostageArea',
        'Discounts'         => 'Discount',
        'PaymentMethods'    => 'PaymentMethod',
    );

    private static $defaults = array(
        "TaxPriceInclude" => true
    );

    /**
     * Determine if there is tax name (from site config), if so
     * include with the string determining if the price includes or exludes
     * tax and return both.
     *
     * @return String
     */
    public function getTaxString() {
        $return = "";

        if($this->owner->TaxName && $this->owner->TaxPriceInclude) {
            $return .= _t("Checkout.Including", "Including");
            $return .= " " . $this->owner->TaxName;
        } elseif($this->owner->TaxName && !$this->owner->TaxPriceInclude) {
            $return .= _t("Checkout.Excluding", "Excluding");
            $return .= " " . $this->owner->TaxName;
        }

        return $return;
    }

    public function updateCMSFields(FieldList $fields) {
        
        // Payment Methods
        $payment_table = GridField::create(
            'PaymentMethods',
            _t("CheckoutAdmin.PaymentMethods", "Payment Methods"),
            $this->owner->PaymentMethods(),
            GridFieldConfig::create()->addComponents(
                new GridFieldToolbarHeader(),
                new GridFieldAddNewButton('toolbar-header-right'),
                new GridFieldSortableHeader(),
                new GridFieldDataColumns(),
                new GridFieldPaginator(20),
                new GridFieldEditButton(),
                new GridFieldDeleteAction(),
                new GridFieldDetailForm()
            )
        );

        // setup compressed payment options
        $payment_fields = ToggleCompositeField::create(
            'PaymentSettings',
            _t("CheckoutAdmin.Payments","Payment Settings"),
            array(
                TextField::create(
                    'OrderPrefix',
                    _t("CheckoutAdmin.OrderPrefix","Add prefix to order numbers"),
                    null,
                    9
                )->setAttribute(
                    "placeholder",
                    _t("CheckoutAdmin.OrderPrefixPlaceholder", "EG 'abc'")
                ),
                
                TextAreaField::create(
                    'PaymentSuccessContent',
                    _t("CheckoutAdmin.PaymentSuccessContent", "Payment successfull content")
                )->setRows(4)
                ->setColumns(30)
                ->addExtraClass('stacked'),
                
                TextAreaField::create(
                    'PaymentFailerContent',
                    _t("CheckoutAdmin.PaymentFailerContent", "Payment failer content")
                )->setRows(4)
                ->setColumns(30)
                ->addExtraClass('stacked'),
                
                $payment_table
            )
        );

        // Add html description of how to edit contries
        $country_html = "<div class=\"field\">";
        $country_html .= "<p>First select valid countries using the 2 character ";
        $country_html .= "shortcode (see http://fasteri.com/list/2/short-names-of-countries-and-iso-3166-codes).</p>";
        $country_html .= "<p>You can add multiple countries seperating them with";
        $country_html .= "a comma or use a '*' for all countries.</p>";
        $country_html .= "</div>";

        $country_html_field = LiteralField::create("CountryDescription", $country_html);

        // Deal with product features
        $postage_field = new GridField(
            'PostageAreas',
            '',
            $this->owner->PostageAreas(),
            GridFieldConfig::create()
                ->addComponents(
                    new GridFieldButtonRow('before'),
                    new GridFieldToolbarHeader(),
                    new GridFieldTitleHeader(),
                    new GridFieldEditableColumns(),
                    new GridFieldDeleteAction(),
                    new GridFieldAddNewInlineButton('toolbar-header-left')
                )
        );

        // Add country dropdown to inline editing
        $postage_field
            ->getConfig()
            ->getComponentByType('GridFieldEditableColumns')
            ->setDisplayFields(array(
                'Title' => array(
                    'title' => 'Title',
                    'field' => 'TextField'
                ),
                'Country' => array(
                    'title' => 'ISO 3166 codes',
                    'field' => 'TextField'
                ),
                'ZipCode' => array(
                    'title' => 'Zip/Post Codes',
                    'field' => 'TextField'
                ),
                'Calculation'  => array(
                    'title' => 'Base unit',
                    'callback' => function($record, $column, $grid) {
                        return DropdownField::create(
                            $column,
                            "Based on",
                            singleton('PostageArea')
                                ->dbObject('Calculation')
                                ->enumValues()
                        )->setValue("Weight");
                    }
                ),
                'Unit' => array(
                    'title' => 'Unit (equals or above)',
                    'field' => 'NumericField'
                ),
                'Cost' => array(
                    'title' => 'Cost',
                    'field' => 'NumericField'
                )
            ));

        // Setup compressed postage options
        $postage_fields = ToggleCompositeField::create(
            'PostageFields',
            'Postage Options',
            array(
                $country_html_field,
                $postage_field
            )
        );


        // Setup compressed postage options
        $discount_fields = ToggleCompositeField::create(
            'DiscountFields',
            'Discounts',
            array(
                GridField::create(
                    'Discounts',
                    '',
                    $this->owner->Discounts(),
                    GridFieldConfig_RecordEditor::create()
                )
            )
        );

        // Compress tax fields
        $tax_fields = ToggleCompositeField::create(
            'TaxSettings',
            _t("CheckoutAdmin.TaxSettings", "Tax Settings"),
            array(
                NumericField::create('TaxRate'),
                TextField::create(
                    "TaxName",
                    _t("CheckoutAdmin.TaxName", "Name of your tax (EG 'VAT')")
                ),
                CheckboxField::create(
                    "TaxPriceInclude",
                    _t("CheckoutAdmin.TaxPriceInclude", "Show price including tax?")
                )
            )
        );

        // Add config sets
        $fields->addFieldToTab('Root.Checkout', $payment_fields);
        $fields->addFieldToTab('Root.Checkout', $postage_fields);
        $fields->addFieldToTab('Root.Checkout', $discount_fields);
        $fields->addFieldToTab('Root.Checkout', $tax_fields);
    }
}
