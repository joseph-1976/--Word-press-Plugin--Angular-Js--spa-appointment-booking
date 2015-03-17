<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Database entity.
 */
abstract class AB_Entity
{

    // Protected properties.

    /**
     * Reference to global database object.
     * @var wpdb
     */
    protected $wpdb;

    /**
     * Name of table in database.
     * Must be defined in the child class.
     * @static
     * @var string
     */
    protected static $table_name = null;

    /**
     * Schema of entity fields in database.
     * Must be defined in the child class as
     * array(
     *     '[FIELD_NAME]' => array(
     *         'format'  => '[FORMAT]',
     *         'default' => '[DEFAULT_VALUE]',
     *     )
     * )
     * @static
     * @var array
     */
    protected static $schema = null;

    // Private properties.

    /**
     * Values of fields.
     * @var array
     */
    private $values = array();

    /**
     * Formats of fields.
     * @var array
     */
    private $formats = array();

    /**
     * Values loaded from the database.
     * @var boolean
     */
    private $loaded_values = null;


    // Public methods.

    /**
     * Constructor.
     */
    public function __construct()
    {
        /** @var WPDB $wpdb */
        global $wpdb;

        // Reference to global database object.
        $this->wpdb = $wpdb;

        // Initialize $values and $formats.
        foreach (static::$schema as $field_name => $options) {
            $this->values[$field_name] = array_key_exists('default', $options) ? $options['default'] : null;
            $this->formats[$field_name] = array_key_exists('format', $options) ? $options['format'] : '%s';
        }
    }

    /**
     * Set value to field.
     *
     * @param string $field
     * @param mixed $value
     * @throws InvalidArgumentException
     */
    public function set($field, $value)
    {
        if (!array_key_exists($field, $this->values)) {
            throw new InvalidArgumentException(sprintf('Trying to set unknown field "%s" for entity "%s"', $field, get_class($this)));
        }

        $this->values[$field] = $value;
    }

    /**
     * Get value of field.
     *
     * @param string $field
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function get($field)
    {
        if (!array_key_exists($field, $this->values)) {
            throw new InvalidArgumentException(sprintf('Trying to get unknown field "%s" for entity "%s"', $field, get_class($this)));
        }

        return $this->values[$field];
    }

    /**
     * Magic set method.
     *
     * @param string $field
     * @param mixed $value
     */
    public function __set($field, $value)
    {
        $this->set($field, $value);
    }

    /**
     * Magic get method.
     *
     * @param string $field
     * @return mixed
     */
    public function __get($field)
    {
        return $this->get($field);
    }

    /**
     * Load entity from database by ID.
     *
     * @param integer $id
     * @return boolean
     */
    public function load($id)
    {
        return $this->loadBy(array('id' => $id));
    }

    /**
     * Load entity from database by fields values.
     *
     * @param array $fields
     * @return bool
     */
    public function loadBy(array $fields)
    {
        // Prepare WHERE clause.
        $where = array();
        $values = array();
        foreach ($fields as $field => $value) {
            if ($value === null) {
                $where[] = sprintf('`%s` IS NULL', $field);
            } else {
                $where[] = sprintf('`%s` = %s', $field, $this->formats[$field]);
                $values[] = $value;
            }
        }

        $query = sprintf(
            'SELECT * FROM `%s` WHERE %s LIMIT 1',
            static::$table_name,
            implode(' AND ', $where)
        );

        $row = $this->wpdb->get_row(
            empty ($values) ? $query : $this->wpdb->prepare($query, $values)
        );

        if ($row) {
            $this->setData($row);
            $this->loaded_values = $this->values;
        } else {
            $this->loaded_values = null;
        }

        return $this->isLoaded();
    }

    /**
     * Check whether the entity was loaded from the database or not.
     *
     * @return bool
     */
    public function isLoaded()
    {
        return $this->loaded_values !== null;
    }

    /**
     * Set values to fields.
     * The method can be used to update only some fields.
     *
     * @param array|object $data
     * @param bool $overwrite_loaded_values
     */
    public function setData($data, $overwrite_loaded_values = false)
    {
        if (is_array($data) || $data instanceof stdClass) {
            foreach ($data as $field => $value) {
                if (array_key_exists($field, $this->values)) {
                    $this->values[$field] = $value;
                }
            }

            // This parameter is used by the entity manager.
            if ($overwrite_loaded_values) {
                $this->loaded_values = $this->values;
            }
        }
    }

    /**
     * Get values of fields as array.
     *
     * @return array
     */
    public function getData()
    {
        return $this->values;
    }

    /**
     * Get modified fields with initial values.
     *
     * @return array
     */
    public function getModified()
    {
        return array_diff_assoc($this->loaded_values ?: array(), $this->values);
    }

    /**
     * Save entity to database.
     *
     * @return int|false
     */
    public function save()
    {

        // Prepare query data.
        $set = array();
        $values = array();
        foreach ($this->values as $field => $value) {

            if ($field == 'id') {


                continue;
            }
            if ($value === null) {
                $set[] = sprintf('`%s` = NULL', $field);
            } else {
                $set[] = sprintf('`%s` = %s', $field, $this->formats[$field]);
                $values[] = $value;
            }

        }

        // Run query.

        if ($this->values['id']) {
            $res = $this->wpdb->query($this->wpdb->prepare(
                sprintf(
                    'UPDATE `%s` SET %s WHERE `id` = %d',
                    static::$table_name,
                    implode(', ', $set),
                    $this->values['id']
                ),
                $values
            ));
        } else {

            $res = $this->wpdb->query($this->wpdb->prepare(
                sprintf(
                    'INSERT INTO `%s` SET %s',
                    static::$table_name,
                    implode(', ', $set)
                ),
                $values
            ));
            if ($res) {

                $this->values['id'] = $this->wpdb->insert_id;
            }

        }

        if ($res) {
            // Update loaded values.
            $this->loaded_values = $this->values;
        }

        return $res;

    }

    /**
     * Delete entity from database.
     *
     * @return int|false
     */
    public function delete()
    {
        if ($this->values['id']) {
            return $this->wpdb->delete(static::$table_name, array('id' => $this->values['id']), array('%d'));
        }

        return false;
    }

    /**
     * Get table name.
     *
     * @static
     * @return string
     */
    public static function getTableName()
    {
        return static::$table_name;
    }

    /**
     * Get schema.
     *
     * @static
     * @return array
     */
    public static function getSchema()
    {
        return static::$schema;
    }
}
