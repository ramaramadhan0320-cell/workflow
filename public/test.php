<?php

$hash = '$2y$12$iEMmMDyf.EsCod11e7dPsuPFTLh2F8.Qm5R1zIjqtWJSaFsbLGaua'; // copy dari DB

var_dump(password_verify('123456', $hash));