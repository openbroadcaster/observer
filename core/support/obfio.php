<?php

// Copyright 2012-2025 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Input/Output class. Outputs data and error messages.
 *
 * @package Class
 */
class OBFIO
{
    private $use_http_status = false;

    public function __construct()
    {
        if (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/v2/')) {
            $this->use_http_status = true;
        }
    }

    /**
     * Create an instance of OBFIO or return the already-created instance.
     *
     * @return instance
     */
    public static function &get_instance()
    {
        static $instance;

        if (isset($instance)) {
            return $instance;
        }

        $instance = new OBFIO();

        return $instance;
    }

    /**
     * Echo JSON encoded error message based on the error code. Errors include
     * OB_ERROR_BAD_POSTDATA, OB_ERROR_BAD_CONTROLLER, OB_ERROR_BAD_DATA, and
     * OB_ERROR_DENIED.
     *
     * @param error_no
     */
    public function error($error_no)
    {
        $user = OBFUser::get_instance();

        if ($this->use_http_status) {
            http_response_code(400);
        }

        switch ($error_no) {
            case OB_ERROR_BAD_POSTDATA:
                $msg = 'Invalid POST data.';
                break;

            case OB_ERROR_BAD_CONTROLLER:
                $msg = 'Invalid controller.';
                break;

            case OB_ERROR_BAD_DATA:
                $msg = 'Invalid controller data.';
                break;

            case OB_ERROR_DENIED:
                if ($this->use_http_status) {
                    http_response_code(403);
                }
                $msg = 'Access denied.';
                break;

            case OB_ERROR_SERVER:
                if ($this->use_http_status) {
                    http_response_code(500);
                }
                $msg = 'Server error.';
                break;

            case OB_ERROR_NOTFOUND:
                if ($this->use_http_status) {
                    http_response_code(404);
                }
                $msg = 'Not found.';
                break;
        }

        $this->output(['error' => ['no' => $error_no,'msg' => $msg,'uid' => $user->param('id')]]);
    }

    /**
     * JSON-encode data and echo it.
     *
     * @param data
     */
    public function output($data)
    {
        echo json_encode($data);
    }
}
