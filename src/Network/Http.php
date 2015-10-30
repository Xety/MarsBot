<?php
namespace Mars\Network;

use Mars\Network\Exception\HttpException;

class Http
{
    /**
     * Headers for the request.
     *
     * @var array
     */
    protected $headers;

    /**
     * Headers of the response.
     *
     * @var array
     */
    protected $responseHeaders;

    /**
     * Body of the response.
     *
     * @var string
     */
    protected $responseBody;

    /**
     * The status line of the response.
     *
     * @var string
     */
    protected $responseStatusLine;

    /**
     * Returned information about the response.
     *
     * @var array
     */
    protected $responseInfo = [];

    /**
     * Define if the response should fail when
     * the request encounters an HTTP error condition.
     *
     * @var bool
     */
    protected $failOnError = false;

    /**
     * Options passed to cURL for the request.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Initializes the cURL resource handle.
     */
    public function __construct()
    {
        $this->headers = [];
        $this->options[CURLOPT_HEADERFUNCTION] = [$this, '_parseHeader'];
        $this->options[CURLOPT_WRITEFUNCTION] = [$this, '_parseBody'];

        if ($options = $this->getOptions()) {
            $this->options = array_replace($this->options, $options);
        }
    }

    /**
     * Make an HTTP GET request to the specified endpoint.
     *
     * @param string $uri URI address to request.
     * @param mixed $query Querystring parameters.
     *
     * @return \Http
     */
    public function get($uri, $query = false)
    {
        $ch = $this->_initializeRequest();

        if (is_array($query)) {
            $uri .= "?" . http_build_query($query);
        } elseif ($query) {
            $uri .= "?" . $query;
        }

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_exec($ch);
        $this->_checkResponse($ch);
        curl_close($ch);

        return $this;
    }

    /**
     * Make an HTTP POST request to the specified endpoint.
     *
     * @param string $uri URI address to request.
     * @param mixed $data Data post parameters.
     *
     * @return \Http
     */
    public function post($uri, $data)
    {
        $ch = $this->_initializeRequest();

        if (is_array($data)) {
            $data = http_build_query($data);
        }

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_exec($ch);
        $this->_checkResponse($ch);
        curl_close($ch);

        return $this;
    }

    /**
     * Throw an exception where the request encounters an HTTP error condition.
     *
     * An error condition is considered to be:
     * - 400-499 - Client error
     * - 500-599 - Server error
     *
     * Note that this doesn't use the builtin CURL_FAILONERROR option,
     * as this fails fast, making the HTTP body and headers inaccessible.
     *
     * @param bool $option The option to pass to the failOnError.
     *
     * @return void
     */
    public function failOnError($option = true)
    {
        $this->failOnError = $option;
    }

    /**
     * Is the response OK.
     *
     * @return bool
     */
    public function isOk()
    {
        $status = $this->getStatus();

        return ($status >= 200 && $status < 300);
    }

    /**
     * Is the response an HTTP error.
     *
     * @return bool
     */
    public function isError()
    {
        $status = $this->getStatus();

        return ($status >= 400);
    }

    /**
     * Access the content body of the response.
     *
     * @return string
     */
    public function getBody()
    {
        return $this->responseBody;
    }

    /**
     * Access the status code of the response.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->responseInfo['http_code'];
    }

    /**
     * Access value of given header from the response.
     *
     * @param string $header The header name.
     *
     * @return string|bool
     */
    public function getHeader($header)
    {
        if (array_key_exists($header, $this->responseHeaders)) {
            return $this->responseHeaders[$header];
        }

        return false;
    }

    /**
     * Return the full list of the response headers.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->responseHeaders;
    }

    /**
     * Return informations about the request.
     *
     * @see http://www.php.net/manual/en/function.curl-getinfo.php
     *
     * @return array
     */
    public function getInfo()
    {
        return $this->responseInfo;
    }

