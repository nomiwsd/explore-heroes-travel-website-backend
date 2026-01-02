<?php
/**
 * Test actual HTTP endpoints that frontend calls
 */

echo "=================================================\n";
echo "FRONTEND API TEST\n";
echo "=================================================\n\n";

// Test the actual URLs that frontend calls
$baseUrl = 'https://thee-checklist-guarantees-appropriations.trycloudflare.com';

$endpoints = [
    // Public APIs (no auth needed)
    ['GET', '/api/translations/languages', 'Public languages list'],
    ['GET', '/api/translations/en', 'Public EN translations'],

    // Admin APIs (need auth - will get 401/403 but should not be 500)
    ['GET', '/admin/module/user/api/permissions', 'Admin permissions'],
    ['GET', '/admin/module/user/api/users', 'Admin users list'],
    ['GET', '/admin/module/user/api/roles', 'Admin roles list'],
    ['POST', '/admin/module/language/translations/en/build', 'Build translations'],
];

echo "Testing actual HTTP endpoints...\n\n";

foreach ($endpoints as $endpoint) {
    list($method, $path, $description) = $endpoint;
    $url = $baseUrl . $path;

    echo "Testing: $description\n";
    echo "  $method $url\n";

    // Use curl to test the actual endpoint
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, false);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }

    // Add some common headers that frontend might send
    curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(
        curl_getinfo($ch, CURLINFO_HEADER_OUT) ?: [],
        [
            'Accept: application/json',
            'Content-Type: application/json',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Origin: https://explore-heros-travel-website.vercel.app',
            'Referer: https://explore-heros-travel-website.vercel.app/',
        ]
    ));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);

    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo "  ❌ CURL ERROR: $error\n";
    } else {
        // Check status code
        if ($httpCode == 200) {
            echo "  ✅ Status: $httpCode OK\n";
        } elseif (in_array($httpCode, [401, 403])) {
            echo "  ⚠️  Status: $httpCode (Auth required - expected for admin APIs)\n";
        } elseif ($httpCode == 500) {
            echo "  ❌ Status: $httpCode INTERNAL SERVER ERROR\n";
            echo "  📄 Response: " . substr($body, 0, 200) . "\n";
        } else {
            echo "  ❓ Status: $httpCode (Unexpected)\n";
            echo "  📄 Response: " . substr($body, 0, 200) . "\n";
        }

        // Check CORS headers
        $corsHeaders = [];
        if (strpos($headers, 'Access-Control-Allow-Origin') !== false) {
            $corsHeaders[] = 'Has CORS headers';
        }

        if (!empty($corsHeaders)) {
            echo "  🌐 CORS: OK\n";
        } else {
            echo "  ❌ CORS: Missing CORS headers\n";
        }
    }

    echo "\n";
}

echo "=================================================\n";
echo "ANALYSIS\n";
echo "=================================================\n\n";

echo "If you're still getting 500 errors in frontend:\n\n";
echo "1. Check browser Network tab for exact URLs being called\n";
echo "2. Check if frontend is using correct base URL\n";
echo "3. Check browser Console for CORS errors\n";
echo "4. Clear browser cache and try again\n";
echo "5. Check if frontend .env has correct API_URL\n\n";

echo "Common issues:\n";
echo "- Frontend calling wrong URL (old Cloudflare URL)\n";
echo "- Browser cache has old CORS policy\n";
echo "- Frontend .env has wrong NEXT_PUBLIC_API_URL\n";
echo "- Authentication token missing for admin APIs\n\n";

echo "=================================================\n";
