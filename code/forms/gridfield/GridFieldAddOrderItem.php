<?php

/**
 * A specific gridfield field designed to allow the creation of a new
 * order item and that auto completes all fields from a pre-defined
 * object (default Product).
 *
 * @package orders
 *
 * @author ilateral <info@ilateral.co.uk>
 * @author Michael Strong <github@michaelstrong.co.uk>
**/
class GridFieldAddOrderItem implements GridField_ActionProvider, GridField_HTMLProvider {

	/**
	 * HTML Fragment to render the field.
	 *
	 * @var string
	 **/
	protected $targetFragment;



	/**
	 * Default field to create the dataobject by should be Title.
	 *
	 * @var string
	 **/
	protected $dataObjectField = "Title";
    
    /**
	 * Default field to create the dataobject from.
	 *
	 * @var string
	 **/
	protected $source_class = "Product";


    public function getSourceClass() {
        return $this->source_class;
    }
    
    
    public function setSourceClass($class) {
        $this->source_class = $class;
        return $this;
    }
    
    /**
	 * Fields that we try and find our source object based on
	 *
	 * @var array
	 **/
	protected $filter_fields = array(
        "StockID"
    );


    public function getFilterFields() {
        return $this->filter_fields;
    }
    
    
    public function setFilterFields($fields) {
        $this->filter_fields = $fields;
        return $this;
    }
    
    /**
	 * If filter fails, set this field when creating
	 *
	 * @var String
	 **/
	protected $create_field = "Title";


    public function getCreateField() {
        return $this->create_field;
    }
    
    
    public function setCreateField($field) {
        $this->create_field = $field;
        return $this;
    }
    
    /**
	 * Fields that we are mapping from the source object to our item
	 *
	 * @var array
	 **/
	protected $source_fields = array(
        "Title" => "Title",
        "StockID" => "StockID",
        "Price" => "Price",
        "TaxRate" => "TaxPercent"
    );


    public function getSourceFields() {
        return $this->source_fields;
    }
    
    
    public function setSourceFields($fields) {
        $this->source_fields = $fields;
        return $this;
    }

	public function __construct($targetFragment = 'before', $dataObjectField = "Title") {
		$this->targetFragment = $targetFragment;
		$this->dataObjectField = (string) $dataObjectField;
	}


	/**
	 * Provide actions to this component.
	 *
	 * @param $gridField GridField
	 *
	 * @return array
	 **/
	public function getActions($gridField) {
		return array("add");
	}


	/**
	 * Handles the add action for the given DataObject
	 *
	 * @param $gridFIeld GridFIeld
	 * @param $actionName string
	 * @param $arguments mixed
	 * @param $data array
	 **/
	public function handleAction(GridField $gridField, $actionName, $arguments, $data) {
		if($actionName == "add") {
			$dbField = $this->getDataObjectField();

			$objClass = $gridField->getModelClass();
            $source_class = $this->getSourceClass();
            
			$obj = new $objClass();
			if($obj->hasField($dbField)) {
				if($obj->canCreate()) {
                    $string = $data['gridfieldaddbydbfield'][$obj->ClassName][$dbField];
                
                    // First we see if the source class has a matched filter
                    $filter = array();
                    
                    foreach($this->getFilterFields() as $filter_field) {
                        $filter[$filter_field] = $string;
                    }
                    
                    $source_item = $source_class::get()
                        ->filter($filter)
                        ->first();
                        
                    if($source_item) {
                        foreach($this->getSourceFields() as $obj_field => $source_field) {
                            $obj->setCastedField(
                                $obj_field,
                                $source_item->$source_field
                            );
                        }
                    } else
                        $obj->setCastedField($this->getCreateField(), $string);
                    
                    $obj->setCastedField("Quantity", 1);
                    
					$id = $gridField->getList()->add($obj);
					if(!$id) {
						$gridField->setError(_t(
							"GridFieldAddOrderItem.AddFail", 
							"Unable to save {class} to the database.", 
							"Unable to add the DataObject.",
							array(
								"class" => get_class($obj)
							)), 
							"error"
						);
					}
				} else {
					return Security::permissionFailure(
						Controller::curr(),
						_t(
							"GridFieldAddOrderItem.PermissionFail",
							"You don't have permission to create a {class}.",
							"Unable to add the DataObject.",
							array(
								"class" => get_class($obj)
							)
						)
					);
				}
			} else {
				throw new UnexpectedValueException("Invalid field (" . $dbField . ") on  " . $obj->ClassName . ".");
			}
		}
	}



	/**
	 * Renders the TextField and add button to the GridField.
	 *
	 * @param $girdField GridField
	 *
	 * @return string HTML
	 **/
	public function getHTMLFragments($gridField) {
		$dataClass = $gridField->getList()->dataClass();
		$obj = singleton($dataClass);
		if(!$obj->canCreate()) return "";

		$dbField = $this->getDataObjectField();
        

		$textField = TextField::create("gridfieldaddbydbfield[" . $obj->ClassName . "][" . Convert::raw2htmlatt($dbField) . "]")
            ->setAttribute(
                "placeholder",
                _t(
                    "GridFieldAddOrderItem.Add",
                    "Add by {Filters} or {Title}",
                    array(
                        "Filters" => implode(",", $this->getFilterFields()),
                        "Title" => $this->getCreateField()
                    )
                )
            )->addExtraClass("no-change-track");

		$addAction = new GridField_FormAction(
            $gridField, 
			'add',
			_t("GridFieldAddOrderItem.Add","Add"), 
			'add', 
			'add'
		);
		$addAction->setAttribute('data-icon', 'add');

		// Start thinking about rending this back to the GF
		$forTemplate = new ArrayData(array());
		$forTemplate->Fields = new ArrayList();

		$forTemplate->Fields->push($textField);
		$forTemplate->Fields->push($addAction);

		return array(
			$this->targetFragment => $forTemplate->renderWith("GridFieldAddOrderItem")
		);
	}



	/**
	 * Returns the database field for which we'll add the new data object.
	 *
	 * @return string
	 **/
	public function getDataObjectField() {
		return $this->dataObjectField;
	}



	/**
	 * Set the database field.
	 *
	 * @param $field string
	 **/
	public function setDataObjectField($field) {
		$this->dataObjectField = (string) $field;
	}

}
