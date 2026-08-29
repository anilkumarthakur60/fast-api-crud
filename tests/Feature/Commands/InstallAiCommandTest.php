<?php

declare(strict_types=1);

use Anil\FastApiCrud\Commands\InstallAiCommand;
use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    $this->projectRoot = sys_get_temp_dir() . '/fast-api-ai-' . uniqid();
    (new Filesystem)->ensureDirectoryExists($this->projectRoot);
    $this->app->setBasePath($this->projectRoot);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->projectRoot);
});

it('installs Claude Code and AGENTS.md integration by default', function () {
    $this->artisan('fast-api:install-ai')->assertSuccessful();

    $root = $this->projectRoot;

    expect("{$root}/.claude/skills/fast-api-crud/SKILL.md")->toBeFile()
        ->and("{$root}/.claude/skills/fast-api-crud/references/query-and-responses.md")->toBeFile()
        ->and("{$root}/.claude/agents/fast-api-crud.md")->toBeFile()
        ->and("{$root}/.agents/skills/fast-api-crud/SKILL.md")->toBeFile()
        ->and("{$root}/.cursor")->not->toBeDirectory();

    $mcp = json_decode(file_get_contents("{$root}/.mcp.json"), true);
    expect($mcp['mcpServers']['fast-api-crud'])->toBe(['command' => 'php', 'args' => ['artisan', 'fast-api:mcp']]);

    foreach (['CLAUDE.md', 'AGENTS.md'] as $file) {
        $content = file_get_contents("{$root}/{$file}");
        expect($content)->toContain(InstallAiCommand::MARKER_START)
            ->and($content)->toContain(InstallAiCommand::MARKER_END)
            ->and($content)->toContain('anil/fast-api-crud');
    }
});

it('merges into existing files without clobbering them and is idempotent', function () {
    $root = $this->projectRoot;
    file_put_contents("{$root}/CLAUDE.md", "# My project\n\nKeep this.\n");
    file_put_contents("{$root}/.mcp.json", json_encode(['mcpServers' => ['other' => ['command' => 'npx', 'args' => ['x']]]]));

    $this->artisan('fast-api:install-ai')->assertSuccessful();
    $this->artisan('fast-api:install-ai')->assertSuccessful();

    $claude = file_get_contents("{$root}/CLAUDE.md");
    expect($claude)->toStartWith("# My project\n\nKeep this.")
        ->and(substr_count($claude, InstallAiCommand::MARKER_START))->toBe(1);

    $mcp = json_decode(file_get_contents("{$root}/.mcp.json"), true);
    expect($mcp['mcpServers'])->toHaveKeys(['other', 'fast-api-crud'])
        ->and($mcp['mcpServers']['other']['command'])->toBe('npx');
});

it('refreshes a stale managed block in place', function () {
    $root = $this->projectRoot;
    file_put_contents("{$root}/AGENTS.md", "Intro\n\n" . InstallAiCommand::MARKER_START . "\nold guidance\n" . InstallAiCommand::MARKER_END . "\n\nOutro\n");

    $this->artisan('fast-api:install-ai', ['--target' => 'agents'])->assertSuccessful();

    $agents = file_get_contents("{$root}/AGENTS.md");
    expect($agents)->toStartWith("Intro\n")
        ->and($agents)->toEndWith("Outro\n")
        ->and($agents)->not->toContain('old guidance')
        ->and($agents)->toContain('Route::fastApiResource');
});

it('installs cursor and copilot targets', function () {
    $this->artisan('fast-api:install-ai', ['--target' => 'cursor,copilot'])->assertSuccessful();

    $root = $this->projectRoot;

    expect("{$root}/.cursor/rules/fast-api-crud.mdc")->toBeFile()
        ->and(file_get_contents("{$root}/.cursor/rules/fast-api-crud.mdc"))->toStartWith('---')
        ->and("{$root}/.cursor/skills/fast-api-crud/SKILL.md")->toBeFile()
        ->and("{$root}/.github/copilot-instructions.md")->toBeFile()
        ->and("{$root}/.claude")->not->toBeDirectory();

    $cursor = json_decode(file_get_contents("{$root}/.cursor/mcp.json"), true);
    expect($cursor['mcpServers']['fast-api-crud']['command'])->toBe('php');

    $vscode = json_decode(file_get_contents("{$root}/.vscode/mcp.json"), true);
    expect($vscode['servers']['fast-api-crud'])->toBe(['type' => 'stdio', 'command' => 'php', 'args' => ['artisan', 'fast-api:mcp']]);
});

it('installs everything with --target=all', function () {
    $this->artisan('fast-api:install-ai', ['--target' => 'all'])->assertSuccessful();

    $root = $this->projectRoot;

    foreach (['.claude/skills/fast-api-crud/SKILL.md', 'AGENTS.md', '.cursor/rules/fast-api-crud.mdc', '.github/copilot-instructions.md'] as $file) {
        expect("{$root}/{$file}")->toBeFile();
    }
});

it('rejects unknown targets', function () {
    $this->artisan('fast-api:install-ai', ['--target' => 'emacs'])->assertFailed();
});

it('does not overwrite a locally modified skill unless forced', function () {
    $root = $this->projectRoot;
    $skill = "{$root}/.claude/skills/fast-api-crud/SKILL.md";

    $this->artisan('fast-api:install-ai', ['--target' => 'claude'])->assertSuccessful();
    file_put_contents($skill, "local edits\n");

    $this->artisan('fast-api:install-ai', ['--target' => 'claude'])->assertSuccessful();
    expect(file_get_contents($skill))->toBe("local edits\n");

    $this->artisan('fast-api:install-ai', ['--target' => 'claude', '--force' => true])->assertSuccessful();
    expect(file_get_contents($skill))->toContain('name: fast-api-crud');
});
