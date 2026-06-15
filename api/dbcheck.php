<?php
/** TEMPORARY diagnostic — delete after the DB connection is confirmed.
 *  Visit /api/dbcheck.php to see whether the credentials in config.php connect. */
require __DIR__ . '/_db.php';
db(); // on failure this prints the real error (detail) and exits
json_out(200, ['ok' => true, 'message' => 'Database connection works. You can delete api/dbcheck.php now.']);
