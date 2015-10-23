<?php
namespace Mars\Network\Http\Auth;

use Mars\Network\Http\Request;

/**
 * Basic authentication adapter for \Mars\Network\Http\Client
 *
 * Generally not directly constructed, but instead used by \Mars\Network\Http\Client
 * when $options['auth']['type'] is 'basic'
 */
class Basic
{
    /**
     * Add Authorization header to the request.
     *
     * @param \Mars\Network\Http\Request $request Request instance.
     * @param array $credentials Credentials.
     *
     * @return void
     *
     * @see http://www.ietf.org/rfc/rfc2617.txt
     */
    public function authentication(Request $request, array $credentials)
    {
        if (isset($credentials['username'], $credentials['password'])) {
            $value = $this->_generateHeader($credentials['username'], $credentials['password']);
            $request->header('Authorization', $value);
        }
    }

    /**
     * Proxy Authentication
     *
     * @param \Mars\Network\Http\Request $request Request instance.
     * @param array $credentials Credentials.
     *
     * @return void
     *
     * @see http://www.ietf.org/rfc/rfc2617.txt
     */
    public function proxyAuthentication(Request $request, array $credentials)
    {
        if (isset($credentials['username'], $credentials['password'])) {
            $value = $this->_generateHeader($credentials['username'], $credentials['password']);
            $request->header('Proxy-Authorization', $value);
        }
    }

    /**
     * Generate basic [proxy] authentication header
     *
     * @param string $user Username.
     * @param string $pass Password.
     *
     * @return string
     */
    protected function _generateHeader($user, $pass)
    {
        return 'Basic ' . base64_encode($user . ':' . $pass);
    }
}
