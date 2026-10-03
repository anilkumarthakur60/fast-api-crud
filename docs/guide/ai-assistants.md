# AI Assistants (Skills, Agents & MCP)

The package ships first-class support for AI coding assistants so that Claude Code,
Codex, Cursor, GitHub Copilot and any other MCP-capable agent already **know how to
use fast-api-crud** in your project — no prompting or copy-pasting docs required.

Three pieces work together:

| Piece | What it gives the agent |
|---|---|
| **Skill** (`fast-api-crud`) | A `SKILL.md` overview (golden rules, workflow, cheat-sheets, debugging checklist) plus a **complete reference set** derived from the package source — one file per area: controllers, routes, scaffolding, query params & responses, hooks/contracts/traits, `HasApiResponse`, macros, helpers, config/enums, Spatie permissions integration, testing. A test asserts every public helper, responder, scope, macro, config key and controller property is documented. Auto-loaded when the task touches the package. |
| **Agent** (`fast-api-crud` subagent, Claude Code) | A specialist that scaffolds with `fast-api:make-all`, wires `Route::fastApiResource`, configures controllers through properties instead of hand-written methods, and writes tests. |
| **MCP server** (`php artisan fast-api:mcp`) | Live introspection of *your* app: controllers, their properties, routes, models, effective config and query-key names — plus a `scaffold` tool. |

## Install

```bash
php artisan fast-api:install-ai                 # Claude Code + AGENTS.md (default)
php artisan fast-api:install-ai --target=all    # + Cursor + GitHub Copilot
php artisan fast-api:install-ai --target=cursor,copilot
```

Restart your editor / agent afterwards so it picks up the new MCP config.
Re-run the command after upgrading the package to refresh the guidance; use `--force` to
overwrite skill files you edited locally.

### What gets written

| Target | Files |
|---|---|
| `claude` | `.claude/skills/fast-api-crud/` · `.claude/agents/fast-api-crud.md` · `.mcp.json` (server entry merged) · managed block in `CLAUDE.md` |
| `agents` | `.agents/skills/fast-api-crud/` · managed block in `AGENTS.md` (read by Codex, Gemini CLI, Cursor, Jules, Amp…) |
| `cursor` | `.cursor/rules/fast-api-crud.mdc` · `.cursor/skills/fast-api-crud/` · `.cursor/mcp.json` |
| `copilot` | managed block in `.github/copilot-instructions.md` · `.vscode/mcp.json` |

Everything is idempotent: Markdown blocks are wrapped in
`<!-- fast-api-crud:start -->` / `<!-- fast-api-crud:end -->` markers and replaced in
place, JSON configs are merged, and existing files are never clobbered.

Prefer `vendor:publish`? The skill and agent are also available as a tag:

```bash
php artisan vendor:publish --provider="Anil\FastApiCrud\FastApiCrudServiceProvider" --tag=ai
```

## The MCP server

```bash
php artisan fast-api:mcp     # stdio transport, newline-delimited JSON-RPC 2.0
```

No extra dependency — the server is implemented inside the package. Register it manually
in any MCP client with:

```json
{
  "mcpServers": {
    "fast-api-crud": { "command": "php", "args": ["artisan", "fast-api:mcp"] }
  }
}
```

### Tools

| Tool | Purpose |
|---|---|
| `list_controllers` | Every controller extending `BaseController` / `BaseWebController`, with model, resource and route count. |
| `describe_controller` | Bindings, configuration properties (`$scopes`, `$with`, `$allowedIncludes`, `$updatableColumns`, …), overridden methods/hooks, routes + middleware, and the managed model. |
| `list_routes` | Routes pointing at fast-api controllers (optionally one class). |
| `model_info` | Table, fillable/guarded, casts, columns, scopes (= valid `filters` keys), relations (candidates for `$with`/`$allowedIncludes`), hooks, contracts, soft deletes. |
| `get_config` | The effective `fast-api` config. |
| `query_reference` | Request-parameter cheat-sheet using **your** configured key names, with an example URL. |
| `read_reference` | Read one reference topic (`skill`, `controllers`, `routes`, `scaffolding`, `query-and-responses`, `hooks-contracts-traits`, `api-response`, `macros`, `helpers`, `config`, `permissions`, `testing`, `agents`). |
| `search_reference` | Full-text search across all topics — look up any helper, macro, property, config key, hook or status method. |
| `scaffold` | Runs `fast-api:make-all` for one or more models (never overwrites). |

### Resources & prompts

Resources: `fast-api://skill`, one `fast-api://skill/{topic}` per reference topic,
`fast-api://agents`, and `fast-api://config` (live config as JSON).

Prompts: `build-resource` (`model`, optional `web`) walks an agent through adding a full
resource; `debug-endpoint` (`symptom`) drives a structured diagnosis.

## Using it

Once installed, just ask your assistant in plain language:

> "Add a Comment resource with filters by post and approved status, searchable by body,
> and only expose `approved` through updateColumn."

The skill tells the agent *how* the package works; the MCP server tells it *what your
project looks like right now*; the subagent (Claude Code) does the work end-to-end and
finishes with tests.
