<?php
namespace Mars\Network\Http;

use Mars\Configure\Configure\Exception\Exception;
use Mars\Network\Http\Message;

/**
 * Implements methods for HTTP requests.
 *
 * Used by \Mars\Network\Http\Client to contain request information
 * for making requests.
 */
class Request extends Message
{
    /**
     * The HTTP method to use.
     *
     * @var string
     */
    protected $_method = self::METHOD_GET;

    /**
     * Request body to send.
     *
     * @var mixed
     */
    protected $_body;

    /**
     * The URL to request.
     *
     * @var string
     */
    protected $_url;

    /**
     * Headers to be sent.
     *
     * @var array
     */
    protected $_headers = [
        'Connection' => 'close',
        'User-Agent' => 'MarsFramework'
    ];

    /**
     * Get/Set the HTTP method.
     *
     * @param string|null $method The method for the request.
     *
     * @return $this|string Either this or the current method.
     *
     * @throws \Mars\Configure\Configure\Exception\Exception On invalid methods.
     */
    public function method($method = null)
    {
        if ($method === null) {
            return $this->_method;
        }
        $name = get_called_class() . '::METHOD_' . strtoupper($method);
        if (!defined($name)) {
            throw new Exception('Invalid method type');
        }
        $this->_method = $method;
        return $this;
    }

    /**
     * Get/Set the url for the request.
     *
     * @param string|null $url The url for the request. Leave null for get
     *
     * @return $this|string Either $this or the url value.
     */
    public function url($url = null)
    {
        if ($url === null) {
            return $this->_url;
        }
        $this->_url = $url;
        return $this;
    }

    /**
     * Get/Set headers into the request.
     *
     * You can get the value of a header, or set one/many headers.
     * Headers are set / fetched in a case insensitive way.
     *
     * ### Getting headers
     *
     * `$request->header('Content-Type');`
     *
     * ### Setting one header
     *
     * `$request->header('Content-Type', 'application/json');`
     *
     * ### Setting multiple headers
     *
     * `$request->header(['Connection' => 'close', 'User-Agent' => 'MarsFramework']);`
     *
     * @param string|array|null $name The name to get, or array of multiple values to set.
     * @param string|null $value The value to set for the header.
     *
     * @return mixed Either $this when setting or header value when getting.
     */
    public function header($name = null, $value = null)
    {
        if ($value === null && is_string($name)) {
            $name = $this->_normalizeHeader($name);
            return isset($this->_headers[$name]) ? $this->_headers[$name] : null;
        }
        if ($value !== null && !is_array($name)) {
            $name = [$name => $value];
        }
        foreach ($name as $key => $val) {
            $key = $this->_normalizeHeader($key);
            $this->_headers[$key] = $val;
        }
        return $this;
    }

    /**
     * Get/Set cookie values.
     *
     * ### Getting a cookie
     *
     * `$request->cookie('session');`
     *
     * ### Setting one cookie
     *
     * `$request->cookie('session', '123456');`
     *
     * ### Setting multiple headers
     *
     * `$request->cookie(['test' => 'value', 'split' => 'banana']);`
     *
     * @param string $name The name of the cookie to get/set
     * @param string|null $value Either the value or null when getting values.
     *
     * @return mixed Either $this or the cookie value.
     */
    public function cookie($name, $value = null)
    {
        if ($value === null && is_string($name)) {
            return isset($this->_cookies[$name]) ? $this->_cookies[$name] : null;
        }
        if (is_string($name) && is_string($value)) {
            $name = [$name => $value];
        }
        foreach ($name as $key => $val) {
            $this->_cookies[$key] = $val;
        }
        return $this;
    }
}
