<?php
/**
 * Site API: WP_User_Signup_Query class
 *
 * @package Plugins/Sites/Aliases/Queries
 * @since 1.0.0
 */

/**
 * Core class used for querying signups.
 *
 * @since 1.0.0
 *
 * @see WP_User_Signup_Query::__construct() for accepted arguments.
 */
class WP_User_Signup_Query {

	/**
	 * SQL for database query.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	public $request;

	/**
	 * SQL query clauses.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var array
	 */
	protected $sql_clauses = array(
		'select'  => '',
		'from'    => '',
		'where'   => array(),
		'groupby' => '',
		'orderby' => '',
		'limits'  => '',
	);

	/**
	 * Registered query container.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var object WP_Date_Query
	 */
	public $registered_query = false;

	/**
	 * Activated query container.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var object WP_Date_Query
	 */
	public $activated_query = false;

	/**
	 * Meta query container.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var object WP_Date_Query
	 */
	public $meta_query = false;

	/**
	 * Query vars set by the user.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var array
	 */
	public $query_vars;

	/**
	 * Default values for query vars.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var array
	 */
	public $query_var_defaults;

	/**
	 * List of signups located by the query.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var array
	 */
	public $signups;

	/**
	 * The amount of found signups for the current query.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var int
	 */
	public $found_user_signups = 0;

	/**
	 * The number of pages.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var int
	 */
	public $max_num_pages = 0;

	/**
	 * The database object
	 *
	 * @since 1.0.0
	 *
	 * @var WPDB
	 */
	private $db;

	/**
	 * Sets up the site signup query, based on the query vars passed.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string|array $query {
	 *     Optional. Array or query string of site signup query parameters. Default empty.
	 *
	 *     @type int          $ID                 An signup ID to only return that signup. Default empty.
	 *     @type array        $signup__in         Array of signup IDs to include. Default empty.
	 *     @type array        $signup__not_in     Array of signup IDs to exclude. Default empty.
	 *     @type string       $domain             Limit results to those affiliated with a given domain.
	 *                                            Default empty.
	 *     @type array        $domain__in         Array of domains to include affiliated signups for. Default empty.
	 *     @type array        $domain__not_in     Array of domains to exclude affiliated signups for. Default empty.
	 *     @type string       $path               Limit results to those affiliated with a given path.
	 *                                            Default empty.
	 *     @type array        $path__in           Array of paths to include affiliated signups for. Default empty.
	 *     @type array        $path__not_in       Array of paths to exclude affiliated signups for. Default empty.
	 *     @type string       $user_login         Limit results to those affiliated with a given login.
	 *                                            Default empty.
	 *     @type array        $user_login__in     Array of logins to include affiliated signups for. Default empty.
	 *     @type array        $user_login__not_in Array of logins to exclude affiliated signups for. Default empty.
	 *     @type string       $user_email         Limit results to those affiliated with a given email.
	 *                                            Default empty.
	 *     @type array        $user_email__in     Array of emails to include affiliated signups for. Default empty.
	 *     @type array        $user_email__not_in Array of emails to exclude affiliated signups for. Default empty.
	 *     @type array        $registered_query   Date query clauses to limit signups by. See WP_Date_Query.
	 *                                            Default null.
	 *     @type array        $activated_query    Date query clauses to limit signups by. See WP_Date_Query.
	 *                                            Default null.
	 *     @type int          $active             Limit results to those affiliated with a given path.
	 *     @type string       $key                Limit results to those affiliated with a given key.
	 *                                            Default empty.
	 *     @type array        $key__in            Array of keys to include affiliated signups for. Default empty.
	 *     @type array        $key__not_in        Array of keys to exclude affiliated signups for. Default empty.
	 *     @type string       $fields             Site fields to return. Accepts 'ids' (returns an array of site signup IDs)
	 *                                            or empty (returns an array of complete site signup objects). Default empty.
	 *     @type bool         $count              Whether to return a site signup count (true) or array of site signup objects.
	 *                                            Default false.
	 *     @type int          $number             Maximum number of signups to retrieve. Default null (no limit).
	 *     @type int          $offset             Number of signups to offset the query. Used to build LIMIT clause.
	 *                                            Default 0.
	 *     @type bool         $no_found_rows      Whether to disable the `SQL_CALC_FOUND_ROWS` query. Default true.
	 *     @type string|array $orderby            Site status or array of statuses. Accepts 'id', 'registered', 'activated',
	 *                                            'domain_length', 'path_length', 'login', or 'email. Also accepts false,
	 *                                            an empty array, or 'none' to disable `ORDER BY` clause.
	 *                                            Default 'id'.
	 *     @type string       $order              How to order retrieved signups. Accepts 'ASC', 'DESC'. Default 'ASC'.
	 *     @type string       $search             Search term(s) to retrieve matching signups for. Default empty.
	 *     @type array        $search_columns     Array of column names to be searched. Accepts 'domain' and 'status'.
	 *                                            Default empty array.
	 *
	 *     @type bool         $update_user_signup_cache Whether to prime the cache for found signups. Default false.
	 * }
	 */
	public function __construct( $query = '' ) {
		$this->db = $GLOBALS['wpdb'];
		$this->query_var_defaults = array(
			'fields'             => '',
			'ID'                 => '',
			'signup__in'         => '',
			'signup__not_in'     => '',
			'domain'             => '',
			'domain__in'         => '',
			'domain__not_in'     => '',
			'path'               => '',
			'path__in'           => '',
			'path__not_in'       => '',
			'title'              => '',
			'user_login'         => '',
			'user_login__in'     => '',
			'user_login__not_in' => '',
			'user_email'         => '',
			'user_email__in'     => '',
			'user_email__not_in' => '',
			'active'             => 0,
			'key'                => '',
			'key__in'            => '',
			'key__not_in'        => '',
			'number'             => 100,
			'offset'             => '',
			'orderby'            => 'signup_id',
			'order'              => 'ASC',
			'search'             => '',
			'search_columns'     => array(),
			'count'              => false,
			'registered_query'   => null, // See WP_Date_Query
			'activated_query'    => null, // See WP_Date_Query
			'meta_query'         => null, // See WP_Meta_Query
			'no_found_rows'      => true,
			'update_user_signup_cache' => true,
		);

		if ( ! empty( $query ) ) {
			$this->query( $query );
		}
	}