    /**
     * Return the cURL options.
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Access the message string from the status line of the response.
     *
     * @return string
     */
    public function getStatusMessage()
    {
        return $this->responseStatusLine;
    }

    /**
     * Set a default timeout for the request. The client will error if the
     * request takes longer than this to respond.
     *
     * @param int $timeout Number of seconds to wait for a response.
     *
     * @return void
     */
    public function setTimeout($timeout)
    {
        $this->options[CURLOPT_TIMEOUT] = $timeout;
        $this->options[CURLOPT_CONNECTTIMEOUT] = $timeout;
    }

    /**
     * Push outgoing requests through the specified proxy server.
     *
     * @param string $host The proxy host.
     * @param int|bool $port The proxy port.
     *
     * @return void
     */
    public function setProxy($host, $port = false)
    {
        $this->options[CURLOPT_PROXY] = $host;

        if (is_numeric($port)) {
            $this->options[CURLOPT_PROXYPORT] = $port;
        }
    }

    /**
     * Assign the entire set of request header lines from given array.
     *
     * @param array $headers The headers to set for the request.
     *
     * @return void
     */
    public function setHeaders($headers)
    {
        foreach ($headers as $header => $value) {
            $this->setHeader($header, $value);
        }
    }

    /**
     * Set a request header.
     *
     * @param string $header Name of the header
     * @param string $value Value of the header
     *
     * @return void
     */
    public function setHeader($header, $value)
    {
        $this->headers[$header] = "$header: $value";
    }

    /**
     * Assign a custom user agent to the request.
     *
     * @param string $value The value to assign.
     *
     * @return void
     */
    public function setUserAgent($value)
    {
        $this->setHeader('User-Agent', $value);
    }

    /**
     * Clear previously cached request data and prepare for
     * making a fresh request.
     *
     * @return cURL object|bool
     */
    protected function _initializeRequest()
    {
        $this->isComplete = false;
        $this->responseBody = "";
        $this->responseHeaders = [];
        $this->options[CURLOPT_HTTPHEADER] = $this->headers;

        $ch = curl_init();
        curl_setopt_array($ch, $this->options);

        return $ch;
    }

    /**
     * Check the response for possible errors. If failOnError is true
     * then throw a protocol level exception. Network errors are always
     * raised as exceptions.
     *
     * @param object $ch The cURL object.
     *
     * @throws \Http\Exception\HttpException When cURL has occurred an error.
     * @throws \Http\Exception\HttpException When the error code is between 400-499.
     * @throws \Http\Exception\HttpException When the error code is between 500-599.
     *
     * @return void
     */
    protected function _checkResponse($ch)
    {
        $this->responseInfo = curl_getinfo($ch);
        if (curl_errno($ch)) {
            throw new HttpException(curl_error($ch), curl_errno($ch));
        }
        if ($this->failOnError) {
            $status = $this->getStatus();
            if ($status >= 400 && $status <= 499) {
                throw new HttpException($this->getStatusMessage(), $status, $this->getResponse());
            } elseif ($status >= 500 && $status <= 599) {
                throw new HttpException($this->getStatusMessage(), $status, $this->getResponse());
            }
        }
    }

    /**
     * Callback methods collects header lines from the response.
     *
     * @param object $curl The cURL object.
     * @param string $headers The headers of the response.
     *
     * @return int
     */
    protected function _parseHeader($curl, $headers)
    {
        if (!$this->responseStatusLine && strpos($headers, 'HTTP/') === 0) {
            $this->responseStatusLine = $headers;
        } else {
            $parts = explode(': ', $headers);
            if (isset($parts[1])) {
                $this->responseHeaders[$parts[0]] = trim($parts[1]);
            }
        }

        return strlen($headers);
    }

    /**
     * Callback method collects body content from the response.
     *
     * @param object $curl The cURL object.
     * @param string $body The body of the response.
     *
     * @return int
     */
    protected function _parseBody($curl, $body)
    {
        $this->responseBody .= $body;

        return strlen($body);
    }
}
