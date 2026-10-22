<?php
// Run once from a local terminal: php make-admin.php admin@example.com
require_once __DIR__.'/config/database.php';
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$email=$argv[1]??'';if(!filter_var($email,FILTER_VALIDATE_EMAIL)){fwrite(STDERR,"Usage: php make-admin.php existing-user@example.com\n");exit(1);}
$stmt=$pdo->prepare("UPDATE users SET role='admin' WHERE email=?");$stmt->execute([$email]);if($stmt->rowCount()!==1){fwrite(STDERR,"No matching user found (or already admin).\n");exit(1);}echo "Administrator role granted to {$email}. Remove this script after use.\n";
