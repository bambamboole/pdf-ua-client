<?php

declare(strict_types=1);

use Workbench\App\Providers\WorkbenchServiceProvider;

use function Orchestra\Testbench\package_path;

it('redirects Boost skill output for supported agents to the package root', function () {
    $provider = new WorkbenchServiceProvider($this->app);
    $provider->register();
    $provider->boot();

    $skeleton = ltrim(str_replace(package_path(), '', base_path()), '/');
    $upToPackageRoot = str_repeat('../', substr_count($skeleton, '/') + 1);

    expect(config('boost.agents.claude_code.skills_path'))
        ->toBe($upToPackageRoot.'.claude/skills')
        ->and(config('boost.agents.codex.skills_path'))
        ->toBe($upToPackageRoot.'.agents/skills');
});
