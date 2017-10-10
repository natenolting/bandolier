<?php
/**
 * Paths
 *
 * Created 6/28/17 10:22 AM
 * Path helpers
 *
 * @author Nate Nolting <naten@paulbunyan.net>
 * @package Bandolier\Type
 */

namespace Pbc\Bandolier\Type;

/**
 * Class Paths
 * @package Pbc\Bandolier\Type
 */
class Paths
{
    /**
     * Path to check for whether inside
     * a docker container or not.
     */
    const CURL_CHECK_FILE = '/.dockerenv';

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var string
     */
    protected $curlCheckFile = "";

    /**
     * Paths constructor.
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Check to see what curl path should be used. If running in
     * localhost or currently run inside a container use web,
     * otherwise use the current SERVER_NAME
     * @param $toPath
     * @param Paths $paths pass an instance of Path (or mock)
     * @param null $dockerEnv path to environment file that should exist if we're in a docker container
     * @return string
     */
    public static function curlPath($toPath, $paths = null, $dockerEnv = null)
    {
        if (!$paths) {
            $paths = new Paths();
        }

        if (!$dockerEnv) {
            $dockerEnv = self::CURL_CHECK_FILE;
        }
        $serverName = self::serverName();

        if ($serverName === 'web'
            || (strpos($serverName, '.local') !== false && $paths->checkForEnvironmentFile($dockerEnv))
        ) {
            $serverName = 'web';
        }

        return self::httpProtocol() . '://' . $serverName . DIRECTORY_SEPARATOR . ltrim($toPath, DIRECTORY_SEPARATOR);
    }

    /**
     * Check environment for SERVER_PORT and fallback to the server global
     * @return int
     */
    public static function serverName()
    {
        return env('SERVER_NAME', (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'web'));
    }


    /**
     * @param string $file
     * @return bool
     * @codeCoverageIgnore
     */
    protected function checkForEnvironmentFile($file = self::CURL_CHECK_FILE)
    {
        return $file && file_exists($file);
    }

    /**
     * httpProtocol
     * Return what the http protocol is for the current page.
     * @return string
     */
    public static function httpProtocol()
    {
        return self::httpsOn() || self::serverPort() === 443 ? 'https' : 'http';
    }

    /**
     * @return bool
     */
    public static function httpsOn()
    {
        return strtolower(self::https()) === 'on';
    }

    /**
     * Check environment for HTTPS and fallback to the server global
     * @return null
     */
    public static function https()
    {
        return env(
            'HTTPS',
            (isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : 'off')
        );
    }

    /*
     * Get the curl check file name. this is used to check in we're in a container or not.
     */

    /**
     * Check environment for SERVER_PORT and fallback to the server global
     * @return int
     */
    public static function serverPort()
    {
        return (int)env(
            'SERVER_PORT',
            (isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : 80)
        );
    }

    /**
     * Get content from a path
     * @param array $params parameters
     * @return string
     */
    public static function fileGetContents($params)
    {
        /** @var string $toPath Path to get request from */
        /** @var array $clientParams parameters passed into client */
        /** @var \GuzzleHttp\Client $client */
        /** @var string $request_type request type */
        /** @var array $requestParams parameters to pass into request */
        /** @var string $request type of request */
        $parameters = Arrays::defaultAttributes([
            "toPath" => self::httpProtocol() . '://' . self::serverName() . '/',
            "clientParams" => [],
            "client" => "\\GuzzleHttp\\Client",
            "request" => "GET",
            "requestParams" => [],
        ], $params);
        extract($parameters);

        $baseUri = parse_url($toPath, PHP_URL_SCHEME) . "://" . parse_url($toPath, PHP_URL_HOST);
        $clientParams['base_uri'] = $baseUri;
        if (is_string($client)) {
            $client = new $client($clientParams);
        }
        $path = substr($toPath, strlen($baseUri), strlen($baseUri));
        return $client->request($request, $path, $requestParams)->getBody()->getContents();
    }

    /**
     * @return string
     */
    public function getCurlCheckFile()
    {
        return $this->curlCheckFile;
    }

    /**
     * @param string $curlCheckFile
     */
    public function setCurlCheckFile($curlCheckFile = null)
    {
        if (!$curlCheckFile) {
            $curlCheckFile = self::CURL_CHECK_FILE;
        }
        $this->curlCheckFile = $curlCheckFile;
    }

    /**
     * Traverse path to make file
     * @param string $filePath path to file to check if it exists
     * @param string|null $content content of file to be written
     * @return bool|int
     */
    public static function filePutContents($filePath, $content = null)
    {
        // https://stackoverflow.com/a/282140/405758
        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0775, true);
        }
        return file_put_contents($filePath, $content);
    }
}
