<?php
// cli-config.php
require_once "ORMbootstrap.php";

return \Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet($entityManager);