	/**
	 * Parses arguments passed to the site signup query with default query parameters.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @see WP_User_Signup_Query::__construct()
	 *
	 * @param string|array $query Array or string of WP_User_Signup_Query arguments. See WP_User_Signup_Query::__construct().
	 */
	public function parse_query( $query = '' ) {
		if ( empty( $query ) ) {
			$query = $this->query_vars;
		}

		$this->query_vars = wp_parse_args( $query, $this->query_var_defaults );

		/**
		 * Fires after the site signup query vars have been parsed.
		 *
		 * @since 1.0.0
		 *
		 * @param WP_User_Signup_Query &$this The WP_User_Signup_Query instance (passed by reference).
		 */
		do_action_ref_array( 'parse_user_signups_query', array( &$this ) );
	}

	/**
	 * Sets up the WordPress query for retrieving signups.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string|array $query Array or URL query string of parameters.
	 * @return array|int List of signups, or number of signups when 'count' is passed as a query var.
	 */
	public function query( $query ) {
		$this->query_vars = wp_parse_args( $query );

		return $this->get_user_signups();
	}

	/**
	 * Retrieves a list of signups matching the query vars.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return array|int List of signups, or number of signups when 'count' is passed as a query var.
	 */
	public function get_user_signups() {
		$this->parse_query();

		/**
		 * Fires before site signups are retrieved.
		 *
		 * @since 1.0.0
		 *
		 * @param WP_User_Signup_Query &$this Current instance of WP_User_Signup_Query, passed by reference.
		 */
		do_action_ref_array( 'pre_get_user_signups', array( &$this ) );

		// $args can include anything. Only use the args defined in the query_var_defaults to compute the key.
		$key          = md5( serialize( wp_array_slice_assoc( $this->query_vars, array_keys( $this->query_var_defaults ) ) ) );
		$last_changed = wp_cache_get_last_changed( 'user_signups' );

		if ( false === $last_changed ) {
			$last_changed = microtime();
			wp_cache_set( 'last_changed', $last_changed, 'user_signups' );
		}

		$cache_key   = "get_user_signups:{$key}:{$last_changed}";
		$cache_value = wp_cache_get( $cache_key, 'user_signups' );

		if ( false === $cache_value ) {
			$signup_ids = $this->get_signup_ids();
			if ( ! empty( $signup_ids ) ) {
				$this->set_found_user_signups( $signup_ids );
			}

			$cache_value = array(
				'signup_ids'         => $signup_ids,
				'found_user_signups' => $this->found_user_signups,
			);
			wp_cache_add( $cache_key, $cache_value, 'user_signups' );
		} else {
			$signup_ids = $cache_value['signup_ids'];
			$this->found_user_signups = $cache_value['found_user_signups'];
		}

		if ( $this->found_user_signups && $this->query_vars['number'] ) {
			$this->max_num_pages = ceil( $this->found_user_signups / $this->query_vars['number'] );
		}

		// If querying for a count only, there's nothing more to do.
		if ( $this->query_vars['count'] ) {
			// $signup_ids is actually a count in this case.
			return intval( $signup_ids );
		}

		$signup_ids = array_map( 'intval', $signup_ids );

		if ( 'ids' == $this->query_vars['fields'] ) {
			$this->signups = $signup_ids;

			return $this->signups;
		}

		// Prime site network caches.
		if ( $this->query_vars['update_user_signup_cache'] ) {
			_prime_user_signup_caches( $signup_ids );
		}

		// Fetch full site signup objects from the primed cache.
		$_signups = array();
		foreach ( $signup_ids as $signup_id ) {
			$_signup = WP_User_Signup::get( $signup_id );
			if ( ! empty( $_signup ) ) {
				$_signups[] = $_signup;
			}
		}

		/**
		 * Filters the site query results.
		 *
		 * @since 1.0.0
		 *
		 * @param array                $results An array of sign-ups.
		 * @param WP_User_Signup_Query &$this   Current instance of WP_User_Signup_Query, passed by reference.
		 */
		$_signups = apply_filters_ref_array( 'the_user_signups', array( $_signups, &$this ) );

		// Convert to WP_User_Signup instances.
		$this->signups = array_map( array( 'WP_User_Signup', 'get' ), $_signups );

		return $this->signups;
	}

