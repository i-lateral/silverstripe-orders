<?php

/**
 * An address that belongs to a member object. This allows us to define
 * more than one address that a user can have or send orders to.
 *
 * @package checkout
 * @author i-lateral (http://www.i-lateral.com)
 */
class MemberAddress extends DataObject
{

    public static $db = array(
        'Company'            => 'Varchar',
        'FirstName'         => 'Varchar',
        'Surname'           => 'Varchar',
        'Address1'          => 'Varchar',
        'Address2'          => 'Varchar',
        'City'              => 'Varchar',
        'State'             => 'Varchar',
        'PostCode'          => 'Varchar',
        'Country'           => 'Varchar',
        'Default'           => 'Boolean'
    );

    public static $has_one = array(
        "Owner" => "Member"
    );
    
    public static $summary_fields = array(
        "FirstName",
        "Surname",
        "Address1",
        "City",
        "State",
        "PostCode",
        "Default"
    );

    /**
     * Anyone logged in can create
     *
     * @return Boolean
     */
    public function canCreate($member = null)
    {
        if (!$member) {
            $member = Member::currentUser();
        }

        $extended = $this->extendedCan('canCreate', $member);
        if ($extended !== null) {
            return $extended;
        }

        if ($member) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Only creators or admins can view
     *
     * @return Boolean
     */
    public function canView($member = null)
    {
        if (!$member) {
            $member = Member::currentUser();
        }
        
        $extended = $this->extendedCan('canView', $member);
        if ($extended !== null) {
            return $extended;
        }

        if ($member && $this->OwnerID == $member->ID) {
            return true;
        } elseif ($member && Permission::checkMember($member->ID, array("ADMIN"))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Only order creators or admins can edit
     *
     * @return Boolean
     */
    public function canEdit($member = null)
    {
        if (!$member) {
            $member = Member::currentUser();
        }
        
        $extended = $this->extendedCan('canEdit', $member);
        if ($extended !== null) {
            return $extended;
        }
        
        if ($member && $this->OwnerID == $member->ID) {
            return true;
        } elseif ($member && Permission::checkMember($member->ID, array("ADMIN"))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Only creators or admins can delete
     *
     * @return Boolean
     */
    public function canDelete($member = null)
    {
        if (!$member) {
            $member = Member::currentUser();
        }
        
        $extended = $this->extendedCan('canDelete', $member);
        if ($extended !== null) {
            return $extended;
        }

        if ($member && $this->OwnerID == $member->ID) {
            return true;
        } elseif ($member && Permission::checkMember($member->ID, array("ADMIN"))) {
            return true;
        } else {
            return false;
        }
    }
}
