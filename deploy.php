<?php

require 'functions.php';

$config = require 'config.php';

$backups = [];
$runId = gmdate('YmdHis');

try
{
	$file = downloadRepository($config['vcs'])['file'];

	$remote = unzipRespository($file);

	$changes = getChanges($config['target']['path'], $remote);

	$stats = deploy($config, $changes, $remote)['stats'];

	file_put_contents(APP . $runId, serialize($stats));

	echo "Process finished. \n";

	print_r($stats);
}
catch (\Exception $e)
{
	echo 'Error deploying. Detail: ' . $e->getMessage();
}
catch (\InvalidArgumentException $e)
{
	echo $e->getMessage();
}