	/**
	 * Used internally to get a list of site signup IDs matching the query vars.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @return int|array A single count of site signup IDs if a count query. An array of site signup IDs if a full query.
	 */
	protected function get_signup_ids() {
		$order = $this->parse_order( $this->query_vars['order'] );

		// Disable ORDER BY with 'none', an empty array, or boolean false.
		if ( in_array( $this->query_vars['orderby'], array( 'none', array(), false ), true ) ) {
			$orderby = '';
		} elseif ( ! empty( $this->query_vars['orderby'] ) ) {
			$ordersby = is_array( $this->query_vars['orderby'] ) ?
				$this->query_vars['orderby'] :
				preg_split( '/[,\s]/', $this->query_vars['orderby'] );

			$orderby_array = array();
			foreach ( $ordersby as $_key => $_value ) {
				if ( ! $_value ) {
					continue;
				}

				if ( is_int( $_key ) ) {
					$_orderby = $_value;
					$_order   = $order;
				} else {
					$_orderby = $_key;
					$_order   = $_value;
				}

				$parsed = $this->parse_orderby( $_orderby );

				if ( empty( $parsed ) ) {
					continue;
				}

				if ( 'signup__in' === $_orderby ) {
					$orderby_array[] = $parsed;
					continue;
				}

				$orderby_array[] = $parsed . ' ' . $this->parse_order( $_order );
			}

			$orderby = implode( ', ', $orderby_array );
		} else {
			$orderby = "us.signup_id {$order}";
		}

		$number = absint( $this->query_vars['number'] );
		$offset = absint( $this->query_vars['offset'] );

		if ( ! empty( $number ) ) {
			if ( $offset ) {
				$limits = 'LIMIT ' . $offset . ',' . $number;
			} else {
				$limits = 'LIMIT ' . $number;
			}
		}

		if ( $this->query_vars['count'] ) {
			$fields = 'COUNT(*)';
		} else {
			$fields = 'us.signup_id';
		}

		// Parse site signup IDs for an IN clause.
		$signup_id = absint( $this->query_vars['ID'] );
		if ( ! empty( $signup_id ) ) {
			$this->sql_clauses['where']['ID'] = $this->db->prepare( 'us.signup_id = %d', $signup_id );
		}

		// Parse site signup IDs for an IN clause.
		if ( ! empty( $this->query_vars['signup__in'] ) ) {
			$this->sql_clauses['where']['signup__in'] = "us.signup_id IN ( " . implode( ',', wp_parse_id_list( $this->query_vars['site__in'] ) ) . ' )';
		}

		// Parse site signup IDs for a NOT IN clause.
		if ( ! empty( $this->query_vars['signup__not_in'] ) ) {
			$this->sql_clauses['where']['signup__not_in'] = "us.signup_id NOT IN ( " . implode( ',', wp_parse_id_list( $this->query_vars['site__not_in'] ) ) . ' )';
		}

		// domain
		if ( ! empty( $this->query_vars['domain'] ) ) {
			$this->sql_clauses['where']['domain'] = $this->db->prepare( 'us.domain = %s', $this->query_vars['domain'] );
		}

		// Parse site signup domain for an IN clause.
		if ( is_array( $this->query_vars['domain__in'] ) ) {
			$this->sql_clauses['where']['domain__in'] = "us.domain IN ( '" . implode( "', '", $this->db->_escape( $this->query_vars['domain__in'] ) ) . "' )";
		}

		// Parse site signup domain for a NOT IN clause.
		if ( is_array( $this->query_vars['domain__not_in'] ) ) {
			$this->sql_clauses['where']['domain__not_in'] = "us.domain NOT IN ( '" . implode( "', '", $this->db->_escape( $this->query_vars['domain__not_in'] ) ) . "' )";
		}

		// path
		if ( ! empty( $this->query_vars['path'] ) ) {
			$this->sql_clauses['where']['path'] = $this->db->prepare( 'us.path = %s', $this->query_vars['path'] );
		}

		// Parse site signup path for an IN clause.
		if ( is_array( $this->query_vars['path__in'] ) ) {
			$this->sql_clauses['where']['path__in'] = "us.path IN ( '" . implode( "', '", $this->db->_escape( $this->query_vars['path__in'] ) ) . "' )";
		}

		// Parse site signup path for a NOT IN clause.
		if ( is_array( $this->query_vars['path__not_in'] ) ) {
			$this->sql_clauses['where']['path__not_in'] = "us.path NOT IN ( '" . implode( "', '", $this->db->_escape( $this->query_vars['path__not_in'] ) ) . "' )";
		}

		// user_login
		if ( ! empty( $this->query_vars['user_login'] ) ) {
			$this->sql_clauses['where']['user_login'] = $this->db->prepare( 'us.user_login = %s', $this->query_vars['user_login'] );
		}

		// Parse site signup user_login for an IN clause.
		if ( is_array( $this->query_vars['user_login__in'] ) ) {
			$this->sql_clauses['where']['user_login__in'] = "us.user_login IN ( '" . implode( "', '", $this->db->_escape( $this->query_vars['user_login__in'] ) ) . "' )";
		}

		// Parse site signup user_login for a NOT IN clause.
		if ( is_array( $this->query_vars['user_login__not_in'] ) ) {
			$this->sql_clauses['where']['user_login__not_in'] = "us.user_login NOT IN ( '" . implode( "', '", $this->db->_escape( $this->query_vars['user_login__not_in'] ) ) . "' )";
		}

		// user_email
		if ( ! empty( $this->query_vars['user_email'] ) ) {
			$this->sql_clauses['where']['user_email'] = $this->db->prepare( 'us.user_email = %s', $this->query_vars['user_email'] );
		}

		// Parse site signup user_email for an IN clause.
		if ( is_array( $this->query_vars['user_email__in'] ) ) {
			$this->sql_clauses['where']['user_email__in'] = "us.user_email IN ( '" . implode( "', '", $this->db->_escape( $this->query_vars['user_email__in'] ) ) . "' )";
		}

		// Parse site signup user_email for a NOT IN clause.
		if ( is_array( $this->query_vars['user_email__not_in'] ) ) {
			$this->sql_clauses['where']['user_email__not_in'] = "us.user_email NOT IN ( '" . implode( "', '", $this->db->_escape( $this->query_vars['user_email__not_in'] ) ) . "' )";
		}

		if ( isset( $this->query_vars['active'] ) ) {
			$this->sql_clauses['where']['active'] = $this->db->prepare( 'us.active = %d', (int) $this->query_vars['active'] );
		}

		// Falsey search strings are ignored.
		if ( strlen( $this->query_vars['search'] ) ) {
			$search_columns = array();

			if ( $this->query_vars['search_columns'] ) {
				$search_columns = array_intersect( $this->query_vars['search_columns'], array( 'domain', 'path', 'title', 'user_login', 'user_email', 'activation_key' ) );
			}

			if ( empty( $search_columns ) ) {
				$search_columns = array( 'domain', 'path', 'title', 'user_login', 'user_email', 'activation_key' );
			}

			/**
			 * Filters the columns to search in a WP_User_Signup_Query search.
			 *
			 * The default columns include 'domain' and 'path.
			 *
			 * @since 1.0.0
			 *
			 * @param array         $search_columns Array of column names to be searched.
			 * @param string        $search         Text being searched.
			 * @param WP_User_Signup_Query $this           The current WP_User_Signup_Query instance.
			 */
			$search_columns = apply_filters( 'user_signup_search_columns', $search_columns, $this->query_vars['search'], $this );

			$this->sql_clauses['where']['search'] = $this->get_search_sql( $this->query_vars['search'], $search_columns );
		}

		$registered_query = $this->query_vars['registered_query'];
		if ( ! empty( $registered_query ) && is_array( $registered_query ) ) {
			$this->registered_query = new WP_Date_Query( $registered_query, 'us.registered' );
			$this->sql_clauses['where']['registered_query'] = preg_replace( '/^\s*AND\s*/', '', $this->registered_query->get_sql() );
		}

		$activated_query = $this->query_vars['activated_query'];
		if ( ! empty( $activated_query) && is_array( $activated_query) ) {
			$this->activated_query = new WP_Date_Query( $activated_query, 'us.activated' );
			$this->sql_clauses['where']['activated_query'] = preg_replace( '/^\s*AND\s*/', '', $this->activated_query->get_sql() );
		}

		$meta_query = $this->query_vars['meta_query'];
		if ( ! empty( $meta_query ) && is_array( $meta_query ) ) {
			$this->meta_query                         = new WP_Meta_Query( $meta_query );
			$clauses                                  = $this->meta_query->get_sql( 'blog_signup', 'us', 'id', $this );
			$join                                     = $clauses['join'];
			$this->sql_clauses['where']['meta_query'] = preg_replace( '/^\s*AND\s*/', '', $clauses['where'] );
		} else {
			$join = '';
		}

		$where = implode( ' AND ', $this->sql_clauses['where'] );

		$pieces = array( 'fields', 'join', 'where', 'orderby', 'limits', 'groupby' );

		/**
		 * Filters the site signup query clauses.
		 *
		 * @since 1.0.0
		 *
		 * @param array $pieces A compacted array of site signup query clauses.
		 * @param WP_User_Signup_Query &$this Current instance of WP_User_Signup_Query, passed by reference.
		 */
		$clauses = apply_filters_ref_array( 'user_signup_clauses', array( compact( $pieces ), &$this ) );

		$fields  = isset( $clauses['fields']  ) ? $clauses['fields']  : '';
		$join    = isset( $clauses['join']    ) ? $clauses['join']    : '';
		$where   = isset( $clauses['where']   ) ? $clauses['where']   : '';
		$orderby = isset( $clauses['orderby'] ) ? $clauses['orderby'] : '';
		$limits  = isset( $clauses['limits']  ) ? $clauses['limits']  : '';
		$groupby = isset( $clauses['groupby'] ) ? $clauses['groupby'] : '';

		if ( $where ) {
			$where = "WHERE {$where}";
		}

		if ( $groupby ) {
			$groupby = "GROUP BY {$groupby}";
		}

		if ( $orderby ) {
			$orderby = "ORDER BY {$orderby}";
		}

		$found_rows = '';
		if ( ! $this->query_vars['no_found_rows'] ) {
			$found_rows = 'SQL_CALC_FOUND_ROWS';
		}

		$this->sql_clauses['select']  = "SELECT {$found_rows} {$fields}";
		$this->sql_clauses['from']    = "FROM {$this->db->signups} us {$join}";
		$this->sql_clauses['groupby'] = $groupby;
		$this->sql_clauses['orderby'] = $orderby;
		$this->sql_clauses['limits']  = $limits;

		$this->request = "{$this->sql_clauses['select']} {$this->sql_clauses['from']} {$where} {$this->sql_clauses['groupby']} {$this->sql_clauses['orderby']} {$this->sql_clauses['limits']}";

		if ( $this->query_vars['count'] ) {
			return intval( $this->db->get_var( $this->request ) );
		}

		$signup_ids = $this->db->get_col( $this->request );

		return array_map( 'intval', $signup_ids );
	}

