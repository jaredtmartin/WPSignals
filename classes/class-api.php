<?php
namespace WP_Stream;

class API {
  // public function logit($record){
  //   error_log("logit logged: $record");
  // }
  // static function new_records($records){
  //   $defaults = array(
  //     'headers'   => array(),
  //     'method'    => 'GET',
  //     'body'      => '',
  //     'sslverify' => true,
  //   );
  // }
  /**
   * Used to prioritise the streams transport which support non-blocking
   *
   * @filter http_api_transports
   *
   * @return bool
   */
  public static function http_api_transport_priority( $request_order, $args, $url ) {
    if ( isset( $args['blocking'] ) && false === $args['blocking'] ) {
      $request_order = array( 'streams', 'curl' );
    }

    return $request_order;
  }
  /**
   * Helper function to create and escape a URL for an API request.
   *
   * @param string The endpoint path, with a starting slash.
   * @param array  The $_GET parameters.
   *
   * @return string A properly escaped URL.
   */
  public function request_url( $path, $params = array() ) {
    return esc_url_raw(
      add_query_arg(
        $params,
        untrailingslashit( wp_stream_get_instance()->settings->api_url ) . $path
      )
    );
  }

  /**
   * Create new records.
   *
   * @param array $records
   * @param bool  $blocking
   *
   * @return mixed
   */
  public function new_records( $records, $blocking = true ) {
    if ( ! get_option('site_uuid') ) {
      // error_log("skipping new records cause we cant find a site_uuid");
      return false;
    }
    $url  = $this->request_url( sprintf( '/domain/%s/records', urlencode( get_option('site_uuid') ) ) );
    // $url = 'http://requestb.in/qtl6krqt';
    // error_log("new_records - url:$url");
    $args = array(
      'method' => 'POST',
      'body' => json_encode( array( 'records' => $records ) ),
      'blocking' => (bool) $blocking
    );

    return $this->remote_request( $url, $args );
  }

  /**
   * Helper function to query the marketplace API via wp_remote_request.
   *
   * @param string The url to access.
   * @param string The method of the request.
   * @param array  The headers sent during the request.
   * @param bool   Allow API calls to be cached.
   * @param int    Set transient expiration in seconds.
   *
   * @return object The results of the wp_remote_request request.
   */
  protected function remote_request( $url = '', $args = array(), $allow_cache = true, $expiration = 300 ) {
    if ( empty( $url ) || empty( get_option('api_key') ) ) {
      return false;
    }
    // error_log("url: ".json_encode($url));
    $defaults = array(
      'headers'   => array(),
      'method'    => 'GET',
      'body'      => '',
      'sslverify' => true,
    );

    $args = wp_parse_args( $args, $defaults );

    $args['headers']['Stream-Site-API-Key'] = get_option('api_key');
    $args['headers']['Content-Type']        = 'application/json';

    add_filter( 'http_api_transports', array( __CLASS__, 'http_api_transport_priority' ), 10, 3 );

    $transient = 'wp_stream_' . md5( $url );
    // error_log("args:".json_encode($args));
    // error_log("url:".$url);
    if ( 'GET' === $args['method'] && $allow_cache ) {
      if ( false === ( $request = get_transient( $transient ) ) ) {
        $request = wp_remote_request( $url, $args );

        set_transient( $transient, $request, $expiration );
      }
    } else {
      $request = wp_remote_request( $url, $args );
    }

    remove_filter( 'http_api_transports', array( __CLASS__, 'http_api_transport_priority' ), 10 );

    // Return early if the request is non blocking
    if ( isset( $args['blocking'] ) && false === $args['blocking'] ) {
      return true;
    }

    if ( ! is_wp_error( $request ) ) {
      /**
       * Filter the request data of the API response.
       *
       * Does not fire on non-blocking requests.
       *
       * @since 2.0.0
       *
       * @param string $url
       * @param array  $args
       *
       * @return array
       */
      $data = apply_filters( 'wp_stream_api_request_data', json_decode( $request['body'] ), $url, $args );

      // Loose comparison needed
      if ( 200 == $request['response']['code'] || 201 == $request['response']['code'] ) {
        return $data;
      } else {
        // Disconnect if unauthorized or no longer exists, loose comparison needed
        if ( 403 == $request['response']['code'] || 410 == $request['response']['code'] ) {
          WP_Stream_Admin::remove_api_authentication();
        }

        $this->errors['errors']['http_code'] = $request['response']['code'];
      }

      if ( isset( $data->error ) ) {
        $this->errors['errors']['api_error'] = $data->error;
      }
    } else {
      $this->errors['errors']['remote_request_error'] = $request->get_error_message();

      wp_stream_get_instance()->admin->notice( sprintf( '<strong>%s</strong> %s.', __( 'Stream API Error.', 'stream' ), $this->errors['errors']['remote_request_error'] ) );
    }

    if ( ! empty( $this->errors ) ) {
      delete_transient( $transient );
    }

    return false;
  }
}
