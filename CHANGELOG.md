# Changelog

All notable changes to this package are recorded here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and versions follow
[Semantic Versioning](https://semver.org/).

## [Unreleased]

## [0.1.0]

First public release.

- A documentation page at `/mcp/docs` for every server registered with
  Laravel MCP, read from the running servers over the protocol: tools,
  resources, resource templates and prompts, with their schemas and annotations.
- Install instructions for Claude Code, Claude Desktop, ChatGPT, OpenAI Codex,
  Cursor, VS Code, Zed, Windsurf and DeepSeek Harness: a config snippet and a
  step-by-step walkthrough each, per operating system where they differ.
- Screenshots per walkthrough step and a video per client.
- Client snippets and steps replaceable from config, per platform.
- Branding: name, tagline, logo, favicon, accent colours, links and footer.
- Public text configured apart from the text written for the model, with the
  model's text optionally kept off the page.
- Access restricted to allowed addresses by default.
- A JSON manifest at `/mcp/docs.json` and the `mcp:docs:collect` and
  `mcp:docs:steps` commands.
