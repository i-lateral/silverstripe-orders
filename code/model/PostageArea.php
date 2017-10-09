<?php
/**
 * Postage objects list available postage costs and destination locations
 *
 * @author Mo <morven@ilateral.co.uk>
 */
class PostageArea extends DataObject
{

    private static $db = array(
        "Title"         => "Varchar",
        "Country"       => "Varchar(255)",
        "ZipCode"       => "Text",
        "Calculation"   => "Enum('Price,Weight,Items','Weight')",
        "Unit"          => "Decimal",
        "Cost"          => "Decimal",
        "Tax"           => "Decimal"
    );

    private static $has_one = array(
        "Site"          => "SiteConfig"
    );
    
    private static $casting = array(
        "TaxAmount"     => "Currency",
        "Total"         => "Currency"
    );

    /**
     * Get the amount of tax for this postage object.
     *
     * @return Float
     */
    public function getTaxAmount()
    {   
        if ($this->Cost && $this->Tax) {
            return Checkout::round_up((($this->Cost / 100) * $this->Tax), 2);
        } else {
            return 0;
        }
    }

    /**
     * Get the total cost including tax
     * 
     * @param int $decimal_size Should we round this number to a
     *             specific size? If set will round the output. 
     * @return Float
     */
    public function Total($decimal_size = null)
    {
        if ($this->Cost && $this->Tax) {
            $cost = $this->Cost + $this->getTaxAmount();
        } else {
            $cost = $this->Cost;
        }
        
        if($decimal_size) {
            $cost = number_format($cost, $decimal_size);
        }
        
        return $cost;
    }

    public function canView($member = null)
    {
        $extended = $this->extendedCan('canView', $member);
        if ($extended !== null) {
            return $extended;
        }
        
        return true;
    }
    
    public function canCreate($member = null)
    {
        $extended = $this->extendedCan('canCreate', $member);
        if ($extended !== null) {
            return $extended;
        }
        
        return true;
    }

    public function canEdit($member = null)
    {
        $extended = $this->extendedCan('canEdit', $member);
        if ($extended !== null) {
            return $extended;
        }
        
        return true;
    }

    public function canDelete($member = null)
    {
        $extended = $this->extendedCan('canDelete', $member);
        if ($extended !== null) {
            return $extended;
        }
        
        return true;
    }
}
