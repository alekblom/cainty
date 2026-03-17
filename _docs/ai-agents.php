<h1>AI Agents</h1>
<p>Cainty's AI agent system lets you automate content creation using 6 LLM providers and 24+ models.</p>

<h2>How It Works</h2>
<ol>
    <li><strong>Create an agent</strong> — Choose a provider, model, and system prompt</li>
    <li><strong>Execute</strong> — Run manually or on a schedule</li>
    <li><strong>Review</strong> — Generated content enters the queue for review</li>
    <li><strong>Publish</strong> — Approve, edit, or reject from the content queue</li>
</ol>

<h2>Supported Providers</h2>
<table>
    <thead>
        <tr><th>Provider</th><th>Example Models</th><th>Config Key</th></tr>
    </thead>
    <tbody>
        <tr><td>Anthropic</td><td>Claude Sonnet 4.5, Claude Haiku</td><td><code>ANTHROPIC_API_KEY</code></td></tr>
        <tr><td>OpenAI</td><td>GPT-4o, GPT-4o-mini</td><td><code>OPENAI_API_KEY</code></td></tr>
        <tr><td>Google</td><td>Gemini 2.0 Flash, Gemini Pro</td><td><code>GOOGLE_API_KEY</code></td></tr>
        <tr><td>DeepSeek</td><td>DeepSeek Chat, DeepSeek Reasoner</td><td><code>DEEPSEEK_API_KEY</code></td></tr>
        <tr><td>xAI</td><td>Grok 2, Grok 3</td><td><code>XAI_API_KEY</code></td></tr>
        <tr><td>Ollama</td><td>Any local model</td><td><code>OLLAMA_BASE_URL</code></td></tr>
    </tbody>
</table>

<h2>Setting Up API Keys</h2>
<p>You can configure API keys two ways:</p>
<ul>
    <li><strong>.env file</strong> — Add keys directly to your configuration</li>
    <li><strong>Admin UI</strong> — Go to <strong>Admin &gt; Settings &gt; LLM Keys</strong> to manage keys through the interface (stored encrypted)</li>
</ul>

<h2>Creating an Agent</h2>
<p>Navigate to <strong>Admin &gt; Agents &gt; New Agent</strong> and configure:</p>
<ul>
    <li><strong>Name</strong> — A descriptive name for the agent</li>
    <li><strong>Provider</strong> — Which LLM service to use</li>
    <li><strong>Model</strong> — Specific model from the provider</li>
    <li><strong>System Prompt</strong> — Instructions that define the agent's behavior</li>
    <li><strong>User Prompt Template</strong> — Template for generating content (supports variables)</li>
    <li><strong>Target Category</strong> — Where generated posts will be categorized</li>
</ul>

<h2>Content Queue</h2>
<p>All AI-generated content goes through a review queue before publishing:</p>
<ul>
    <li><strong>Pending</strong> — Newly generated, awaiting review</li>
    <li><strong>Approved</strong> — Published as a post</li>
    <li><strong>Rejected</strong> — Discarded</li>
</ul>
<p>Review content at <strong>Admin &gt; Content Queue</strong>. You can edit the title, body, and category before approving.</p>

<h2>Agent Memory</h2>
<p>Agents maintain memory across runs. This helps them:</p>
<ul>
    <li>Avoid generating duplicate topics</li>
    <li>Build on previous content</li>
    <li>Maintain consistent voice and style</li>
</ul>

<h2>Run History</h2>
<p>Every agent execution is logged. View run history at <strong>Admin &gt; Agents &gt; Runs</strong> to see:</p>
<ul>
    <li>Execution timestamp</li>
    <li>Provider and model used</li>
    <li>Success or failure status</li>
    <li>Token usage and response time</li>
</ul>
