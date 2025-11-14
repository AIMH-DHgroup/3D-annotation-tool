<?php
/**
 * Fetch an external digital object page over HTTP/S with basic input validation.
 *
 * This endpoint acts as a simple proxy to retrieve remote HTML content.  A
 * client must provide a `urlob` query parameter containing an HTTP or
 * HTTPS URL.  The URL is validated using PHP's `filter_var` and its
 * scheme is checked to ensure that only `http` and `https` requests are
 * permitted, mitigating server‑side request forgery (SSRF) attacks.  If
 * the URL is valid, the remote resource is fetched using `file_get_contents`
 * and returned within a JSON object with keys `html` and `urlob`.  Any
 * errors (invalid URL, disallowed scheme, retrieval failure) result in a
 * JSON response with an `error` property explaining the problem.
 */
header('Content-Type: application/json');

$url = $_GET['urlob'] ?? '';
$url = trim($url);

// Validate the URL: must be a valid http or https URL
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode(['error' => 'Invalid URL provided.']);
    exit;
}

$scheme = parse_url($url, PHP_URL_SCHEME);
if (!in_array(strtolower($scheme), ['http', 'https'])) {
    echo json_encode(['error' => 'Only HTTP/HTTPS URLs are allowed.']);
    exit;
}

// Fetch the remote resource.  Use @ to suppress warnings if the request fails.
$response = @file_get_contents($url);
if ($response === false) {
    echo json_encode(['error' => 'Unable to fetch remote resource.']);
    exit;
}

echo json_encode([
    'html'  => $response,
    'urlob' => $url
]);
?>