	/**
	 * Populates found_user_signups and max_num_pages properties for the current query
	 * if the limit clause was used.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param  array $signup_ids Optional array of signup IDs
	 */
	private function set_found_user_signups( $signup_ids = array() ) {

		if ( ! empty( $this->query_vars['number'] ) && ! empty( $this->query_vars['no_found_rows'] ) ) {
			/**
			 * Filters the query used to retrieve found site signup count.
			 *
			 * @since 1.0.0
			 *
			 * @param string              $found_user_signups_query SQL query. Default 'SELECT FOUND_ROWS()'.
			 * @param WP_User_Signup_Query $user_signup_query         The `WP_User_Signup_Query` instance.
			 */
			$found_user_signups_query = apply_filters( 'found_user_signups_query', 'SELECT FOUND_ROWS()', $this );

			$this->found_user_signups = (int) $this->db->get_var( $found_user_signups_query );
		} elseif ( ! empty( $signup_ids ) ) {
			$this->found_user_signups = count( $signup_ids );
		}
	}

	/**
	 * Used internally to generate an SQL string for searching across multiple columns.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param string $string  Search string.
	 * @param array  $columns Columns to search.
	 * @return string Search SQL.
	 */
	protected function get_search_sql( $string, $columns ) {

		if ( false !== strpos( $string, '*' ) ) {
			$like = '%' . implode( '%', array_map( array( $this->db, 'esc_like' ), explode( '*', $string ) ) ) . '%';
		} else {
			$like = '%' . $this->db->esc_like( $string ) . '%';
		}

		$searches = array();
		foreach ( $columns as $column ) {
			$searches[] = $this->db->prepare( "$column LIKE %s", $like );
		}

		return '(' . implode( ' OR ', $searches ) . ')';
	}

