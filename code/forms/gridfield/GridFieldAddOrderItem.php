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
class GridFieldAddOrderItem implements GridField_ActionProvider, GridField_HTMLProvider, GridField_URLHandler
{

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
     * @var string SSViewer template to render the results presentation
     */
    protected $results_format = '$Title';
    
    /**
     * Default field to create the dataobject from.
     *
     * @var string
     **/
    protected $source_class = "Product";


    public function getSourceClass()
    {
        return $this->source_class;
    }
    
    
    public function setSourceClass($class)
    {
        $this->source_class = $class;
        return $this;
    }
    
    /**
     * When we check for existing items, should we check based on all
     * filters or any of the chosen (setting this to true uses 
     * $list->filter() where as false uses $list->filterAny())
     * 
     * @var boolean
     */
    protected $strict_filter = true;

    /**
     * Getter for strict_filter param
     *
     * @return boolean
     */
    public function getStrictFilter()
    {
        return $this->strict_filter;
    }
    
    /**
     * Setter for strict_filter param
     *
     * @param boolean $bool
     * @return void
     */
    public function setStrictFilter($bool)
    {
        $this->strict_filter = $bool;
        return $this;
    }

    /**
     * Fields that we try and find our source object based on
     *
     * @var array
     **/
    protected $filter_fields = array(
        "Title",
        "StockID"
    );


    public function getFilterFields()
    {
        return $this->filter_fields;
    }
    
    
    public function setFilterFields($fields)
    {
        $this->filter_fields = $fields;
        return $this;
    }
    
    /**
     * Fields that we use to filter items for our autocomplete
     *
     * @var array
     **/
    protected $autocomplete_fields = array(
        "Title",
        "StockID"
    );


    public function getAutocompleteFields()
    {
        return $this->autocomplete_fields;
    }
    
    
    public function setAutocompleteFields($fields)
    {
        $this->autocomplete_fields = $fields;
        return $this;
    }
    
    /**
     * If filter fails, set this field when creating
     *
     * @var String
     **/
    protected $create_field = "Title";


