<?php
// Schedule with Windows Task Scheduler or cron: php path/to/shareplate/cron/expire-food.php
require_once __DIR__.'/../config/database.php';
$stmt=$pdo->prepare("UPDATE food_donations SET status='Expired', updated_at=NOW() WHERE status IN ('Active','Partially Collected') AND expiry_at<=NOW()");
$stmt->execute();
if(PHP_SAPI==='cli') echo 'Expired listings updated: '.$stmt->rowCount().PHP_EOL;
