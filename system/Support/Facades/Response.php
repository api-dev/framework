<?php

namespace Support\Facades;

use Core\Template;
use Core\View;
use Http\JsonResponse;
use Http\Response as HttpResponse;
use Support\Contracts\ArrayableInterface;

use Str;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;


class Response
{
    /**
     * An array of registered Response macros.
     *
     * @var array
     */
    protected static $macros = array();

    /**
     * @var array Array of legacy HTTP headers
     */
    protected static $legacyHeaders = array();

    /**
     * Return a new Response from the application.
     *
     * @param  string  $content
     * @param  int     $status
     * @param  array   $headers
     * @return \Http\Response
     */
    public static function make($content = '', $status = 200, array $headers = array())
    {
        return new HttpResponse($content, $status, $headers);
    }

    /**
     * Return a new View Response from the application.
     *
     * @param  string  $view
     * @param  array   $data
     * @param  int     $status
     * @param  array   $headers
     * @return \Http\Response
     */
    public static function view($view, $data = array(), $status = 200, array $headers = array())
    {
        return static::make(View::make($view, $data), $status, $headers);
    }

    /**
     * Return a new JSON Response from the application.
     *
     * @param  string|array  $data
     * @param  int    $status
     * @param  array  $headers
     * @param  int    $options
     * @return \Http\JsonResponse
     */
    public static function json($data = array(), $status = 200, array $headers = array(), $options = 0)
    {
        if ($data instanceof ArrayableInterface) {
            $data = $data->toArray();
        }

        return new JsonResponse($data, $status, $headers, $options);
    }

    /**
     * Return a new Streamed Response from the application.
     *
     * @param  \Closure  $callback
     * @param  int      $status
     * @param  array    $headers
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public static function stream($callback, $status = 200, array $headers = array())
    {
        return new StreamedResponse($callback, $status, $headers);
    }

    /**
     * Create a new file Download Response.
     *
     * @param  \SplFileInfo|string  $file
     * @param  string  $name
     * @param  array   $headers
     * @param  null|string  $disposition
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public static function download($file, $name = null, array $headers = array(), $disposition = 'attachment')
    {
        $response = new BinaryFileResponse($file, 200, $headers, true, $disposition);

        if ( ! is_null($name)) {
            return $response->setContentDisposition($disposition, $name, Str::ascii($name));
        }

        return $response;
    }

    /**
     * Create a new Error Response instance.
     *
     * The Response Status code will be set using the specified code.
     *
     * The specified error should match a View in your Views/Error directory.
     *
     * <code>
     *      // Create a 404 response.
     *      return Response::error('404');
     *
     *      // Create a 404 response with data.
     *      return Response::error('404', array('message' => 'Not Found'));
     * </code>
     *
     * @param  int       $code
     * @param  array     $data
     * @return Response
     */
    public static function error($status, array $data = array(), $headers = array())
    {
        // Clear the Legacy Headers first.
        static::$legacyHeaders = array();

        $view = Template::make('default')
            ->shares('title', 'Error ' .$status)
            ->nest('content', 'Error/' .$status, $data);

        return static::make($view, $status, $headers);
    }

    /**
     * Register a macro with the Response class.
     *
     * @param  string  $name
     * @param  callable  $callback
     * @return void
     */
    public static function macro($name, $callback)
    {
        static::$macros[$name] = $callback;
    }

    /**
     * Handle dynamic calls into Response macros.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public static function __callStatic($method, $parameters)
    {
        if (isset(static::$macros[$method])) {
            return call_user_func_array(static::$macros[$method], $parameters);
        }

        throw new \BadMethodCallException("Call to undefined method $method");
    }

    //--------------------------------------------------------------------
    // Legacy API Methods
    //--------------------------------------------------------------------

    /**
     * Add the HTTP header to the Headers array.
     *
     * @param  string  $header HTTP header text
     */
    public static function addHeader($header)
    {
        list($key, $value) = explode(' ', $header, 1);

        $key = ltrim($key, ':');

        self::$legacyHeaders[$key] = trim($value);
    }

    /**
     * Add an array with Headers to the view.
     *
     * @param array $headers
     */
    public static function addHeaders(array $headers)
    {
        foreach ($headers as $header) {
            static::addHeader($header);
        }
    }

    /**
     * Send the (legacy) Headers.
     */
    public static function sendHeaders()
    {
        if (! headers_sent()) {
            foreach (self::$legacyHeaders as $header => $value) {
                header("$header: $value", true);
            }
        }

        // Clear the Legacy Headers.
        static::$legacyHeaders = array();
    }

}
