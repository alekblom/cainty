<p align="center">
  <img src="themes/default/assets/img/cainty-logo.svg" alt="Cainty" width="240">
</p>

<p align="center">
  <strong>AI-First Open Source CMS</strong><br>
  Create, manage, and automate content with 6 LLM providers and 24+ models — zero code required.
</p>

<p align="center">
  <a href="https://github.com/alekblom/cainty/blob/main/LICENSE"><img src="https://img.shields.io/badge/license-MIT-ffe454?style=flat-square" alt="MIT License"></a>
  <img src="https://img.shields.io/badge/PHP-8.2+-777bb4?style=flat-square&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/database-SQLite%20%7C%20MySQL-4479a1?style=flat-square" alt="SQLite | MySQL">
</p>

---

## Features

### AI Agent System
- **6 LLM Providers** — Anthropic, OpenAI, Google, DeepSeek, xAI, Ollama
- **24+ Models** — Claude, GPT-4, Gemini, DeepSeek, Grok, and local models
- **Content Queue** — AI-generated drafts go through review before publishing
- **Agent Memory** — Agents remember context across runs
- **Scheduled Execution** — Set agents to run on a schedule

### Content Management
- **WYSIWYG Editor** — Rich text editing with media library integration
- **Posts, Categories & Tags** — Full taxonomy system
- **Media Library** — Upload and manage images and files
- **Shortcode Engine** — Extend content with custom shortcodes
- **Search** — Built-in full-text search

### Developer-Friendly
- **Plugin & Hook System** — Actions and filters like WordPress
- **Theme Engine** — Template hierarchy with parts and layouts
- **Multi-Site** — One install, multiple domains
- **SQLite or MySQL** — Zero-config SQLite default, MySQL for scale
- **No Framework** — Pure PHP 8.2, fast and lightweight

---

## Quick Start

```bash
# 1. Clone
git clone https://github.com/alekblom/cainty.git
cd cainty

# 2. Configure
cp .env.example .env
# Edit .env with your settings

# 3. Open browser
# Navigate to https://yourdomain.com/install.php
```

The web installer handles database setup, admin account creation, and site configuration.

---

## Requirements

| Requirement | Minimum |
|---|---|
| PHP | 8.2+ |
| Database | SQLite 3 (default) or MySQL/MariaDB 5.7+ |
| Web Server | Apache with mod_rewrite |
| Extensions | PDO, mbstring, json, curl |

---

## Configuration

Copy `.env.example` to `.env` and configure:

```env
# Database (sqlite or mysql)
DB_DRIVER=sqlite
DB_PATH=storage/cainty.db

# Theme
THEME=default

# LLM API Keys (add the ones you use)
ANTHROPIC_API_KEY=sk-ant-...
OPENAI_API_KEY=sk-...
GOOGLE_API_KEY=...
```

See the [full configuration docs](https://cainty.com/docs/configuration) for all options.

---

## AI Agent System

Cainty's agent system lets you automate content creation:

1. **Create an agent** — Choose a provider, model, and system prompt
2. **Execute** — Run the agent manually or on a schedule
3. **Review** — Generated content enters the queue for human review
4. **Publish** — Approve, edit, or reject queued content

Supported providers:
| Provider | Example Models |
|---|---|
| Anthropic | Claude Sonnet 4.5, Claude Haiku |
| OpenAI | GPT-4o, GPT-4o-mini |
| Google | Gemini 2.0 Flash, Gemini Pro |
| DeepSeek | DeepSeek Chat, DeepSeek Reasoner |
| xAI | Grok 2, Grok 3 |
| Ollama | Any local model |

---

## Theme Development

Themes live in `themes/{name}/` with this structure:

```
themes/my-theme/
├── theme.json          # Theme metadata
├── templates/
│   ├── layout.php      # Main layout wrapper
│   ├── home.php        # Homepage
│   ├── single-post.php # Single post
│   ├── archive.php     # Archive/category
│   └── page.php        # Static page
├── parts/
│   ├── header.php
│   ├── footer.php
│   └── post-card.php
└── assets/
    ├── css/style.css
    └── js/main.js
```

---

## Plugin Development

Plugins use a hook system with actions and filters:

```php
// plugins/my-plugin/boot.php
use Cainty\Plugins\Hook;

// Action: run code at a specific point
Hook::on('header_after', function () {
    echo '<div class="announcement">Welcome!</div>';
});

// Filter: modify data
Hook::filter('content_render', function ($content) {
    return str_replace('old', 'new', $content);
});
```

---

## Hosted Option

Don't want to self-host? Get a fully managed Cainty instance:

**$9/mo** — Includes hosting, updates, backups, and AI credits.

[Get started at cainty.com](https://cainty.com)

---

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/my-feature`)
3. Commit your changes
4. Push to the branch
5. Open a Pull Request

---

## License

Cainty is open source software licensed under the [MIT License](LICENSE).

Built by [Alexiuz](https://alexiuz.com).
