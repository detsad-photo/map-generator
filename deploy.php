<?php
namespace Deployer;

require 'recipe/common.php';
require 'recipe/composer.php';

set('repository', 'git@github.com:detsad-photo/map-generator.git');

add('shared_files', []);
add('shared_dirs', []);
add('writable_dirs', []);

host('maps.detsad.photo')
    ->setSshMultiplexing(false)
    ->setHostname( '178.154.205.10')
    ->setRemoteUser('ubuntu')
    ->setDeployPath('/var/www/maps.detsad.photo');

after('deploy:failed', 'deploy:unlock');
