<?php

namespace Mindbox\Loyalty\Install;

interface InstallerInterface
{
    public function up();
    public function down();
}