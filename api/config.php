<?php
/**
 * API Configuration
 * 
 * ไฟล์นี้ใช้สำหรับการตั้งค่า API
 */

// Set headers for API responses
header('Content-Type: application/json; charset=UTF-8');

// Allow cross-origin requests (CORS) if needed
// Uncomment these lines if you need CORS
/*
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
*/

// Include required files
require_once '../config/database.php';
require_once '../auth/session.php';
require_once '../includes/functions.php';

/**
 * Standard API response format
 * 
 * @param string $status Status code (success, error)
 * @param string $message Message to include in response
 * @param array $data Additional data to include in response
 * @return array Response array
 */
function apiResponse($status, $message, $data = []) {
    return [
        'status' => $status,
        'message' => $message,
        'data' => $data
    ];
}

/**
 * Send JSON response and exit
 * 
 * @param array $response Response data to send
 * @param int $statusCode HTTP status code
 */
function sendJsonResponse($response, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Check if request is valid API request
 * 
 * @param string $method Required HTTP method
 * @return bool True if request is valid, false otherwise
 */
function validateApiRequest($method) {
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        sendJsonResponse(
            apiResponse('error', 'Invalid request method', ['required_method' => $method]),
            405 // Method Not Allowed
        );
        return false;
    }
    
    return true;
}

/**
 * Check if user is authenticated for API
 * If not, send error response
 */
function apiCheckAuth() {
    if (!isLoggedIn()) {
        sendJsonResponse(
            apiResponse('error', 'Unauthorized access', []),
            401 // Unauthorized
        );
    }
}

/**
 * Check if user has admin/IT staff role for API
 * If not, send error response
 */
function apiCheckAdminAuth() {
    if (!isLoggedIn() || (!isITStaff() && !isAdmin())) {
        sendJsonResponse(
            apiResponse('error', 'Forbidden access', []),
            403 // Forbidden
        );
    }
}
