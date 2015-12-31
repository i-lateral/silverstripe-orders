<?php
class GridFieldMapExistingAction implements GridField_ColumnProvider, GridField_ActionProvider
{
    
    /**
     * List of fields that will be mapped to the new object
     * 
     * @var Array
     */
    protected $map_fields = array();
    
    public function getMapFields()
    {
        return $this->map_fields;
    }
    
    public function setMapFields($map)
    {
        $this->map_fields = $map;
        return $this;
    }
    
    /**
     * If we want to add this object to our target object as an
     * association then set the association name below. 
     * 
     * @var String
     */
    protected $association = "CustomerID";
    
    public function getAssociation()
    {
        return $this->association;
    }
    
    public function setAssociation($association)
    {
        $this->association = $association;
        return $this;
    }
    
    public function augmentColumns($gridField, &$columns)
    {
        if (!in_array('Actions', $columns)) {
            $columns[] = 'Actions';
        }
    }
    
    public function getColumnAttributes($gridField, $record, $columnName)
    {
        return array('class' => 'col-buttons');
    }
    
    public function getColumnMetadata($gridField, $columnName)
    {
        if ($columnName == 'Actions') {
            return array('title' => '');
        }
    }
    
    public function getColumnsHandled($gridField)
    {
        return array('Actions');
    }
    
    public function getColumnContent($gridField, $record, $columnName)
    {
        $link = $gridField
            ->getForm()
            ->getController()
            ->Link("edit");
            
        $target = $gridField
            ->getForm()
            ->getRecord();
        
        $field = GridField_FormAction::create(
            $gridField,
            'Select'.$record->ID,
            'Select',
            "doselect",
            array(
                "RecordClassName" => $record->ClassName,
                "RecordID" => $record->ID,
                "TargetClassName" => $target->ClassName,
                "TargetID" => $target->ID,
                "Redirect" => $link
            )
        )->addExtraClass("ss-ui-action-constructive");
        
        return $field->Field();
    }
    
    public function getActions($gridField)
    {
        return array('doselect');
    }
    
    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if (
            array_key_exists("RecordID", $arguments) &&
            array_key_exists("RecordClassName", $arguments) &&
            array_key_exists("TargetClassName", $arguments) &&
            array_key_exists("TargetID", $arguments) &&
            $actionName == "doselect"
        ) {
            $target_class = $arguments["TargetClassName"];
            $record_class = $arguments["RecordClassName"];
            
            $target = $target_class::get()->byID($arguments["TargetID"]);
            $record = $record_class::get()->byID($arguments["RecordID"]);
            
            if ($target && $record) {
                foreach ($this->getMapFields() as $target_field => $record_field) {
                    $target->setCastedField($target_field, $record->$record_field);
                }
                
                // If we have an association setup, set it now
                $association = $this->association;
                
                if ($association) {
                    $target->{$association} = $record->ID;
                }
                
                $target->write();
                return Controller::curr()->redirect($arguments["Redirect"]);
            }
        }
        
        return Controller::curr()->getResponse()->setStatusCode(
            500,
            'Could not use selected object'
        );
    }
}
