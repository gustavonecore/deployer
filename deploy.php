<?php

require 'functions.php';

$config = require 'config.php';

$backups = [];
$runId = gmdate('YmdHis');

$opts  = [
	"persist:",
];

$options = getopt(null, $opts);

try
{
	$dryRun = !(array_key_exists('persist', $options) && $options['persist'] === 'true');

	$file = downloadRepository($config['vcs'])['file'];

	$remote = unzipRespository($file);

	$changes = getChanges($config['target']['path'], $remote);

	$stats = deploy($config, $changes, $remote, $dryRun)['stats'];

	file_put_contents(APP . 'deploys/' . $runId, serialize($stats) . '.txt');

	echo "Process finished. \n\n";

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
