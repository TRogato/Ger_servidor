<?php

namespace Servidor\Projects\Applications\Templates;

use Servidor\Projects\Application;

interface Template
{
    public function getApp(): Application;

    public function getLogs(): array;

    public function nginxTemplate(): string;

    public function publicDir(): string;

    public function pullCode(): bool;

    public function requiresUser(): bool;
}
