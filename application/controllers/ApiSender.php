<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ApiSender extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->require_login();
        $this->load->model(['History_model', 'Environment_model']);
    }

    public function send() {
        $uid = $this->user['id'];

        $method      = strtoupper($this->input->post('method') ?? 'GET');
        $url         = $this->input->post('url');
        $headers     = json_decode($this->input->post('headers') ?? '[]', true);
        $params      = json_decode($this->input->post('params') ?? '[]', true);
        $body_type   = $this->input->post('body_type') ?? 'none';
        $body        = $this->input->post('body');
        $auth_type   = $this->input->post('auth_type') ?? 'none';
        $auth_data   = json_decode($this->input->post('auth_data') ?? '{}', true);
        $request_id  = $this->input->post('request_id');

        // Replace environment variables {{var}}
        $env_vars = $this->Environment_model->get_active_vars($uid);
        $url      = $this->replace_vars($url, $env_vars);
        $body     = $this->replace_vars($body, $env_vars);

        // Append query params to URL
        $enabled_params = array_filter($params ?? [], fn($p) => !empty($p['key']) && ($p['enabled'] ?? true));
        if (!empty($enabled_params)) {
            $query = http_build_query(array_column($enabled_params, 'value', 'key'));
            $url .= (strpos($url, '?') !== false ? '&' : '?') . $query;
        }

        // Build headers array
        $send_headers = [];
        foreach ($headers as $h) {
            if (!empty($h['key']) && ($h['enabled'] ?? true)) {
                $send_headers[$h['key']] = $this->replace_vars($h['value'], $env_vars);
            }
        }

        // Auth
        switch ($auth_type) {
            case 'bearer':
                $send_headers['Authorization'] = 'Bearer ' . ($auth_data['token'] ?? '');
                break;
            case 'basic':
                $send_headers['Authorization'] = 'Basic ' . base64_encode(($auth_data['username'] ?? '') . ':' . ($auth_data['password'] ?? ''));
                break;
            case 'api-key':
                $send_headers[$auth_data['key'] ?? 'X-API-Key'] = $auth_data['value'] ?? '';
                break;
        }

        // ── Handle file uploads in form-data ─────────────────────
        $upload_fields = [];
        if ($body_type === 'form-data' && !empty($_FILES)) {
            // Text rows sent as JSON alongside files
            $text_rows = json_decode($this->input->post('form_text_rows') ?? '[]', true);
            foreach ($text_rows as $row) {
                if (!empty($row['key'])) {
                    $upload_fields[$row['key']] = $row['value'] ?? '';
                }
            }
            // File fields
            foreach ($_FILES as $field_name => $file_info) {
                if (is_array($file_info['name'])) {
                    // multiple files on same key
                    foreach ($file_info['name'] as $i => $fname) {
                        if ($file_info['error'][$i] === UPLOAD_ERR_OK) {
                            $upload_fields[$field_name][] = new CURLFile(
                                $file_info['tmp_name'][$i],
                                $file_info['type'][$i],
                                $fname
                            );
                        }
                    }
                } else {
                    if ($file_info['error'] === UPLOAD_ERR_OK) {
                        $upload_fields[$field_name] = new CURLFile(
                            $file_info['tmp_name'],
                            $file_info['type'],
                            $file_info['name']
                        );
                    }
                }
            }
        }

        // Send via cURL
        $start_time = microtime(true);
        $result = $this->curl_send($method, $url, $send_headers, $body_type, $body, $upload_fields);
        $elapsed = round((microtime(true) - $start_time) * 1000);

        $response_size = strlen($result['body']);

        // Save to history
        $this->History_model->save([
            'user_id'          => $uid,
            'request_id'       => $request_id ?: null,
            'method'           => $method,
            'url'              => $url,
            'request_headers'  => json_encode($send_headers),
            'request_body'     => $body,
            'response_status'  => $result['status'],
            'response_time'    => $elapsed,
            'response_size'    => $response_size,
            'response_headers' => json_encode($result['headers']),
            'response_body'    => $result['body'],
        ]);

        $this->json([
            'success'         => true,
            'status'          => $result['status'],
            'status_text'     => $this->http_status_text($result['status']),
            'time'            => $elapsed,
            'size'            => $this->format_size($response_size),
            'size_bytes'      => $response_size,
            'headers'         => $result['headers'],
            'body'            => $result['body'],
            'content_type'    => $result['content_type'],
        ]);
    }

    // ── cURL sender ──────────────────────────────────────────────
    private function curl_send($method, $url, $headers, $body_type, $body, $upload_fields = []) {
        $ch = curl_init();

        $header_lines = [];
        foreach ($headers as $k => $v) {
            $header_lines[] = "$k: $v";
        }

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER     => $header_lines,
            CURLOPT_CUSTOMREQUEST  => $method,
        ]);

        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE']) && $body_type !== 'none') {

            if ($body_type === 'raw') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                if (!isset($headers['Content-Type'])) {
                    $header_lines[] = 'Content-Type: application/json';
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $header_lines);
                }

            } elseif ($body_type === 'form-data') {

                if (!empty($upload_fields)) {
                    // Has files — pass as array (cURL sets multipart automatically)
                    // Flatten any multi-file arrays
                    $post_data = [];
                    foreach ($upload_fields as $k => $v) {
                        if (is_array($v)) {
                            // Multiple files: use key[0], key[1]... notation
                            foreach ($v as $i => $f) {
                                $post_data["{$k}[{$i}]"] = $f;
                            }
                        } else {
                            $post_data[$k] = $v;
                        }
                    }
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                } else {
                    // No files — text-only form-data
                    $form_rows = json_decode($body, true) ?? [];
                    $form_data = [];
                    foreach ($form_rows as $row) {
                        if (!empty($row['key']) && ($row['enabled'] ?? true)) {
                            $form_data[$row['key']] = $row['value'] ?? '';
                        }
                    }
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $form_data);
                }

            } elseif ($body_type === 'x-www-form-urlencoded') {
                $form_rows = json_decode($body, true) ?? [];
                $form_data = [];
                foreach ($form_rows as $row) {
                    if (!empty($row['key']) && ($row['enabled'] ?? true)) {
                        $form_data[$row['key']] = $row['value'] ?? '';
                    }
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($form_data));
                if (!isset($headers['Content-Type'])) {
                    $header_lines[] = 'Content-Type: application/x-www-form-urlencoded';
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $header_lines);
                }
            }
        }

        // Capture response headers
        $response_headers = [];
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$response_headers) {
            $len = strlen($header);
            $parts = explode(':', $header, 2);
            if (count($parts) < 2) return $len;
            $response_headers[trim($parts[0])] = trim($parts[1]);
            return $len;
        });

        $response_body = curl_exec($ch);
        $http_status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $content_type  = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        if ($response_body === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return ['status' => 0, 'body' => json_encode(['error' => $error]), 'headers' => [], 'content_type' => 'application/json'];
        }

        curl_close($ch);

        return [
            'status'       => $http_status,
            'body'         => $response_body,
            'headers'      => $response_headers,
            'content_type' => $content_type,
        ];
    }

    private function replace_vars($text, $vars) {
        if (empty($vars) || empty($text)) return $text;
        foreach ($vars as $var) {
            $text = str_replace('{{' . $var->var_key . '}}', $var->var_value, $text);
        }
        return $text;
    }

    private function format_size($bytes) {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }

    private function http_status_text($code) {
        $texts = [
            200 => 'OK', 201 => 'Created', 204 => 'No Content',
            301 => 'Moved Permanently', 302 => 'Found', 304 => 'Not Modified',
            400 => 'Bad Request', 401 => 'Unauthorized', 403 => 'Forbidden',
            404 => 'Not Found', 405 => 'Method Not Allowed', 422 => 'Unprocessable Entity',
            429 => 'Too Many Requests', 500 => 'Internal Server Error', 502 => 'Bad Gateway',
            503 => 'Service Unavailable', 0 => 'Connection Error',
        ];
        return $texts[$code] ?? 'Unknown';
    }
}