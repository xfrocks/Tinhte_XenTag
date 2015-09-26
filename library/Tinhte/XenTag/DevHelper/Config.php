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
            'files' => array('data_writer' => false, 'model' => false, 'route_prefix_admin' => false, 'controller_admin' => false),
        ),
    );
    protected $_dataPatches = array(
        'xf_forum' => array(
            'tinhte_xentag_tags' => array('name' => 'tinhte_xentag_tags', 'type' => 'serialized'),
        ),
        'xf_page' => array(
            'tinhte_xentag_tags' => array('name' => 'tinhte_xentag_tags', 'type' => 'serialized'),
        ),
        'xf_tag' => array(
            'tinhte_xentag_staff' => array('name' => 'tinhte_xentag_staff', 'type' => 'boolean', 'default' => 0),
            'tinhte_xentag_title' => array('name' => 'tinhte_xentag_title', 'type' => 'string', 'length' => 255, 'default' => ''),
            'tinhte_xentag_description' => array('name' => 'tinhte_xentag_description', 'type' => 'string', 'default' => ''),
            'tinhte_xentag_url' => array('name' => 'tinhte_xentag_url', 'type' => 'string', 'default' => ''),
        ),
    );
    protected $_exportPath = '/Users/sondh/XenForo/Tinhte/XenTag';
    protected $_exportIncludes = array();
    protected $_exportExcludes = array();
    protected $_exportAddOns = array();
    protected $_exportStyles = array();
    protected $_options = array();

    /**
     * Return false to trigger the upgrade!
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
            array('primary_key_1', 'primary_key_2'), // or 'primary_key', both are okie
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