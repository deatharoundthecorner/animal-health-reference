<?php
declare(strict_types=1);

// The endangered-animals list now lives on the Animals page as a filter.
// This stub keeps old links and bookmarks working.
header('Location: animals.php?threatened=1', true, 301);
exit;
