<?php
/**
 * View - load template pages
 *
 * @author David Carr - dave@novaframework.com
 * @author Virgil-Adrian Teaca - virgil@giulianaeassociati.com
 * @version 3.0
 */

namespace Core;

use Core\BaseView;
use Core\Template;

use Response;


/**
 * View class to load views files.
 */
class View extends BaseView
{
    /**
     * Constructor
     * @param mixed $path
     * @param array $data
     *
     * @throws \UnexpectedValueException
     */
    protected function __construct($view, $path, array $data = array())
    {
        parent::__construct($view, $path, $data);
    }

    /**
     * Create a View instance
     *
     * @param string $path
     * @param array|string $data
     * @param string|null $module
     * @return View
     */
    public static function make($view, $data = array(), $module = null)
    {
        if (is_string($data)) {
            if (! empty($data) && ($module === null)) {
                // The Module name given as second parameter; adjust the information.
                $module = $data;
            }

            $data = array();
        }

        // Prepare the (relative) file path according with Module parameter presence.
        if ($module !== null) {
            $path = str_replace('/', DS, APPDIR ."Modules/$module/Views/$view.php");
        } else {
            $path = str_replace('/', DS, APPDIR ."Views/$view.php");
        }

        return new View($view, $path, $data);
    }

    /**
     * Magic Method for handling dynamic functions.
     *
     * @param  string  $method
     * @param  array   $params
     * @return void|mixed
     */
    public static function __callStatic($method, $params)
    {
        // Process the compat Methods associated to Headers management.
        switch ($method) {
            case 'addHeader':
            case 'addHeaders':
            case 'sendHeaders':
                return call_user_func_array(array(Response::class, $method), $params);

            default:
                break;
        }

        // The called Class; for getting a View instance.
        $className = static::class;

        // Flag for fetching the View rendering output.
        $shouldFetch = false;

        // Flag for sending, or not, the HTTP Headers before rendering.
        $withHeaders = true;

        // Prepare the required information.
        if ($method == 'fetch') {
            $shouldFetch = true;
        } else if ($method == 'render') {
            if (count($params) == 4) {
                // There is a withHeaders parameter.
                $withHeaders = array_pop($params);
            }
        } else if ($method == 'renderTemplate') {
            $className = Template::class;
        } else {
            // No valid Compat Method found; go out.
            return null;
        }

        // Create a View instance, using the current Class and the given parameters.
        $instance = call_user_func_array(array($className, 'make'), $params);

        if ($shouldFetch) {
            // Render the object and return the captured output.
            return $instance->fetch();
        }

        if ($withHeaders) {
            // Send the HTTP Headers first.
            Response::sendHeaders();
        }

        // Render the View object.
        return $instance->render();
    }
}
