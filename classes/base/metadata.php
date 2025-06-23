<?php

namespace OB\Classes\Base;

class Metadata
{
    protected $name;
    protected $description;
    protected $type;
    protected $settings;

    public function __construct($name, $description, $type, $settings = [])
    {
        $this->name = $name;
        $this->description = $description;
        $this->type = $type;
        $this->settings = $settings;
        $this->db = \OBFDB::get_instance();

        // letters, numbers, underscores only for name
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $this->name)) {
            throw new \Exception('Invalid metadata name');
        }
    }

    /**
     * Return a string or array of strings to be included in the SELECT part of a query.
     */
    public function querySelect()
    {
        $default = $this->settings->default;
        if (is_array($default)) {
            $default = implode(',', $default);
        }

        return 'COALESCE(media.metadata_' . $this->name . ', "' . $this->db->escape($default) . '") as metadata_' . $this->name;
    }

    /**
     * Return a string or array of strings to be included in the SET part of a query.
     * This is used for adding and inserting items that use metadata.
     * TODO: This is not yet used by media_model.
     */
    public function querySet($value)
    {
        return 'metadata_' . $this->name . ' = "' . $this->db->escape($value) . '"';
    }

    /**
     * Process row after it has been fetched from the database.
     */
    public function processRow(&$row)
    {
    }
}
