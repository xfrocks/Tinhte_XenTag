<?php

class Tinhte_XenTag_DevHelper_Config extends DevHelper_Config_Base
{
    protected $_dataClasses = array(
        'tag_watch' => array(
            'name' => 'tag_watch',
            'camelCase' => 'TagWatch',
            'camelCaseWSpace' => 'Tag Watch',
            'fields' => array(
                'user_id' => array('name' => 'user_id', 'type' => 'uint', 'required' => true),
                'tag_id' => array('name' => 'tag_id', 'type' => 'uint', 'required' => true),
                'send_alert' => array('name' => 'send_alert', 'type' => 'uint', 'required' => true, 'default' => 0),
                'send_email' => array('name' => 'send_email', 'type' => 'uint', 'required' => true, 'default' => 0),
            ),
            'id_field' => 'n/a',
            'title_field' => 'n/a',
            'primaryKey' => array('tag_id', 'user_id'),
            'indeces' => array(
                'user_id' => array('name' => 'user_id', 'fields' => array('user_id'), 'type' => 'NORMAL'),
            ),
            'files' => array(
                'data_writer' => false,
                'model' => false,
                'route_prefix_admin' => false,
                'controller_admin' => false,
            ),
        ),
    );
    protected $_dataPatches = array(
        'xf_forum' => array(
            'tinhte_xentag_tags' => array('name' => 'tinhte_xentag_tags', 'type' => 'serialized'),
        ),
        'xf_page' => array(
            'tinhte_xentag_tags' => array('name' => 'tinhte_xentag_tags', 'type' => 'serialized'),
        ),
    );
    protected $_exportPath = '/Users/sondh/XenForo/Tinhte/XenTag';
    protected $_exportIncludes = array();

    /**
     * Return false to trigger the upgrade!
     * common use methods:
     *    public function addDataClass($name, $fields = array(), $primaryKey = false, $indeces = array())
     *    public function addDataPatch($table, array $field)
     *    public function setExportPath($path)
     **/
    protected function _upgrade()
    {
        return true; // remove this line to trigger update

        /*
        $this->addDataClass(
                'name_here',
                array( // fields
                        'field_here' => array(
                                'type' => 'type_here',
                                // 'length' => 'length_here',
                                // 'required' => true,
                                // 'allowedValues' => array('value_1', 'value_2'),
                                // 'default' => 0,
                                // 'autoIncrement' => true,
                        ),
                        // other fields go here
                ),
                'primary_key_field_here',
                array( // indeces
                        array(
                                'fields' => array('field_1', 'field_2'),
                                'type' => 'NORMAL', // UNIQUE or FULLTEXT
                        ),
                ),
        );
        */
    }
}