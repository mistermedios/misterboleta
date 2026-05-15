<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Simple test for functions
echo "Testing sanitize function...\n";
assert(sanitize('<script>alert(1)</script>') === '&lt;script&gt;alert(1)&lt;/script&gt;');
echo "✓ sanitize test passed\n";

echo "Testing formatDate...\n";
assert(formatDate('2026-05-14') === '14 May 2026');
echo "✓ formatDate test passed\n";

echo "All tests passed!\n";
?>