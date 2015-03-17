<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

final class AB_EntityManager {

    private static $instances = array();

    /**
     * Entity class name.
     * @var string
     */
    private $entity_name = null;

    /**
     * Name of table in database.
     * @var string
     */
    private $table_name = null;

    /**
     * Formats of fields.
     * @var array
     */
    private $formats = array();

    /**
     * Constructor.
     *
     * @param string $entity_name
     */
    private function __construct( $entity_name ) {
        /** @var WPDB $wpdb */
        global $wpdb;

        // Reference to global database object.
        $this->wpdb = $wpdb;

        $this->entity_name = $entity_name;
        $this->table_name  = $entity_name::getTableName();

        // Initialize $formats.
        foreach ( $entity_name::getSchema() as $field_name => $options ) {
            $this->formats[ $field_name ] = array_key_exists( 'format', $options ) ? $options[ 'format' ] : '%s';
        }
    }

    /**
     * Get instance of entity manager.
     *
     * @param string $entity_name
     * @return AB_EntityManager
     */
    static public function getInstance( $entity_name ) {
        if ( !array_key_exists( $entity_name, self::$instances ) ) {
            self::$instances[ $entity_name ] = new self( $entity_name );
        }

        return self::$instances[ $entity_name ];
    }

    /**
     * Find all entities.
     *
     * @param array $order_by
     * @return array
     */
    public function findAll( array $order_by = array() ) {
        return $this->findBy( array(), $order_by );
    }

    /**
     * Find entities in database by fields values.
     *
     * @param array $fields
     * @param array $order_by
     * @return array
     */
    public function findBy( array $fields, array $order_by = array() ) {
        $result = array();

        // Prepare WHERE clause.
        $where = array();
        $values = array();
        foreach ( $fields as $field => $value ) {
            if ( $value === null ) {
                $where[] = sprintf( '`%s` IS NULL', $field );
            }
            else {
                $where[] = sprintf( '`%s` = %s', $field, $this->formats[ $field ] );
                $values[] = $value;
            }
        }
        // Prepare ORDER BY clause.
        $order = array();
        foreach ( $order_by as $field => $direction ) {
            $order[] = sprintf( '`%s` %s', $field, $direction );
        }

        $query = sprintf(
            'SELECT * FROM `%s`%s%s',
            $this->table_name,
            empty( $where ) ? '' : ' WHERE ' . implode( ' AND ', $where ),
            empty( $order ) ? '' : ' ORDER BY ' . implode( ', ',  $order )
        );

        $rows = $this->wpdb->get_results(
            empty( $values ) ? $query  : $this->wpdb->prepare( $query, $values ),
            ARRAY_A
        );

        if ( is_array( $rows ) ) {
            foreach ( $rows as $row ) {
                $entity = new $this->entity_name;
                $entity->setData( $row, true );

                $result[] = $entity;
            }
        }

        return $result;
    }
}