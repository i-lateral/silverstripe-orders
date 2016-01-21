<?php
/**
 * Postage objects list available postage costs and destination locations
 *
 * @author morven
 */
class PostageArea extends DataObject
{

    private static $db = array(
        "Title"         => "Varchar",
        "Country"       => "Varchar(255)",
        "ZipCode"       => "Varchar(255)",
        "Calculation"   => "Enum('Price,Weight,Items','Weight')",
        "Unit"          => "Decimal",
        "Cost"          => "Decimal",
        "Tax"           => "Decimal"
    );

    private static $has_one = array(
        "Site"          => "SiteConfig"
    );
    
    private static $casting = array(
        "Total"          => "Currency"
    );

    /**
     * Get the total cost including tax
     * 
     * @return Decimal
     */
    public function Total()
    {
        if ($this->Cost && $this->Tax) {
            return $this->Cost + (($this->Cost / 100) * $this->Tax);
        }
        
        return $this->Cost;
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
