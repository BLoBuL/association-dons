<?php
$r=dirname(__DIR__);$b=file_get_contents($r.'/base/association_dons.php');$a=file_get_contents($r.'/association_dons_administrations.php');
if(strpos($b,'spip_asso_dons')===false||strpos($a,'sql_drop_table')!==false){fwrite(STDERR,"Structure Dons invalide.\n");exit(1);}echo "OK: structure Association Dons.\n";
