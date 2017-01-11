Deployer
--------
A simple PHP script to allow deploy to FTP-based web servers using github as VCS.

**To run:**

 1. Copy/paste `config-default.php` to `config.php`
 2. Update the configuration with your options. This scripts needs a valid Github OAUTH token.
 3. Get the deploying stats `php deploy`
 4. If you want to persist the changes `php deploy --persist=true`

And voila!, your git repo is updated without git.

**TODO:**

 1. Add the rollback feature using the serialized deploy files.
 2. Add a simple UI