    public function getCreateField()
    {
        return $this->create_field;
    }
    
    
    public function setCreateField($field)
    {
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


    public function getSourceFields()
    {
        return $this->source_fields;
    }
    
    
    public function setSourceFields($fields)
    {
        $this->source_fields = $fields;
        return $this;
    }
    
    /**
     * Number of results to appear in autocomplete
     * 
     * @var int
     */
    protected $results_limit = 20;
    
    public function getResultsLimit()
    {
        return $this->results_limit;
    }
    
    
    public function setResultsLimit($fields)
    {
        $this->results_limit = $fields;
        return $this;
    }

    public function __construct($targetFragment = 'before', $dataObjectField = "Title")
    {
        $this->targetFragment = $targetFragment;
        $this->dataObjectField = (string) $dataObjectField;
    }
    
    /**
     *
     * @param GridField $gridField
     * @return array
     */
    public function getURLHandlers($gridField)
    {
        return array(
            'search' => 'doSearch',
        );
    }


    /**
     * Provide actions to this component.
     *
     * @param $gridField GridField
     *
     * @return array
     **/
    public function getActions($gridField)
    {
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
    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if ($actionName == "add") {
            
            // Get our submitted fields and object class
            $dbField = $this->getDataObjectField();
            $objClass = $gridField->getModelClass();
            $source_class = $this->getSourceClass();
            $source_item = null;
            $filter = array();

            // Has the user used autocomplete
            if (isset($data['relationID']) && $data['relationID']) {
                $id = $data['relationID'];
            } else {
                $id = null;
            }
            
            $obj = new $objClass();
            
            // Is this a valid field
            if (!$obj->hasField($dbField)) {
                throw new UnexpectedValueException("Invalid field (" . $dbField . ") on  " . $obj->ClassName . ".");
            }
            
            // If we have an ID try and get an existing object then
            // check if we have a copy in items
            if ($id) {
                $source_item = $source_class::get()->byID($id);
                
                foreach ($this->getFilterFields() as $filter_field) {
                    $filter[$filter_field] = $source_item->$filter_field;
                }
            } else {
                // Generate the filter we need to use
                $string = $data['gridfieldaddbydbfield'];
                
                foreach ($this->getFilterFields() as $filter_field) {
                    $filter[$filter_field] = $string;
                }
            }
            
            // First check if we already have an object or if we need to
            // create one
            if ($this->getStrictFilter()) {
                $existing_obj = $gridField
                    ->getList()
                    ->filter($filter)
                    ->first();
            } else {
                $existing_obj = $gridField
                    ->getList()
                    ->filterAny($filter)
                    ->first();
            }
            
            if ($existing_obj) {
                $obj = $existing_obj;
            }
        
            if ($obj->ID && $obj->canEdit()) {
                // An existing record and can edit, update quantity
                $curr_qty = ($obj->Quantity) ? $obj->Quantity : 0;
                
                $obj->setCastedField(
                    "Quantity",
                     $curr_qty + 1
                );
                
                $id = $gridField->getList()->add($obj);
            }
            
            if (!$obj->ID && $obj->canCreate()) {
                // If source item not set, try and get one or get a 
                // an existing record
                if (!$source_item) {
                    $source_item = $source_class::get()
                        ->filterAny($filter)
                        ->first();
                }
                    
                if ($source_item) {
                    foreach ($this->getSourceFields() as $obj_field => $source_field) {
                        $obj->setCastedField(
                            $obj_field,
                            $source_item->$source_field
                        );
                    }
                } else {
                    $obj->setCastedField($this->getCreateField(), $string);
                }
                
                $obj->setCastedField("Quantity", 1);
                
                $id = $gridField->getList()->add($obj);
            }
            
            if (!$id) {
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
            
            // Finally, issue a redirect to update totals
            $controller = Controller::curr();
    
            $response = $controller->response;
            $response->addHeader('X-Pjax', 'Content');
            $response->addHeader('X-Reload', true);
            
            return $controller->redirect($gridField->getForm()->controller->Link(), 302);
        }
    }



    /**
     * Renders the TextField and add button to the GridField.
     *
     * @param $girdField GridField
     *
     * @return string HTML
     **/
    public function getHTMLFragments($gridField)
    {        
        $dataClass = $gridField->getList()->dataClass();
        $obj = singleton($dataClass);

        if (!$obj->canCreate()) {
            return "";
        }

        $text_field = TextField::create("gridfieldaddbydbfield")
            ->setAttribute(
                "placeholder",
                _t(
                    "GridFieldAddOrderItem.TypeToAdd",
                    "Type to add by {Filters} or {Title}",
                    "Inform the user what to add based on",
                    array(
                        "Filters" => implode(", ", $this->getFilterFields()),
                        "Title" => $this->getCreateField()
                    )
                )
            )->addExtraClass("relation-search no-change-track")
            ->setAttribute(
                'data-search-url',
                Controller::join_links($gridField->Link('search'))
            );

        $find_action = new GridField_FormAction(
            $gridField,
            'gridfield_relationfind',
			_t('GridField.Find', "Find"), 'find', 'find'
        );
		$find_action->setAttribute('data-icon', 'relationfind');

        $add_action = new GridField_FormAction(
            $gridField,
            'gridfield_orderitemadd',
            _t("GridFieldAddOrderItem.Add", "Add"),
            'add',
            'add'
        );
        $add_action->setAttribute('data-icon', 'add');

        // Start thinking about rending this back to the GF
        $fields = new ArrayList();

        $fields->push($text_field);
        $fields->push($find_action);
        $fields->push($add_action);
        
        $forTemplate = new ArrayData(array());
        $forTemplate->Fields = $fields;

        return array(
            $this->targetFragment => $forTemplate->renderWith("GridFieldAddOrderItem")
        );
    }
    
    /**
     * Returns a json array of a search results that can be used by for
     * example Jquery.ui.autosuggestion
     *
     * @param GridField $gridField
     * @param SS_HTTPRequest $request
     */
    public function doSearch($gridField, $request)
    {
        $product_class = $this->getSourceClass();
        $params = array();
        
        // Do we have filter fields setup?
        if ($this->getAutocompleteFields()) {
            $search_fields = $this->getAutocompleteFields();
        } else {
            $search_fields = $this->scaffoldSearchFields($product_class);
        }
        
        if (!$search_fields) {
            throw new LogicException(
                sprintf('GridFieldAddExistingAutocompleter: No searchable fields could be found for class "%s"',
                $product_class)
            );
        }
        
        foreach ($search_fields as $search_field) {
            $name = (strpos($search_field, ':') !== false) ? $search_field : "$search_field:StartsWith";
            $params[$name] = $request->getVar('gridfieldaddbydbfield');
        }
        
        $results = DataList::create($product_class)
            ->filterAny($params)
            ->sort(strtok($search_fields[0], ':'), 'ASC')
            ->limit($this->getResultsLimit());

        $json = array();
        
        $originalSourceFileComments = Config::inst()->get('SSViewer', 'source_file_comments');
        Config::inst()->update('SSViewer', 'source_file_comments', false);
        
        foreach ($results as $result) {
            $json[$result->ID] = html_entity_decode(SSViewer::fromString($this->results_format)->process($result));
        }
        
        Config::inst()->update('SSViewer', 'source_file_comments', $originalSourceFileComments);
        
        return Convert::array2json($json);
    }



    /**
     * Returns the database field for which we'll add the new data object.
     *
     * @return string
     **/
    public function getDataObjectField()
    {
        return $this->dataObjectField;
    }



    /**
     * Set the database field.
     *
     * @param $field string
     **/
    public function setDataObjectField($field)
    {
        $this->dataObjectField = (string) $field;
    }
}
