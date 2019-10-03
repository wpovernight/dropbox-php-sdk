<?php

namespace Kunnu\Dropbox\Http\Clients;

use Kunnu\Dropbox\Http\DropboxRawResponse;
use Kunnu\Dropbox\Exceptions\DropboxClientException;

/**
 * DropboxWpHttpClient.
 */
class DropboxWpHttpClient implements DropboxHttpClientInterface
{
    /**
     * Create a new DropboxWpHttpClient instance.
     */
    public function __construct()
    {
    }

    /**
     * Send request to the server and fetch the raw response.
     *
     * @param  string $url     URL/Endpoint to send the request to
     * @param  string $method  Request Method
     * @param  string|resource $body Request Body
     * @param  array  $headers Request Headers
     * @param  array  $options Additional Options
     *
     * @return \Kunnu\Dropbox\Http\DropboxRawResponse Raw response from the server
     *
     * @throws \Kunnu\Dropbox\Exceptions\DropboxClientException
     */
    public function send($url, $method, $body, $headers = [], $options = [])
    {
        switch ($method) {
            case 'GET':
                $response = wp_remote_get(
                    $url,
                    [
                        'headers'   => $headers,
                        'timeout'   => 45,
                        'sslverify' => true,
                    ],
                );
                break;
            default:
            case 'POST':
                error_log($url.' / '.$body);
                $response = wp_remote_post(
                    $url,
                    [
                        'headers'   => $headers,
                        'body'      => $body,
                        'timeout'   => 45,
                        'sslverify' => true,
                    ],
                );
                break;
        }

        if ( is_wp_error( $response ) ) {
            throw new DropboxClientException( $response->get_error_message(), $response->get_error_code() );
        }

        // Check the response code
        $response_code    = wp_remote_retrieve_response_code( $response );
        $response_message = wp_remote_retrieve_response_message( $response );

        if ( $response_code >= 400 && ! empty( $response_message ) ) {
            throw new DropboxClientException( $response_message, $response_code );
        } elseif ( 200 != $response_code ) {
            throw new DropboxClientException( 'Unknown error occurred', $response_code );
        }

        if ( $_headers = wp_remote_retrieve_headers( $response ) ) {
            $headers = [];
            foreach( $_headers->getAll() as $header => $value ) {
                $header = ucwords( $header, '-' );
                if (isset($headers[$header])) {
                   $headers[$header][] = $value;
                } else{
                    $headers[$header] = [$value];
                }
            }
        } else {
            $headers = [];
        }

        if (array_key_exists('sink', $options)) {
            //Response Body is saved to a file
            $body = '';
        } else {
            //Get the Response Body
            $body = wp_remote_retrieve_body( $response );
        }

        //Create and return a DropboxRawResponse object
        return new DropboxRawResponse($headers, $body, $response_code);
    }

}