	/**
	 * Parses and sanitizes 'orderby' keys passed to the site signup query.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param string $orderby Alias for the field to order by.
	 * @return string|false Value to used in the ORDER clause. False otherwise.
	 */
	protected function parse_orderby( $orderby ) {

		$parsed = false;

		switch ( $orderby ) {
			case 'id':
			case 'signup_id':
				$parsed = 'us.signup_id';
				break;
			case 'signup__in':
				$signup__in = implode( ',', array_map( 'absint', $this->query_vars['signup__in'] ) );
				$parsed = "FIELD( us.signup_id, $signup__in )";
				break;
			case 'domain':
			case 'path':
			case 'registered':
			case 'activated':
			case 'user_login':
			case 'user_email':
				$parsed = $orderby;
				break;
			case 'domain_length':
				$parsed = 'CHAR_LENGTH(domain)';
				break;
		}

		return $parsed;
	}

	/**
	 * Parses an 'order' query variable and cast it to 'ASC' or 'DESC' as necessary.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param string $order The 'order' query variable.
	 * @return string The sanitized 'order' query variable.
	 */
	protected function parse_order( $order ) {
		if ( ! is_string( $order ) || empty( $order ) ) {
			return 'ASC';
		}

		if ( 'ASC' === strtoupper( $order ) ) {
			return 'ASC';
		} else {
			return 'DESC';
		}
	}
}
